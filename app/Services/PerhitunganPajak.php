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
    public function __construct(private AnalisisKeuangan $analisis) {}

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
        $konfigurasi = $this->konfigurasi($tahun);

        $akumulasi = 0;
        $baris = [];

        foreach (MockData::bulan() as $bulan => $nama) {
            $akumulasi += $bruto[$bulan];
            $tersusun = $draf->get($bulan);

            // Membatalkan satu bulan ikut membatalkan bulan sesudahnya, jadi
            // tiap baris perlu tahu berapa draf yang akan terbawa.
            $ikut = $draf->keys()->map(fn ($b): int => (int) $b)->filter(fn (int $b): bool => $b >= $bulan);

            $baris[] = [
                'bulan' => $bulan,
                'nama' => $nama,
                'bruto' => $bruto[$bulan],
                'akumulasi' => $akumulasi,
                'omzet_kena_pajak' => $tersusun?->omzet_kena_pajak,
                'pph_final' => $tersusun?->pph_final,
                'status' => match (true) {
                    $tersusun === null => 'belum',
                    $bruto[$bulan] === 0 => 'nihil',
                    default => 'tersusun',
                },
                'terkunci_tahunan' => $tersusun?->id_draf_tahunan !== null,
                // Draf disusun berurutan: hanya bulan tepat setelah yang terakhir.
                'boleh_susun' => $bulan === $terakhirTersusun + 1,
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
