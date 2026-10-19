<?php

namespace Database\Seeders;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\DrafTahunanHarta;
use App\Models\DrafTahunanUtang;
use App\Models\Harta;
use App\Models\KodeHarta;
use App\Models\KodeUtang;
use App\Models\KonfigurasiPajak;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Models\Utang;
use App\Support\MockData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Satu wajib pajak contoh beserta tiga tahun riwayatnya.
 *
 * Datanya sengaja dibuat taat pada aturan aplikasinya sendiri: setiap draf
 * tahunan benar-benar beralaskan dua belas draf bulanan, dan setiap draf
 * bulanan beralaskan transaksi penghasilan yang nyata. Karena itu angkanya
 * dihitung dari baris yang ada, bukan disalin dari ringkasan.
 */
class DataContohSeeder extends Seeder
{
    /** Bulan terakhir yang drafnya tersusun pada tiap tahun. */
    private const TERSUSUN = [2024 => 12, 2025 => 12, 2026 => 6];

    /** Tahun yang draf tahunannya sudah disusun. */
    private const TAHUNAN_TERSUSUN = [2024, 2025];

    /**
     * Peredaran bruto tiap bulan, dalam juta rupiah.
     *
     * @var array<int, array<int, int>>
     */
    private const BRUTO = [
        2024 => [44, 46, 48, 50, 52, 50, 54, 51, 53, 52, 51, 53],
        2025 => [55, 58, 62, 60, 64, 59, 63, 61, 66, 64, 65, 65],
        2026 => [110, 125, 140, 132, 148, 125, 136, 0, 0, 0, 0, 0],
    ];

    /**
     * Saldo tiap utang pada akhir tahun. Saldo menyusut seiring cicilan,
     * dan inilah yang dibekukan ke lampiran draf tahunan.
     *
     * @var array<int, array<int, int>>
     */
    private const SALDO_UTANG = [
        1 => [2024 => 210_000_000, 2025 => 195_000_000],
        2 => [2024 => 75_000_000, 2025 => 60_000_000],
        3 => [2025 => 20_000_000],
    ];

    private KonfigurasiPajak $pajak;

    public function run(): void
    {
        $this->pajak = KonfigurasiPajak::berlakuUntukTahun(MockData::TAHUN)
            ?? throw new \RuntimeException('Jalankan KonfigurasiPajakSeeder lebih dahulu.');

        $profil = $this->buatProfil();
        $this->buatHarta($profil);
        $this->buatUtang($profil);

        foreach (array_keys(self::BRUTO) as $tahun) {
            $this->buatPenghasilan($profil, $tahun);
            $this->buatDrafBulanan($profil, $tahun);
        }

        foreach (self::TAHUNAN_TERSUSUN as $tahun) {
            $this->buatDrafTahunan($profil, $tahun);
        }
    }

    private function buatProfil(): Profil
    {
        $p = MockData::profil();

        return Profil::create([
            'email' => $p['email'],
            'password' => 'rahasia123',
            'nik' => $p['nik'],
            'nama' => $p['nama'],
            'tempat_lahir' => $p['tempat_lahir'],
            'tanggal_lahir' => $p['tanggal_lahir'],
            'jenis_kelamin' => $p['jenis_kelamin'],
            'kewarganegaraan' => $p['kewarganegaraan'],
            'nomor_handphone' => $p['telepon'],
            'alamat' => $p['alamat'],
            'rt' => $p['rt'],
            'rw' => $p['rw'],
            'kelurahan' => $p['kelurahan'],
            'kecamatan' => $p['kecamatan'],
            'kota' => $p['kota'],
            'provinsi' => $p['provinsi'],
            'negara' => $p['negara'],
            'profil_lengkap' => true,
        ]);
    }

