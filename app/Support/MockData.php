<?php

namespace App\Support;

/**
 * Tetapan dan daftar bawaan yang tidak disimpan di basis data.
 *
 * Isinya tinggal dua macam. Pertama, tetapan tampilan: tahun pajak berjalan,
 * nama bulan, kewarganegaraan, dan keterangan tiap kategori harta. Kedua,
 * daftar contoh yang dibaca seeder sebagai isi awal tabel — profil, kode
 * harta, kode utang, harta, utang, dan konfigurasi pajak. Seluruh angka yang
 * ditampilkan halaman sudah dibaca dari basis data, bukan dari sini.
 */
class MockData
{
    public const TAHUN = 2026;

    /** Satu-satunya kewarganegaraan yang dilayani; nilai inilah yang tersimpan. */
    public const KEWARGANEGARAAN = 'WNI';

    /**
     * Tulisan kewarganegaraan di layar. Dipanjangkan supaya tidak terbaca
     * sama dengan kolom Negara pada kartu Alamat, yang artinya berbeda.
     */
    public static function kewarganegaraanTampil(): string
    {
        return self::KEWARGANEGARAAN.' — Warga Negara Indonesia';
    }

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
            'peran' => 'Wajib Pajak Orang Pribadi UMKM',
            'email' => 'budi.santoso@gmail.com',
            // NIK menyandikan tanggal lahir 25-09-80, cocok dengan kolom di bawah.
            'nik' => '3578012509800001',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
            'kewarganegaraan' => self::KEWARGANEGARAAN,
            'telepon' => '0812 3456 7890',
            'alamat' => 'Jl. Rungkut Asri Timur 12',
            'rt' => '004',
            'rw' => '007',
            'kelurahan' => 'Kalirungkut',
            'kecamatan' => 'Rungkut',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
            'negara' => 'Indonesia',
            'profil_lengkap' => true,
        ];
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
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Nama Bank', 'isi' => 'khas:0'],
                    ['judul' => 'Nomor Rekening', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Saldo', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup uang tunai, tabungan, giro, deposito, dan setara kas lainnya. Saldo dicatat sebesar jumlah pada akhir tahun pajak.',
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
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Nama Penerima', 'isi' => 'khas:0'],
                    ['judul' => 'Nilai Piutang', 'isi' => 'nilai', 'kanan' => true],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Saldo Saat Ini', 'isi' => 'nilai_kini', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup tagihan kepada pihak lain, termasuk pihak afiliasi. Nilai piutang adalah jumlah awal tagihan, sedangkan saldo saat ini adalah sisa yang belum dibayar pada akhir tahun pajak.',
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
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Nama Penerbit', 'isi' => 'khas:0'],
                    ['judul' => 'Nomor Akun', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup saham, obligasi, reksa dana, asuransi, dan penyertaan modal. Harga perolehan dicatat sebesar jumlah yang dibayarkan saat membeli, bukan nilai pasar saat ini.',
            ],
            'bergerak' => [
                'nama' => 'Harta Bergerak',
                'singkat' => 'Bergerak',
                'keterangan_isi' => 'Isi data kendaraan, mesin, atau harta bergerak lain yang dimiliki',
                'keterangan_ubah' => 'Perbarui data kendaraan, mesin, atau harta bergerak lain yang sudah tercatat',
                // Kategori ini tanpa judul seksi: kolom khasnya duduk di tengah formulir.
                'seksi' => false,
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => 'Nilai Saat Ini',
                'wajib_nilai_kini' => false,
                'bantuan_nilai' => 'Harga perolehan adalah harga beli. Nilai saat ini bersifat opsional dan tidak masuk perhitungan.',
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dilepas',
                'judul_rincian' => null,
                'contoh_keterangan' => 'Honda Brio warna putih',
                'label_lokasi' => null,
                'kolom' => ['Nomor Polisi / Registrasi', 'Kepemilikan'],
                'contoh' => ['Contoh: L 1234 AB', 'Contoh: Atas nama sendiri'],
                'bawaan_kolom' => [null, 'Atas nama sendiri'],
                'bantuan_kolom' => [null, null],
                'bantuan_khas' => 'Nomor polisi boleh dikosongkan untuk harta tanpa registrasi, seperti mesin atau gerobak',
                'wajib_kolom' => [false, true],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Nomor Polisi', 'isi' => 'khas:0'],
                    ['judul' => 'Kepemilikan', 'isi' => 'khas:1'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup kendaraan, mesin, dan alat angkut lain. Harga perolehan dicatat sebesar harga beli, bukan nilai jual saat ini.',
            ],
            'tidak-bergerak' => [
                'nama' => 'Harta Tidak Bergerak',
                'singkat' => 'Tidak bergerak',
                'keterangan_isi' => 'Isi data tanah, bangunan, atau properti lain yang dimiliki',
                'keterangan_ubah' => 'Perbarui data tanah, bangunan, atau properti lain yang sudah tercatat',
                // Kategori ini tanpa judul seksi: kolom khasnya duduk di tengah formulir.
                'seksi' => false,
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => 'Nilai Saat Ini',
                'wajib_nilai_kini' => false,
                'bantuan_nilai' => 'Harga perolehan adalah harga beli. Ukuran bangunan boleh dikosongkan untuk tanah kosong.',
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dialihkan',
                'judul_rincian' => null,
                'contoh_keterangan' => 'Rumah tinggal utama',
                'label_lokasi' => null,
                'khas_sebelum_keterangan' => true,
                'kolom' => ['Lokasi Harta', 'Ukuran Tanah (m²)', 'Ukuran Bangunan (m²)', 'Sumber Kepemilikan', 'Nomor Sertifikat'],
                'contoh' => ['Contoh: Jl. Rungkut Asri Timur 12, Surabaya', 'Contoh: 120', 'Contoh: 90', '', 'Contoh: SHM 04182'],
                'bantuan_kolom' => [null, null, null, null, null],
                'wajib_kolom' => [true, false, false, true, false],
                'digit_kolom' => [null, [1, 6], [1, 6], null, null],
                'pilihan_kolom' => [null, null, null, ['Hasil sendiri', 'Warisan', 'Utang', 'Hibah', 'Hadiah', 'Sumber lainnya'], null],
                'bawaan_kolom' => [null, null, null, 'Hasil sendiri', null],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Lokasi', 'isi' => 'khas:0'],
                    ['judul' => 'No. Sertifikat', 'isi' => 'khas:4'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup tanah dan bangunan, baik untuk tempat tinggal maupun usaha. Harga perolehan dicatat sebesar harga beli, bukan nilai jual saat ini.',
            ],
            'lainnya' => [
                'nama' => 'Harta Lainnya',
                'singkat' => 'Lainnya',
                'keterangan_isi' => 'Isi data harta lainnya yang dimiliki',
                'keterangan_ubah' => 'Perbarui data harta lainnya yang sudah tercatat',
                // Kategori ini tanpa judul seksi: kolom khasnya duduk di tengah formulir.
                'seksi' => false,
                'label_nilai' => 'Harga Perolehan',
                'label_nilai_kini' => 'Nilai Saat Ini',
                'wajib_nilai_kini' => false,
                'bantuan_nilai' => null,
                'label_tahun' => 'Tahun Perolehan',
                'bantuan_pelepasan' => 'Isi tahun pelepasan bila harta telah dijual atau dilepas',
                'judul_rincian' => null,
                'contoh_keterangan' => 'Emas 100 gram, sertifikat Antam',
                'label_lokasi' => null,
                'kolom' => ['Bukti / Nomor Kepemilikan'],
                'contoh' => ['Contoh: ANTAM-88214'],
                'bantuan_kolom' => [null],
                'wajib_kolom' => [false],
                'tabel' => [
                    ['judul' => 'Kode', 'isi' => 'kode'],
                    ['judul' => 'Deskripsi', 'isi' => 'deskripsi'],
                    ['judul' => 'Keterangan', 'isi' => 'keterangan'],
                    ['judul' => 'Nomor Kepemilikan', 'isi' => 'khas:0'],
                    ['judul' => 'Tahun', 'isi' => 'tahun'],
                    ['judul' => 'Harga Perolehan', 'isi' => 'nilai', 'kanan' => true],
                ],
                'catatan' => 'Kategori ini mencakup harta tidak berwujud seperti paten dan merek dagang, serta harta berwujud lain seperti emas, perhiasan, dan peralatan. Harga perolehan dicatat sebesar harga beli.',
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
                    ['id' => 6, 'kode' => '0403', 'nama' => 'Mobil Penumpang', 'keterangan' => 'Toyota Avanza 2022, dipakai untuk pengiriman pesanan', 'tahun' => 2022, 'nilai' => 112_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['L 1234 BS', 'Atas nama sendiri'], 'terkunci' => true],
                    ['id' => 7, 'kode' => '0402', 'nama' => 'Sepeda Motor', 'keterangan' => 'Honda Vario untuk operasional harian toko', 'tahun' => 2024, 'nilai' => 18_000_000, 'nilai_kini' => 15_500_000, 'tahun_pelepasan' => null, 'khas' => ['L 5678 CD', 'Atas nama sendiri'], 'terkunci' => false],
                ],
                'tidak-bergerak' => [
                    ['id' => 8, 'kode' => '0502', 'nama' => 'Tanah dan/atau Bangunan untuk Tempat Tinggal', 'keterangan' => 'Rumah tinggal keluarga, ditempati sendiri', 'tahun' => 2020, 'nilai' => 147_000_000, 'nilai_kini' => 185_000_000, 'tahun_pelepasan' => null, 'khas' => ['Jl. Rungkut Asri Timur 12, Surabaya', '120', '90', 'Hasil sendiri', 'SHM 04182'], 'terkunci' => true],
                    ['id' => 11, 'kode' => '0506', 'nama' => 'Tanah dan/atau Bangunan untuk Usaha (toko, pabrik, dsb)', 'keterangan' => 'Toko cabang kedua', 'tahun' => 2024, 'nilai' => 100_000_000, 'nilai_kini' => 115_000_000, 'tahun_pelepasan' => null, 'khas' => ['Jl. Raya Kalirungkut 88, Surabaya', '80', '64', 'Hasil sendiri', 'SHM 07734'], 'terkunci' => false],
                ],
                'lainnya' => [
                    ['id' => 9, 'kode' => '0701', 'nama' => 'Emas Batangan', 'keterangan' => 'Logam mulia 50 gram', 'tahun' => 2024, 'nilai' => 91_000_000, 'nilai_kini' => 98_000_000, 'tahun_pelepasan' => null, 'khas' => ['ANTM-LM-778120'], 'terkunci' => false],
                ],
                default => [],
            },
        );
    }

    /**
     * Kode utang resmi beserta uraiannya, dipakai KodeSeeder sebagai sumber
     * isi tabel kode_utang. Tampilan membacanya dari tabel lewat KodeUtang.
     *
     * @return array<string, string>
     */
    public static function kodeUtang(): array
    {
        return [
            '101' => 'Utang Bank/Lembaga Keuangan Bukan Bank (KPR, Leasing Kendaraan Bermotor, dan sejenisnya)',
            '102' => 'Kartu Kredit',
            '103' => 'Utang Afiliasi',
            '109' => 'Utang Lainnya',
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
}
