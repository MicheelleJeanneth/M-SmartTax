<?php

namespace App\Support;

use App\Models\KonfigurasiPajak;
use App\Services\SimulasiAset;

/**
 * Isi Panduan Pengguna. Setiap bagian berdiri sebagai satu halaman dan
 * dipilih lewat daftar isi di sebelah kiri.
 *
 * Sebuah bagian berisi daftar kartu yang ditampilkan berurutan, lalu
 * 'catatan' yang selalu menutup halaman sebagai kotak keterangan.
 * Kartu pertama memakai judul bagian; kartu lain menyebut judulnya sendiri.
 *
 * Satu kartu boleh memakai bagian mana pun di bawah ini, dan selalu
 * ditampilkan dengan urutan yang sama seperti daftar ini:
 *
 * - keterangan: satu baris penjelas di bawah judul kartu.
 * - pengantar: satu paragraf pembuka.
 * - langkah: daftar bernomor, tiap baris {judul, keterangan}.
 * - butir: daftar bertitik biasa.
 * - isi: urutan bebas dari ['p', teks], ['peringatan', teks], dan
 *   ['sub', judul, teks].
 * - tabel: {kepala, baris, lebar?, tegas?} dengan jumlah kolom bebas.
 *   'lebar' menyebut kelas lebar tiap kolom, 'tegas' menyebut berapa kolom
 *   awal yang ditulis gelap. Sebuah sel boleh berupa lambang
 *   ['ikon' => ..., 'kelas' => ...] atau lencana ['lencana' => ...].
 * - tanya: daftar pasangan {tanya, jawab}.
 */
