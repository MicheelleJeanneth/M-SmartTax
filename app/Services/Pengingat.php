<?php

namespace App\Services;

use App\Support\MockData;
use Illuminate\Support\Carbon;

/**
 * Pengingat dihitung ulang setiap Dashboard dibuka, bukan lewat penjadwal.
 *
 * Mockup: hasil pemeriksaan langsung dikembalikan sebagai array dan penanda
 * "dibaca" disimpan di session. Setelah tabel `pengingat` ada, method segarkan()
 * menyimpan kondisi yang terpenuhi sebagai baris baru dan menonaktifkan
 * pengingat yang kondisinya tidak berlaku lagi, dengan `jenis` sebagai penjaga
 * agar tidak ada baris ganda.
 */
class Pengingat
{
    public function __construct(private readonly AnalisisKeuangan $analisis) {}

    /**
     * Seluruh pengingat aktif, diurutkan dari jatuh tempo terdekat.
     * Pengingat tanpa jatuh tempo diletakkan setelahnya.
     *
     * @return array<int, array{jenis: string, judul: string, pesan: string, jatuh_tempo: string|null, dibaca: bool, rute: string, ikon: string}>
     */
    public function segarkan(): array
    {
        $dibaca = session('pengingat_dibaca', []);

        return collect([
            ...$this->drafBulananBelumDisusun(),
            ...$this->batasSetorMendekat(),
            ...$this->drafTahunanBelumDisusun(),
            ...$this->hartaBelumDicatat(),
            ...$this->ambangBebasTerlampaui(),
            ...$this->batasBrutoMendekat(),
        ])
            ->map(fn (array $p): array => [
                ...$p,
                'dibaca' => in_array($p['jenis'], $dibaca, true),
                'rute' => $this->rute($p['jenis']),
                'aksi' => $this->aksi($p['jenis']),
            ])
            ->sortBy(fn (array $p): string => $p['jatuh_tempo'] ?? '9999-12-31')
            ->values()
            ->all();
    }

    /** Halaman tujuan saat sebuah pengingat ditekan (spesifikasi bagian 4). */
    public function rute(string $jenis): string
    {
        return match (true) {
            str_starts_with($jenis, 'draf_tahunan') => 'draf-tahunan.index',
            str_starts_with($jenis, 'harta_kosong') => 'harta.index',
            default => 'draf-bulanan.index',
        };
    }

    /** Label tindakan di sisi kanan baris pengingat. */
    public function aksi(string $jenis): string
    {
        return str_starts_with($jenis, 'harta_kosong') ? 'Lengkapi' : 'Buka';
    }

    /** Menandai satu pengingat sudah dibaca. */
    public function tandaiDibaca(string $jenis): void
    {
        $dibaca = session('pengingat_dibaca', []);
        $dibaca[] = $jenis;

        session(['pengingat_dibaca' => array_values(array_unique($dibaca))]);
    }

    /**
     * 3.1 Masa pajak sudah berakhir tetapi drafnya belum tersusun.
     *
     * @return array<int, array<string, mixed>>
     */
    private function drafBulananBelumDisusun(): array
    {
        $hariIni = now();
        $pengingat = [];

        foreach ([MockData::TAHUN - 1, MockData::TAHUN] as $tahun) {
            foreach (range(1, 12) as $bulan) {
                $akhirMasa = Carbon::create($tahun, $bulan)->endOfMonth();

                if ($akhirMasa->isAfter($hariIni) || $this->drafTersusun($tahun, $bulan)) {
                    continue;
                }

                $namaBulan = MockData::bulan()[$bulan];
                $batas = $akhirMasa->copy()->addMonthNoOverflow()->day(15);

                $pengingat[] = [
                    'jenis' => sprintf('draf_bulanan_%d_%02d', $tahun, $bulan),
                    'judul' => "Draf pajak $namaBulan $tahun belum disusun",
                    'pesan' => "Susun draf pajak penghasilan $namaBulan $tahun sebelum batas penyetoran pada {$batas->translatedFormat('j F Y')}.",
                    'jatuh_tempo' => $batas->toDateString(),
                    'ikon' => 'calendar',
                ];
            }
        }

        return $pengingat;
    }

