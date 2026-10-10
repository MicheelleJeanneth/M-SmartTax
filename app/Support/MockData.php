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

    /**
     * Kode harta sesuai lampiran SPT beserta uraian resminya, per kategori.
     * Uraian inilah yang mengisi kolom Deskripsi secara otomatis.
     *
     * Kategori yang daftarnya belum tersedia mengembalikan array kosong.
     *
     * @return array<string, string>
     */
    public static function kodeHarta(string $kategori): array
    {
        return match ($kategori) {
            'kas' => [
                '0101' => 'Uang Tunai/Bank Note/Koin',
                '0102' => 'Tabungan (Bank/Lembaga Keuangan)',
                '0103' => 'Giro',
                '0104' => 'Deposito',
                '0105' => 'Uang Elektronik',
                '0106' => 'Cek',
                '0107' => 'Wessel',
                '0108' => 'Kertas Komersial',
                '0109' => 'Setara Kas Lainnya',
            ],
            'piutang' => [
                '0201' => 'Piutang Usaha',
                '0202' => 'Piutang Afiliasi',
                '0209' => 'Piutang Lainnya',
            ],
            'investasi' => [
                '0301' => 'Saham yang dibeli untuk dijual kembali',
                '0302' => 'Saham Non Bursa',
                '0303' => 'Saham Bursa',
                '0304' => 'Obligasi Perusahaan',
                '0305' => 'Obligasi Pemerintah',
                '0306' => 'Surat Utang Lainnya',
                '0307' => 'Kontrak Investasi Kolektif (KIK) Indonesia',
                '0308' => 'Instrumen Derivatif',
                '0309' => 'Penyertaan modal dalam perusahaan lain yang bukan atas saham',
                '0310' => 'Asuransi',
                '0311' => 'Unit link di Asuransi',
                '0399' => 'Investasi Lainnya',
            ],
            'bergerak' => [
                '0401' => 'Sepeda',
                '0402' => 'Sepeda Motor',
                '0403' => 'Mobil Penumpang',
                '0404' => 'Bus',
                '0405' => 'Kendaraan Angkutan Jalan',
                '0406' => 'Kendaraan Tujuan Khusus',
                '0407' => 'Kereta',
                '0408' => 'Pesawat Terbang',
                '0409' => 'Kapal',
                '0410' => 'Mesin',
                '0411' => 'Gerobak',
                '0412' => 'Kapal Pesiar',
                '0499' => 'Harta Bergerak Lainnya',
            ],
            'tidak-bergerak' => [
                '0501' => 'Tanah Kosong',
                '0502' => 'Tanah dan/atau Bangunan untuk Tempat Tinggal',
                '0503' => 'Apartemen',
                '0504' => 'Vessel',
                '0505' => 'Tanah atau Lahan untuk Usaha (lahan pertanian, perkebunan, dsb)',
                '0506' => 'Tanah dan/atau Bangunan untuk Usaha (toko, pabrik, dsb)',
                '0507' => 'Tanah dan/atau Bangunan yang disewakan',
                '0509' => 'Harta Tidak Bergerak Lainnya',
            ],
            'lainnya' => [
                '0601' => 'Paten',
                '0602' => 'Royalti',
                '0603' => 'Merek Dagang',
                '0699' => 'Harta Tidak Berwujud Lainnya',
                '0701' => 'Emas Batangan',
                '0702' => 'Emas Perhiasan',
                '0703' => 'Batangan Non Emas',
                '0704' => 'Perhiasan Non Emas',
                '0705' => 'Permata',
                '0706' => 'Barang-barang Seni dan Antik',
                '0707' => 'Peralatan Olahraga Khusus',
                '0708' => 'Peralatan Elektronik',
                '0709' => 'Perabotan Rumah Tangga',
                '0710' => 'Peralatan Kantor',
                '0711' => 'Jet Ski',
                '0712' => 'Persediaan Usaha',
                '0799' => 'Harta Lainnya',
            ],
            default => [],
        };
    }

    /**
     * Definisi enam kategori harta. Tiap kategori punya sebutan sendiri untuk
     * kolom nilai, tahun, dan lokasi, karena istilahnya berbeda-beda di SPT.
     *
     * 'contoh' dan 'bantuan_kolom' sejajar dengan 'kolom' menurut urutannya.
     */
    public static function kategoriHarta(): array
    {
        return [
            'kas' => [
                'nama' => 'Kas dan Setara Kas',
                'singkat' => 'Kas',
                'keterangan_isi' => 'Isi data kas atau setara kas yang dimiliki',
                'keterangan_ubah' => 'Perbarui data kas atau setara kas yang sudah tercatat',
                'label_nilai' => 'Saldo',
                'label_nilai_kini' => null,
                'bantuan_nilai' => null,
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila rekening telah ditutup.',
                'judul_rincian' => 'Rincian Rekening',
                'contoh_keterangan' => 'Tabungan operasional',
                'label_lokasi' => 'Lokasi / Negara',
                'kolom' => ['Nama Bank', 'Nomor Rekening', 'Atas Nama'],
                'contoh' => ['Contoh: Bank BCA', 'Contoh: 1234567890', 'Contoh: Budi Santoso'],
                'bantuan_kolom' => [null, null, 'Kosongkan bagian rekening jika harta berupa uang tunai.'],
                'wajib_kolom' => [false, false, false],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Nama Bank', 'isi' => 'khas:0'],
                    ['judul' => 'Nomor Rekening', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Saldo', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Untuk kas dan setara kas, saldo dicatat sebesar jumlah pada akhir tahun pajak.',
            ],
            'piutang' => [
                'nama' => 'Piutang',
                'singkat' => 'Piutang',
                'keterangan_isi' => 'Isi data piutang yang masih memiliki sisa tagihan',
                'keterangan_ubah' => 'Perbarui data piutang yang masih memiliki sisa tagihan',
                'label_nilai' => 'Nilai Piutang',
                'label_nilai_kini' => 'Saldo Piutang Saat Ini',
                'wajib_nilai_kini' => true,
                'bantuan_nilai' => 'Nilai piutang adalah jumlah awal tagihan, saldo saat ini adalah sisa yang belum dibayar pada akhir tahun pajak.',
                'label_tahun' => 'Tahun Dimulai',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila piutang telah lunas.',
                'judul_rincian' => 'Data Penerima Pinjaman',
                'contoh_keterangan' => 'Piutang pelanggan grosir, jatuh tempo Desember',
                'label_lokasi' => 'Lokasi Penerima',
                'kolom' => ['Nama Penerima Pinjaman', 'NIK Penerima'],
                'contoh' => ['Contoh: Siti Aminah', '16 digit'],
                'bantuan_kolom' => [null, 'NIK boleh dikosongkan jika penerima berupa badan usaha.'],
                'wajib_kolom' => [true, false],
                // Banyaknya digit untuk kolom yang hanya boleh berisi angka; null = teks bebas.
                'digit_kolom' => [null, 16],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Nama Penerima', 'isi' => 'khas:0'],
                    ['judul' => 'Nilai Piutang', 'isi' => 'nilai', 'kanan' => true],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Saldo Saat Ini', 'isi' => 'nilai_kini', 'kanan' => true],
                ],
                'catatan' => 'Nilai piutang adalah jumlah awal tagihan, sedangkan saldo saat ini adalah sisa yang belum dibayar pada akhir tahun pajak.',
            ],
            'investasi' => [
                'nama' => 'Investasi / Sekuritas',
                'singkat' => 'Investasi',
                'keterangan_isi' => 'Isi data investasi atau sekuritas yang dimiliki',
                'keterangan_ubah' => 'Perbarui data investasi atau sekuritas yang sudah tercatat',
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => 'Nilai Saat Ini',
                'wajib_nilai_kini' => false,
                'bantuan_nilai' => 'Harga perolehan adalah jumlah yang dibayarkan saat membeli. Nilai saat ini bersifat opsional dan tidak masuk perhitungan.',
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila investasi telah dijual atau dicairkan',
                'judul_rincian' => 'Data Penerbit',
                'contoh_keterangan' => 'Saham BBCA sebanyak 500 lembar',
                'label_lokasi' => 'Lokasi Penerbit',
                'kolom' => ['Nama Penerbit', 'Nomor Akun', 'NIK Penerbit'],
                'contoh' => ['Contoh: PT Bank', 'Contoh: SB-0092841', '16 digit'],
                'bantuan_kolom' => [null, null, 'Nomor akun dan NIK penerbit boleh dikosongkan'],
                'wajib_kolom' => [true, false, false],
                'digit_kolom' => [null, null, 16],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Nama Penerbit', 'isi' => 'khas:0'],
                    ['judul' => 'Nomor Akun', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Harga perolehan dicatat sebesar jumlah yang dibayarkan saat membeli, bukan nilai pasar saat ini.',
            ],
            'bergerak' => [
                'nama' => 'Harta Bergerak',
                'singkat' => 'Bergerak',
                'keterangan_isi' => 'Isi data harta bergerak yang dimiliki',
                'keterangan_ubah' => 'Perbarui data harta bergerak yang sudah tercatat',
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => null,
                'bantuan_nilai' => null,
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dialihkan.',
                'judul_rincian' => 'Rincian Kendaraan',
                'contoh_keterangan' => 'Kendaraan operasional toko',
                'label_lokasi' => 'Lokasi / Negara',
                'kolom' => ['Nomor Polisi', 'Kepemilikan'],
                'contoh' => ['Contoh: L 1234 BS', 'Contoh: Milik Sendiri'],
                'bantuan_kolom' => [null, null],
                'wajib_kolom' => [false, false],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Nomor Polisi', 'isi' => 'khas:0'],
                    ['judul' => 'Kepemilikan', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Harta bergerak dicatat sebesar harga perolehan, bukan nilai jual saat ini.',
            ],
            'tidak-bergerak' => [
                'nama' => 'Harta Tidak Bergerak',
                'singkat' => 'Tidak bergerak',
                'keterangan_isi' => 'Isi data harta tidak bergerak yang dimiliki',
                'keterangan_ubah' => 'Perbarui data harta tidak bergerak yang sudah tercatat',
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => null,
                'bantuan_nilai' => null,
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dialihkan.',
                'judul_rincian' => 'Rincian Properti',
                'contoh_keterangan' => 'Rumah tinggal keluarga',
                'label_lokasi' => 'Lokasi / Negara',
                'kolom' => ['Lokasi', 'Luas T/B', 'No. Sertifikat'],
                'contoh' => ['Contoh: Mulyorejo, Surabaya', 'Contoh: 120/90', 'Contoh: SHM 02.11.884'],
                'bantuan_kolom' => [null, 'Luas tanah/bangunan dalam m², contoh 120/90.', null],
                'wajib_kolom' => [false, false, false],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Lokasi', 'isi' => 'khas:0'],
                    ['judul' => 'No. Sertifikat', 'isi' => 'khas:2'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Harta tidak bergerak dicatat sebesar harga perolehan, bukan nilai jual saat ini.',
            ],
            'lainnya' => [
                'nama' => 'Harta Lainnya',
                'singkat' => 'Lainnya',
                'keterangan_isi' => 'Isi data harta lainnya yang dimiliki',
                'keterangan_ubah' => 'Perbarui data harta lainnya yang sudah tercatat',
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => null,
                'bantuan_nilai' => null,
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dialihkan.',
                'judul_rincian' => 'Rincian Harta',
                'contoh_keterangan' => 'Logam mulia batangan',
                'label_lokasi' => 'Lokasi / Negara',
                'kolom' => ['Nomor Kepemilikan'],
                'contoh' => ['Contoh: ANTM-LM-778120'],
                'bantuan_kolom' => [null],
                'wajib_kolom' => [false],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Nomor Kepemilikan', 'isi' => 'khas:0'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Harta lainnya dicatat sebesar harga perolehan.',
            ],
        ];
    }

    /** Baris harta per kategori. 'khas' sejajar dengan 'kolom' di kategoriHarta(). */
    public static function harta(string $kategori): array
    {
        // Negara selalu Indonesia: aplikasi ini khusus wajib pajak dalam negeri.
        return array_map(
            fn (array $baris): array => $baris + ['negara' => 'Indonesia'],
            match ($kategori) {
                'kas' => [
                    ['id' => 1, 'kode' => '0102', 'nama' => 'Tabungan (Bank/Lembaga Keuangan)', 'keterangan' => 'Rekening utama untuk transaksi harian toko', 'tahun' => 2021, 'nilai' => 32_000_000, 'nilai_kini' => 34_000_000, 'tahun_pelepasan' => null, 'khas' => ['Bank Mandiri', '1400012345678', 'Budi Santoso'], 'terkunci' => false],
                    ['id' => 2, 'kode' => '0104', 'nama' => 'Deposito', 'keterangan' => 'Deposito berjangka, jatuh tempo setiap 12 bulan', 'tahun' => 2023, 'nilai' => 100_000_000, 'nilai_kini' => 105_000_000, 'tahun_pelepasan' => null, 'khas' => ['Bank BCA', '8720045512', 'Budi Santoso'], 'terkunci' => true],
                ],
                'piutang' => [
                    ['id' => 3, 'kode' => '0201', 'nama' => 'Piutang Usaha', 'keterangan' => 'Piutang pelanggan grosir, jatuh tempo Desember', 'tahun' => 2025, 'nilai' => 15_000_000, 'nilai_kini' => 12_000_000, 'tahun_pelepasan' => null, 'khas' => ['Siti Aminah', '3578014503920003'], 'terkunci' => false],
                    ['id' => 4, 'kode' => '0202', 'nama' => 'Piutang Afiliasi', 'keterangan' => null, 'tahun' => 2024, 'nilai' => 5_000_000, 'nilai_kini' => 5_000_000, 'tahun_pelepasan' => null, 'khas' => ['CV Mitra Sejahtera', null], 'terkunci' => true],
                ],
                'investasi' => [
                    ['id' => 5, 'kode' => '0303', 'nama' => 'Saham Bursa', 'keterangan' => 'Portofolio saham jangka panjang', 'tahun' => 2024, 'nilai' => 20_000_000, 'nilai_kini' => 24_000_000, 'tahun_pelepasan' => null, 'khas' => ['PT Bank Central Asia Tbk', 'XL-0099123', null], 'terkunci' => true],
                    ['id' => 10, 'kode' => '0307', 'nama' => 'Kontrak Investasi Kolektif (KIK) Indonesia', 'keterangan' => 'Reksa dana pasar uang', 'tahun' => 2025, 'nilai' => 10_000_000, 'nilai_kini' => 10_600_000, 'tahun_pelepasan' => null, 'khas' => ['Manulife Aset Manajemen', 'RD-00912345', null], 'terkunci' => false],
                ],
                'bergerak' => [
                    ['id' => 6, 'kode' => '0403', 'nama' => 'Mobil Penumpang', 'keterangan' => 'Toyota Avanza 2022, dipakai untuk pengiriman pesanan', 'tahun' => 2022, 'nilai' => 112_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['L 1234 BS', 'Milik Sendiri'], 'terkunci' => true],
                    ['id' => 7, 'kode' => '0402', 'nama' => 'Sepeda Motor', 'keterangan' => 'Honda Vario', 'tahun' => 2023, 'nilai' => 18_000_000, 'nilai_kini' => 15_500_000, 'tahun_pelepasan' => null, 'khas' => ['L 5678 BS', 'Milik Sendiri'], 'terkunci' => false],
                ],
                'tidak-bergerak' => [
                    ['id' => 8, 'kode' => '0502', 'nama' => 'Tanah dan/atau Bangunan untuk Tempat Tinggal', 'keterangan' => 'Rumah tinggal keluarga, ditempati sendiri', 'tahun' => 2020, 'nilai' => 247_000_000, 'nilai_kini' => 310_000_000, 'tahun_pelepasan' => null, 'khas' => ['Mulyorejo, Surabaya', '120/90', 'SHM 02.11.884'], 'terkunci' => true],
                ],
                'lainnya' => [
                    ['id' => 9, 'kode' => '0701', 'nama' => 'Emas Batangan', 'keterangan' => 'Logam mulia 50 gram', 'tahun' => 2024, 'nilai' => 91_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['ANTM-LM-778120'], 'terkunci' => false],
                ],
                default => [],
            },
        );
    }

    /**
     * Kode utang sesuai lampiran SPT, dengan uraian lengkapnya.
     * Dipakai pada daftar pilihan saat menambah atau mengubah utang.
     *
     * @return array<string, string>
     */
    public static function kodeUtang(): array
    {
        return [
            '101' => '101 – Utang Bank/Lembaga Keuangan Bukan Bank (KPR, Leasing Kendaraan Bermotor, dan sejenisnya)',
            '102' => '102 – Kartu Kredit',
            '103' => '103 – Utang Afiliasi',
            '109' => '109 – Utang Lainnya',
        ];
    }

    /**
     * Uraian ringkas kode utang untuk tempat yang sempit: filter, tabel,
     * halaman detail, dan laporan PDF.
     *
     * @return array<string, string>
     */
    public static function kodeUtangSingkat(): array
    {
        return [
            '101' => '101 – Utang Bank/LKBB',
            '102' => '102 – Kartu Kredit',
            '103' => '103 – Utang Afiliasi',
            '109' => '109 – Utang Lainnya',
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
