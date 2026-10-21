<?php

namespace Tests\Feature;

use App\Models\DrafBulanan;
use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Models\Utang;
use App\Support\PenggunaSaatIni;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Autentikasi dan pemisahan data antar akun.
 *
 * Seluruh halaman aplikasi membaca pengguna aktif lewat PenggunaSaatIni, yang
 * menyaring setiap kueri dengan id_profil. Pengujian di sini memastikan
 * penyaring itu memang berlaku, bukan hanya ada di kodenya.
 */
class AutentikasiTest extends TestCase
{
    /** Akun kedua dengan profil lengkap tetapi tanpa satu pun data. */
    private function akunLain(): Profil
    {
        return Profil::create([
            'email' => 'siti.aminah@gmail.com',
            'password' => 'rahasia-siti-8',
            'nik' => '3578014503920003',
            'nama' => 'Siti Aminah',
            'tempat_lahir' => 'Sidoarjo',
            'tanggal_lahir' => '1992-03-05',
            'jenis_kelamin' => 'Wanita',
            'nomor_handphone' => '0813 2222 3333',
            'alamat' => 'Jl. Melati 8',
            'rt' => '002',
            'rw' => '003',
            'kelurahan' => 'Sepanjang',
            'kecamatan' => 'Taman',
            'kota' => 'Sidoarjo',
            'provinsi' => 'Jawa Timur',
            'profil_lengkap' => true,
            'status' => true,
        ]);
    }

