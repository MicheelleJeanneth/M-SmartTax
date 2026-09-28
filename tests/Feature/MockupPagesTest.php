<?php

namespace Tests\Feature;

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
        $this->get('/dashboard')
            ->assertSee('Draf pajak Juli 2026 belum disusun')
            ->assertSee('Ambang bebas pajak telah terlampaui')
            ->assertSee('3 belum dibaca');

        $this->post('/pengingat/draf_bulanan_2026_07/buka')->assertRedirect('/draf-bulanan');
        $this->get('/dashboard')->assertSee('2 belum dibaca');
    }

    public function test_pdf_laporan_dapat_diunduh(): void
    {
        $this->get('/laporan/utang/unduh')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
