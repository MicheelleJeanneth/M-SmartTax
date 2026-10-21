<?php

namespace App\Services;

use App\Models\DrafBulanan;
use App\Models\KonfigurasiPajak;
use App\Models\Penghasilan;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan PPh Final bulanan.
 *
 * Ambang bebas pajak dihitung menumpuk sepanjang tahun, jadi omzet yang kena
 * pajak pada satu bulan bergantung pada akumulasi bulan-bulan sebelumnya.
 * Seluruh angka diturunkan dari transaksi penghasilan yang tersimpan, bukan
 * dari ringkasan, supaya draf dan daftar transaksi tidak mungkin berbeda.
 */
class PerhitunganPajak
{
    /**
     * Penghasilan paling awal milik pengguna, dibaca sekali per permintaan.
     *
     * @var array{0: bool, 1: Carbon|null}
     */
    private array $awal = [false, null];

    public function __construct(private AnalisisKeuangan $analisis) {}

    /**
     * Bulan pertama pada satu tahun yang menjadi kewajiban wajib pajak.
     *
     * Wajib pajak yang baru mulai mencatat pada Oktober tidak punya masa pajak
     * Januari sampai September, jadi rantai draf tahun itu dimulai dari Oktober.
     * Bila ia sudah mencatat sejak tahun sebelumnya, rantainya mulai Januari.
     * Null berarti tahun itu belum menjadi kewajibannya sama sekali.
     */
    public function bulanPertama(int $tahun): ?int
    {
        $awal = $this->awalPencatatan();

        if ($awal === null || (int) $awal->year > $tahun) {
            return null;
        }

        return (int) $awal->year < $tahun ? 1 : (int) $awal->month;
    }

    /**
     * Masa pajak satu bulan sudah berakhir.
     *
     * Draf hanya boleh disusun setelah bulannya lewat: menyusun di tengah
     * bulan akan mengunci bulan itu, sehingga penghasilan yang masuk sesudahnya
     * tidak lagi dapat dicatat dan PPh Final-nya jadi lebih kecil dari
     * seharusnya. Batas setornya pun baru tanggal 15 bulan berikutnya, jadi
     * tidak ada untungnya menyusun lebih awal.
     */
    public function masaBerakhir(int $tahun, int $bulan): bool
    {
        return Carbon::create($tahun, $bulan)->endOfMonth()->isPast();
    }

    /** Banyaknya draf bulanan yang membuat satu tahun pajak lengkap. */
    public function jumlahWajib(int $tahun): int
    {
        $pertama = $this->bulanPertama($tahun);

        return $pertama === null ? 0 : 13 - $pertama;
    }

    /**
     * Bulan yang giliran disusun berikutnya, atau null bila tidak ada lagi.
     *
     * Draf disusun berurutan, tetapi urutannya dimulai dari bulan pertama yang
     * menjadi kewajiban, bukan dari Januari.
     */
    public function bulanBerikutnya(int $tahun): ?int
    {
        $pertama = $this->bulanPertama($tahun);

        if ($pertama === null) {
            return null;
        }

        $terakhir = $this->drafTahun($tahun)->keys()->max();
        $berikut = $terakhir === null ? $pertama : (int) $terakhir + 1;

        return $berikut > 12 ? null : $berikut;
    }

    private function awalPencatatan(): ?Carbon
    {
        if (! $this->awal[0]) {
            $paling = Penghasilan::query()
                ->where('id_profil', PenggunaSaatIni::id())
                ->min('tanggal');

            $this->awal = [true, $paling === null ? null : Carbon::parse($paling)];
        }

        return $this->awal[1];
    }

