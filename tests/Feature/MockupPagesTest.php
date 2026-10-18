<?php

namespace Tests\Feature;

use App\Services\FilterTahun;
use App\Services\KunciPencatatan;
use App\Support\MockData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MockupPagesTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function halaman(): array
    {
        $uri = [
            '/login', '/register', '/profil/lengkapi', '/dashboard',
            '/penghasilan', '/penghasilan/create', '/penghasilan/25/edit',
            '/harta', '/harta/tidak-bergerak', '/harta/kas/tambah', '/harta/kas/1', '/harta/kas/1/ubah',
            '/utang', '/utang/create', '/utang/2', '/utang/2/edit',
            '/draf-bulanan', '/draf-bulanan/susun', '/draf-bulanan/susun?bulan=8', '/draf-bulanan/6',
            '/draf-bulanan?tahun=2025', '/draf-bulanan/12?tahun=2025', '/draf-bulanan/susun?bulan=3&tahun=2025',
            '/draf-tahunan', '/draf-tahunan/susun?tahun=2025', '/draf-tahunan/2025',
            '/simulasi',
            '/laporan/draf-bulanan', '/laporan/draf-bulanan?bulan=9', '/laporan/draf-tahunan', '/laporan/draf-tahunan?tahun=2026',
            '/laporan/harta', '/laporan/utang',
            '/laporan/draf-bulanan/pratinjau', '/laporan/draf-tahunan/pratinjau?tahun=2025', '/laporan/harta/pratinjau', '/laporan/utang/pratinjau',
            '/profil', '/profil/ubah', '/profil/kata-sandi', '/panduan', '/panduan/mencatat-harta', '/panduan/pertanyaan-umum',
        ];

        return array_combine($uri, array_map(fn (string $u): array => [$u], $uri));
    }

    #[DataProvider('halaman')]
    public function test_halaman_mockup_dapat_dibuka(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_panduan_menyorot_bagian_yang_sedang_dibuka(): void
    {
        $this->get('/panduan')->assertOk()->assertSee('Urutan yang perlu diperhatikan');

        // Bagian lain menggantikan isinya, bukan menambah di bawahnya.
        $this->get('/panduan/mencatat-utang')
            ->assertOk()
            ->assertSee('Mencatat Utang')
            ->assertDontSee('Urutan yang perlu diperhatikan');

        $this->get('/panduan/tidak-ada')->assertNotFound();
    }

    public function test_panduan_menampilkan_blok_tanya_jawab(): void
    {
        $this->get('/panduan/mencatat-penghasilan')
            ->assertOk()
            ->assertSee('Yang perlu dicatat dan tidak')
            ->assertSee('Pertanyaan yang sering muncul')
            ->assertSee('PPh Final dihitung dari peredaran bruto, bukan dari laba.');

        // Bagian yang belum punya blok tanya jawab tidak ikut memunculkannya.
        $this->get('/panduan/memulai')->assertOk()->assertDontSee('Pertanyaan yang sering muncul');
    }

    public function test_panduan_menampilkan_kartu_teks_dan_lambang(): void
    {
        $this->get('/panduan/draf-bulanan')
            ->assertOk()
            ->assertSee('Cara PPh Final dihitung')
            ->assertSee('Membatalkan draf')
            ->assertSee('Arti lambang pada kolom Aksi')
            // Lambang pada tabel memakai warna yang sama dengan halaman draf bulanan.
            ->assertSee('text-danger', false)
            ->assertSee('text-locked', false);
    }

    public function test_panduan_membaca_ambang_dari_konfigurasi_pajak(): void
    {
        $pajak = MockData::konfigurasiPajak();

        // Angka pada Pertanyaan Umum tidak ditulis ulang, melainkan dibaca
        // dari konfigurasi yang sama dengan perhitungan draf.
        $this->get('/panduan/pertanyaan-umum')
            ->assertOk()
            ->assertSee('Rp'.angka($pajak['ambang_maksimal']))
            ->assertSee('Rp'.angka($pajak['ambang_bebas']))
            ->assertSee('tarif '.persen($pajak['tarif_final']))
            ->assertSee($pajak['dasar_hukum']);
    }

    public function test_panduan_memakai_lencana_yang_sama_dengan_draf_tahunan(): void
    {
        $this->get('/panduan/draf-tahunan')
            ->assertOk()
            ->assertSee('Dua analisis keuangan')
            // Teks lencana datang dari komponen, bukan ditulis ulang di panduan.
            ->assertSee('Normal')
            ->assertSee('Perlu Ditinjau')
            ->assertSee('Perlu Diperiksa');
    }

    public function test_data_terkunci_tidak_bisa_diubah_lewat_url(): void
    {
        $this->get('/penghasilan/1/edit')->assertForbidden();
        $this->get('/harta/kas/2/ubah')->assertForbidden();
        $this->get('/utang/1/edit')->assertForbidden();
    }

    public function test_tanggal_tidak_boleh_dipindah_ke_bulan_yang_drafnya_sudah_disusun(): void
    {
        $terkunci = app(KunciPencatatan::class)->awalTerbuka()->subDay()->toDateString();
        $terbuka = app(KunciPencatatan::class)->awalTerbuka()->toDateString();

        // Transaksi aktif dipindahkan mundur ke bulan yang drafnya sudah disusun.
        $this->from('/penghasilan/25/edit')
            ->put('/penghasilan/25', ['tanggal' => $terkunci, 'nominal' => '1.000.000'])
            ->assertRedirect('/penghasilan/25/edit')
            ->assertSessionHasErrors('tanggal');

        // Transaksi baru pada bulan yang sama juga ditolak.
        $this->from('/penghasilan/create')
            ->post('/penghasilan', ['tanggal' => $terkunci, 'nominal' => '1.000.000'])
            ->assertSessionHasErrors('tanggal');

        // Bulan yang drafnya belum disusun tetap boleh.
        $this->put('/penghasilan/25', ['tanggal' => $terbuka, 'nominal' => '1.000.000'])
            ->assertRedirect('/penghasilan')
            ->assertSessionHasNoErrors();

        // Baris yang sudah terkunci tidak bisa diubah lewat request langsung.
        $this->put('/penghasilan/1', ['tanggal' => $terbuka, 'nominal' => '1.000.000'])->assertForbidden();
    }

    public function test_simpan_perubahan_kembali_ke_daftar_bukan_ke_detail(): void
    {
        // Halaman detail sudah tidak punya tombol Ubah, jadi daftar adalah tempat asal pengguna.
        $this->put('/harta/kas/1', ['nama' => 'Deposito'])->assertRedirect('/harta/kas');
        $this->put('/utang/2', ['deskripsi' => 'Kredit kendaraan bermotor'])->assertRedirect('/utang');
        $this->put('/penghasilan/25', ['tanggal' => '2026-07-05'])->assertRedirect('/penghasilan');
    }

    public function test_filter_tahun_penghasilan_mulai_dari_catatan_pertama(): void
    {
        $filter = app(FilterTahun::class);
        $terlama = min($filter->daftarPenghasilan());

        $this->assertSame($filter->tahunBerjalan(), max($filter->daftarPenghasilan()));

        // Tahun sebelum pencatatan dimulai tidak boleh dipakai, termasuk lewat URL.
        $this->assertSame($filter->tahunBerjalan(), $filter->pilih($terlama - 1, $filter->daftarPenghasilan()));
        $this->get('/penghasilan?tahun='.($terlama - 1))
            ->assertOk()
            ->assertDontSee('<option value="'.($terlama - 1).'"', false);
    }

    public function test_ganti_tahun_mengubah_isi_draf_bulanan(): void
    {
        // Tahun berjalan baru sebagian tersusun; tahun lalu sudah lengkap dua belas bulan.
        $this->get('/draf-bulanan?tahun=2026')
            ->assertSee('6 dari 12')
            ->assertSee('Rp 110.000.000');

        $this->get('/draf-bulanan?tahun=2025')
            ->assertSee('12 dari 12')
            ->assertSee('Rp 55.000.000')
            ->assertDontSee('Rp 110.000.000');

        // Desember 2026 belum disusun, Desember 2025 sudah.
        $this->get('/draf-bulanan/12?tahun=2026')->assertNotFound();
        $this->get('/draf-bulanan/12?tahun=2025')->assertOk()->assertSee('Desember 2025');
    }

    public function test_hanya_bulan_berikutnya_yang_bisa_disusun(): void
    {
        $this->get('/draf-bulanan')
            ->assertSee(route('draf-bulanan.create', ['bulan' => 7]), false)
            ->assertDontSee(route('draf-bulanan.create', ['bulan' => 8]), false);
    }

    public function test_judul_halaman_dan_sapaan_dashboard_tampil(): void
    {
        $this->get('/profil')->assertSee('<h1', false)->assertSee('Profil')->assertDontSee('@yield', false);
        $this->get('/dashboard')->assertSee('Hi, Budi Santoso')->assertDontSee('@yield', false);
    }

    public function test_alur_registrasi_sampai_dashboard(): void
    {
        $this->post('/register')->assertRedirect('/login');
        $this->get('/login')->assertSee('Akun berhasil dibuat');

        // Profil belum lengkap: login dan URL langsung sama-sama diarahkan ke Lengkapi Profil.
        $this->post('/login')->assertRedirect('/profil/lengkapi');
        $this->get('/dashboard')->assertRedirect('/profil/lengkapi');
        $this->get('/harta/kas')->assertRedirect('/profil/lengkapi');
        $this->get('/profil/lengkapi')->assertOk();

        // Setelah profil tersimpan, seluruh halaman terbuka.
        $this->post('/profil/lengkapi', $this->profilSah())->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();

        // Keluar mengembalikan keadaan awal.
        $this->post('/logout')->assertRedirect('/login');
    }

    /**
     * NIK yang tanggal lahirnya cocok dengan kolom di bawahnya.
     *
     * @param  array<string, string>  $ubah
     * @return array<string, string>
     */
    private function profilSah(array $ubah = []): array
    {
        return array_merge([
            'nik' => '3578012509800001',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
        ], $ubah);
    }

    public function test_nik_diperiksa_terhadap_tanggal_lahir(): void
    {
        // Tanggal pada NIK berbeda satu hari dari kolom Tanggal Lahir.
        $this->from('/profil/ubah')
            ->put('/profil', $this->profilSah(['nik' => '3578012609800001']))
            ->assertRedirect('/profil/ubah')
            ->assertSessionHasErrors('nik');

        // NIK menunjukkan wanita (tanggal 25 + 40 = 65), pilihannya Pria.
        $this->from('/profil/ubah')
            ->put('/profil', $this->profilSah(['nik' => '3578016509800001']))
            ->assertRedirect('/profil/ubah')
            ->assertSessionHasErrors('nik');

        // Pasangan yang cocok diterima, dan NIK memang masih dapat diubah.
        $this->put('/profil', $this->profilSah(['nik' => '3578016509800001', 'jenis_kelamin' => 'Wanita']))
            ->assertRedirect('/profil')
            ->assertSessionHasNoErrors();
    }

    public function test_hanya_draf_tahunan_terbaru_yang_dapat_dibatalkan(): void
    {
        $tersusun = collect(MockData::drafTahunan())->where('status', 'tersusun');

        // Penanda diturunkan dari data, bukan dari urutan baris pada tabel.
        $this->assertSame($tersusun->max('tahun'), MockData::tahunBolehDibatalkan());

        $halaman = $this->get('/draf-tahunan')->assertOk()->getContent();

        // Hanya tahun terbaru yang punya dialog pembatalan; tahun lama ditandai tidak dapat dibatalkan.
        $this->assertStringContainsString('batal-tahunan-'.$tersusun->max('tahun'), $halaman);
        $this->assertStringNotContainsString('batal-tahunan-'.$tersusun->min('tahun'), $halaman);
        $this->assertStringContainsString('Batalkan draf tahun setelahnya terlebih dahulu', $halaman);
    }

    public function test_kewarganegaraan_tampil_panjang_tetapi_tersimpan_wni(): void
    {
        $this->assertSame('WNI', MockData::KEWARGANEGARAAN);
        $this->assertSame('WNI — Warga Negara Indonesia', MockData::kewarganegaraanTampil());

        foreach (['/profil', '/profil/ubah', '/profil/lengkapi'] as $uri) {
            $this->get($uri)->assertOk()->assertSee(MockData::kewarganegaraanTampil(), false);
        }

        // Pada formulir, yang terkirim tetap nilai pendeknya lewat kolom tersembunyi.
        $this->get('/profil/ubah')
            ->assertOk()
            ->assertSee('<input type="hidden" name="kewarganegaraan" value="WNI">', false);
    }

    public function test_ubah_kata_sandi_menegakkan_aturannya(): void
    {
        $this->get('/profil/kata-sandi')->assertOk()->assertSee('Kata Sandi Saat Ini');

        $sah = ['kata_sandi_lama' => 'rahasia-lama', 'kata_sandi' => 'rahasia-baru-8', 'kata_sandi_confirmation' => 'rahasia-baru-8'];

        $this->from('/profil/kata-sandi')->put('/profil/kata-sandi', $sah)
            ->assertRedirect('/profil')
            ->assertSessionHasNoErrors();

        // Terlalu pendek, ulangan tidak sama, dan sama dengan kata sandi lama.
        foreach ([
            ['kata_sandi' => 'pendek', 'kata_sandi_confirmation' => 'pendek'],
            ['kata_sandi_confirmation' => 'beda-sendiri'],
            ['kata_sandi' => 'rahasia-lama', 'kata_sandi_confirmation' => 'rahasia-lama'],
        ] as $salah) {
            $this->from('/profil/kata-sandi')
                ->put('/profil/kata-sandi', array_merge($sah, $salah))
                ->assertRedirect('/profil/kata-sandi')
                ->assertSessionHasErrors('kata_sandi');
        }
    }

    public function test_nik_tidak_lagi_terkunci_pada_ubah_profil(): void
    {
        $this->get('/profil/ubah')
            ->assertOk()
            ->assertSee('Ya, Ubah NIK')
            // Hanya email yang tersisa terkunci pada formulir ini.
            ->assertSee('Email tidak dapat diubah karena digunakan untuk masuk ke sistem.', false);
    }

    public function test_filter_tahun_dashboard(): void
    {
        // Tahun tidak valid kembali ke tahun berjalan.
        $this->get('/dashboard?tahun=99')->assertOk()->assertSee('TAHUN PAJAK 2026');
        $this->get('/dashboard?tahun=1899')->assertOk()->assertSee('TAHUN PAJAK 2026');

        // Tahun dalam rentang tetap dipakai, meski datanya kosong.
        $this->get('/dashboard?tahun=2021')
            ->assertOk()
            ->assertSee('TAHUN PAJAK 2021')
            ->assertSee('Belum ada data penghasilan pada tahun ini');
    }

    public function test_pengingat_dihitung_dan_dapat_ditandai_dibaca(): void
    {
        $awal = $this->get('/dashboard')
            ->assertSee('Draf pajak Juli 2026 belum disusun')
            ->assertSee('Ambang bebas pajak telah terlampaui');

        // Jumlahnya ikut tanggal berjalan: tiap bulan lewat menambah satu draf yang belum disusun.
        $this->assertSame(1, preg_match('/(\d+) belum dibaca/', $awal->getContent(), $cocok));
        $belumDibaca = (int) $cocok[1];
        $this->assertGreaterThan(1, $belumDibaca);

        $this->post('/pengingat/draf_bulanan_2026_07/buka')->assertRedirect('/draf-bulanan');
        $this->get('/dashboard')->assertSee(($belumDibaca - 1).' belum dibaca');
    }

    public function test_pdf_laporan_dapat_diunduh(): void
    {
        $this->get('/laporan/utang/unduh')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
