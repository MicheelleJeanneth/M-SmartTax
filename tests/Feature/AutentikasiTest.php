<?php

namespace Tests\Feature;

use App\Models\DrafBulanan;
use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Pengingat;
use App\Models\Profil;
use App\Models\Utang;
use App\Services\PerhitunganPajak;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
            'username' => 'siti.aminah',
            'email' => 'siti.aminah@gmail.com',
            'password' => 'rahasia-siti-8',
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
            ->post('/login', ['username' => $profil->username, 'password' => 'rahasia123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_username_yang_sudah_dipakai_ditolak(): void
    {
        $username = Profil::query()->value('username');

        Auth::logout();

        $this->from('/register')
            ->post('/register', [
                'username' => $username,
                'password' => 'rahasia-lain-8',
                'password_confirmation' => 'rahasia-lain-8',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('username');

        $this->assertSame(1, Profil::where('username', $username)->count());
    }

    public function test_email_yang_sudah_terdaftar_ditolak(): void
    {
        $email = Profil::query()->value('email');

        Auth::logout();

        $this->from('/register')
            ->post('/register', [
                'username' => 'calon.baru',
                'email' => $email,
                'password' => 'rahasia-lain-8',
                'password_confirmation' => 'rahasia-lain-8',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Profil::where('email', $email)->count());
        $this->assertFalse(Profil::where('username', 'calon.baru')->exists());
    }

    /**
     * Pendaftaran tanpa email adalah alasan utama perpindahan dari email ke
     * username: tidak semua pelaku UMKM memilikinya.
     */
    public function test_dapat_mendaftar_tanpa_email(): void
    {
        Auth::logout();

        $this->post('/register', [
            'username' => 'tanpa.email',
            'email' => '',
            'password' => 'rahasia-baru-8',
            'password_confirmation' => 'rahasia-baru-8',
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $baru = Profil::where('username', 'tanpa.email')->firstOrFail();

        // Kolom email unik, jadi yang dikosongkan wajib tersimpan sebagai null:
        // untaian kosong akan menabrak akun berikutnya yang juga tanpa email.
        $this->assertNull($baru->email);

        $this->post('/login', ['username' => 'tanpa.email', 'password' => 'rahasia-baru-8'])
            ->assertRedirect('/profil/lengkapi');
        $this->assertAuthenticatedAs($baru);
    }

    public function test_akun_kedua_tanpa_email_tidak_menabrak_yang_pertama(): void
    {
        Auth::logout();

        foreach (['tanpa.satu', 'tanpa.dua'] as $username) {
            $this->post('/register', [
                'username' => $username,
                'password' => 'rahasia-baru-8',
                'password_confirmation' => 'rahasia-baru-8',
            ])->assertRedirect('/login')->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Profil::whereNull('email')->count());
    }

    /**
     * Username disimpan dalam huruf kecil supaya "Budi" dan "budi" tidak
     * pernah menjadi dua akun yang berbeda.
     */
    public function test_username_dibakukan_menjadi_huruf_kecil(): void
    {
        Auth::logout();

        $this->post('/register', [
            'username' => '  Wajib.Pajak  ',
            'password' => 'rahasia-baru-8',
            'password_confirmation' => 'rahasia-baru-8',
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $this->assertTrue(Profil::where('username', 'wajib.pajak')->exists());

        // Ditulis dengan huruf besar pun tetap masuk ke akun yang sama.
        $this->post('/login', ['username' => 'WAJIB.PAJAK', 'password' => 'rahasia-baru-8'])
            ->assertRedirect('/profil/lengkapi');
        $this->assertAuthenticated();
    }

    public function test_username_dengan_susunan_terlarang_ditolak(): void
    {
        Auth::logout();

        foreach (['ab', '1budi', 'budi santoso', 'budi-santoso', 'budi.', 'budi@mail'] as $username) {
            $this->from('/register')
                ->post('/register', [
                    'username' => $username,
                    'password' => 'rahasia-baru-8',
                    'password_confirmation' => 'rahasia-baru-8',
                ])
                ->assertRedirect('/register')
                ->assertSessionHasErrors('username');

            $this->assertFalse(
                Profil::query()->whereRaw('LOWER(username) = ?', [mb_strtolower(trim($username))])->exists(),
                "Username \"{$username}\" seharusnya ditolak."
            );
        }
    }

    /**
     * Email boleh ditambahkan belakangan lewat Ubah Profil, dan Lengkapi
     * Profil tidak boleh ikut mengosongkannya.
     */
    public function test_email_dapat_diisi_dan_dikosongkan_lewat_ubah_profil(): void
    {
        $profil = Profil::query()->firstOrFail();

        $isian = fn (?string $email): array => [
            'email' => $email,
            'nama' => 'Budi Santoso',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
            'nomor_handphone' => '081234567890',
            'alamat' => 'Jl. Rungkut Asri Timur 12',
            'rt' => '004',
            'rw' => '007',
            'kelurahan' => 'Kalirungkut',
            'kecamatan' => 'Rungkut',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
        ];

        $this->put('/profil', $isian('budi.baru@gmail.com'))
            ->assertRedirect('/profil')->assertSessionHasNoErrors();
        $this->assertSame('budi.baru@gmail.com', $profil->fresh()->email);

        $this->put('/profil', $isian(''))->assertRedirect('/profil')->assertSessionHasNoErrors();
        $this->assertNull($profil->fresh()->email);

        // Email akun lain tetap tidak boleh dipakai.
        $lain = $this->akunLain();
        $this->from('/profil/ubah')->put('/profil', $isian($lain->email))
            ->assertRedirect('/profil/ubah')
            ->assertSessionHasErrors('email');
    }

    /**
     * NIK sengaja tidak lagi dikumpulkan: ia tidak masuk satu pun perhitungan
     * dan sistem simulasi ini tidak berhak menuntut data kependudukan.
     */
    public function test_kolom_nik_sudah_tidak_ada(): void
    {
        $this->assertFalse(Schema::hasColumn('profil', 'nik'));
    }

    public function test_nomor_msmarttax_diberikan_sendiri_saat_akun_dibuat(): void
    {
        Auth::logout();

        $this->post('/register', [
            'username' => 'pendatang.baru',
            'password' => 'rahasia-baru-8',
            'password_confirmation' => 'rahasia-baru-8',
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $nomor = Profil::where('username', 'pendatang.baru')->value('nomor_msmarttax');

        // MS + tahun pendaftaran + empat digit urut, misalnya MS20260002.
        $this->assertMatchesRegularExpression('/^MS\d{8}$/', $nomor);
        $this->assertSame('MS'.now()->year, substr($nomor, 0, 6));
    }

    /**
     * Nomornya berurut dalam satu tahun, dan nomor akun yang dinonaktifkan
     * tidak boleh dipakai ulang oleh akun sesudahnya.
     */
    public function test_nomor_msmarttax_berurut_dan_tidak_pernah_dipakai_ulang(): void
    {
        $pertama = Profil::query()->firstOrFail();
        $this->assertSame('MS'.now()->year.'0001', $pertama->nomor_msmarttax);

        $kedua = $this->akunLain();
        $this->assertSame('MS'.now()->year.'0002', $kedua->nomor_msmarttax);

        $kedua->hapus();

        $ketiga = Profil::create([
            'username' => 'akun.ketiga',
            'password' => 'rahasia-uji-8',
            'nama' => 'Akun Ketiga',
            'profil_lengkap' => false,
            'status' => true,
        ]);

        $this->assertSame('MS'.now()->year.'0003', $ketiga->nomor_msmarttax);
    }

    /**
     * Kop dokumen memakai nomor M-SmartTax, dan tidak boleh lagi menyebut NIK
     * supaya tidak ada yang mengira dokumennya memuat NPWP.
     */
    public function test_dokumen_laporan_memakai_nomor_msmarttax_bukan_nik(): void
    {
        $nomor = Profil::query()->value('nomor_msmarttax');

        $this->get('/laporan/harta/pratinjau')
            ->assertOk()
            ->assertSee('Nomor M-SmartTax')
            ->assertSee($nomor)
            ->assertDontSee('NPWP')
            ->assertDontSee('<td class="l">NIK</td>', false);
    }

    public function test_ubah_profil_benar_benar_tersimpan(): void
    {
        $profil = Profil::query()->firstOrFail();

        $this->put('/profil', [
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

    /**
     * Kolom bertipe date hanya menerima bentuk Y-m-d. Cast 'date' pada model
     * menghasilkan "1980-09-25 00:00:00", yang ditolak peramban sehingga
     * kolomnya tampil kosong dan tombol Simpan tertahan terus-menerus.
     */
    public function test_tanggal_lahir_terisi_pada_formulir_ubah_profil(): void
    {
        $profil = Profil::query()->firstOrFail();

        $this->get('/profil/ubah')
            ->assertOk()
            ->assertSee('name="tanggal_lahir" type="date" value="'.$profil->tanggal_lahir->format('Y-m-d').'"', false);
    }

    public function test_nomor_handphone_diseragamkan_dan_diperiksa(): void
    {
        $profil = Profil::query()->firstOrFail();

        $isian = fn (string $nomor): array => [
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

    public function test_ingatan_service_tidak_terbawa_antar_pengguna(): void
    {
        // Laravel menyimpan instance controller pada objek Route, jadi service
        // beserta ingatannya dapat melayani dua permintaan berturut-turut.
        // Ingatan yang tidak berkunci id profil akan menyajikan jawaban milik
        // pengguna sebelumnya; di sini wajib pajak contoh dibuka lebih dahulu.
        $this->get('/dashboard')->assertOk();
        $this->get('/simulasi')->assertOk();

        $lain = $this->akunLain();
        Penghasilan::create([
            'id_profil' => $lain->id_profil,
            'tanggal' => MockData::TAHUN.'-04-20',
            'nominal' => 50_000_000,
            'keterangan' => 'Penjualan April',
            'status' => true,
        ]);

        $this->actingAs($lain);
        PenggunaSaatIni::lupakan();

        // Wajib pajak contoh mencatat sejak Januari 2024, akun ini sejak April
        // tahun berjalan. Jawaban keduanya tidak boleh tertukar.
        $this->assertSame(4, app(PerhitunganPajak::class)->bulanPertama(MockData::TAHUN));

        // Baris keterangannya muncul di bawah kartu Hasil Simulasi, jadi
        // diperiksa setelah Hitung, bukan pada halaman kosong.
        $this->post('/simulasi', [
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'uang_muka' => '90.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '8,5',
        ])
            ->assertOk()
            ->assertSee('Anda mulai mencatat pada April, sehingga rata-rata ini memperhitungkan bulan sebelum pencatatan dimulai.');

        // Pengingat juga tidak boleh menagih bulan sebelum April.
        $this->get('/dashboard')->assertOk();

        $ditagih = Pengingat::where('id_profil', $lain->id_profil)
            ->where('jenis', 'like', 'draf_bulanan_%')
            ->pluck('jenis')
            ->filter(fn (string $jenis): bool => (int) substr($jenis, -2) < 4)
            ->values()
            ->all();

        $this->assertSame([], $ditagih, 'Bulan sebelum April bukan masa pajak akun ini.');
    }
}