    public function test_akun_lain_tidak_melihat_data_wajib_pajak_pertama(): void
    {
        $this->actingAs($this->akunLain());

        // Seluruh halaman tetap terbuka, hanya isinya kosong.
        foreach (['/dashboard', '/penghasilan', '/harta/kas', '/utang', '/draf-bulanan', '/draf-tahunan', '/laporan/harta'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->get('/penghasilan')->assertDontSee('Penjualan katalog');
        $this->get('/harta/kas')->assertDontSee('Bank Mandiri');
        $this->get('/utang')->assertDontSee('KPR rumah tinggal');
        $this->get('/dashboard')->assertSee('Hi, Siti Aminah');
    }

    public function test_akun_lain_tidak_dapat_membuka_catatan_milik_orang_lain(): void
    {
        $penghasilan = Penghasilan::query()->value('id_penghasilan');
        $harta = Harta::with('kodeHarta')->firstOrFail();
        $utang = Utang::query()->value('id_utang');

        $this->actingAs($this->akunLain());

        $this->get("/penghasilan/{$penghasilan}")->assertNotFound();
        $this->get("/harta/{$harta->kodeHarta->kategori}/{$harta->id}")->assertNotFound();
        $this->get("/utang/{$utang}")->assertNotFound();
        $this->get('/draf-bulanan/6')->assertNotFound();
        $this->get('/draf-tahunan/2025')->assertNotFound();
    }

    public function test_akun_lain_tidak_dapat_mengubah_catatan_milik_orang_lain(): void
    {
        $penghasilan = Penghasilan::whereNull('id_draf_bulanan')->value('id_penghasilan');
        $nominal = Penghasilan::whereKey($penghasilan)->value('nominal');

        $this->actingAs($this->akunLain());

        $this->put("/penghasilan/{$penghasilan}", ['tanggal' => now()->toDateString(), 'nominal' => '1'])
            ->assertNotFound();
        $this->delete("/penghasilan/{$penghasilan}")->assertNotFound();

        $this->assertSame($nominal, Penghasilan::whereKey($penghasilan)->value('nominal'));
    }

    public function test_akun_yang_dinonaktifkan_tidak_dapat_masuk(): void
    {
        $profil = Profil::query()->firstOrFail();
        $profil->hapus();

        Auth::logout();

        $this->from('/login')
            ->post('/login', ['email' => $profil->email, 'password' => 'rahasia123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_yang_sudah_terdaftar_ditolak(): void
    {
        $email = Profil::query()->value('email');

        Auth::logout();

        $this->from('/register')
            ->post('/register', [
                'email' => $email,
                'password' => 'rahasia-lain-8',
                'password_confirmation' => 'rahasia-lain-8',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Profil::where('email', $email)->count());
    }

    public function test_ubah_profil_benar_benar_tersimpan(): void
    {
        $profil = Profil::query()->firstOrFail();

        $this->put('/profil', [
            'nik' => $profil->nik,
            'nama' => 'Budi Santoso Wijaya',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
            'nomor_handphone' => '0811 9999 8888',
            'alamat' => 'Jl. Kenjeran 44',
            'rt' => '005',
            'rw' => '006',
            'kelurahan' => 'Kalijudan',
            'kecamatan' => 'Mulyorejo',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
        ])->assertRedirect('/profil')->assertSessionHasNoErrors();

        $profil->refresh();
        $this->assertSame('Budi Santoso Wijaya', $profil->nama);
        $this->assertSame('Malang', $profil->tempat_lahir);
        $this->assertSame('Jl. Kenjeran 44', $profil->alamat);
        $this->assertSame('Kalijudan', $profil->kelurahan);
    }

    public function test_nik_tidak_dapat_memakai_nik_akun_lain(): void
    {
        $lain = $this->akunLain();

        $this->from('/profil/ubah')
            ->put('/profil', [
                'nik' => $lain->nik,
                'nama' => 'Budi Santoso',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1992-03-05',
                'jenis_kelamin' => 'Wanita',
                'nomor_handphone' => '0812 3456 7890',
                'alamat' => 'Jl. Rungkut Asri Timur 12',
                'rt' => '004',
                'rw' => '007',
                'kelurahan' => 'Kalirungkut',
                'kecamatan' => 'Rungkut',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
            ])
            ->assertRedirect('/profil/ubah')
            ->assertSessionHasErrors('nik');
    }

    public function test_nomor_handphone_diseragamkan_dan_diperiksa(): void
    {
        $profil = Profil::query()->firstOrFail();

        $isian = fn (string $nomor): array => [
            'nik' => $profil->nik,
            'nama' => 'Budi Santoso',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
            'nomor_handphone' => $nomor,
            'alamat' => 'Jl. Rungkut Asri Timur 12',
            'rt' => '004',
            'rw' => '007',
            'kelurahan' => 'Kalirungkut',
            'kecamatan' => 'Rungkut',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
        ];

        // Ketiga bentuk ini tersimpan sebagai angka yang sama persis.
        foreach (['0812 3456 7890', '081234567890', '+62 812-3456-7890'] as $nomor) {
            $this->put('/profil', $isian($nomor))->assertRedirect('/profil')->assertSessionHasNoErrors();
            $this->assertSame('081234567890', $profil->fresh()->nomor_handphone, "Bentuk \"{$nomor}\" tidak diseragamkan.");
        }

        // Yang bukan nomor handphone Indonesia ditolak.
        foreach (['halo apa kabar', '123', '08', '08123456789012', '6281234567890123'] as $nomor) {
            $this->from('/profil/ubah')->put('/profil', $isian($nomor))
                ->assertRedirect('/profil/ubah')
                ->assertSessionHasErrors('nomor_handphone');
        }

        // Spasinya dibubuhkan saat ditampilkan, bukan saat disimpan.
        $this->assertSame('0812 3456 7890', telepon('081234567890'));
        $this->assertSame('0813 2222 333', telepon('08132222333'));
        $this->get('/profil')->assertOk()->assertSee('0812 3456 7890');
    }

    public function test_dua_akun_dapat_menyusun_draf_masa_pajak_yang_sama(): void
    {
        Carbon::setTestNow('2026-10-21');

        // Wajib pajak contoh sudah punya draf Januari sampai Juni 2026.
        $this->assertTrue(DrafBulanan::where('tahun_pajak', 2026)->where('bulan', 6)->exists());

        $lain = $this->akunLain();
        $this->actingAs($lain);
        PenggunaSaatIni::lupakan();

        Penghasilan::create([
            'id_profil' => $lain->id_profil,
            'tanggal' => '2026-06-11',
            'nominal' => 1_500_000,
            'keterangan' => 'Penjualan Juni',
            'status' => true,
        ]);

        // kode_draf dulu unik secara global, sehingga DB-2026-06 milik wajib
        // pajak pertama menabrak milik akun ini dan penyusunannya gagal 500.
        $this->post('/draf-bulanan', ['bulan' => 6, 'tahun' => 2026])
            ->assertRedirect('/draf-bulanan?tahun=2026');

        $this->assertSame(1, DrafBulanan::where('id_profil', $lain->id_profil)->count());
        $this->assertSame(2, DrafBulanan::where('tahun_pajak', 2026)->where('bulan', 6)->count());

        Carbon::setTestNow();
    }

    public function test_satu_wajib_pajak_tetap_tidak_boleh_punya_dua_draf_pada_masa_yang_sama(): void
    {
        $draf = DrafBulanan::firstOrFail();

        $this->expectException(QueryException::class);

        DrafBulanan::create([
            'kode_draf' => 'DB-GANDA',
            'id_profil' => $draf->id_profil,
            'id_konfigurasi' => $draf->id_konfigurasi,
            'tahun_pajak' => $draf->tahun_pajak,
            'bulan' => $draf->bulan,
            'tanggal_dibuat' => now(),
            'peredaran_bruto' => 0,
            'akumulasi_sebelumnya' => 0,
            'akumulasi_sekarang' => 0,
            'omzet_kena_pajak' => 0,
            'pph_final' => 0,
        ]);
    }
}
