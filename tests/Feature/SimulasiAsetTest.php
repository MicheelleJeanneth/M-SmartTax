<?php

namespace Tests\Feature;

use App\Models\KodeUtang;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Models\Utang;
use App\Services\AnalisisKeuangan;
use App\Services\SimulasiAset;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Tests\TestCase;

class SimulasiAsetTest extends TestCase
{
    /**
     * Rencana kredit yang sah, dipakai sebagai dasar lalu diubah sebagian.
     *
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function rencana(array $ubah = []): array
    {
        return array_merge([
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'uang_muka' => '90.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '8,5',
        ], $ubah);
    }

    public function test_kondisi_keuangan_diambil_dari_data_yang_tercatat(): void
    {
        $this->get('/simulasi')
            ->assertOk()
            // Rata-rata penghasilan bulanan, cicilan berjalan, total utang, kekayaan bersih.
            ->assertSee('Rp 76.333.333')
            ->assertSee('Rp 5.850.000')
            ->assertSee('Rp 238.000.000')
            ->assertSee('Rp 412.000.000');
    }

    public function test_angsuran_dihitung_dengan_metode_anuitas(): void
    {
        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Rp 7.385.951')      // angsuran per bulan
            ->assertSee('Rp 13.235.951')     // total kewajiban bulanan
            ->assertSee('17,3%')             // rasio angsuran terhadap penghasilan
            ->assertSee('65,3%');            // rasio total utang terhadap penghasilan
    }

    public function test_koma_desimal_pada_bunga_dibaca_sama_dengan_titik(): void
    {
        $koma = $this->post('/simulasi', $this->rencana(['suku_bunga' => '8,5']))->getContent();
        $titik = $this->post('/simulasi', $this->rencana(['suku_bunga' => '8.5']))->getContent();

        $this->assertSame($koma, $titik);
    }

    public function test_bunga_nol_membagi_pokok_secara_rata(): void
    {
        $this->assertSame(6_000_000, app(SimulasiAset::class)->hitungCicilan(360_000_000, 60, 0));
    }

    public function test_kesimpulan_hanya_menimbang_rasio_angsuran(): void
    {
        // Rasio utang 65,3% tergolong besar, tetapi rasio angsuran 17,3% tetap
        // menentukan kesimpulannya. Kalimat penjelas tidak boleh memvonis.
        $halaman = $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Layak')
            ->assertSee('tidak ikut menentukan kesimpulan di atas')
            ->getContent();

        foreach (['tergolong aman', 'tergolong tinggi', 'perlu diperhatikan'] as $vonis) {
            $this->assertStringNotContainsString($vonis, $halaman);
        }
    }

    public function test_rasio_angsuran_menentukan_ketiga_kesimpulan(): void
    {
        $kondisi = ['penghasilan_bulanan' => 10_000_000, 'penghasilan_tahun' => 120_000_000,
            'cicilan_berjalan' => 0, 'total_utang' => 0];
        $simulasi = app(SimulasiAset::class);

        $this->assertSame('Layak', $simulasi->hitungKelayakan($kondisi, 2_900_000, 0)['kesimpulan']);
        $this->assertSame('Perlu Dipertimbangkan', $simulasi->hitungKelayakan($kondisi, 3_500_000, 0)['kesimpulan']);
        $this->assertSame('Belum Disarankan', $simulasi->hitungKelayakan($kondisi, 4_100_000, 0)['kesimpulan']);
    }

    public function test_panduan_memakai_ambang_yang_sama_dengan_perhitungan(): void
    {
        $tingkat = SimulasiAset::tingkatKelayakan();

        $this->assertSame(['Layak', 'Perlu Dipertimbangkan', 'Belum Disarankan'], array_column($tingkat, 'kesimpulan'));
        $this->assertSame(['di bawah 30%', '30% sampai 40%', 'di atas 40%'], array_column($tingkat, 'batas'));

        // Halaman panduan menampilkan daftar yang sama, bukan angka yang ditulis ulang.
        $this->get('/panduan/simulasi')
            ->assertOk()
            ->assertSee('di bawah 30%')
            ->assertSee('30% sampai 40%')
            ->assertSee('di atas 40%')
            ->assertSee('Tekan Hitung Kelayakan');
    }

    public function test_cicilan_berjalan_ikut_membentuk_rasio_angsuran(): void
    {
        $kondisi = ['penghasilan_bulanan' => 10_000_000, 'penghasilan_tahun' => 120_000_000,
            'cicilan_berjalan' => 2_000_000, 'total_utang' => 0];

        $hasil = app(SimulasiAset::class)->hitungKelayakan($kondisi, 2_000_000, 0);

        $this->assertSame(4_000_000, $hasil['total_kewajiban']);
        $this->assertSame(40.0, $hasil['rasio']);
    }

    public function test_pembelian_tunai_tidak_menimbulkan_angsuran(): void
    {
        $this->post('/simulasi', $this->rencana(['cara_pembayaran' => 'Tunai']))
            ->assertOk()
            ->assertSee('Pembelian dilakukan secara tunai')
            // Rasio dihitung dari cicilan berjalan saja: 5.850.000 dari 76.333.333.
            ->assertSee('7,7%');
    }

    public function test_penghasilan_nol_tidak_menghitung_rasio_dan_bukan_galat(): void
    {
        $kondisi = ['penghasilan_bulanan' => 0, 'penghasilan_tahun' => 0,
            'cicilan_berjalan' => 1_000_000, 'total_utang' => 0];

        $hasil = app(SimulasiAset::class)->hitungKelayakan($kondisi, 2_000_000, 0);

        $this->assertFalse($hasil['dapat_dihitung']);
        $this->assertNull($hasil['rasio']);
        $this->assertNull($hasil['kesimpulan']);
        $this->assertSame(3_000_000, $hasil['total_kewajiban']);
    }

    public function test_dampak_konsistensi_memakai_perhitungan_draf_tahunan(): void
    {
        // Pembelian kredit menambah kekayaan bersih sebesar uang mukanya saja,
        // karena harta bertambah seharga aset sementara utang bertambah sebesar
        // pokok pinjaman. Selisih keduanya harus tepat sebesar uang muka, berapa
        // pun keadaan awalnya.
        $harga = 450_000_000;
        $pokok = 360_000_000;
        $uangMuka = $harga - $pokok;

        $dampak = app(SimulasiAset::class)->dampakKonsistensi(MockData::TAHUN, $harga, $pokok);

        $this->assertSame(
            $uangMuka,
            $dampak['perkiraan']['pertambahan_bersih'] - $dampak['sekarang']['pertambahan_bersih'],
        );
        // Pembaginya sama, sehingga kedua rasio benar-benar dapat dibandingkan.
        $this->assertSame($dampak['sekarang']['bruto'], $dampak['perkiraan']['bruto']);
    }

    public function test_uang_muka_tidak_boleh_melebihi_harga_aset(): void
    {
        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana(['uang_muka' => '500.000.000']))
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors(['uang_muka' => 'Uang muka tidak boleh melebihi harga aset.']);
    }

    public function test_masukan_yang_tidak_sah_ditolak(): void
    {
        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana([
                'nama_aset' => '',
                'harga_aset' => '0',
                'jangka_waktu' => '400',
                'suku_bunga' => '120',
            ]))
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors(['nama_aset', 'harga_aset', 'jangka_waktu', 'suku_bunga']);

        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana(['nama_aset' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('nama_aset');
    }

    public function test_peraga_penghasilan_nol_menghentikan_hasil_sebelum_rasio(): void
    {
        $this->post('/simulasi', $this->rencana(['peraga' => 'penghasilan-nol']))
            ->assertOk()
            ->assertSee('Rp 13.235.951')   // total kewajiban bulanan tetap tampil
            ->assertSee('Rasio tidak dapat dihitung karena belum ada data penghasilan pada tahun ini.')
            ->assertDontSee('Rasio angsuran terhadap penghasilan')
            ->assertDontSee('Kesimpulan')
            // Draf tahunan pembandingnya ada; yang nol brutonya. Keterangan
            // keadaan 5.3 tidak boleh muncul di sini karena menyesatkan.
            ->assertSee('Rasio belum dapat dihitung karena belum ada peredaran bruto pada tahun ini.')
            ->assertDontSee('setelah draf tahunan pertama tersusun');
    }

    public function test_peraga_tanpa_draf_tahunan_mengganti_kartu_konsistensi(): void
    {
        $this->post('/simulasi', $this->rencana(['peraga' => 'tanpa-draf-tahunan']))
            ->assertOk()
            ->assertSee('Dampak terhadap konsistensi harta akan muncul setelah draf tahunan pertama tersusun.')
            ->assertDontSee('Status Sekarang');
    }

    public function test_peraga_yang_tidak_dikenali_diabaikan(): void
    {
        $this->get('/simulasi?peraga=apa-saja')
            ->assertOk()
            ->assertSee('Rp 76.333.333');
    }

    public function test_hasil_simulasi_tidak_disimpan(): void
    {
        $this->post('/simulasi', $this->rencana())->assertOk();

        // Halaman dibuka kembali tanpa membawa rencana apa pun.
        $this->get('/simulasi')
            ->assertOk()
            ->assertDontSee('Hasil Simulasi')
            ->assertSee('tidak dapat diubah di halaman ini');
    }

    public function test_formulir_rencana_dibuka_tanpa_angka_bawaan(): void
    {
        $halaman = $this->get('/simulasi')->assertOk()->getContent();

        // Setiap kolom di kartu rencana masuk ke rumus dan keluarannya berupa
        // vonis kelayakan, jadi tidak boleh ada angka yang terlihat sah tetapi
        // bukan milik pengguna.
        foreach (['harga_aset', 'uang_muka', 'jangka_waktu', 'suku_bunga', 'nama_aset'] as $kolom) {
            preg_match('/<input[^>]*name="'.$kolom.'"[^>]*>/', $halaman, $cocok);
            $this->assertNotEmpty($cocok, "Kolom {$kolom} tidak ditemukan.");
            preg_match('/value="([^"]*)"/', $cocok[0], $isi);
            $this->assertSame('', $isi[1] ?? '', "Kolom {$kolom} masih punya nilai bawaan.");
        }

        // Jenis aset tidak terpilih lebih dulu, hanya menawarkan pilihan kosong.
        $this->assertStringContainsString('<option value="">Pilih jenis aset</option>', $halaman);

        // Cara pembayaran tetap Kredit supaya ketiga kolom kredit terlihat.
        $this->assertMatchesRegularExpression('/<option value="Kredit"[^>]*selected/', $halaman);

        // Contoh angkanya pindah ke placeholder, yang tidak ikut terkirim.
        $this->assertStringContainsString('placeholder="Contoh: 450.000.000"', $halaman);
    }

    public function test_rencana_kosong_ditolak_bukan_dihitung(): void
    {
        $this->from('/simulasi')
            ->post('/simulasi', ['cara_pembayaran' => 'Kredit'])
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors(['jenis_aset', 'nama_aset', 'harga_aset', 'jangka_waktu', 'suku_bunga'])
            // Uang muka sengaja tidak wajib: kosong berarti tanpa uang muka.
            ->assertSessionDoesntHaveErrors('uang_muka');
    }

    public function test_uang_muka_kosong_dibaca_nol_dan_menghasilkan_angsuran_lebih_besar(): void
    {
        $dasar = [
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '8,5',
        ];

        // Dikosongkan: pokok pinjamannya penuh.
        $this->post('/simulasi', $dasar)
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertSee(rupiah(450_000_000));

        // Diisi: pokoknya berkurang, jadi hasilnya lebih ringan daripada yang kosong.
        $this->post('/simulasi', [...$dasar, 'uang_muka' => '90.000.000'])
            ->assertOk()
            ->assertSee(rupiah(360_000_000));
    }

    public function test_bunga_kosong_tidak_diam_diam_dibaca_nol(): void
    {
        // Bunga nol membuat simulasi tampak lebih ringan daripada kenyataan,
        // jadi nol harus diketik sendiri, bukan jatuh ke sana karena lalai.
        $this->from('/simulasi')
            ->post('/simulasi', [
                'jenis_aset' => 'tidak-bergerak',
                'nama_aset' => 'Ruko cabang ketiga',
                'cara_pembayaran' => 'Kredit',
                'harga_aset' => '450.000.000',
                'uang_muka' => '90.000.000',
                'jangka_waktu' => '60',
            ])
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors('suku_bunga');

        // Nol yang diketik sendiri diterima.
        $this->post('/simulasi', [
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'uang_muka' => '90.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '0',
        ])->assertOk()->assertSessionHasNoErrors();
    }

    public function test_rencana_yang_diisi_tetap_menghasilkan_vonis(): void
    {
        $this->post('/simulasi', [
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'uang_muka' => '90.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '8,5',
        ])->assertOk()->assertSee('Hasil Simulasi')->assertSee(rupiah(360_000_000));
    }

    public function test_rata_rata_penghasilan_menyebut_pembaginya(): void
    {
        // Pembaginya tetap dua belas bulan; yang ditambahkan hanya keterangannya,
        // supaya angka di kartu dapat ditelusuri pengguna.
        $this->get('/simulasi')
            ->assertOk()
            ->assertSee('Penghasilan tahun ini dibagi dua belas bulan');

        $bruto = app(AnalisisKeuangan::class)->peredaranBruto(MockData::TAHUN);

        $this->assertSame(
            (int) round($bruto / 12),
            app(SimulasiAset::class)->kondisi(MockData::TAHUN)['penghasilan_bulanan'],
        );
    }

    public function test_pencatatan_yang_dimulai_di_tengah_tahun_diberi_keterangan(): void
    {
        // Wajib pajak contoh mencatat sejak Januari, jadi barisnya tidak muncul.
        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertDontSee('Anda mulai mencatat pada');

        $lain = Profil::create([
            'email' => 'tengah@uji.test', 'password' => 'rahasia-uji-8', 'nik' => '3578011708950007',
            'nama' => 'Tengah Tahun', 'tempat_lahir' => 'Surabaya', 'tanggal_lahir' => '1995-08-17',
            'jenis_kelamin' => 'Pria', 'nomor_handphone' => '081234567899', 'alamat' => 'Jl. Uji',
            'rt' => '001', 'rw' => '001', 'kelurahan' => 'U', 'kecamatan' => 'U', 'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur', 'profil_lengkap' => true, 'status' => true,
        ]);
        Penghasilan::create([
            'id_profil' => $lain->id_profil, 'tanggal' => MockData::TAHUN.'-04-20',
            'nominal' => 548_500_000, 'keterangan' => 'uji', 'status' => true,
        ]);

        $this->actingAs($lain);
        PenggunaSaatIni::lupakan();

        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Anda mulai mencatat pada April, sehingga rata-rata ini memperhitungkan bulan sebelum pencatatan dimulai.');
    }

    public function test_catatan_penutup_memakai_kotak_biru_muda(): void
    {
        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('bg-[#F5FBFD]', false)
            ->assertSee('Hasil simulasi merupakan perkiraan berdasarkan data yang tercatat pada sistem dan tidak disimpan.');
    }

    public function test_label_rasio_menyebut_satuan_pembaginya(): void
    {
        // Kedua rasio memakai pembagi yang berbeda: sebulan dan setahun.
        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Rasio angsuran terhadap penghasilan bulanan')
            ->assertSee('Rasio total utang terhadap penghasilan tahunan')
            ->assertSee('dari rata-rata penghasilan bulanan');
    }

    public function test_kotak_kesimpulan_menyebut_total_utang_setelah_pembelian(): void
    {
        $kondisi = app(SimulasiAset::class)->kondisi(MockData::TAHUN);
        $pokok = 450_000_000 - 90_000_000;

        $halaman = $this->post('/simulasi', $this->rencana())->assertOk()->getContent();

        // Angkanya adalah utang berjalan ditambah pokok pinjaman baru, bukan
        // "sisa utang" — pengguna bisa saja belum punya utang sama sekali.
        $this->assertStringContainsString(
            'total utang setelah pembelian '.rupiah($kondisi['total_utang'] + $pokok),
            $halaman,
        );
        $this->assertStringNotContainsString('sisa utang', $halaman);
    }

    public function test_cicilan_utang_berjalan_ikut_membentuk_total_kewajiban(): void
    {
        $profil = Profil::create([
            'email' => 'cicilan@uji.test', 'password' => 'rahasia-uji-8', 'nik' => '3578011708950007',
            'nama' => 'Uji Cicilan', 'tempat_lahir' => 'Surabaya', 'tanggal_lahir' => '1995-08-17',
            'jenis_kelamin' => 'Pria', 'nomor_handphone' => '081234567899', 'alamat' => 'Jl. Uji',
            'rt' => '001', 'rw' => '001', 'kelurahan' => 'U', 'kecamatan' => 'U', 'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur', 'profil_lengkap' => true, 'status' => true,
        ]);
        Penghasilan::create([
            'id_profil' => $profil->id_profil, 'tanggal' => MockData::TAHUN.'-04-20',
            'nominal' => 548_500_000, 'keterangan' => 'uji', 'status' => true,
        ]);
        Utang::create([
            'id_profil' => $profil->id_profil,
            'id_kode_utang' => KodeUtang::where('kode', '101')->value('id_kode_utang'),
            'deskripsi' => 'KPR Rumah', 'nama_kreditur' => 'Bank BCA',
            'saldo' => 60_000_000,
            // Kolom inilah yang membuat baris Cicilan berjalan tidak nol.
            'cicilan_bulanan' => 5_000_000,
            'tahun_peminjaman' => MockData::TAHUN,
            'negara_kreditur' => 'Indonesia', 'status' => true,
        ]);

        $this->actingAs($profil);
        PenggunaSaatIni::lupakan();

        // Bunga nol membuat angsuran menjadi pokok dibagi jangka waktu.
        $this->post('/simulasi', [
            'jenis_aset' => 'tidak-bergerak', 'nama_aset' => 'Ruko Cabang ke 2',
            'cara_pembayaran' => 'Kredit', 'harga_aset' => '160.000.000',
            'jangka_waktu' => '10', 'suku_bunga' => '0',
        ])
            ->assertOk()
            ->assertSee(rupiah(16_000_000))   // angsuran per bulan
            ->assertSee(rupiah(5_000_000))    // cicilan berjalan
            ->assertSee(rupiah(21_000_000))   // total kewajiban bulanan
            ->assertSee('45,9%')
            ->assertSee('Belum Disarankan');
    }

    public function test_cicilan_kosong_dihitung_nol(): void
    {
        $utang = Utang::whereNull('cicilan_bulanan')->with('kodeUtang')->first();

        $this->assertNotNull($utang, 'Data contoh butuh satu utang tanpa cicilan berkala.');
        $this->assertNull($utang->cicilan);

        // Halaman rinciannya tetap terbuka dan menyebut keadaannya.
        $this->get('/utang/'.$utang->id)
            ->assertOk()
            ->assertSee('Cicilan Bulanan')
            ->assertSee('Tidak diangsur berkala');
    }
}
