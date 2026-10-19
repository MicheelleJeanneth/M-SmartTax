<?php

namespace Tests\Feature;

use App\Models\DrafTahunan;
use App\Models\Harta;
use App\Models\Utang;
use Tests\TestCase;

/**
 * Aturan kunci periode berdraf untuk harta dan utang.
 *
 * Berbeda dengan penghasilan yang hanya milik satu bulan, satu harta ikut
 * lampiran draf tahunan setiap tahun selama masih dimiliki. Pengujian di sini
 * menjaga kedua akibatnya: kuncinya tidak boleh lepas selama masih ada draf
 * tahun lain yang memuatnya, dan tahun berdraf tidak boleh menerima data baru.
 */
class KunciPeriodeBerdrafTest extends TestCase
{
    /** Harta yang termuat draf tahun lebih lama sekaligus draf tahun terbaru. */
    private function hartaLintasTahun(): Harta
    {
        $lama = DrafTahunan::min('tahun_pajak');

        return Harta::with('kodeHarta')->get()
            ->first(fn (Harta $h): bool => $h->dimilikiPada((int) $lama));
    }

    public function test_harta_tetap_terkunci_selama_draf_tahun_lama_masih_tersusun(): void
    {
        $harta = $this->hartaLintasTahun();
        $terbaru = (int) DrafTahunan::max('tahun_pajak');
        $terlama = (int) DrafTahunan::min('tahun_pajak');
        $alamat = "/harta/{$harta->kodeHarta->kategori}/{$harta->id}/ubah";

        // Baris terkunci tetap boleh dibuka, tetapi yang tampil formulir khusus
        // nilai, bukan formulir penuh.
        $this->assertTrue($harta->terkunci);
        $this->get($alamat)->assertOk()->assertSee('sudah masuk draf pajak tahunan');

        // Draf terbaru dibatalkan, tetapi draf tahun lama masih memuat harta ini.
        $this->delete("/draf-tahunan/{$terbaru}")->assertRedirect('/draf-tahunan');

        $this->assertTrue($harta->fresh()->terkunci, 'Harta ikut terbuka padahal draf tahun lama masih tersusun.');
        $this->get($alamat)->assertOk()->assertSee('sudah masuk draf pajak tahunan');

        // Setelah draf terakhir ikut dibatalkan, barulah formulir penuh terbuka.
        $this->delete("/draf-tahunan/{$terlama}")->assertRedirect('/draf-tahunan');

        $this->assertFalse($harta->fresh()->terkunci);
        $this->get($alamat)->assertOk()->assertDontSee('sudah masuk draf pajak tahunan');
    }

    public function test_penanda_draf_tahunan_menunjuk_draf_terbaru_yang_masih_memuat_barisnya(): void
    {
        $harta = $this->hartaLintasTahun();
        $terbaru = (int) DrafTahunan::max('tahun_pajak');

        $this->assertSame(
            (int) DrafTahunan::where('tahun_pajak', $terbaru)->value('id_draf_tahunan'),
            (int) $harta->id_draf_tahunan,
        );

        $this->delete("/draf-tahunan/{$terbaru}")->assertRedirect('/draf-tahunan');

        // Penandanya dialihkan ke draf tersusun terbaru yang tersisa, bukan dikosongkan.
        $this->assertSame(
            (int) DrafTahunan::orderByDesc('tahun_pajak')->value('id_draf_tahunan'),
            (int) $harta->fresh()->id_draf_tahunan,
        );
    }

    public function test_utang_tetap_terkunci_selama_draf_tahun_lama_masih_tersusun(): void
    {
        $terlama = (int) DrafTahunan::min('tahun_pajak');
        $utang = Utang::get()->first(fn (Utang $u): bool => $u->berlakuPada($terlama));

        $this->delete('/draf-tahunan/'.DrafTahunan::max('tahun_pajak'))->assertRedirect('/draf-tahunan');

        $this->assertTrue($utang->fresh()->terkunci, 'Utang ikut terbuka padahal draf tahun lama masih tersusun.');
        $this->get("/utang/{$utang->id}/edit")->assertOk()->assertSee('sudah masuk draf pajak tahunan');
    }

    public function test_harta_tidak_dapat_dicatat_mundur_ke_tahun_berdraf(): void
    {
        $berdraf = (int) DrafTahunan::min('tahun_pajak');
        $jumlah = Harta::count();

        $this->from('/harta/kas/tambah')
            ->post('/harta/kas', [
                'kode' => '0101',
                'keterangan' => 'Uji periode berdraf',
                'nilai' => '5.000.000',
                'tahun' => $berdraf,
            ])
            ->assertRedirect('/harta/kas/tambah')
            ->assertSessionHasErrors('tahun');

        $this->assertSame($jumlah, Harta::count());
    }

