<?php

namespace App\Services;

use App\Models\DrafTahunan;
use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Models\Utang;
use App\Support\MockData;
use Illuminate\Database\Eloquent\Builder;

/**
 * Perhitungan harta, utang, dan analisis keuangan untuk Dashboard,
 * Draf Tahunan, dan Simulasi Aset. Seluruhnya dihitung di sisi server.
 */
class AnalisisKeuangan
{
    /** Batas rasio konsistensi: di bawah 0,60 normal; sampai 0,90 perlu ditinjau. */
    private const BATAS_NORMAL = 0.60;

    private const BATAS_TINJAU = 0.90;

    /**
     * Kumpulan keadaan yang tidak muncul pada data contoh, dipaksa tampil agar
     * tata letaknya dapat diperiksa dengan mata dan diuji sebagai kasus batas.
     */
    private ?string $peraga = null;

    public function pakaiPeraga(?string $nama): ?string
    {
        return $this->peraga = in_array($nama, ['penghasilan-nol', 'tanpa-draf-tahunan'], true) ? $nama : null;
    }

    /**
     * Harta dihitung bila diperoleh sampai tahun itu dan belum dilepas.
     */
    public function totalHarta(int $tahun): int
    {
        return (int) $this->harta($tahun)->sum('nilai');
    }

    /**
     * Utang dihitung bila dipinjam sampai tahun itu dan belum lunas.
     */
    public function totalUtang(int $tahun): int
    {
        return (int) $this->utang($tahun)->sum('saldo');
    }

    public function kekayaanBersih(int $tahun): int
    {
        return $this->totalHarta($tahun) - $this->totalUtang($tahun);
    }

    /**
     * Jumlah cicilan bulanan utang yang masih berjalan. Utang yang dibayar
     * sekaligus saat jatuh tempo tidak punya cicilan dan dihitung nol.
     */
    public function cicilanBerjalan(int $tahun): int
    {
        return (int) $this->utang($tahun)->sum('cicilan_bulanan');
    }

    /** Peredaran bruto tahun terpilih, dijumlahkan dari transaksi penghasilan. */
    public function peredaranBruto(int $tahun): int
    {
        return (int) $this->penghasilan($tahun)->sum('nominal');
    }

    /**
     * Peredaran bruto per bulan, selalu dua belas bulan termasuk yang bernilai nol.
     *
     * @return array<int, int>
     */
    public function brutoPerBulan(int $tahun): array
    {
        $bulan = array_fill(1, 12, 0);

        foreach ($this->penghasilan($tahun)->get(['tanggal', 'nominal']) as $baris) {
            $bulan[(int) $baris->tanggal->month] += (int) $baris->nominal;
        }

        return $bulan;
    }

    /**
     * Nilai harta untuk keenam kategori, urut tetap.
     *
     * Kategori tanpa harta tetap disertakan bernilai nol supaya selalu muncul
     * di legenda; pada diagram lingkaran bagiannya memang tidak tergambar.
     *
     * @return array<string, int>
     */
    public function hartaPerKategori(int $tahun): array
    {
        $terisi = $this->harta($tahun)
            ->join('kode_harta', 'harta.id_kode_harta', '=', 'kode_harta.id_kode_harta')
            ->groupBy('kode_harta.kategori')
            ->selectRaw('kode_harta.kategori as kategori, SUM(harta.nilai) as jumlah')
            ->pluck('jumlah', 'kategori');

        $kategori = [];

        foreach (array_keys(MockData::kategoriHarta()) as $kunci) {
            $kategori[$kunci] = (int) ($terisi[$kunci] ?? 0);
        }

        return $kategori;
    }