class Panduan
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function daftar(): array
    {
        // Tarif dan ambang dibaca dari konfigurasi pajak, bukan ditulis ulang,
        // supaya panduan tidak pernah menyebut angka yang berbeda dari
        // perhitungan. Panduan menulis rupiah tanpa spasi, karena itu memakai
        // angka() dan bukan rupiah().
        $pajak = KonfigurasiPajak::berlakuUntukTahun(MockData::TAHUN)
            ?? throw new \RuntimeException('Konfigurasi pajak belum tersedia.');
        $batasAtas = 'Rp'.angka((int) $pajak->ambang_maksimal);
        $batasBebas = 'Rp'.angka((int) $pajak->ambang_bebas);

        return [
            'memulai' => [
                'judul' => 'Memulai',
                'kartu' => [
                    [
                        'pengantar' => 'M-SmartTax membantu Anda menyusun draf perhitungan PPh Final 0,5% untuk wajib pajak orang pribadi UMKM. Draf ini disusun secara mandiri dan digunakan sebagai gambaran sebelum menyetor dan melapor melalui saluran resmi.',
                        'langkah' => [
                            ['judul' => 'Lengkapi profil Anda', 'keterangan' => 'Isi nama dan alamat sesuai kartu tanda penduduk. Data ini tercetak pada seluruh dokumen laporan, jadi pastikan sesuai sebelum menyimpan.'],
                            ['judul' => 'Catat penghasilan setiap bulan', 'keterangan' => 'Masukkan seluruh penerimaan usaha beserta tanggal dan keterangannya. Semakin rutin dicatat, semakin mudah menyusun draf.'],
                            ['judul' => 'Susun draf dan cetak dokumen', 'keterangan' => 'Setelah data lengkap, susun draf bulanan lalu unduh dokumennya melalui menu Laporan.'],
                        ],
                    ],
                    [
                        'judul' => 'Urutan yang perlu diperhatikan',
                        'tabel' => [
                            'kepala' => ['Tindakan', 'Syarat'],
                            'baris' => [
                                ['Menyusun draf bulanan', 'Data penghasilan bulan itu sudah lengkap'],
                                ['Membatalkan draf bulanan', 'Hapus dari bulan terakhir, mundur satu per satu'],
                                ['Menyusun draf tahunan', 'Seluruh draf bulanan tahun itu sudah tersusun'],
                                ['Mengubah data terkunci', 'Batalkan dulu draf yang menguncinya'],
                            ],
                        ],
                    ],
                ],
                'catatan' => 'M-SmartTax tidak terhubung dengan sistem Direktorat Jenderal Pajak. Penyetoran dan pelaporan pajak tetap dilakukan melalui Coretax, e-Billing, atau kantor pelayanan pajak.',
            ],

            'mencatat-penghasilan' => [
                'judul' => 'Mencatat Penghasilan',
                'kartu' => [
                    [
                        'pengantar' => 'Penghasilan adalah seluruh penerimaan bruto dari usaha Anda, sebelum dikurangi biaya apa pun. Catat setiap transaksi agar akumulasi omzet terhitung tepat.',
                        'langkah' => [
                            ['judul' => 'Buka menu Data Penghasilan', 'keterangan' => 'Pilih Data Penghasilan pada kelompok Input Data di sisi kiri, lalu tekan tombol Tambah.'],
                            ['judul' => 'Isi tanggal, nominal, dan keterangan', 'keterangan' => 'Tanggal mengikuti saat penerimaan uang. Nominal diisi sebesar penerimaan bruto. Keterangan membantu Anda mengenali transaksi saat memeriksanya kembali.'],
                            ['judul' => 'Simpan', 'keterangan' => 'Data langsung masuk ke akumulasi omzet tahun berjalan dan terlihat di dashboard.'],
                        ],
                    ],
                    [
                        'judul' => 'Yang perlu dicatat dan tidak',
                        'tabel' => [
                            'kepala' => ['Dicatat', 'Tidak dicatat'],
                            'baris' => [
                                ['Penjualan barang dagangan', 'Pinjaman yang diterima'],
                                ['Imbalan jasa', 'Penjualan aset usaha'],
                                ['Pesanan dan kontrak kerja', 'Penghasilan dari pekerjaan tetap'],
                                ['Penerimaan melalui marketplace', 'Warisan, hibah, dan hadiah'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Nominal diisi sebelum atau sesudah dikurangi biaya?', 'jawab' => 'Sebelum dikurangi biaya apa pun. PPh Final dihitung dari peredaran bruto, bukan dari laba.'],
                            ['tanya' => 'Bagaimana jika pembeli membayar dengan cicilan?', 'jawab' => 'Catat setiap kali uang diterima, sesuai tanggal penerimaannya. Sisa yang belum dibayar dicatat sebagai piutang di menu Data Harta.'],
                            ['tanya' => 'Apakah data penghasilan bisa diubah setelah disimpan?', 'jawab' => 'Bisa, selama draf bulan tersebut belum disusun. Setelah draf disusun, data terkunci dan ditandai dengan lambang gembok.'],
                        ],
                    ],
                ],
                'catatan' => 'Catat penghasilan sesegera mungkin setelah menerimanya. Mengumpulkan catatan di akhir bulan membuat transaksi mudah terlewat.',
            ],

            'mencatat-harta' => [
                'judul' => 'Mencatat Harta',
                'kartu' => [
                    [
                        'pengantar' => 'Harta dicatat sebagai lampiran draf tahunan, yaitu seluruh kekayaan yang Anda miliki pada akhir tahun pajak. Catatlah begitu harta diperoleh agar tidak terlewat saat menyusun draf tahunan.',
                        'langkah' => [
                            ['judul' => 'Pilih kategori yang sesuai', 'keterangan' => 'Buka menu Data Harta, lalu pilih salah satu dari enam tab kategori di atas tabel.'],
                            ['judul' => 'Pilih kode harta', 'keterangan' => 'Deskripsi akan terisi sendiri mengikuti kode yang dipilih. Kode ini mengikuti daftar resmi pada formulir SPT Tahunan.'],
                            ['judul' => 'Isi rincian dan nilai', 'keterangan' => 'Setiap kategori meminta rincian berbeda, misalnya nomor rekening untuk kas atau nomor polisi untuk kendaraan. Isi keterangan agar mudah dibedakan dari harta lain berkode sama.'],
                        ],
                    ],
                    [
                        'judul' => 'Enam kategori harta',
                        'tabel' => [
                            'kepala' => ['Kode', 'Kategori', 'Contoh', 'Dinilai dengan'],
                            // Kolom kode dilebarkan sedikit supaya "06 · 07" tetap satu baris.
                            'lebar' => ['w-[12%]', 'w-[27%]', 'w-[33%]', 'w-[28%]'],
                            'tegas' => 2,
                            'baris' => [
                                ['01', 'Kas dan Setara Kas', 'Uang tunai, tabungan, deposito', 'Saldo akhir tahun'],
                                ['02', 'Piutang', 'Piutang usaha, pinjaman ke orang lain', 'Saldo akhir tahun'],
                                ['03', 'Investasi', 'Saham, reksadana, obligasi', 'Harga perolehan'],
                                ['04', 'Harta Bergerak', 'Mobil, motor, mesin, gerobak', 'Harga perolehan'],
                                ['05', 'Harta Tidak Bergerak', 'Tanah, rumah, ruko, apartemen', 'Harga perolehan'],
                                ['06 · 07', 'Harta Lainnya', 'Emas, perhiasan, merek dagang, persediaan', 'Harga perolehan'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Nilai harta diisi harga beli atau harga sekarang?', 'jawab' => 'Harga beli, kecuali kas dan piutang yang memakai saldo akhir tahun. Kolom Nilai Saat Ini bersifat opsional dan tidak masuk perhitungan.'],
                            ['tanya' => 'Bagaimana jika harta sudah dijual?', 'jawab' => 'Jangan dihapus. Isi Tahun Pelepasan pada halaman Ubah, sehingga harta tetap tercatat untuk tahun sebelumnya tetapi tidak dihitung lagi pada tahun setelahnya.'],
                            ['tanya' => 'Apakah harta pribadi juga dicatat?', 'jawab' => 'Ya. Lampiran harta pada SPT Tahunan mencakup seluruh harta wajib pajak, bukan hanya harta usaha.'],
                        ],
                    ],
                ],
                'catatan' => 'Pertambahan harta yang jauh melampaui penghasilan akan ditandai sebagai Perlu Ditinjau pada draf tahunan. Penandaan itu bukan kesalahan, melainkan pengingat untuk memeriksa kelengkapan data.',
            ],

            'mencatat-utang' => [
                'judul' => 'Mencatat Utang',
                'kartu' => [
                    [
                        'pengantar' => 'Utang dicatat sebagai lampiran draf tahunan, yaitu seluruh kewajiban yang belum lunas pada akhir tahun pajak. Utang juga mengurangi kekayaan bersih Anda.',
                        'langkah' => [
                            ['judul' => 'Buka menu Data Utang', 'keterangan' => 'Pilih Data Utang pada kelompok Input Data, lalu tekan tombol Tambah.'],
                            ['judul' => 'Pilih kode dan isi saldo', 'keterangan' => 'Deskripsi terisi sendiri dari kode. Saldo diisi sebesar sisa kewajiban pada akhir tahun, bukan jumlah pinjaman semula.'],
                            ['judul' => 'Isi data kreditur', 'keterangan' => 'Nama pemberi pinjaman wajib diisi. NIK kreditur boleh dikosongkan untuk pinjaman perorangan.'],
                        ],
                    ],
                    [
                        'judul' => 'Kode utang',
                        'tabel' => [
                            'kepala' => ['Kode', 'Jenis Utang', 'Contoh'],
                            'lebar' => ['w-[9%]', 'w-[31%]', 'w-[60%]'],
                            'tegas' => 2,
                            'baris' => [
                                ['101', 'Utang Bank atau LKBB', 'Kredit usaha, kredit kepemilikan rumah, kredit kendaraan'],
                                ['102', 'Kartu Kredit', 'Sisa tagihan kartu kredit yang belum dibayar'],
                                ['103', 'Utang Afiliasi', 'Pinjaman dari pihak yang memiliki hubungan istimewa'],
                                ['109', 'Utang Lainnya', 'Pinjaman koperasi, pinjaman keluarga, utang ke pemasok'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Saldo diisi jumlah pinjaman atau sisa cicilan?', 'jawab' => 'Sisa yang belum dibayar pada akhir tahun. Jika pinjaman semula Rp200 juta dan sudah dibayar Rp60 juta, saldo yang dicatat Rp140 juta.'],
                            ['tanya' => 'Bagaimana jika utang sudah lunas?', 'jawab' => 'Isi Tahun Pelunasan pada halaman Ubah. Utang tetap tercatat untuk tahun sebelumnya tetapi tidak dihitung lagi setelahnya.'],
                            ['tanya' => 'Apakah utang pribadi juga dicatat?', 'jawab' => 'Ya, sama seperti harta. Lampiran utang pada SPT Tahunan mencakup seluruh kewajiban wajib pajak.'],
                        ],
                    ],
                ],
                'catatan' => 'Perbarui saldo utang setiap akhir tahun sebelum menyusun draf tahunan, karena nilainya berkurang seiring cicilan yang Anda bayar.',
            ],

            'draf-bulanan' => [
                'judul' => 'Menyusun Draf Bulanan',
                'kartu' => [
                    [
                        'pengantar' => 'Draf bulanan menghitung PPh Final yang terutang pada satu masa pajak. Susun draf setelah seluruh penghasilan bulan tersebut selesai dicatat.',
                        'langkah' => [
                            ['judul' => 'Pilih bulan yang akan disusun', 'keterangan' => 'Buka menu Draf Pajak Penghasilan Bulanan. Bulan yang sudah ada datanya dan siap disusun ditandai dengan tombol Susun pada kolom Aksi.'],
                            ['judul' => 'Periksa rincian dan perhitungan', 'keterangan' => 'Sistem menampilkan seluruh transaksi bulan itu beserta langkah perhitungannya. Pastikan tidak ada transaksi yang terlewat sebelum menyimpan.'],
                            ['judul' => 'Simpan draf', 'keterangan' => 'Setelah disimpan, seluruh data penghasilan bulan itu terkunci dan tidak dapat diubah atau dihapus.'],
                        ],
                    ],
                    [
                        'judul' => 'Cara PPh Final dihitung',
                        'keterangan' => 'Tarif 0,5% dikenakan atas peredaran bruto, tetapi Rp500.000.000 pertama dalam satu tahun tidak dikenai pajak.',
                        'tabel' => [
                            'kepala' => ['Keadaan', 'Yang terjadi'],
                            'baris' => [
                                ['Akumulasi masih di bawah Rp500 juta', 'PPh Final nihil, draf tetap disusun'],
                                ['Akumulasi melewati Rp500 juta', 'Hanya kelebihannya yang dikenai 0,5%'],
                                ['Bulan setelahnya', 'Seluruh peredaran bruto dikenai 0,5%'],
                                ['Tidak ada penghasilan pada bulan itu', 'Susun draf nihil agar catatan bulanan lengkap'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Membatalkan draf',
                        'isi' => [
                            ['p', 'Draf yang salah tidak dihapus, melainkan dibatalkan. Data penghasilan bulan itu terbuka kembali dan drafnya dapat disusun ulang.'],
                            ['peringatan', 'Pembatalan hanya dapat dimulai dari bulan terakhir yang tersusun, lalu mundur satu per satu. Untuk membatalkan draf April, batalkan dulu draf Mei dan seterusnya.'],
                            ['p', 'Aturan ini menjaga ketepatan perhitungan, karena akumulasi omzet setiap bulan bergantung pada bulan sebelumnya. Jika satu bulan di tengah diubah tanpa membatalkan bulan setelahnya, seluruh perhitungan setelahnya menjadi keliru.'],
                        ],
                    ],
                    [
                        'judul' => 'Arti lambang pada kolom Aksi',
                        'tabel' => [
                            'kepala' => ['Lambang', 'Arti', 'Keterangan'],
                            'lebar' => ['w-[9%]', 'w-[40%]', 'w-[51%]'],
                            'tegas' => 2,
                            // Lambang dan warnanya sama persis dengan tabel draf bulanan.
                            'baris' => [
                                [['ikon' => 'eye', 'kelas' => 'text-ink-3'], 'Lihat', 'Membuka rincian draf yang sudah tersusun'],
                                [['ikon' => 'circle-x', 'kelas' => 'text-danger'], 'Batalkan', 'Tersedia hanya pada draf bulan terakhir'],
                                [['ikon' => 'circle-x', 'kelas' => 'text-muted'], 'Belum dapat dibatalkan', 'Masih ada draf bulan setelahnya'],
                                [['ikon' => 'lock', 'kelas' => 'text-locked'], 'Terkunci', 'Sudah masuk draf tahunan, batalkan draf tahunan dahulu'],
                            ],
                        ],
                    ],
                ],
                'catatan' => 'Batas setor PPh Final adalah tanggal 15 bulan berikutnya. Susun draf sebelum tanggal tersebut agar Anda punya waktu memeriksa dan menyetor melalui saluran resmi.',
            ],

            'draf-tahunan' => [
                'judul' => 'Menyusun Draf Tahunan',
                'kartu' => [
                    [
                        'pengantar' => 'Draf tahunan merangkum seluruh draf bulanan tahun itu beserta lampiran harta dan utang pada akhir tahun. Draf ini menjadi gambaran isi SPT Tahunan Anda.',
                        'langkah' => [
                            ['judul' => 'Pastikan seluruh draf bulanan selesai', 'keterangan' => 'Tombol Susun baru tersedia setelah seluruh bulan tersusun, termasuk bulan yang drafnya nihil. Bulan sebelum penghasilan pertama Anda tercatat tidak ikut dihitung.'],
                            ['judul' => 'Perbarui data harta dan utang', 'keterangan' => 'Sistem mengambil keadaan harta dan utang pada saat draf disusun. Perbarui saldo dan tambahkan harta baru sebelum melanjutkan.'],
                            ['judul' => 'Periksa rekap dan hasil analisis', 'keterangan' => 'Halaman penyusunan menampilkan rekap seluruh bulan tahun itu, lampiran harta dan utang, serta dua kartu analisis keuangan.'],
                            ['judul' => 'Simpan draf tahunan', 'keterangan' => 'Seluruh draf bulanan, data harta, dan data utang tahun tersebut akan terkunci.'],
                        ],
                    ],
                    [
                        'judul' => 'Dua analisis keuangan',
                        'keterangan' => 'Kedua analisis ini bersifat pengingat, bukan penilaian. Draf tetap dapat disimpan apa pun hasilnya.',
                        'isi' => [
                            ['sub', 'Pertumbuhan Kekayaan', 'Membandingkan kekayaan bersih akhir tahun ini dengan tahun sebelumnya. Kekayaan bersih adalah jumlah harta dikurangi jumlah utang.'],
                            ['sub', 'Konsistensi Harta', 'Membandingkan pertambahan harta bersih dengan penghasilan setahun. Jika pertambahan harta jauh melampaui penghasilan, kemungkinan ada penghasilan atau utang yang belum tercatat.'],
                        ],
                        'tabel' => [
                            'kepala' => ['Status', 'Arti'],
                            'baris' => [
                                [['lencana' => 'normal'], 'Pertambahan harta wajar dibanding penghasilan'],
                                [['lencana' => 'tinjau'], 'Periksa kembali kelengkapan data penghasilan dan utang'],
                                [['lencana' => 'periksa'], 'Selisih sangat besar, kemungkinan ada data yang terlewat'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Mengapa tombol Susun tidak tersedia?', 'jawab' => 'Masih ada draf bulanan yang belum tersusun. Kolom Draf Bulanan pada tabel menunjukkan berapa yang sudah selesai dari jumlah yang dibutuhkan tahun itu.'],
                            ['tanya' => 'Apakah status Perlu Ditinjau menghalangi penyimpanan?', 'jawab' => 'Tidak. Status itu hanya pengingat. Pertambahan harta yang besar bisa saja wajar, misalnya karena warisan atau hibah.'],
                            ['tanya' => 'Bagaimana jika ada data yang perlu diperbaiki setelah draf tahunan disimpan?', 'jawab' => 'Batalkan draf tahunan terlebih dahulu. Seluruh draf bulanan, harta, dan utang tahun itu akan terbuka kembali.'],
                        ],
                    ],
                ],
                'catatan' => 'Batas lapor SPT Tahunan orang pribadi adalah 31 Maret tahun berikutnya. Susun draf tahunan pada awal tahun agar ada waktu memeriksa sebelum melapor.',
            ],

            'laporan' => [
                'judul' => 'Mencetak Laporan',
                'kartu' => [
                    [
                        'pengantar' => 'Menu Laporan mengubah draf yang sudah tersusun menjadi dokumen PDF berkop dan beridentitas wajib pajak, siap dicetak atau disimpan sebagai arsip.',
                        'langkah' => [
                            ['judul' => 'Pilih jenis laporan', 'keterangan' => 'Ada empat laporan pada kelompok Laporan di sisi kiri, masing-masing untuk draf bulanan, draf tahunan, data harta, dan data utang.'],
                            ['judul' => 'Tentukan periode', 'keterangan' => 'Pilih tahun, dan untuk laporan bulanan pilih juga bulannya. Pratinjau dokumen akan berubah mengikuti pilihan Anda.'],
                            ['judul' => 'Periksa pratinjau, lalu unduh', 'keterangan' => 'Pratinjau menampilkan dokumen persis seperti hasil cetaknya. Tekan Unduh PDF untuk menyimpan berkasnya ke perangkat Anda.'],
                        ],
                    ],
                    [
                        'judul' => 'Empat jenis laporan',
                        'tabel' => [
                            'kepala' => ['Laporan', 'Isi', 'Tersedia bila'],
                            'lebar' => ['w-[25%]', 'w-[49%]', 'w-[26%]'],
                            'baris' => [
                                ['Draf Pajak Bulanan', 'Rincian transaksi dan perhitungan PPh Final satu masa pajak', 'Draf bulanan tersusun'],
                                ['Draf Pajak Tahunan', 'Rekap seluruh bulan, daftar harta dan utang, kekayaan bersih', 'Draf tahunan tersusun'],
                                ['Data Harta', 'Daftar seluruh harta menurut enam kategori', 'Ada data harta'],
                                ['Data Utang', 'Daftar seluruh utang beserta data kreditur', 'Ada data utang'],
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Apakah dokumen ini bisa dipakai untuk melapor?', 'jawab' => 'Tidak. Dokumen ini adalah draf perhitungan mandiri, bukan bukti pelaporan pajak. Gunakan angkanya sebagai acuan saat mengisi formulir resmi melalui Coretax atau saluran DJP lainnya.'],
                            ['tanya' => 'Dokumen tersimpan di mana setelah diunduh?', 'jawab' => 'Di folder unduhan perangkat Anda, mengikuti pengaturan peramban. Dokumen tidak disimpan di dalam sistem, jadi Anda dapat mengunduhnya kembali kapan saja.'],
                            ['tanya' => 'Bagaimana jika data profil saya berubah setelah dokumen diunduh?', 'jawab' => 'Dokumen yang sudah tersimpan di perangkat tidak ikut berubah. Unduh ulang jika Anda membutuhkan dokumen dengan data terbaru.'],
                        ],
                    ],
                ],
                'catatan' => 'Simpan dokumen laporan sebagai arsip pribadi.',
            ],

            'simulasi' => [
                'judul' => 'Simulasi Aset',
                'kartu' => [
                    [
                        'judul' => 'Simulasi Kelayakan Pembelian Aset',
                        'pengantar' => 'Simulasi membantu Anda memperkirakan kesiapan keuangan sebelum membeli aset baru, baik secara tunai maupun kredit. Hasilnya tidak disimpan dan dapat dicoba berkali-kali.',
                        'langkah' => [
                            ['judul' => 'Periksa kondisi keuangan Anda', 'keterangan' => 'Kartu paling atas menampilkan rata-rata penghasilan bulanan, cicilan yang sedang berjalan, total utang, dan kekayaan bersih, semuanya diambil otomatis dari data Anda.'],
                            ['judul' => 'Isi rencana pembelian', 'keterangan' => 'Masukkan nama aset, jenis aset, harga, dan cara pembayaran. Untuk pembelian kredit, isi juga uang muka, jangka waktu, dan bunga per tahun.'],
                            ['judul' => 'Tekan Hitung Kelayakan', 'keterangan' => 'Hasil muncul di bawah form. Ubah angka lalu hitung ulang untuk membandingkan beberapa kemungkinan.'],
                        ],
                    ],
                    [
                        'judul' => 'Cara membaca hasil',
                        'keterangan' => 'Kelayakan dinilai dari perbandingan seluruh kewajiban bulanan, yaitu angsuran baru ditambah cicilan yang sedang berjalan, terhadap rata-rata penghasilan bulanan Anda.',
                        'tabel' => [
                            'kepala' => ['Kesimpulan', 'Rasio angsuran', 'Arti'],
                            'lebar' => ['w-[26%]', 'w-[18%]', 'w-[56%]'],
                            // Label dan ambangnya dibaca dari SimulasiAset agar tidak
                            // pernah berbeda dengan vonis yang benar-benar dipakai.
                            'baris' => array_map(
                                fn (array $t, string $arti): array => [
                                    ['lencana' => $t['nada'], 'teks' => $t['kesimpulan']],
                                    $t['batas'],
                                    $arti,
                                ],
                                SimulasiAset::tingkatKelayakan(),
                                [
                                    'Masih dalam batas wajar',
                                    'Ruang gerak keuangan menjadi sempit',
                                    'Pertimbangkan uang muka lebih besar atau jangka waktu lebih panjang',
                                ],
                            ),
                        ],
                    ],
                    [
                        'judul' => 'Pertanyaan yang sering muncul',
                        'tanya' => [
                            ['tanya' => 'Apakah hasil ini menjamin pengajuan kredit disetujui?', 'jawab' => 'Tidak. Simulasi ini memakai perhitungan sederhana berdasarkan penghasilan dan utang Anda. Bank menilai dengan pertimbangan yang jauh lebih banyak, termasuk riwayat kredit dan agunan.'],
                            ['tanya' => 'Apakah hasil simulasi tersimpan?', 'jawab' => 'Tidak. Menutup atau meninggalkan halaman akan menghapus hasilnya. Catat sendiri jika Anda ingin membandingkan beberapa pilihan.'],
                            ['tanya' => 'Apa guna kartu Dampak pada Analisis Konsistensi?', 'jawab' => 'Kartu itu memperkirakan apakah pembelian ini akan membuat pertambahan harta Anda melampaui penghasilan, sehingga draf tahunan nanti ditandai Perlu Ditinjau.'],
                        ],
                    ],
                ],
                'catatan' => 'Setelah aset benar-benar dibeli, catat pada menu Data Harta. Jika dibeli secara kredit, catat juga sisa kewajibannya pada menu Data Utang.',
            ],

            'pertanyaan-umum' => [
                'judul' => 'Pertanyaan Umum',
                'kartu' => [
                    [
                        'keterangan' => 'Pertanyaan yang paling sering diajukan tentang M-SmartTax dan cakupannya.',
                    ],
                    [
                        'judul' => 'Tentang Sistem',
                        'warna' => 'subjudul',
                        'tanya' => [
                            ['tanya' => 'Apakah M-SmartTax terhubung dengan Direktorat Jenderal Pajak?', 'jawab' => 'Tidak. M-SmartTax berdiri sendiri dan tidak terhubung dengan Coretax, e-Filing, maupun e-Billing. Seluruh penyetoran dan pelaporan tetap dilakukan melalui saluran resmi DJP.'],
                            ['tanya' => 'Siapa yang dapat menggunakan sistem ini?', 'jawab' => 'Wajib pajak orang pribadi pelaku UMKM dengan peredaran bruto tidak melebihi '.$batasAtas.' dalam satu tahun pajak, yang memenuhi syarat PPh Final berdasarkan '.$pajak->dasar_hukum.'.'],
                            ['tanya' => 'Apa dasar hukum perhitungannya?', 'jawab' => $pajak->dasar_hukum.' yang berlaku sejak '.tanggal_id($pajak->tanggal_berlaku).', dengan tarif '.persen($pajak->tarif_final).' dan ambang bebas pajak '.$batasBebas.' per tahun.'],
                        ],
                    ],
                    [
                        'judul' => 'Batas dan Ambang',
                        'warna' => 'subjudul',
                        'tanya' => [
                            ['tanya' => 'Apa yang terjadi jika omzet melewati Rp4,8 miliar?', 'jawab' => 'Anda tidak lagi memenuhi syarat PPh Final dan harus beralih ke ketentuan umum. Sistem akan menampilkan pemberitahuan, namun perhitungan dengan ketentuan umum berada di luar cakupan M-SmartTax.'],
                            ['tanya' => 'Apakah ambang Rp500 juta berlaku setiap tahun?', 'jawab' => 'Ya. Akumulasi dihitung ulang dari nol setiap awal tahun pajak, sehingga '.$batasBebas.' pertama pada tahun berikutnya kembali tidak dikenai pajak.'],
                        ],
                    ],
                    [
                        'judul' => 'Data dan Akun',
                        'warna' => 'subjudul',
                        'tanya' => [
                            ['tanya' => 'Mengapa M-SmartTax tidak meminta NIK saya?', 'jawab' => 'Karena NIK tidak dibutuhkan untuk satu pun perhitungan di sini, dan M-SmartTax adalah sistem simulasi yang tidak berhak menuntut data kependudukan Anda. Sebagai gantinya setiap akun menerima nomor M-SmartTax, misalnya MS20260001, yang tercetak pada dokumen laporan.'],
                            ['tanya' => 'Apakah nomor M-SmartTax sama dengan NPWP?', 'jawab' => 'Bukan. Nomor itu hanya penanda akun di dalam sistem ini dan tidak dikenal Direktorat Jenderal Pajak. Saat melapor melalui saluran resmi, gunakan NPWP atau NIK Anda yang sebenarnya.'],
                            ['tanya' => 'Mengapa username tidak dapat diubah?', 'jawab' => 'Username digunakan untuk masuk ke sistem, sehingga dikunci agar akun Anda tetap dapat diakses. Email di sebelahnya boleh Anda isi, ganti, atau kosongkan kapan saja.'],
                            ['tanya' => 'Apakah saya wajib punya email untuk mendaftar?', 'jawab' => 'Tidak. Pendaftaran hanya memerlukan username dan kata sandi. Email bersifat pilihan dan tidak pernah digunakan untuk mengirim apa pun.'],
                            ['tanya' => 'Mengapa sebagian data tidak dapat diubah atau dihapus?', 'jawab' => 'Data yang sudah masuk ke dalam draf akan terkunci agar perhitungan tetap tepat. Batalkan draf yang menguncinya jika Anda perlu memperbaiki data tersebut.'],
                            ['tanya' => 'Apakah data saya dapat dilihat orang lain?', 'jawab' => 'Tidak. Setiap akun hanya dapat mengakses datanya sendiri. Jangan membagikan kata sandi Anda kepada siapa pun.'],
                        ],
                    ],
                ],
                'catatan' => 'Untuk pertanyaan perpajakan yang tidak terjawab di sini, hubungi Kring Pajak 1500200 atau kantor pelayanan pajak tempat Anda terdaftar.',
            ],
        ];
    }
}
