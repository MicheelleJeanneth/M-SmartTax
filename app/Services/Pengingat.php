<?php

namespace App\Services;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\Harta;
use App\Models\KonfigurasiPajak;
use App\Models\Penghasilan;
use App\Models\Pengingat as BarisPengingat;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pengingat dihitung ulang setiap Dashboard dibuka, bukan lewat penjadwal.
 *
 * Kondisi yang terpenuhi disimpan sebagai baris pada tabel pengingat, dengan
 * 'jenis' sebagai penjaga agar tidak ada baris ganda. Kondisi yang sudah tidak
 * berlaku ditandai tidak aktif, sehingga penanda "dibaca" tetap bertahan antar
 * kunjungan tanpa perlu menyimpan apa pun di session.
 */
class Pengingat
{
    /**
     * Pasangan tahun dan bulan yang drafnya sudah tersusun. Dibaca sekali
     * karena pemeriksaan 3.1 menelusuri setiap bulan dari tahun pertama
     * sampai tahun berjalan.
     *
     * @var Collection<int, string>|null
     */
    private ?Collection $tersusun = null;

    public function __construct(
        private readonly AnalisisKeuangan $analisis,
        private readonly FilterTahun $filterTahun,
    ) {}

    /**
     * Seluruh pengingat aktif, diurutkan dari jatuh tempo terdekat.
     * Pengingat tanpa jatuh tempo diletakkan setelahnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function segarkan(): array
    {
        $kondisi = collect([
            ...$this->drafBulananBelumDisusun(),
            ...$this->batasSetorMendekat(),
            ...$this->drafTahunanBelumDisusun(),
            ...$this->hartaBelumDicatat(),
            ...$this->ambangBebasTerlampaui(),
            ...$this->batasBrutoMendekat(),
        ]);

        $this->simpan($kondisi);

        return BarisPengingat::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->get()
            ->sortBy(fn (BarisPengingat $p): string => $p->tanggal_jatuh_tempo?->toDateString() ?? '9999-12-31')
            ->map(fn (BarisPengingat $p): array => [
                'jenis' => $p->jenis,
                'judul' => $p->judul,
                'pesan' => $p->pesan,
                'jatuh_tempo' => $p->tanggal_jatuh_tempo?->toDateString(),
                'dibaca' => (bool) $p->dibaca,
                'ikon' => $this->ikon($p->jenis),
                'rute' => $this->rute($p->jenis),
                'aksi' => $this->aksi($p->jenis),
            ])
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

    /** Lambang pada baris pengingat, ditentukan oleh jenisnya. */
    public function ikon(string $jenis): string
    {
        return match (true) {
            str_starts_with($jenis, 'draf_tahunan') => 'file-text',
            str_starts_with($jenis, 'harta_kosong') => 'list',
            str_starts_with($jenis, 'ambang_bebas') => 'info',
            str_starts_with($jenis, 'batas_maksimal') => 'triangle-alert',
            default => 'calendar',
        };
    }

    /** Menandai satu pengingat sudah dibaca. */
    public function tandaiDibaca(string $jenis): void
    {
        BarisPengingat::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('jenis', $jenis)
            ->update(['dibaca' => true]);
    }