    /**
     * Harta keenam kategori beserta rincian khasnya. Nilai 'khas' pada data
     * tiruan berurutan sesuai daftar kolom kategorinya.
     */
    private function buatHarta(Profil $profil): void
    {
        $kolom = [
            'kas' => ['nama_bank', 'nomor_rekening', 'atas_nama'],
            'piutang' => ['nama_penerima', 'nik_penerima'],
            'investasi' => ['nama_penerbit', 'nomor_akun', 'nik_penerbit'],
            'bergerak' => ['nomor_polisi', 'kepemilikan'],
            'tidak-bergerak' => ['lokasi_harta', 'ukuran_tanah', 'ukuran_bangunan', 'sumber_kepemilikan', 'nomor_sertifikat'],
            'lainnya' => ['nomor_kepemilikan'],
        ];

        foreach ($kolom as $kategori => $nama) {
            foreach (MockData::harta($kategori) as $baris) {
                $kode = KodeHarta::where('kode', $baris['kode'])->firstOrFail();

                $harta = Harta::create([
                    'id_profil' => $profil->id_profil,
                    'id_kode_harta' => $kode->id_kode_harta,
                    'deskripsi' => $baris['nama'],
                    'keterangan' => $baris['keterangan'],
                    'nilai' => $baris['nilai'],
                    'nilai_saat_ini' => $baris['nilai_kini'],
                    'tahun_perolehan' => $baris['tahun'],
                    'tahun_pelepasan' => $baris['tahun_pelepasan'],
                ]);

                $rincian = array_combine($nama, array_pad(array_slice($baris['khas'], 0, count($nama)), count($nama), null));
                Harta::RINCIAN[$kategori]::create(['id_harta' => $harta->id_harta] + array_filter($rincian, fn ($v) => $v !== null));
            }
        }
    }

    private function buatUtang(Profil $profil): void
    {
        foreach (MockData::utang() as $baris) {
            $kode = KodeUtang::where('kode', $baris['kode'])->firstOrFail();

            Utang::create([
                'id_utang' => $baris['id'],
                'id_profil' => $profil->id_profil,
                'id_kode_utang' => $kode->id_kode_utang,
                'deskripsi' => $baris['deskripsi'],
                'keterangan' => $baris['keterangan'],
                'saldo' => $baris['saldo'],
                'cicilan_bulanan' => $baris['cicilan'],
                'tahun_peminjaman' => $baris['tahun'],
                'tahun_pelunasan' => $baris['tahun_pelunasan'],
                'nama_kreditur' => $baris['kreditur'],
                'nik_kreditur' => $baris['nik_kreditur'],
                'negara_kreditur' => $baris['negara'],
            ]);
        }
    }

    /** Empat transaksi tiap bulan, sisa pembagian ditaruh di transaksi terakhir. */
    private function buatPenghasilan(Profil $profil, int $tahun): void
    {
        $contoh = ['Penjualan katalog minggu ke-1', 'Pesanan korporat', 'Penjualan marketplace', 'Penjualan katalog minggu ke-4'];

        foreach (self::BRUTO[$tahun] as $i => $juta) {
            if ($juta === 0) {
                continue;
            }

            $bulan = $i + 1;
            $nilai = $juta * 1_000_000;
            $porsi = intdiv($nilai, 4_000_000) * 1_000_000;

            foreach ([5, 12, 19, 26] as $urutan => $hari) {
                Penghasilan::create([
                    'id_profil' => $profil->id_profil,
                    'tanggal' => Carbon::create($tahun, $bulan, $hari),
                    'nominal' => $urutan === 3 ? $nilai - ($porsi * 3) : $porsi,
                    'keterangan' => $contoh[$urutan],
                ]);
            }
        }
    }

    /**
     * Draf bulanan dihitung dari transaksi yang sudah tersimpan, lalu
     * transaksinya ditautkan balik sehingga terkunci.
     */
    private function buatDrafBulanan(Profil $profil, int $tahun): void
    {
        $akumulasi = 0;

        foreach (range(1, 12) as $bulan) {
            $transaksi = Penghasilan::where('id_profil', $profil->id_profil)
                ->tahun($tahun)->bulan($bulan)->get();

            $bruto = (int) $transaksi->sum('nominal');
            $sebelum = $akumulasi;
            $akumulasi += $bruto;

            if ($bulan > self::TERSUSUN[$tahun]) {
                continue;
            }

            // Hanya kelebihan di atas ambang bebas yang dikenai tarif.
            $kena = max(0, min($akumulasi - $this->pajak->ambang_bebas, $bruto));

            $draf = DrafBulanan::create([
                'kode_draf' => sprintf('DB-%d-%02d', $tahun, $bulan),
                'id_profil' => $profil->id_profil,
                'id_konfigurasi' => $this->pajak->id_konfigurasi,
                'tahun_pajak' => $tahun,
                'bulan' => $bulan,
                'tanggal_dibuat' => Carbon::create($tahun, $bulan, 1)->addMonthNoOverflow()->day(5),
                'peredaran_bruto' => $bruto,
                'akumulasi_sebelumnya' => $sebelum,
                'akumulasi_sekarang' => $akumulasi,
                'omzet_kena_pajak' => $kena,
                'pph_final' => (int) round($kena * $this->pajak->tarif_final / 100),
            ]);

            Penghasilan::whereIn('id_penghasilan', $transaksi->pluck('id_penghasilan'))
                ->update(['id_draf_bulanan' => $draf->id_draf_bulanan]);
        }
    }