    /**
     * 3.2 Draf masa pajak sebelumnya sudah tersusun dan hari ini tanggal 10 sampai 15.
     *
     * @return array<int, array<string, mixed>>
     */
    private function batasSetorMendekat(): array
    {
        $hariIni = now();

        if ($hariIni->day < 10 || $hariIni->day > 15) {
            return [];
        }

        $masa = $hariIni->copy()->subMonthNoOverflow();

        if (! $this->drafTersusun((int) $masa->year, (int) $masa->month)) {
            return [];
        }

        $namaMasa = MockData::bulan()[(int) $masa->month];

        return [[
            'jenis' => sprintf('batas_setor_%d_%02d', $hariIni->year, $hariIni->month),
            'judul' => "Batas penyetoran $namaMasa mendekat",
            'pesan' => "Penyetoran Pajak Penghasilan Final masa pajak $namaMasa {$masa->year} paling lambat 15 {$hariIni->translatedFormat('F')} melalui saluran resmi Direktorat Jenderal Pajak.",
            'jatuh_tempo' => $hariIni->copy()->day(15)->toDateString(),
            'ikon' => 'calendar',
        ]];
    }

    /**
     * 3.3 Dua belas draf bulanan lengkap tetapi draf tahunannya belum ada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function drafTahunanBelumDisusun(): array
    {
        $pengingat = [];

        foreach (MockData::drafTahunan() as $draf) {
            if ($draf['draf_bulanan'] < 12 || $draf['status'] === 'tersusun') {
                continue;
            }

            $pengingat[] = [
                'jenis' => "draf_tahunan_{$draf['tahun']}",
                'judul' => "Draf pajak tahunan {$draf['tahun']} belum disusun",
                'pesan' => "Seluruh draf bulanan tahun {$draf['tahun']} telah lengkap. Susun draf pajak penghasilan tahunan sebelum batas pelaporan pada 31 Maret ".($draf['tahun'] + 1).'.',
                'jatuh_tempo' => ($draf['tahun'] + 1).'-03-31',
                'ikon' => 'file-text',
            ];
        }

        return $pengingat;
    }

    /**
     * 3.4 Sudah ada penghasilan tetapi belum ada satu pun harta.
     *
     * @return array<int, array<string, mixed>>
     */
    private function hartaBelumDicatat(): array
    {
        $adaPenghasilan = MockData::penghasilan() !== [];
        $adaHarta = collect(array_keys(MockData::kategoriHarta()))
            ->contains(fn (string $kategori): bool => MockData::harta($kategori) !== []);

        if (! $adaPenghasilan || $adaHarta) {
            return [];
        }

        return [[
            'jenis' => 'harta_kosong',
            'judul' => 'Data harta belum dicatat',
            'pesan' => 'Catat data harta yang dimiliki karena diperlukan dalam penyusunan draf pajak penghasilan tahunan.',
            'jatuh_tempo' => null,
            'ikon' => 'list',
        ]];
    }

    /**
     * 3.5 Peredaran bruto tahun berjalan melampaui ambang bebas pajak.
     *
     * @return array<int, array<string, mixed>>
     */
    private function ambangBebasTerlampaui(): array
    {
        $tahun = MockData::TAHUN;
        $konfigurasi = MockData::konfigurasiPajak($tahun);
        $bruto = $this->analisis->peredaranBruto($tahun);

        if ($bruto <= $konfigurasi['ambang_bebas']) {
            return [];
        }

        return [[
            'jenis' => "ambang_bebas_$tahun",
            'judul' => 'Ambang bebas pajak telah terlampaui',
            'pesan' => "Peredaran bruto tahun $tahun telah melampaui ".rupiah($konfigurasi['ambang_bebas']).', sehingga Pajak Penghasilan Final mulai dikenakan atas kelebihan tersebut.',
            'jatuh_tempo' => null,
            'ikon' => 'info',
        ]];
    }

    /**
     * 3.6 Peredaran bruto mencapai delapan puluh persen dari batas maksimal.
     *
     * @return array<int, array<string, mixed>>
     */
    private function batasBrutoMendekat(): array
    {
        $tahun = MockData::TAHUN;
        $konfigurasi = MockData::konfigurasiPajak($tahun);
        $bruto = $this->analisis->peredaranBruto($tahun);

        if ($bruto < $konfigurasi['ambang_maksimal'] * 0.8) {
            return [];
        }

        return [[
            'jenis' => "batas_maksimal_$tahun",
            'judul' => 'Peredaran bruto mendekati batas',
            'pesan' => "Peredaran bruto tahun $tahun telah mencapai ".rupiah($bruto).'. Apabila melampaui '.rupiah($konfigurasi['ambang_maksimal']).', skema Pajak Penghasilan Final tidak dapat digunakan pada tahun pajak berikutnya.',
            'jatuh_tempo' => null,
            'ikon' => 'triangle-alert',
        ]];
    }

    private function drafTersusun(int $tahun, int $bulan): bool
    {
        if ($tahun === MockData::TAHUN) {
            return $bulan <= MockData::BULAN_TERSUSUN;
        }

        $draf = collect(MockData::drafTahunan())->firstWhere('tahun', $tahun);

        return $bulan <= ($draf['draf_bulanan'] ?? 0);
    }
}
