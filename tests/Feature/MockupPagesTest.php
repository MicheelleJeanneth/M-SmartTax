<?php

namespace Tests\Feature;

use App\Services\FilterTahun;
use App\Services\KunciPencatatan;
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
            '/draf-tahunan', '/draf-tahunan/susun?tahun=2025', '/draf-tahunan/2025',
            '/simulasi', '/simulasi?harga_aset=450000000&uang_muka=90000000&jangka_waktu=60&suku_bunga=8.5',
            '/laporan/draf-bulanan', '/laporan/draf-bulanan?bulan=9', '/laporan/draf-tahunan', '/laporan/draf-tahunan?tahun=2026',
            '/laporan/harta', '/laporan/utang',
            '/laporan/draf-bulanan/pratinjau', '/laporan/draf-tahunan/pratinjau?tahun=2025', '/laporan/harta/pratinjau', '/laporan/utang/pratinjau',
            '/profil', '/profil/ubah', '/panduan',
        ];

        return array_combine($uri, array_map(fn (string $u): array => [$u], $uri));
    }

    #[DataProvider('halaman')]
    public function test_halaman_mockup_dapat_dibuka(string $uri): void
    {
        $this->get($uri)->assertOk();
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
        $this->post('/profil/lengkapi')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();

        // Keluar mengembalikan keadaan awal.
        $this->post('/logout')->assertRedirect('/login');
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