    /**
     * Draf tahunan beserta salinan beku harta dan utangnya, lalu mengunci
     * draf bulanan, harta, dan utang tahun itu.
     */
    private function buatDrafTahunan(Profil $profil, int $tahun): void
    {
        $bulanan = DrafBulanan::where('id_profil', $profil->id_profil)->where('tahun_pajak', $tahun)->get();
        $harta = Harta::where('id_profil', $profil->id_profil)->dimilikiPadaTahun($tahun)->with('kodeHarta')->get();
        $utang = Utang::where('id_profil', $profil->id_profil)->berlakuPadaTahun($tahun)->with('kodeUtang')->get();

        $totalHarta = (int) $harta->sum('nilai');
        $totalUtang = (int) $utang->sum(fn (Utang $u): int => self::SALDO_UTANG[$u->id_utang][$tahun] ?? $u->saldo);
        $bersih = $totalHarta - $totalUtang;
        $bruto = (int) $bulanan->sum('peredaran_bruto');

        $lalu = DrafTahunan::where('id_profil', $profil->id_profil)->where('tahun_pajak', $tahun - 1)->first();

        $draf = DrafTahunan::create([
            'kode_draf' => 'DT-'.$tahun,
            'id_profil' => $profil->id_profil,
            'id_konfigurasi' => $this->pajak->id_konfigurasi,
            'tahun_pajak' => $tahun,
            'tanggal_dibuat' => Carbon::create($tahun + 1, 3, 1),
            'total_peredaran_bruto' => $bruto,
            'total_pph_final' => (int) $bulanan->sum('pph_final'),
            'total_harta' => $totalHarta,
            'total_utang' => $totalUtang,
            'kekayaan_bersih' => $bersih,
            'pertumbuhan_kekayaan' => $lalu && $lalu->kekayaan_bersih > 0
                ? round(($bersih - $lalu->kekayaan_bersih) / $lalu->kekayaan_bersih * 100, 2)
                : null,
            'rasio_konsistensi' => $lalu && $bruto > 0
                ? round((($totalHarta - $lalu->total_harta) - ($totalUtang - $lalu->total_utang)) / $bruto, 4)
                : null,
        ]);

        $draf->status_konsistensi = $draf->rasio_konsistensi === null ? null : match (true) {
            (float) $draf->rasio_konsistensi < 0.60 => 'normal',
            (float) $draf->rasio_konsistensi <= 0.90 => 'tinjau',
            default => 'periksa',
        };
        $draf->save();

        foreach ($harta as $h) {
            DrafTahunanHarta::create([
                'id_draf_tahunan' => $draf->id_draf_tahunan,
                'kategori' => $h->kodeHarta->kategori,
                'kode' => $h->kodeHarta->kode,
                'deskripsi' => $h->deskripsi,
                'nilai' => $h->nilai,
            ]);
        }

        foreach ($utang as $u) {
            DrafTahunanUtang::create([
                'id_draf_tahunan' => $draf->id_draf_tahunan,
                'kode' => $u->kodeUtang->kode,
                'deskripsi' => $u->deskripsi,
                'nama_kreditur' => $u->nama_kreditur,
                'saldo' => self::SALDO_UTANG[$u->id_utang][$tahun] ?? $u->saldo,
            ]);
        }

        // Mengunci: draf bulanan tahun itu, serta harta dan utang yang terekap.
        DrafBulanan::whereIn('id_draf_bulanan', $bulanan->pluck('id_draf_bulanan'))
            ->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);
        Harta::whereIn('id_harta', $harta->pluck('id_harta'))->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);
        Utang::whereIn('id_utang', $utang->pluck('id_utang'))->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);
    }
}