    /**
     * Dua belas baris tetap untuk tabel Draf Pajak Penghasilan Bulanan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rekapTahun(int $tahun): array
    {
        $bruto = $this->analisis->brutoPerBulan($tahun);
        $draf = $this->drafTahun($tahun);
        $terakhirTersusun = $draf->keys()->max() ?? 0;
        $pertamaWajib = $this->bulanPertama($tahun);
        $berikutnya = $this->bulanBerikutnya($tahun);

        // Perkiraan omzet kena pajak dan PPh untuk bulan yang drafnya belum
        // disusun. Dibaca lewat model, bukan konfigurasi(), karena tahun yang
        // belum punya aturan pajak tidak boleh menggagalkan seluruh halaman.
        $konfigurasi = KonfigurasiPajak::berlakuUntukTahun($tahun);
        $batasBebas = $konfigurasi === null ? null : (int) $konfigurasi->ambang_bebas;
        $tarif = $konfigurasi === null ? null : (float) $konfigurasi->tarif_final;

        $akumulasi = 0;
        $baris = [];

        foreach (MockData::bulan() as $bulan => $nama) {
            $akumulasi += $bruto[$bulan];
            $tersusun = $draf->get($bulan);

            // Membatalkan satu bulan ikut membatalkan bulan sesudahnya, jadi
            // tiap baris perlu tahu berapa draf yang akan terbawa.
            $ikut = $draf->keys()->map(fn ($b): int => (int) $b)->filter(fn (int $b): bool => $b >= $bulan);

            // Bulan yang drafnya sudah tersusun memakai angka bekunya; sisanya
            // perkiraan dari data penghasilan yang ada sekarang.
            $omzet = $tersusun?->omzet_kena_pajak;
            $pph = $tersusun?->pph_final;

            if ($omzet === null && $batasBebas !== null) {
                $omzet = $this->omzetKenaPajak($akumulasi, $bruto[$bulan], $batasBebas);
                $pph = $this->pphFinal($omzet, $tarif);
            }

            $baris[] = [
                'bulan' => $bulan,
                'nama' => $nama,
                'bruto' => $bruto[$bulan],
                'akumulasi' => $akumulasi,
                'omzet_kena_pajak' => $omzet,
                'pph_final' => $pph,
                'status' => match (true) {
                    $tersusun === null => 'belum',
                    $bruto[$bulan] === 0 => 'nihil',
                    default => 'tersusun',
                },
                'terkunci_tahunan' => $tersusun?->id_draf_tahunan !== null,
                // Bulan sebelum penghasilan pertama bukan masa pajaknya.
                'wajib' => $pertamaWajib !== null && $bulan >= $pertamaWajib,
                // Draf disusun berurutan, mulai dari bulan pertama yang wajib,
                // dan hanya setelah masa pajaknya berakhir.
                'boleh_susun' => $bulan === $berikutnya && $this->masaBerakhir($tahun, $bulan),
                // Gilirannya sudah tiba tetapi bulannya masih berjalan.
                'menunggu_akhir' => $bulan === $berikutnya && ! $this->masaBerakhir($tahun, $bulan),
                // Setiap bulan tersusun boleh dibatalkan, kecuali yang sudah
                // dikunci draf tahunan. Bulan sesudahnya ikut terbatalkan.
                'boleh_batal' => $tersusun !== null && $tersusun->id_draf_tahunan === null,
                'jumlah_ikut' => $tersusun === null ? 0 : $ikut->count(),
                'ikut_sampai' => $tersusun === null ? null : MockData::bulan()[$ikut->max()],
            ];
        }

        return $baris;
    }

    /**
     * Rincian satu bulan untuk halaman Susun dan Rincian.
     *
     * @return array<string, mixed>
     */
    public function perhitungan(int $bulan, int $tahun): array
    {
        $bruto = $this->analisis->brutoPerBulan($tahun);
        $konfigurasi = $this->konfigurasi($tahun);
        $batasBebas = (int) $konfigurasi->ambang_bebas;
        $tarif = (float) $konfigurasi->tarif_final;

        $akumulasiSebelum = array_sum(array_slice($bruto, 0, $bulan - 1, true));
        $brutoBulanIni = $bruto[$bulan];
        $akumulasi = $akumulasiSebelum + $brutoBulanIni;
        $omzetKenaPajak = $this->omzetKenaPajak($akumulasi, $brutoBulanIni, $batasBebas);

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'namaBulan' => MockData::bulan()[$bulan],
            'transaksi' => Penghasilan::query()
                ->where('id_profil', PenggunaSaatIni::id())
                ->tahun($tahun)->bulan($bulan)
                ->orderBy('tanggal')
                ->get(),
            'brutoBulanIni' => $brutoBulanIni,
            'akumulasiSebelum' => $akumulasiSebelum,
            'akumulasi' => $akumulasi,
            'batasBebas' => $batasBebas,
            'terlampauiSejak' => $this->terlampauiSejak($bruto, $bulan, $tahun, $batasBebas),
            'bulanLalu' => $bulan === 1
                ? 'Desember '.($tahun - 1)
                : MockData::bulan()[$bulan - 1].' '.$tahun,
            'batasSetor' => Carbon::create($tahun, $bulan, 1)->addMonthNoOverflow()->day(15),
            'sisaBebas' => max(0, $batasBebas - $akumulasiSebelum),
            'omzetKenaPajak' => $omzetKenaPajak,
            'tarif' => $tarif,
            'pphFinal' => $this->pphFinal($omzetKenaPajak, $tarif),
            'nihil' => $brutoBulanIni === 0,
        ];
    }

    /** Hanya kelebihan di atas ambang bebas yang dikenai tarif. */
    public function omzetKenaPajak(int $akumulasi, int $brutoBulanIni, int $batasBebas): int
    {
        return max(0, min($akumulasi - $batasBebas, $brutoBulanIni));
    }

    public function pphFinal(int $omzetKenaPajak, float $tarif): int
    {
        return (int) round($omzetKenaPajak * $tarif / 100);
    }

    /** Konfigurasi pajak yang berlaku pada satu tahun. */
    public function konfigurasi(int $tahun): KonfigurasiPajak
    {
        return KonfigurasiPajak::berlakuUntukTahun($tahun)
            ?? throw new \RuntimeException("Konfigurasi pajak tahun {$tahun} belum tersedia.");
    }

    /**
     * Draf bulanan tahun itu, berkunci nomor bulan.
     *
     * @return Collection<int, DrafBulanan>
     */
    public function drafTahun(int $tahun): Collection
    {
        return DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->get()
            ->keyBy('bulan');
    }

    /**
     * Bulan pertama saat akumulasi melewati ambang bebas, untuk menjelaskan
     * kenapa omzet bulan ini sudah kena pajak.
     *
     * @param  array<int, int>  $bruto
     */
    private function terlampauiSejak(array $bruto, int $bulan, int $tahun, int $batasBebas): ?string
    {
        $berjalan = 0;

        foreach ($bruto as $i => $nilai) {
            if ($i > $bulan) {
                break;
            }

            $berjalan += $nilai;

            if ($berjalan > $batasBebas) {
                return MockData::bulan()[$i].' '.$tahun;
            }
        }

        return null;
    }
}