    /**
     * Menyimpan kondisi yang terpenuhi dan menonaktifkan yang tidak berlaku lagi.
     *
     * @param  Collection<int, array<string, mixed>>  $kondisi
     */
    private function simpan(Collection $kondisi): void
    {
        $profil = PenggunaSaatIni::id();

        foreach ($kondisi as $p) {
            BarisPengingat::withoutGlobalScope('aktif')->updateOrCreate(
                ['id_profil' => $profil, 'jenis' => $p['jenis']],
                [
                    'judul' => $p['judul'],
                    'pesan' => $p['pesan'],
                    'tanggal_jatuh_tempo' => $p['jatuh_tempo'],
                    'status' => true,
                ],
            );
        }

        // Kondisi yang sudah tidak terpenuhi tidak dihapus, hanya dinonaktifkan,
        // supaya penanda "dibaca" tetap tersimpan bila kondisinya muncul lagi.
        BarisPengingat::query()
            ->where('id_profil', $profil)
            ->whereNotIn('jenis', $kondisi->pluck('jenis'))
            ->update(['status' => false]);
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

        foreach ($this->filterTahun->daftarPenghasilan() as $tahun) {
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
        ]];
    }

    /**
     * 3.3 Dua belas draf bulanan lengkap tetapi draf tahunannya belum ada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function drafTahunanBelumDisusun(): array
    {
        $profil = PenggunaSaatIni::id();
        $pengingat = [];

        $lengkap = DrafBulanan::query()
            ->where('id_profil', $profil)
            ->selectRaw('tahun_pajak, COUNT(*) as jumlah')
            ->groupBy('tahun_pajak')
            ->having('jumlah', '>=', 12)
            ->pluck('tahun_pajak');

        $sudahTersusun = DrafTahunan::query()->where('id_profil', $profil)->pluck('tahun_pajak');

        foreach ($lengkap->diff($sudahTersusun) as $tahun) {
            $pengingat[] = [
                'jenis' => "draf_tahunan_{$tahun}",
                'judul' => "Draf pajak tahunan {$tahun} belum disusun",
                'pesan' => "Seluruh draf bulanan tahun {$tahun} telah lengkap. Susun draf pajak penghasilan tahunan sebelum batas pelaporan pada 31 Maret ".($tahun + 1).'.',
                'jatuh_tempo' => ($tahun + 1).'-03-31',
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
        $profil = PenggunaSaatIni::id();

        $adaPenghasilan = Penghasilan::query()->where('id_profil', $profil)->exists();
        $adaHarta = Harta::query()->where('id_profil', $profil)->exists();

        if (! $adaPenghasilan || $adaHarta) {
            return [];
        }

        return [[
            'jenis' => 'harta_kosong',
            'judul' => 'Data harta belum dicatat',
            'pesan' => 'Catat data harta yang dimiliki karena diperlukan dalam penyusunan draf pajak penghasilan tahunan.',
            'jatuh_tempo' => null,
        ]];
    }

    /**
     * 3.5 Peredaran bruto tahun berjalan melampaui ambang bebas pajak.
     *
     * @return array<int, array<string, mixed>>
     */
    private function ambangBebasTerlampaui(): array
    {
        $tahun = $this->filterTahun->tahunBerjalan();
        $konfigurasi = $this->konfigurasi($tahun);
        $bruto = $this->analisis->peredaranBruto($tahun);

        if ($konfigurasi === null || $bruto <= $konfigurasi->ambang_bebas) {
            return [];
        }

        return [[
            'jenis' => "ambang_bebas_$tahun",
            'judul' => 'Ambang bebas pajak telah terlampaui',
            'pesan' => "Peredaran bruto tahun $tahun telah melampaui ".rupiah($konfigurasi->ambang_bebas).', sehingga Pajak Penghasilan Final mulai dikenakan atas kelebihan tersebut.',
            'jatuh_tempo' => null,
        ]];
    }

    /**
     * 3.6 Peredaran bruto mencapai delapan puluh persen dari batas maksimal.
     *
     * @return array<int, array<string, mixed>>
     */
    private function batasBrutoMendekat(): array
    {
        $tahun = $this->filterTahun->tahunBerjalan();
        $konfigurasi = $this->konfigurasi($tahun);
        $bruto = $this->analisis->peredaranBruto($tahun);

        if ($konfigurasi === null || $bruto < $konfigurasi->ambang_maksimal * 0.8) {
            return [];
        }

        return [[
            'jenis' => "batas_maksimal_$tahun",
            'judul' => 'Peredaran bruto mendekati batas',
            'pesan' => "Peredaran bruto tahun $tahun telah mencapai ".rupiah($bruto).'. Apabila melampaui '.rupiah($konfigurasi->ambang_maksimal).', skema Pajak Penghasilan Final tidak dapat digunakan pada tahun pajak berikutnya.',
            'jatuh_tempo' => null,
        ]];
    }

    private function konfigurasi(int $tahun): ?KonfigurasiPajak
    {
        return KonfigurasiPajak::berlakuUntukTahun($tahun);
    }

    private function drafTersusun(int $tahun, int $bulan): bool
    {
        $this->tersusun ??= DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->get(['tahun_pajak', 'bulan'])
            ->map(fn (DrafBulanan $d): string => $d->tahun_pajak.'-'.$d->bulan);

        return $this->tersusun->contains($tahun.'-'.$bulan);
    }
}