    public function test_harta_yang_diperoleh_dan_dilepas_pada_tahun_berdraf_tetap_boleh_dicatat(): void
    {
        $berdraf = (int) DrafTahunan::min('tahun_pajak');

        // Harta seperti ini tidak pernah masuk lampiran draf mana pun, karena
        // lampiran berisi posisi pada akhir tahun.
        $this->post('/harta/kas', [
            'kode' => '0101',
            'keterangan' => 'Dibeli dan dilepas pada tahun yang sama',
            'nilai' => '5.000.000',
            'tahun' => $berdraf,
            'tahun_pelepasan' => $berdraf,
        ])->assertRedirect('/harta/kas')->assertSessionHasNoErrors();
    }

    public function test_utang_tidak_dapat_dicatat_mundur_ke_tahun_berdraf(): void
    {
        $berdraf = (int) DrafTahunan::max('tahun_pajak');
        $jumlah = Utang::count();

        $this->from('/utang/create')
            ->post('/utang', [
                'kode' => '109',
                'deskripsi' => 'Uji periode berdraf',
                'saldo' => '1.000.000',
                'tahun_peminjaman' => $berdraf,
                'nama_kreditur' => 'Koperasi Sejahtera',
            ])
            ->assertRedirect('/utang/create')
            ->assertSessionHasErrors('tahun_peminjaman');

        $this->assertSame($jumlah, Utang::count());
    }

    public function test_harta_terkunci_hanya_dapat_memperbarui_nilainya(): void
    {
        $harta = Harta::with('kodeHarta')->get()
            ->first(fn (Harta $h): bool => $h->terkunci && $h->tahun_pelepasan === null);
        $kategori = $harta->kodeHarta->kategori;
        $beku = (int) DrafTahunan::orderByDesc('tahun_pajak')->value('total_harta');

        $this->get("/harta/{$kategori}/{$harta->id}/ubah")->assertOk();

        // Kolom di luar nilai diabaikan, meski ikut dikirim.
        $this->put("/harta/{$kategori}/{$harta->id}", [
            'nilai' => angka($harta->nilai),
            'nilai_kini' => '31.000.000',
            'kode' => '0301',
            'tahun' => 2019,
            'keterangan' => 'diselundupkan',
        ])->assertRedirect("/harta/{$kategori}")->assertSessionHasNoErrors();

        $sesudah = $harta->fresh();
        $this->assertSame(31_000_000, (int) $sesudah->nilai_saat_ini);
        $this->assertSame($harta->kode, $sesudah->kode);
        $this->assertSame((int) $harta->tahun_perolehan, (int) $sesudah->tahun_perolehan);
        $this->assertSame($harta->keterangan, $sesudah->keterangan);

        // Laporan tahun yang sudah tersusun tetap memakai salinan bekunya.
        $this->assertSame($beku, (int) DrafTahunan::orderByDesc('tahun_pajak')->value('total_harta'));
    }

    public function test_utang_terkunci_hanya_dapat_memperbarui_saldo_dan_cicilannya(): void
    {
        $utang = Utang::get()->first(fn (Utang $u): bool => $u->terkunci);
        $beku = DrafTahunan::orderBy('tahun_pajak')->pluck('total_utang', 'tahun_pajak');

        $this->get("/utang/{$utang->id}/edit")->assertOk();

        $this->put("/utang/{$utang->id}", [
            'saldo' => '150.000.000',
            'cicilan_bulanan' => '4.100.000',
            'nama_kreditur' => 'Bank Palsu',
            'tahun_peminjaman' => 2019,
            'kode' => '109',
            'deskripsi' => 'diselundupkan',
        ])->assertRedirect('/utang')->assertSessionHasNoErrors();

        $sesudah = $utang->fresh();
        $this->assertSame(150_000_000, (int) $sesudah->saldo);
        $this->assertSame($utang->nama_kreditur, $sesudah->nama_kreditur);
        $this->assertSame((int) $utang->tahun_peminjaman, (int) $sesudah->tahun_peminjaman);
        $this->assertSame($utang->deskripsi, $sesudah->deskripsi);

        $this->assertEquals($beku->all(), DrafTahunan::orderBy('tahun_pajak')->pluck('total_utang', 'tahun_pajak')->all());
    }

    public function test_baris_terkunci_tetap_tidak_dapat_dihapus(): void
    {
        $utang = Utang::get()->first(fn (Utang $u): bool => $u->terkunci);
        $harta = Harta::with('kodeHarta')->get()->first(fn (Harta $h): bool => $h->terkunci);

        $this->delete("/utang/{$utang->id}")->assertForbidden();
        $this->delete("/harta/{$harta->kodeHarta->kategori}/{$harta->id}")->assertForbidden();

        $this->assertTrue(Utang::whereKey($utang->id_utang)->exists());
        $this->assertTrue(Harta::whereKey($harta->id_harta)->exists());
    }

    public function test_url_salah_ketik_menghasilkan_404_bukan_galat_peladen(): void
    {
        $this->get('/penghasilan/buat')->assertNotFound();
        $this->get('/utang/buat')->assertNotFound();
        $this->get('/harta/kas/buat')->assertNotFound();
    }
}
