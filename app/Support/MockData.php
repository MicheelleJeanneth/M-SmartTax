<?php

namespace App\Support;

/**
 * Data contoh untuk mockup.
 *
 * Seluruh angka di sini hanya pengisi tampilan. Setelah skema ERD masuk,
 * ganti isi setiap method dengan query Eloquent — bentuk keluarannya
 * (nama kunci array) sengaja dibuat mirip kolom tabel agar view tidak berubah.
 */
class MockData
{
    public const TAHUN = 2026;

    /** Bulan terakhir yang drafnya sudah tersusun (1–12). */
    public const BULAN_TERSUSUN = 6;

    public static function bulan(): array
    {
        return [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
    }

    /**
     * Konfigurasi pajak yang berlaku pada suatu tahun.
     *
     * Angka ambang tidak boleh ditulis langsung di kode, semuanya dibaca dari sini.
     *
     * @return array{tarif_final: float, ambang_bebas: int, ambang_maksimal: int, dasar_hukum: string}
     */
    public static function konfigurasiPajak(int $tahun = self::TAHUN): array
    {
        return [
            'tarif_final' => 0.5,
            'ambang_bebas' => 500_000_000,
            'ambang_maksimal' => 4_800_000_000,
            'dasar_hukum' => 'PP Nomor 20 Tahun 2026',
        ];
    }

    public static function profil(): array
    {
        return [
            'nama' => 'Budi Santoso',
            'inisial' => 'BS',
            'peran' => 'Wajib Pajak UMKM',
            'email' => 'budi.santoso@example.com',
            'nik' => '3578010509900002',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1990-09-05',
            'jenis_kelamin' => 'Pria',
            'kewarganegaraan' => 'WNI',
            'telepon' => '0812-3456-7890',
            'alamat' => 'Jl. Raya Kertajaya Indah No. 42',
            'rt' => '004',
            'rw' => '007',
            'kelurahan' => 'Manyar Sabrangan',
            'kecamatan' => 'Mulyorejo',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
            'negara' => 'Indonesia',
            'profil_lengkap' => true,
        ];
    }

    /** Ringkasan kartu dashboard. */
    public static function ringkasan(): array
    {
        $harta = collect(array_keys(self::kategoriHarta()))
            ->sum(fn (string $kategori): int => collect(self::harta($kategori))->sum('nilai'));
        $utang = collect(self::utang())->sum('saldo');

        return [
            'penghasilan' => collect(self::drafBulanan())->whereIn('status', ['tersusun', 'nihil'])->sum('bruto'),
            'pph_final' => collect(self::drafBulanan())->sum('pph_final'),
            'harta' => $harta,
            'utang' => $utang,
            'kekayaan_bersih' => $harta - $utang,
        ];
    }

    /** Peredaran bruto per bulan, dipakai grafik batang dan tabel draf. */
    public static function bruto(): array
    {
        return [
            1 => 110_000_000, 2 => 125_000_000, 3 => 140_000_000,
            4 => 132_000_000, 5 => 148_000_000, 6 => 125_000_000,
            7 => 136_000_000, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0,
        ];
    }

    /** Baris tabel draf bulanan — selalu 12 baris tetap. */
    public static function drafBulanan(): array
    {
        $bruto = self::bruto();
        $akumulasi = 0;
        $baris = [];

        foreach (self::bulan() as $i => $nama) {
            $akumulasi += $bruto[$i];

            // Omzet di bawah Rp 500 juta pertama tidak kena PPh Final 0,5%.
            $bebas = 500_000_000;
            $kena = max(0, min($akumulasi, PHP_INT_MAX) - $bebas);
            $kenaBulanIni = max(0, min($akumulasi - $bebas, $bruto[$i]));

            $tersusun = $i <= self::BULAN_TERSUSUN;

            $baris[] = [
                'bulan' => $i,
                'nama' => $nama,
                'bruto' => $bruto[$i],
                'akumulasi' => $akumulasi,
                'omzet_kena_pajak' => $tersusun ? $kenaBulanIni : null,
                'pph_final' => $tersusun ? (int) round($kenaBulanIni * 0.005) : null,
                'status' => $tersusun
                    ? ($bruto[$i] > 0 ? 'tersusun' : 'nihil')
                    : 'belum',
                'terkunci_tahunan' => false,
                'boleh_susun' => $i === self::BULAN_TERSUSUN + 1,
                'boleh_batal' => $i === self::BULAN_TERSUSUN,
            ];
        }

        return $baris;
    }

    /**
     * Transaksi penghasilan tahun berjalan, diturunkan dari peredaran bruto bulanan
     * supaya angka di Dashboard, Data Penghasilan, dan Draf Bulanan selalu sama.
     *
     * @return array<int, array{id: int, tanggal: string, nominal: int, keterangan: string, terkunci: bool}>
     */
    public static function penghasilan(): array
    {
        $contoh = [
            'Penjualan katalog minggu ke-1',
            'Pesanan korporat',
            'Penjualan marketplace',
            'Penjualan katalog minggu ke-4',
        ];

        $baris = [];
        $id = 1;

        foreach (self::bruto() as $bulan => $nilai) {
            if ($nilai === 0) {
                continue;
            }

            // Dibagi empat transaksi; sisa pembagian ditaruh di transaksi terakhir.
            $porsi = intdiv($nilai, 4_000_000) * 1_000_000;

            foreach ([5, 12, 19, 26] as $urutan => $hari) {
                $nominal = $urutan === 3 ? $nilai - ($porsi * 3) : $porsi;

                $baris[] = [
                    'id' => $id++,
                    'tanggal' => sprintf('%d-%02d-%02d', self::TAHUN, $bulan, $hari),
                    'nominal' => $nominal,
                    'keterangan' => $contoh[$urutan],
                    // Penghasilan bulan yang drafnya sudah tersusun ikut terkunci.
                    'terkunci' => $bulan <= self::BULAN_TERSUSUN,
                ];
            }
        }

        return $baris;
    }

    /** Definisi enam kategori harta: label kolom nilai dan kolom khasnya. */
    public static function kategoriHarta(): array
    {
        return [
            'kas' => [
                'nama' => 'Kas dan Setara Kas',
                'singkat' => 'Kas',
                'label_nilai' => 'Saldo Akhir Tahun',
                'kolom' => ['Nama Bank', 'Nomor Rekening', 'Atas Nama'],
            ],
            'piutang' => [
                'nama' => 'Piutang',
                'singkat' => 'Piutang',
                'label_nilai' => 'Saldo Saat Ini',
                'kolom' => ['Nama Peminjam', 'NIK/NPWP'],
            ],
            'investasi' => [
                'nama' => 'Investasi / Sekuritas',
                'singkat' => 'Investasi',
                'label_nilai' => 'Harga Perolehan',
                'kolom' => ['Nama Penerbit', 'Nomor Akun'],
            ],
            'bergerak' => [
                'nama' => 'Harta Bergerak',
                'singkat' => 'Bergerak',
                'label_nilai' => 'Harga Perolehan',
                'kolom' => ['Nomor Polisi', 'Kepemilikan'],
            ],
            'tidak-bergerak' => [
                'nama' => 'Harta Tidak Bergerak',
                'singkat' => 'Tidak bergerak',
                'label_nilai' => 'Harga Perolehan',
                'kolom' => ['Lokasi', 'Luas T/B', 'No. Sertifikat'],
            ],
            'lainnya' => [
                'nama' => 'Harta Lainnya',
                'singkat' => 'Lainnya',
                'label_nilai' => 'Harga Perolehan',
                'kolom' => ['Nomor Kepemilikan'],
            ],
        ];
    }

    /** Baris harta per kategori. 'khas' sejajar dengan 'kolom' di kategoriHarta(). */
    public static function harta(string $kategori): array
    {
        return match ($kategori) {
            'kas' => [
                ['id' => 1, 'kode' => '011', 'nama' => 'Rekening Operasional', 'tahun' => 2021, 'nilai' => 62_000_000, 'nilai_kini' => 64_500_000, 'tahun_pelepasan' => null, 'khas' => ['Bank Mandiri', '1400012345678', 'Budi Santoso'], 'terkunci' => false],
                ['id' => 2, 'kode' => '012', 'nama' => 'Deposito Berjangka', 'tahun' => 2023, 'nilai' => 120_000_000, 'nilai_kini' => 126_000_000, 'tahun_pelepasan' => null, 'khas' => ['Bank BCA', '8720045512', 'Budi Santoso'], 'terkunci' => true],
            ],
            'piutang' => [],
            'investasi' => [],
            'bergerak' => [
                ['id' => 6, 'kode' => '041', 'nama' => 'Mobil Toyota Avanza 2022', 'tahun' => 2022, 'nilai' => 112_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['L 1234 BS', 'Milik Sendiri'], 'terkunci' => true],
                ['id' => 7, 'kode' => '042', 'nama' => 'Motor Honda Vario', 'tahun' => 2023, 'nilai' => 18_000_000, 'nilai_kini' => 15_500_000, 'tahun_pelepasan' => null, 'khas' => ['L 5678 BS', 'Milik Sendiri'], 'terkunci' => false],
            ],
            'tidak-bergerak' => [
                ['id' => 8, 'kode' => '051', 'nama' => 'Rumah Tinggal', 'tahun' => 2020, 'nilai' => 247_000_000, 'nilai_kini' => 310_000_000, 'tahun_pelepasan' => null, 'khas' => ['Mulyorejo, Surabaya', '120/90', 'SHM 02.11.884'], 'terkunci' => true],
            ],
            'lainnya' => [
                ['id' => 9, 'kode' => '061', 'nama' => 'Logam Mulia 50 gram', 'tahun' => 2024, 'nilai' => 91_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['ANTM-LM-778120'], 'terkunci' => false],
            ],
            default => [],
        };
    }

    /**
     * Kode utang sesuai lampiran SPT.
     *
     * @return array<string, string>
     */
    public static function kodeUtang(): array
    {
        return [
            '101' => '101 - Utang Bank / LKBB',
            '102' => '102 - Kartu Kredit',
            '103' => '103 - Utang Afiliasi',
            '109' => '109 - Utang Lainnya',
        ];
    }

    public static function utang(): array
    {
        return [
            ['id' => 1, 'kode' => '101', 'deskripsi' => 'KPR rumah tinggal', 'kreditur' => 'Bank Mandiri', 'nik_kreditur' => null, 'negara' => 'Indonesia', 'tahun' => 2020, 'tahun_pelunasan' => null, 'saldo' => 180_000_000, 'cicilan' => 4_100_000, 'keterangan' => 'Angsuran ke-73 dari 180', 'terkunci' => true],
            ['id' => 2, 'kode' => '101', 'deskripsi' => 'Kredit kendaraan bermotor', 'kreditur' => 'Bank BCA', 'nik_kreditur' => null, 'negara' => 'Indonesia', 'tahun' => 2022, 'tahun_pelunasan' => null, 'saldo' => 45_000_000, 'cicilan' => 1_750_000, 'keterangan' => null, 'terkunci' => false],
            ['id' => 3, 'kode' => '109', 'deskripsi' => 'Pinjaman modal usaha', 'kreditur' => 'Koperasi Sejahtera', 'nik_kreditur' => '3578012207800004', 'negara' => 'Indonesia', 'tahun' => 2025, 'tahun_pelunasan' => null, 'saldo' => 13_000_000, 'cicilan' => null, 'keterangan' => 'Dibayar sekaligus saat jatuh tempo', 'terkunci' => false],
        ];
    }

    public static function drafTahunan(): array
    {
        return [
            ['tahun' => 2026, 'draf_bulanan' => 6, 'bruto' => 780_000_000, 'pph_final' => 1_400_000, 'total_harta' => 650_000_000, 'total_utang' => 238_000_000, 'kekayaan_bersih' => 412_000_000, 'status' => 'belum'],
            ['tahun' => 2025, 'draf_bulanan' => 12, 'bruto' => 742_000_000, 'pph_final' => 1_210_000, 'total_harta' => 500_000_000, 'total_utang' => 183_000_000, 'kekayaan_bersih' => 317_000_000, 'status' => 'tersusun'],
            ['tahun' => 2024, 'draf_bulanan' => 12, 'bruto' => 604_000_000, 'pph_final' => 520_000, 'total_harta' => 410_000_000, 'total_utang' => 162_000_000, 'kekayaan_bersih' => 248_000_000, 'status' => 'tersusun'],
        ];
    }

    /**
     * Rekap 12 bulan untuk tahun yang drafnya sudah lengkap (dipakai Susun/Lihat Draf Tahunan).
     *
     * @return array<int, array{nama: string, bruto: int, akumulasi: int, omzet_kena_pajak: int, pph_final: int}>
     */
    public static function rekapTahunan(): array
    {
        $bruto = [48, 52, 55, 58, 60, 62, 64, 66, 65, 68, 70, 74];
        $akumulasi = 0;
        $baris = [];

        foreach (self::bulan() as $i => $nama) {
            $nilai = $bruto[$i - 1] * 1_000_000;
            $akumulasi += $nilai;
            $kena = max(0, min($akumulasi - 500_000_000, $nilai));

            $baris[$i] = [
                'nama' => $nama,
                'bruto' => $nilai,
                'akumulasi' => $akumulasi,
                'omzet_kena_pajak' => $kena,
                'pph_final' => (int) round($kena * 0.005),
            ];
        }

        return $baris;
    }

    /**
     * Angka perhitungan satu draf bulanan (halaman Susun, Lihat, dan PDF).
     *
     * @return array<string, mixed>
     */
    public static function perhitunganBulanan(int $bulan): array
    {
        $bruto = self::bruto();
        $namaBulan = self::bulan()[$bulan];
        $akumulasiSebelum = array_sum(array_slice($bruto, 0, $bulan - 1, true));
        $brutoBulanIni = $bruto[$bulan];
        $akumulasi = $akumulasiSebelum + $brutoBulanIni;
        $konfigurasi = self::konfigurasiPajak();
        $batasBebas = $konfigurasi['ambang_bebas'];
        $omzetKenaPajak = max(0, min($akumulasi - $batasBebas, $brutoBulanIni));

        $transaksi = collect(self::penghasilan())
            ->filter(fn (array $baris): bool => (int) date('n', strtotime($baris['tanggal'])) === $bulan)
            ->values()
            ->all();

        return [
            'tahun' => self::TAHUN,
            'bulan' => $bulan,
            'namaBulan' => $namaBulan,
            'transaksi' => $transaksi,
            'brutoBulanIni' => $brutoBulanIni,
            'akumulasiSebelum' => $akumulasiSebelum,
            'akumulasi' => $akumulasi,
            'batasBebas' => $batasBebas,
            'sisaBebas' => max(0, $batasBebas - $akumulasiSebelum),
            'omzetKenaPajak' => $omzetKenaPajak,
            'tarif' => $konfigurasi['tarif_final'],
            'pphFinal' => (int) round($omzetKenaPajak * $konfigurasi['tarif_final'] / 100),
            'nihil' => $brutoBulanIni === 0,
        ];
    }

    /**
     * Isi satu draf tahunan (halaman Susun, Lihat, dan PDF).
     *
     * @return array<string, mixed>
     */
    public static function drafTahunanRinci(int $tahun): array
    {
        $rekap = self::rekapTahunan();

        $harta = collect(self::kategoriHarta())->map(fn (array $info, string $kunci): array => [
            'nama' => $info['nama'],
            'jumlah' => count(self::harta($kunci)),
            'nilai' => collect(self::harta($kunci))->sum('nilai'),
        ]);

        $totalHarta = $harta->sum('nilai');
        $totalUtang = collect(self::utang())->sum('saldo');
        $kekayaanBersih = $totalHarta - $totalUtang;
        $kekayaanTahunLalu = 317_000_000;
        $bruto = array_sum(array_column($rekap, 'bruto'));
        $pph = array_sum(array_column($rekap, 'pph_final'));

        return [
            'tahun' => $tahun,
            'rekap' => $rekap,
            'bruto' => $bruto,
            'pph' => $pph,
            'harta' => $harta,
            'utang' => self::utang(),
            'totalHarta' => $totalHarta,
            'totalUtang' => $totalUtang,
            'kekayaanBersih' => $kekayaanBersih,
            'kekayaanTahunLalu' => $kekayaanTahunLalu,
            'pertumbuhan' => $kekayaanBersih - $kekayaanTahunLalu,
            'pertumbuhanPersen' => ($kekayaanBersih - $kekayaanTahunLalu) / $kekayaanTahunLalu * 100,
            'rasioKonsistensi' => $rasioKonsistensi = ($kekayaanBersih - $kekayaanTahunLalu) / ($bruto - $pph) * 100,
            'statusKonsistensi' => match (true) {
                $rasioKonsistensi < 60 => 'normal',
                $rasioKonsistensi <= 100 => 'tinjau',
                default => 'periksa',
            },
        ];
    }

    public static function simulasi(): array
    {
        return [
            'kondisi' => [
                'penghasilan_bulanan' => 130_000_000,
                'total_harta' => 650_000_000,
                'total_utang' => 238_000_000,
                'kekayaan_bersih' => 412_000_000,
                'cicilan_berjalan' => collect(self::utang())->sum('cicilan'),
            ],
            'rencana' => [
                'harga_aset' => 450_000_000,
                'uang_muka' => 90_000_000,
                'jangka_waktu' => 60,
                'suku_bunga' => 8.5,
            ],
        ];
    }
}
