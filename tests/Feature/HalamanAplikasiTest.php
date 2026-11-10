<?php

namespace Tests\Feature;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\DrafTahunanHarta;
use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Models\Utang;
use App\Services\FilterTahun;
use App\Services\KunciPencatatan;
use App\Services\PerhitunganPajak;
use App\Support\MockData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pengujian lintas halaman: setiap halaman dapat dibuka, judul dan sapaannya
 * benar, serta aturan yang tidak terikat satu fitur saja ikut terjaga —
 * autentikasi halaman, kunci periode berdraf, pemeriksaan NIK, dan kolom yang
 * sengaja dibuat terkunci.
 *
 * Aturan yang punya berkas sendiri diuji terpisah: KunciPeriodeBerdrafTest,
 * PembatalanBerantaiTest, dan AutentikasiTest.
 */
class HalamanAplikasiTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function halaman(): array
    {
        $uri = [
            '/profil/lengkapi', '/dashboard',
            '/penghasilan', '/penghasilan/create',
            '/harta', '/harta/tidak-bergerak', '/harta/kas/tambah', '/harta/kas/1',
            '/utang', '/utang/create', '/utang/2',
            '/draf-bulanan', '/draf-bulanan/susun', '/draf-bulanan/susun?bulan=8', '/draf-bulanan/6',
            '/draf-bulanan?tahun=2025', '/draf-bulanan/12?tahun=2025', '/draf-bulanan/susun?bulan=3&tahun=2025',
            '/draf-tahunan', '/draf-tahunan/2025',
            '/simulasi',
            '/laporan/draf-bulanan', '/laporan/draf-bulanan?bulan=9', '/laporan/draf-tahunan', '/laporan/draf-tahunan?tahun=2026',
            '/laporan/harta', '/laporan/utang',
            '/laporan/draf-bulanan/pratinjau', '/laporan/draf-tahunan/pratinjau?tahun=2025', '/laporan/harta/pratinjau', '/laporan/utang/pratinjau',
            '/profil', '/profil/ubah', '/profil/kata-sandi', '/panduan', '/panduan/mencatat-harta', '/panduan/pertanyaan-umum',
        ];

        return array_combine($uri, array_map(fn (string $u): array => [$u], $uri));
    }

    #[DataProvider('halaman')]
    public function test_setiap_halaman_dapat_dibuka(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    /** Masuk dan Daftar justru hanya terbuka untuk tamu. */
    public function test_halaman_tamu_dapat_dibuka(): void
    {
        Auth::logout();

        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    /** Akar situs tidak punya halaman sendiri; pengunjung langsung diarahkan ke Masuk. */
    public function test_akar_situs_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_aplikasi_tertutup_untuk_tamu(): void
    {
        Auth::logout();

        foreach (['/dashboard', '/penghasilan', '/harta/kas', '/utang', '/draf-bulanan', '/profil'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    /** Yang sudah masuk tidak perlu melihat halaman masuk dan daftar lagi. */
    public function test_halaman_tamu_tertutup_setelah_masuk(): void
    {
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
    }

    public function test_kata_sandi_salah_tidak_dapat_masuk(): void
    {
        Auth::logout();
        $username = Profil::query()->value('username');

        $this->from('/login')->post('/login', ['username' => $username, 'password' => 'salah-sekali'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
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
            ->assertSee('tarif PPh final '.persen($pajak['tarif_final']))
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

    /** Transaksi yang drafnya belum disusun, jadi masih boleh diubah. */
    private function penghasilanTerbuka(): int
    {
        return (int) Penghasilan::whereNull('id_draf_bulanan')->value('id_penghasilan');
    }

    /** Transaksi yang sudah masuk draf bulanan. */
    private function penghasilanTerkunci(): int
    {
        return (int) Penghasilan::whereNotNull('id_draf_bulanan')->value('id_penghasilan');
    }

    /** Harta yang belum terekap draf tahunan, jadi masih boleh diubah. */
    private function hartaTerbuka(): Harta
    {
        return Harta::whereNull('id_draf_tahunan')->with('kodeHarta')->firstOrFail();
    }

    public function test_halaman_ubah_penghasilan_terbuka(): void
    {
        $this->get('/penghasilan/'.$this->penghasilanTerbuka().'/edit')->assertOk();
    }

    /** Utang yang belum terekap draf tahunan. */
    private function utangTerbuka(): Utang
    {
        return Utang::whereNull('id_draf_tahunan')->with('kodeUtang')->firstOrFail();
    }

    public function test_halaman_ubah_utang_terbuka(): void
    {
        $this->get('/utang/'.$this->utangTerbuka()->id.'/edit')->assertOk();
    }

    public function test_halaman_ubah_harta_terbuka(): void
    {
        $harta = $this->hartaTerbuka();

        $this->get("/harta/{$harta->kodeHarta->kategori}/{$harta->id}/ubah")->assertOk();
    }

    public function test_data_terkunci_tidak_bisa_diubah_lewat_url(): void
    {
        // Penghasilan terkunci tertutup sepenuhnya: tidak ada nilai berjalan
        // yang masih perlu diperbarui pada satu transaksi.
        $this->get('/penghasilan/'.$this->penghasilanTerkunci().'/edit')->assertForbidden();

        // Harta terkunci membuka formulir khusus nilai, bukan formulir penuh.
        // Utang tidak punya dua formulir: yang terbuka hanya saldo dan tahun
        // pelunasannya, terkunci maupun tidak. Aturan rincinya diuji di
        // KunciPeriodeBerdrafTest.
        $this->get('/harta/kas/2/ubah')->assertOk()->assertSee('sudah masuk draf pajak tahunan');
        $this->get('/utang/1/edit')->assertOk()->assertSee('Yang dapat diubah hanya saldo dan tahun pelunasan');
    }

    public function test_keterangan_penghasilan_wajib_diisi(): void
    {
        $terbuka = app(KunciPencatatan::class)->awalTerbuka()->toDateString();
        $jumlah = Penghasilan::count();

        $this->from('/penghasilan/create')
            ->post('/penghasilan', ['tanggal' => $terbuka, 'nominal' => '1.000.000'])
            ->assertRedirect('/penghasilan/create')
            ->assertSessionHasErrors('keterangan');

        $this->assertSame($jumlah, Penghasilan::count());

        // Diisi: tersimpan seperti biasa.
        $this->post('/penghasilan', [
            'tanggal' => $terbuka, 'nominal' => '1.000.000', 'keterangan' => 'Penjualan katalog',
        ])->assertRedirect('/penghasilan')->assertSessionHasNoErrors();

        // Kolomnya ikut ditandai wajib pada formulir, bukan hanya di server.
        $this->get('/penghasilan/create')->assertOk()->assertSee('name="keterangan" ', false);
        $this->assertStringContainsString(
            'required',
            substr($this->get('/penghasilan/create')->getContent(),
                strpos($this->get('/penghasilan/create')->getContent(), 'name="keterangan"'), 200),
        );
    }

    public function test_simpan_penghasilan_melewati_dialog_penegasan(): void
    {
        $judul = [
            '/penghasilan/create' => 'Simpan data penghasilan?',
            '/penghasilan/'.$this->penghasilanTerbuka().'/edit' => 'Simpan perubahan data penghasilan?',
        ];

        foreach ($judul as $uri => $pertanyaan) {
            $halaman = $this->get($uri)->assertOk()->getContent();

            // Formulirnya menunjuk dialog, dan dialog itu benar-benar ada di halaman.
            $this->assertStringContainsString('data-konfirmasi="simpan-penghasilan"', $halaman, $uri);
            $this->assertStringContainsString('id="simpan-penghasilan"', $halaman, $uri);
            $this->assertStringContainsString($pertanyaan, $halaman, $uri);

            // Tombol penegasannya mengirim formulir yang sama, bukan formulir lain.
            $this->assertStringContainsString('id="formulir-penghasilan"', $halaman, $uri);
            $this->assertStringContainsString('data-kirim="formulir-penghasilan"', $halaman, $uri);

            // Ketiga kolomnya diringkas di dalam dialog sebelum pengguna menegaskan.
            foreach (['tanggal', 'nominal', 'keterangan'] as $kolom) {
                $this->assertStringContainsString('data-ringkas="'.$kolom.'"', $halaman, $uri);
            }

            // Tombol simpan tetap type=submit, supaya pemeriksaan kolom wajib bawaan
            // browser berjalan lebih dulu dan dialognya tidak membuka formulir setengah isi.
            $this->assertStringContainsString('type="submit"', $halaman, $uri);
        }
    }

    public function test_batal_penghasilan_menanyakan_dulu(): void
    {
        foreach (['/penghasilan/create', '/penghasilan/'.$this->penghasilanTerbuka().'/edit'] as $uri) {
            $halaman = $this->get($uri)->assertOk()->getContent();

            $this->assertStringContainsString("buka-dialog', 'batal-penghasilan'", $halaman, $uri);
            $this->assertStringContainsString('id="batal-penghasilan"', $halaman, $uri);
            $this->assertStringContainsString('Batalkan pengisian?', $halaman, $uri);

            // Tombol kiri dialog tidak boleh berbunyi "Batal": pada dialog ini
            // artinya justru tetap di formulir, berlawanan dengan tombol kanannya.
            $this->assertStringContainsString('Lanjut Mengisi', $halaman, $uri);

            // Dulu tombol penegasannya ikut terbaca "Simpan", karena @include
            // di dalam _form mewarisi $tombol milik formulir.
            $this->assertStringContainsString('Ya, Batalkan', $halaman, $uri);
        }
    }

    public function test_simpan_dan_batal_profil_menanyakan_dulu(): void
    {
        $halaman = $this->get('/profil/ubah')->assertOk()->getContent();

        $this->assertStringContainsString('data-konfirmasi="simpan-profil"', $halaman);
        $this->assertStringContainsString('Simpan perubahan profil?', $halaman);
        $this->assertStringContainsString('data-kirim="form-profil"', $halaman);
        $this->assertStringContainsString("buka-dialog', 'batal-profil'", $halaman);

        foreach (['nama', 'nomor_handphone', 'email', 'alamat', 'kota'] as $kolom) {
            $this->assertStringContainsString('data-ringkas="'.$kolom.'"', $halaman);
        }
    }

    public function test_ubah_kata_sandi_menanyakan_dulu_tanpa_menampilkan_sandinya(): void
    {
        $halaman = $this->get('/profil/kata-sandi')->assertOk()->getContent();

        $this->assertStringContainsString('data-konfirmasi="simpan-kata-sandi"', $halaman);
        $this->assertStringContainsString('Ubah kata sandi?', $halaman);
        $this->assertStringContainsString('data-kirim="form-kata-sandi"', $halaman);
        $this->assertStringContainsString("buka-dialog', 'batal-profil'", $halaman);

        // Kata sandi tidak boleh ikut diringkas, sekalipun hanya di dalam
        // dialog penegasan yang terlihat sekejap.
        foreach (['kata_sandi', 'kata_sandi_lama', 'kata_sandi_confirmation'] as $kolom) {
            $this->assertStringNotContainsString('data-ringkas="'.$kolom.'"', $halaman);
        }
    }

    public function test_susun_draf_bulanan_menanyakan_dulu(): void
    {
        $tahun = MockData::TAHUN;
        $bulan = app(PerhitunganPajak::class)->bulanBerikutnya($tahun);
        $this->assertNotNull($bulan, 'Pengujian ini butuh satu bulan yang drafnya belum tersusun.');

        $this->get("/draf-bulanan/susun?bulan={$bulan}&tahun={$tahun}")
            ->assertOk()
            ->assertSee('data-konfirmasi="simpan-draf-bulanan"', false)
            ->assertSee('id="simpan-draf-bulanan"', false)
            ->assertSee('data-kirim="formulir-draf-bulanan"', false)
            ->assertSee('Simpan draf '.MockData::bulan()[$bulan].' '.$tahun.'?')
            // Angkanya dihitung server, jadi tercetak langsung di dalam dialog.
            ->assertSee('Peredaran Bruto')
            ->assertSee('PPh Final Terutang')
            ->assertSee('Batas Setor')
            // Batal juga menanyakan dulu. Di sini tidak ada isian yang hilang,
            // jadi kalimatnya menyebut penyusunannya, bukan pengisiannya.
            ->assertSee("buka-dialog', 'batal-susun'", false)
            ->assertSee('id="batal-susun"', false)
            ->assertSee('Batalkan penyusunan draf?')
            ->assertSee('Lanjut Memeriksa');
    }

    public function test_simpan_dan_batal_utang_menanyakan_dulu(): void
    {
        $utang = Utang::query()->value('id_utang');

        $judul = [
            '/utang/create' => 'Simpan data utang?',
            "/utang/{$utang}/edit" => 'Perbarui saldo utang?',
        ];

        foreach ($judul as $uri => $pertanyaan) {
            $halaman = $this->get($uri)->assertOk()->getContent();

            $this->assertStringContainsString('data-konfirmasi="simpan-utang"', $halaman, $uri);
            $this->assertStringContainsString('id="simpan-utang"', $halaman, $uri);
            $this->assertStringContainsString('data-kirim="formulir-utang"', $halaman, $uri);
            $this->assertStringContainsString($pertanyaan, $halaman, $uri);

            // Saldo selalu diringkas; di halaman tambah ikut kolom lain yang
            // menentukan isi lampiran draf tahunan.
            $this->assertStringContainsString('data-ringkas="saldo"', $halaman, $uri);

            $this->assertStringContainsString("buka-dialog', 'batal-utang'", $halaman, $uri);
            $this->assertStringContainsString('id="batal-utang"', $halaman, $uri);
            $this->assertStringContainsString('Ya, Batalkan', $halaman, $uri);
        }

        // Halaman tambah meringkas kreditur dan tahun peminjamannya juga;
        // halaman perbarui tidak, karena kolom itu tidak dapat lagi berganti.
        $tambah = $this->get('/utang/create')->getContent();
        $perbarui = $this->get("/utang/{$utang}/edit")->getContent();

        foreach (['kode', 'nama_kreditur', 'tahun_peminjaman'] as $kolom) {
            $this->assertStringContainsString('data-ringkas="'.$kolom.'"', $tambah);
            $this->assertStringNotContainsString('data-ringkas="'.$kolom.'"', $perbarui);
        }

        // Tahun pelunasan diringkas di kedua halaman: di sini pun ia berganti.
        $this->assertStringContainsString('data-ringkas="tahun_pelunasan"', $tambah);
        $this->assertStringContainsString('data-ringkas="tahun_pelunasan"', $perbarui);
    }

    public function test_batal_harta_menanyakan_dulu(): void
    {
        $uri = array_map(
            fn (string $kategori): string => "/harta/{$kategori}/tambah",
            array_keys(MockData::kategoriHarta()),
        );

        $harta = $this->hartaTerbuka();
        $uri[] = "/harta/{$harta->kodeHarta->kategori}/{$harta->id}/ubah";

        // Baris terkunci memakai _form-nilai, yang punya tombol Batal sendiri.
        $uri[] = '/harta/kas/2/ubah';

        foreach ($uri as $halaman) {
            $isi = $this->get($halaman)->assertOk()->getContent();

            $this->assertStringContainsString("buka-dialog', 'batal-harta'", $isi, $halaman);
            $this->assertStringContainsString('id="batal-harta"', $isi, $halaman);
            $this->assertStringContainsString('Batalkan pengisian?', $isi, $halaman);
            $this->assertStringContainsString('Lanjut Mengisi', $isi, $halaman);
            $this->assertStringContainsString('Ya, Batalkan', $isi, $halaman);
        }
    }

    public function test_pesan_validasi_berbahasa_indonesia(): void
    {
        $harta = $this->hartaTerbuka();
        $kategori = $harta->kodeHarta->kategori;

        // Tahun melewati tahun berjalan: pesannya disusun pengendalinya sendiri
        // supaya menyebut "Tahun perolehan", bukan "Tahun" saja.
        $this->from("/harta/{$kategori}/tambah")
            ->post("/harta/{$kategori}", [
                'kode' => $harta->kodeHarta->kode,
                'nilai' => '5.000.000',
                'tahun' => (string) (now()->year + 1),
            ])
            ->assertSessionHasErrors(['tahun' => 'Tahun perolehan tidak boleh melebihi tahun berjalan.']);

        // Kolom tanpa pesan khusus jatuh ke lang/id/validation.php, bukan ke
        // kalimat bawaan Laravel yang berbahasa Inggris.
        $this->from("/harta/{$kategori}/tambah")
            ->post("/harta/{$kategori}", [
                'kode' => $harta->kodeHarta->kode,
                'nilai' => '5.000.000',
                'tahun' => '2026',
                'keterangan' => str_repeat('a', 300),
            ])
            ->assertSessionHasErrors(['keterangan' => 'Keterangan tidak boleh lebih dari 255 karakter.']);

        $this->assertSame('id', app()->getLocale());
    }

    public function test_simpan_harta_melewati_dialog_penegasan(): void
    {
        // Seluruh kategori diperiksa, karena susunan kolom nilainya berbeda-beda
        // dan ringkasan dialog disusun dari label kategorinya masing-masing.
        foreach (array_keys(MockData::kategoriHarta()) as $kategori) {
            $halaman = $this->get("/harta/{$kategori}/tambah")->assertOk()->getContent();

            $this->assertStringContainsString('data-konfirmasi="simpan-harta"', $halaman, $kategori);
            $this->assertStringContainsString('id="simpan-harta"', $halaman, $kategori);
            $this->assertStringContainsString('data-kirim="formulir-harta"', $halaman, $kategori);
            $this->assertStringContainsString('Simpan data harta?', $halaman, $kategori);

            foreach (['kode', 'nilai', 'tahun'] as $kolom) {
                $this->assertStringContainsString('data-ringkas="'.$kolom.'"', $halaman, $kategori);
            }
        }

        $harta = $this->hartaTerbuka();
        $this->get("/harta/{$harta->kodeHarta->kategori}/{$harta->id}/ubah")
            ->assertOk()
            ->assertSee('Simpan perubahan data harta?')
            ->assertSee('data-konfirmasi="simpan-harta"', false);
    }

    public function test_formulir_harta_dibuka_tanpa_isian_bawaan(): void
    {
        // Kolom khas tiap kategori menggambarkan keadaan harta milik pengguna.
        // Nilai yang sudah terisi sejak formulir dibuka dapat ikut tersimpan
        // tanpa pernah dibaca, misalnya kepemilikan kendaraan yang sebenarnya
        // atas nama orang lain. Contoh pengisian cukup lewat placeholder.
        foreach (array_keys(MockData::kategoriHarta()) as $kategori) {
            $halaman = $this->get("/harta/{$kategori}/tambah")->assertOk()->getContent();

            preg_match_all('/<input[^>]*name="khas_\d+"[^>]*>/', $halaman, $cocok);
            $this->assertNotEmpty($cocok[0], "Kolom khas {$kategori} tidak ditemukan.");

            foreach ($cocok[0] as $kolom) {
                preg_match('/name="(khas_\d+)"/', $kolom, $nama);
                preg_match('/value="([^"]*)"/', $kolom, $isi);
                $this->assertSame('', $isi[1] ?? '', "Kolom {$nama[1]} pada {$kategori} masih punya isian bawaan.");
            }
        }
    }

    public function test_perbarui_nilai_harta_terkunci_hanya_meringkas_nilainya(): void
    {
        $halaman = $this->get('/harta/kas/2/ubah')->assertOk()->getContent();

        $this->assertStringContainsString('Perbarui nilai harta?', $halaman);
        $this->assertStringContainsString('data-ringkas="nilai"', $halaman);

        // Kode dan tahun tidak dapat diubah pada baris terkunci, jadi tidak
        // ikut diringkas — ringkasan hanya memuat yang benar-benar akan berganti.
        $this->assertStringNotContainsString('data-ringkas="kode"', $halaman);
        $this->assertStringNotContainsString('data-ringkas="tahun"', $halaman);
    }

    public function test_peringatan_dini_memakai_kalimat_dari_server(): void
    {
        $kunci = app(KunciPencatatan::class);

        foreach (['/penghasilan/create', '/penghasilan/'.$this->penghasilanTerbuka().'/edit'] as $uri) {
            $this->get($uri)
                ->assertOk()
                // Browser hanya tahu tanggal batasnya. Dulu ia menyusun sendiri
                // kalimat "Draf pajak <bulan pilihan> sudah disusun", yang keliru
                // untuk bulan yang sebenarnya tidak punya draf.
                ->assertSee('data-kunci-pesan="'.e($kunci->pesanBatas()).'"', false)
                ->assertSee('data-kunci-sebelum="'.$kunci->awalTerbuka()->toDateString().'"', false);
        }
    }

    public function test_tanggal_tidak_boleh_dipindah_ke_bulan_yang_drafnya_sudah_disusun(): void
    {
        $terkunci = app(KunciPencatatan::class)->awalTerbuka()->subDay()->toDateString();
        $terbuka = app(KunciPencatatan::class)->awalTerbuka()->toDateString();

        $id = $this->penghasilanTerbuka();

        // Transaksi aktif dipindahkan mundur ke bulan yang drafnya sudah disusun.
        $this->from("/penghasilan/{$id}/edit")
            ->put("/penghasilan/{$id}", ['tanggal' => $terkunci, 'nominal' => '1.000.000', 'keterangan' => 'uji'])
            ->assertRedirect("/penghasilan/{$id}/edit")
            ->assertSessionHasErrors('tanggal');

        // Transaksi baru pada bulan yang sama juga ditolak.
        $this->from('/penghasilan/create')
            ->post('/penghasilan', ['tanggal' => $terkunci, 'nominal' => '1.000.000', 'keterangan' => 'uji'])
            ->assertSessionHasErrors('tanggal');

        // Bulan yang drafnya belum disusun tetap boleh.
        $this->put("/penghasilan/{$id}", ['tanggal' => $terbuka, 'nominal' => '1.000.000', 'keterangan' => 'uji'])
            ->assertRedirect('/penghasilan')
            ->assertSessionHasNoErrors();

        // Baris yang sudah terkunci tidak bisa diubah lewat request langsung.
        $this->put('/penghasilan/'.$this->penghasilanTerkunci(), ['tanggal' => $terbuka, 'nominal' => '1.000.000', 'keterangan' => 'uji'])
            ->assertForbidden();
    }

    public function test_simpan_perubahan_kembali_ke_daftar_bukan_ke_detail(): void
    {
        // Halaman detail sudah tidak punya tombol Ubah, jadi daftar adalah tempat asal pengguna.
        $harta = $this->hartaTerbuka();
        $kategori = $harta->kodeHarta->kategori;
        $this->put("/harta/{$kategori}/{$harta->id}", [
            'kode' => $harta->kodeHarta->kode,
            'nilai' => '91.000.000',
            'tahun' => $harta->tahun,
        ])->assertRedirect("/harta/{$kategori}");
        $utang = $this->utangTerbuka();
        $this->put('/utang/'.$utang->id, [
            'saldo' => '13.000.000',
            'tahun_pelunasan' => $utang->tahun_pelunasan,
        ])->assertRedirect('/utang');
        $this->put('/penghasilan/'.$this->penghasilanTerbuka(), ['tanggal' => '2026-07-05', 'nominal' => '1.000.000', 'keterangan' => 'uji'])
            ->assertRedirect('/penghasilan');
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
        $this->get('/dashboard')->assertSee('Halo, Budi Santoso')->assertDontSee('@yield', false);
    }

    public function test_alur_registrasi_sampai_dashboard(): void
    {
        Auth::logout();

        $akun = ['username' => 'wajibpajak.baru', 'password' => 'rahasia-baru-8'];

        $this->post('/register', [...$akun, 'password_confirmation' => 'rahasia-baru-8'])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();

        // Akunnya benar-benar tersimpan, dengan profil yang belum lengkap.
        $baru = Profil::where('username', $akun['username'])->firstOrFail();
        $this->assertFalse($baru->profil_lengkap);
        $this->assertTrue(Hash::check('rahasia-baru-8', $baru->password));

        $this->get('/login')->assertSee('Akun berhasil dibuat');

        // Profil belum lengkap: login dan URL langsung sama-sama diarahkan ke Lengkapi Profil.
        $this->post('/login', $akun)->assertRedirect('/profil/lengkapi');
        $this->assertAuthenticatedAs($baru);
        $this->get('/dashboard')->assertRedirect('/profil/lengkapi');
        $this->get('/harta/kas')->assertRedirect('/profil/lengkapi');
        $this->get('/profil/lengkapi')->assertOk();

        $this->post('/profil/lengkapi', $this->profilSah(['nama' => 'Siti Aminah']))
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        // Data dirinya memang tersimpan, bukan hanya lolos validasi.
        $baru->refresh();
        $this->assertTrue($baru->profil_lengkap);
        $this->assertSame('Siti Aminah', $baru->nama);
        $this->assertSame('Kalirungkut', $baru->kelurahan);
        // Kedua kolom ini tidak diambil dari kiriman pengguna.
        $this->assertSame('WNI', $baru->kewarganegaraan);
        $this->assertSame('Indonesia', $baru->negara);

        $this->get('/dashboard')->assertOk();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Isian data diri dan alamat yang lolos seluruh pemeriksaan.
     *
     * @param  array<string, string>  $ubah
     * @return array<string, string>
     */
    private function profilSah(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1980-09-25',
            'jenis_kelamin' => 'Pria',
            'nomor_handphone' => '0812 3456 7890',
            'alamat' => 'Jl. Rungkut Asri Timur 12',
            'rt' => '004',
            'rw' => '007',
            'kelurahan' => 'Kalirungkut',
            'kecamatan' => 'Rungkut',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
        ], $ubah);
    }

    public function test_laporan_bulanan_tersedia_untuk_tahun_lalu_yang_sudah_tersusun(): void
    {
        $lalu = MockData::TAHUN - 1;
        $this->assertSame(12, DrafBulanan::where('tahun_pajak', $lalu)->count());

        // Dua belas draf 2025 tersusun, jadi laporannya harus dapat dicetak.
        $this->get("/laporan/draf-bulanan?tahun={$lalu}&bulan=12")
            ->assertOk()
            ->assertDontSee('belum tersusun');
    }

    public function test_laporan_hanya_menawarkan_tahun_pajak(): void
    {
        $pertama = min(app(FilterTahun::class)->daftarPenghasilan());

        // Harta dan utang Budi berawal jauh sebelum penghasilan pertamanya,
        // dan tahun-tahun itu dulu ikut masuk daftar. Laporan pajak untuk
        // tahun yang belum jadi kewajiban pajak tidak punya arti apa pun.
        foreach (['draf-bulanan', 'draf-tahunan', 'harta', 'utang'] as $jenis) {
            $halaman = $this->get("/laporan/{$jenis}")->assertOk()->getContent();

            $this->assertStringNotContainsString('<option value="'.($pertama - 1).'"', $halaman, $jenis);
            $this->assertStringContainsString('<option value="'.$pertama.'"', $halaman, $jenis);
        }

        // Tahun di luar rentang itu jatuh kembali ke tahun berjalan, bukan
        // menampilkan laporan untuk tahun yang tidak ditawarkan.
        $this->get('/laporan/utang?tahun='.($pertama - 1))
            ->assertOk()
            ->assertSee('value="'.MockData::TAHUN.'" selected', false);
    }

    public function test_bulan_yang_belum_disusun_menawarkan_tombol_menyusun(): void
    {
        $this->get('/laporan/draf-bulanan?tahun='.MockData::TAHUN.'&bulan=12')
            ->assertOk()
            ->assertSee('belum tersusun')
            ->assertSee('Buka Draf Bulanan');
    }

    public function test_alur_susun_dan_batalkan_draf_tahunan(): void
    {
        $tahun = MockData::TAHUN;

        // Draf hanya dapat disusun setelah masa pajaknya berakhir, jadi waktu
        // dimajukan ke awal tahun berikutnya agar seluruh bulan sudah lewat.
        Carbon::setTestNow(($tahun + 1).'-01-05');

        // Tahun yang drafnya sudah tersusun tidak boleh disusun ulang.
        $this->get('/draf-tahunan/susun?tahun='.($tahun - 1))->assertForbidden();

        // Dua belas draf bulanan dilengkapi dulu; bulan tanpa penghasilan jadi draf nihil.
        for ($bulan = 7; $bulan <= 12; $bulan++) {
            $this->post('/draf-bulanan', ['bulan' => $bulan, 'tahun' => $tahun])->assertRedirect();
        }

        $this->get('/draf-tahunan/susun?tahun='.$tahun)->assertOk();

        $this->post('/draf-tahunan', ['tahun' => $tahun])->assertRedirect('/draf-tahunan');

        $draf = DrafTahunan::where('tahun_pajak', $tahun)->firstOrFail();
        $this->assertSame(12, DrafBulanan::where('id_draf_tahunan', $draf->id_draf_tahunan)->count());
        // Seluruh harta dan utang tahun itu ikut terkunci dan tersalin.
        $this->assertSame(0, Harta::whereNull('id_draf_tahunan')->count());
        $this->assertSame(0, Utang::whereNull('id_draf_tahunan')->count());
        $this->assertSame(
            Harta::count(),
            DrafTahunanHarta::where('id_draf_tahunan', $draf->id_draf_tahunan)->count(),
        );

        $this->get('/draf-tahunan/'.$tahun)->assertOk();

        Carbon::setTestNow();

        // Dibatalkan: seluruh penguncian terbuka dan salinannya ikut terhapus.
        $this->delete('/draf-tahunan/'.$tahun)->assertRedirect('/draf-tahunan');
        $this->assertSame(0, DrafTahunan::where('tahun_pajak', $tahun)->count());
        $this->assertSame(0, DrafTahunanHarta::where('id_draf_tahunan', $draf->id_draf_tahunan)->count());
        $this->assertGreaterThan(0, Harta::whereNull('id_draf_tahunan')->count());
    }

    public function test_hanya_draf_tahunan_terbaru_yang_dapat_dibatalkan(): void
    {
        $tersusun = DrafTahunan::orderBy('tahun_pajak')->pluck('tahun_pajak');
        $this->assertGreaterThan(1, $tersusun->count(), 'Pengujian ini butuh lebih dari satu draf tahunan.');

        $halaman = $this->get('/draf-tahunan')->assertOk()->getContent();

        // Hanya tahun terbaru yang punya dialog pembatalan; tahun lama ditandai tidak dapat dibatalkan.
        $this->assertStringContainsString('batal-tahunan-'.$tersusun->max(), $halaman);
        $this->assertStringNotContainsString('batal-tahunan-'.$tersusun->min(), $halaman);
        $this->assertStringContainsString('Batalkan draf tahun setelahnya terlebih dahulu', $halaman);
    }

    public function test_kewarganegaraan_tampil_panjang_tetapi_tersimpan_wni(): void
    {
        $this->assertSame('WNI', MockData::KEWARGANEGARAAN);
        $this->assertSame('WNI — Warga Negara Indonesia', MockData::kewarganegaraanTampil());

        // Halaman baca memuat tulisan panjangnya dalam satu baris.
        $this->get('/profil')->assertOk()->assertSee(MockData::kewarganegaraanTampil(), false);

        // Pada formulir, kotaknya hanya berisi nilai yang tersimpan; kepanjangannya
        // turun menjadi keterangan agar tidak terpotong pada layar sempit.
        foreach (['/profil/ubah', '/profil/lengkapi'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('value="WNI"', false)
                ->assertSee('Warga Negara Indonesia. M-SmartTax hanya melayani wajib pajak WNI.')
                ->assertDontSee(MockData::kewarganegaraanTampil(), false);
        }
    }

    public function test_ubah_kata_sandi_menegakkan_aturannya(): void
    {
        $this->get('/profil/kata-sandi')->assertOk()->assertSee('Kata Sandi Saat Ini');

        // Kata sandi wajib pajak contoh, disemai DataContohSeeder.
        $lama = 'rahasia123';
        $sah = ['kata_sandi_lama' => $lama, 'kata_sandi' => 'rahasia-baru-8', 'kata_sandi_confirmation' => 'rahasia-baru-8'];

        // Terlalu pendek, ulangan tidak sama, dan sama dengan kata sandi lama.
        foreach ([
            ['kata_sandi' => 'pendek', 'kata_sandi_confirmation' => 'pendek'],
            ['kata_sandi_confirmation' => 'beda-sendiri'],
            ['kata_sandi' => $lama, 'kata_sandi_confirmation' => $lama],
        ] as $salah) {
            $this->from('/profil/kata-sandi')
                ->put('/profil/kata-sandi', array_merge($sah, $salah))
                ->assertRedirect('/profil/kata-sandi')
                ->assertSessionHasErrors('kata_sandi');
        }

        // Kata sandi saat ini yang salah juga ditolak, pada kolomnya sendiri.
        $this->from('/profil/kata-sandi')
            ->put('/profil/kata-sandi', array_merge($sah, ['kata_sandi_lama' => 'bukan-kata-sandinya']))
            ->assertRedirect('/profil/kata-sandi')
            ->assertSessionHasErrors('kata_sandi_lama');

        $this->from('/profil/kata-sandi')->put('/profil/kata-sandi', $sah)
            ->assertRedirect('/profil')
            ->assertSessionHasNoErrors();

        // Kata sandinya benar-benar berganti, dan yang lama tidak berlaku lagi.
        $profil = Profil::query()->firstOrFail();
        $this->assertTrue(Hash::check('rahasia-baru-8', $profil->password));
        $this->assertFalse(Hash::check($lama, $profil->password));
    }

    public function test_ubah_profil_hanya_mengunci_username(): void
    {
        $this->get('/profil/ubah')
            ->assertOk()
            ->assertSee('Username tidak dapat diubah karena digunakan untuk masuk ke sistem.', false)
            // NIK sudah tidak dikumpulkan, jadi kolomnya pun tidak boleh ada lagi.
            ->assertDontSee('name="nik"', false)
            ->assertDontSee('Ya, Ubah NIK');
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