    /**
     * Pertumbuhan kekayaan bersih dibanding draf tahunan tahun sebelumnya.
     *
     * @return array{ada_pembanding: bool, kekayaan_lalu: int|null, kekayaan_kini: int, selisih: int|null, persen: float|null}
     */
    public function pertumbuhanKekayaan(int $tahun): array
    {
        $lalu = $this->drafTahunLalu($tahun);
        $kini = $this->kekayaanBersih($tahun);

        if ($lalu === null) {
            return ['ada_pembanding' => false, 'kekayaan_lalu' => null, 'kekayaan_kini' => $kini, 'selisih' => null, 'persen' => null];
        }

        $selisih = $kini - (int) $lalu->kekayaan_bersih;

        return [
            'ada_pembanding' => true,
            'kekayaan_lalu' => (int) $lalu->kekayaan_bersih,
            'kekayaan_kini' => $kini,
            'selisih' => $selisih,
            // Persentase hanya dihitung bila penyebutnya positif.
            'persen' => $lalu->kekayaan_bersih > 0 ? $selisih / $lalu->kekayaan_bersih * 100 : null,
        ];
    }

    /**
     * Pertambahan kekayaan bersih dibanding peredaran bruto tahun terpilih.
     *
     * Simulasi Aset memanggil method ini dua kali: sekali apa adanya, sekali
     * dengan tambahan harta dan utang dari rencana pembelian. Dengan begitu
     * pembagi, ambang, dan nama statusnya selalu sama dengan draf tahunan.
     *
     * @return array{ada_pembanding: bool, pertambahan_harta: int|null, pertambahan_utang: int|null, pertambahan_bersih: int|null, bruto: int, rasio: float|null, status: string|null}
     */
    public function rasioKonsistensi(int $tahun, int $tambahanHarta = 0, int $tambahanUtang = 0): array
    {
        $lalu = $this->drafTahunLalu($tahun);
        $bruto = $this->peredaranBruto($tahun);

        if ($lalu === null) {
            return ['ada_pembanding' => false, 'pertambahan_harta' => null, 'pertambahan_utang' => null,
                'pertambahan_bersih' => null, 'bruto' => $bruto, 'rasio' => null, 'status' => null];
        }

        $pertambahanHarta = $this->totalHarta($tahun) + $tambahanHarta - (int) $lalu->total_harta;
        $pertambahanUtang = $this->totalUtang($tahun) + $tambahanUtang - (int) $lalu->total_utang;
        $pertambahanBersih = $pertambahanHarta - $pertambahanUtang;
        $rasio = $bruto > 0 ? $pertambahanBersih / $bruto : null;

        return [
            'ada_pembanding' => true,
            'pertambahan_harta' => $pertambahanHarta,
            'pertambahan_utang' => $pertambahanUtang,
            'pertambahan_bersih' => $pertambahanBersih,
            'bruto' => $bruto,
            'rasio' => $rasio,
            'status' => $rasio === null ? null : $this->status($rasio),
        ];
    }

    public function status(float $rasio): string
    {
        return match (true) {
            $rasio < self::BATAS_NORMAL => 'normal',
            $rasio <= self::BATAS_TINJAU => 'tinjau',
            default => 'periksa',
        };
    }

    /**
     * Wajib pajak yang sedang masuk. Selama autentikasi belum dipasang,
     * dipakai satu-satunya profil yang ada di basis data.
     */
    private function profilId(): ?int
    {
        return auth()->id() ?? Profil::query()->value('id_profil');
    }

    /** @return Builder<Harta> */
    private function harta(int $tahun)
    {
        return Harta::query()->where('id_profil', $this->profilId())->dimilikiPadaTahun($tahun);
    }

    /** @return Builder<Utang> */
    private function utang(int $tahun)
    {
        return Utang::query()->where('id_profil', $this->profilId())->berlakuPadaTahun($tahun);
    }

    /** @return Builder<Penghasilan> */
    private function penghasilan(int $tahun)
    {
        $kueri = Penghasilan::query()->where('id_profil', $this->profilId())->tahun($tahun);

        return $this->peraga === 'penghasilan-nol' ? $kueri->whereRaw('1 = 0') : $kueri;
    }

    /**
     * Draf tahunan tahun sebelumnya, dipakai sebagai pembanding.
     */
    private function drafTahunLalu(int $tahun): ?DrafTahunan
    {
        if ($this->peraga === 'tanpa-draf-tahunan') {
            return null;
        }

        return DrafTahunan::query()
            ->where('id_profil', $this->profilId())
            ->where('tahun_pajak', $tahun - 1)
            ->first();
    }
}
