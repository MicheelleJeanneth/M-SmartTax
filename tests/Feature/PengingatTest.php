<?php

namespace Tests\Feature;

use App\Models\DrafBulanan;
use App\Models\KonfigurasiPajak;
use App\Models\Penghasilan;
use App\Models\Profil;
use App\Services\Pengingat;
use App\Services\PerhitunganPajak;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengingat draf bulanan hanya menagih masa pajak yang memang menjadi
 * kewajiban pengguna, yaitu sejak penghasilan pertama tercatat.
 */
class PengingatTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Akun bersih tanpa satu pun catatan. */
    private function akunBaru(): Profil
    {
        $profil = Profil::create([
            'email' => 'uji.baru@gmail.com',
            'password' => 'rahasia-uji-8',
            'nik' => '3578011708950007',
            'nama' => 'Uji Baru',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1995-08-17',
            'jenis_kelamin' => 'Pria',
            'nomor_handphone' => '081234567899',
            'alamat' => 'Jl. Uji 1',
            'rt' => '001',
            'rw' => '001',
            'kelurahan' => 'Uji',
            'kecamatan' => 'Uji',
            'kota' => 'Surabaya',
            'provinsi' => 'Jawa Timur',
            'profil_lengkap' => true,
            'status' => true,
        ]);

        $this->actingAs($profil);
        PenggunaSaatIni::lupakan();

        return $profil;
    }

    private function catat(Profil $profil, string $tanggal, int $nominal = 5_000_000): void
    {
        Penghasilan::create([
            'id_profil' => $profil->id_profil,
            'tanggal' => $tanggal,
            'nominal' => $nominal,
            'keterangan' => 'Penjualan uji',
            'status' => true,
        ]);
    }

    /** @return array<int, string> Nama bulan yang ditagih, misalnya "Oktober 2026". */
    private function ditagih(): array
    {
        return collect(app(Pengingat::class)->segarkan())
            ->filter(fn (array $p): bool => str_starts_with($p['jenis'], 'draf_bulanan'))
            ->map(fn (array $p): string => str_replace(['Draf pajak ', ' belum disusun'], '', $p['judul']))
            ->values()
            ->all();
    }

    public function test_akun_tanpa_penghasilan_tidak_ditagih_draf_bulanan(): void
    {
        Carbon::setTestNow('2026-10-20');
        $this->akunBaru();

        // Sebelum diperbaiki, akun baru langsung ditagih Januari sampai September.
        $this->assertSame([], $this->ditagih());
    }

    public function test_penagihan_dimulai_dari_bulan_penghasilan_pertama(): void
    {
        Carbon::setTestNow('2026-12-10');
        $profil = $this->akunBaru();

        // Mendaftar Oktober, mencatat satu penghasilan Oktober, November kosong.
        $this->catat($profil, '2026-10-05');

        // Januari sampai September bukan kewajibannya, jadi tidak ikut ditagih.
        $this->assertSame(['Oktober 2026', 'November 2026'], $this->ditagih());
    }

    public function test_bulan_kosong_di_tengah_tetap_ditagih(): void
    {
        Carbon::setTestNow('2026-10-20');
        $profil = $this->akunBaru();

        // Draf disusun berurutan, jadi bulan tanpa penghasilan pun perlu draf nihil.
        $this->catat($profil, '2026-07-20');

        $this->assertSame(['Juli 2026', 'Agustus 2026', 'September 2026'], $this->ditagih());
    }

    public function test_bulan_berjalan_belum_ditagih(): void
    {
        Carbon::setTestNow('2026-10-20');
        $profil = $this->akunBaru();

        // Oktober belum berakhir, jadi drafnya belum jatuh tempo.
        $this->catat($profil, '2026-10-05');

        $this->assertSame([], $this->ditagih());
    }

    public function test_wajib_pajak_yang_sudah_lama_mencatat_tetap_ditagih_sejak_draf_terakhir(): void
    {
        Carbon::setTestNow('2026-10-20');

        // Budi: penghasilan sejak 2024, draf tersusun sampai Juni 2026.
        $this->assertSame(['Juli 2026', 'Agustus 2026', 'September 2026'], $this->ditagih());
    }

    public function test_rantai_draf_bulanan_dimulai_dari_bulan_penghasilan_pertama(): void
    {
        Carbon::setTestNow('2026-10-20');
        $profil = $this->akunBaru();
        $this->catat($profil, '2026-10-11');

        $pajak = app(PerhitunganPajak::class);

        // Sebelum diperbaiki, hanya Januari yang boleh disusun walau brutonya nol.
        $this->assertSame(10, $pajak->bulanPertama(2026));
        $this->assertSame(10, $pajak->bulanBerikutnya(2026));
        $this->assertSame(3, $pajak->jumlahWajib(2026), 'Oktober sampai Desember, bukan dua belas.');

        $rekap = collect($pajak->rekapTahun(2026))->keyBy('bulan');
        $this->assertFalse($rekap[1]['wajib']);
        $this->assertTrue($rekap[10]['wajib']);

        // Oktober gilirannya, tetapi bulannya masih berjalan: belum boleh disusun.
        $this->assertFalse($rekap[10]['boleh_susun']);
        $this->assertTrue($rekap[10]['menunggu_akhir']);
        $this->post('/draf-bulanan', ['bulan' => 10, 'tahun' => 2026])->assertForbidden();

        // Begitu Oktober berakhir, barulah terbuka.
        Carbon::setTestNow('2026-11-01');

        $this->assertTrue(collect($pajak->rekapTahun(2026))->keyBy('bulan')[10]['boleh_susun']);
        $this->get('/draf-bulanan/susun?bulan=10')->assertOk();
        $this->post('/draf-bulanan', ['bulan' => 1, 'tahun' => 2026])->assertForbidden();
        $this->post('/draf-bulanan', ['bulan' => 10, 'tahun' => 2026])->assertRedirect('/draf-bulanan?tahun=2026');

        $this->assertSame([10], DrafBulanan::where('id_profil', $profil->id_profil)
            ->pluck('bulan')->map(fn ($b): int => (int) $b)->all());
    }

    public function test_tahun_tanpa_penghasilan_tidak_punya_draf_yang_dapat_disusun(): void
    {
        Carbon::setTestNow('2026-10-20');
        $this->akunBaru();

        $pajak = app(PerhitunganPajak::class);
        $this->assertNull($pajak->bulanPertama(2026));
        $this->assertSame(0, $pajak->jumlahWajib(2026));

        $this->get('/draf-bulanan/susun?bulan=1')->assertForbidden();
        $this->post('/draf-bulanan', ['bulan' => 1, 'tahun' => 2026])->assertForbidden();
    }

    public function test_wajib_pajak_lama_tetap_butuh_dua_belas_draf_setahun(): void
    {
        Carbon::setTestNow('2026-10-20');

        // Budi mencatat sejak Januari 2024, jadi aturannya tidak berubah.
        $pajak = app(PerhitunganPajak::class);

        foreach ([2024, 2025, 2026] as $tahun) {
            $this->assertSame(1, $pajak->bulanPertama($tahun));
            $this->assertSame(12, $pajak->jumlahWajib($tahun));
        }

        $this->assertSame(7, $pajak->bulanBerikutnya(2026));
        $this->assertSame(0, collect($pajak->rekapTahun(2026))->where('wajib', false)->count());
    }

    public function test_laporan_tidak_menawarkan_menyusun_bulan_di_luar_masa_pajak(): void
    {
        Carbon::setTestNow('2026-10-20');
        $profil = $this->akunBaru();
        $this->catat($profil, '2026-10-11');

        // Tanpa bulan di alamatnya, halaman membuka Oktober, bukan Januari.
        $this->get('/laporan/draf-bulanan')
            ->assertOk()
            ->assertSee('Draf Oktober 2026 belum tersusun');

        // Januari bukan masa pajaknya: tidak ditawari tombol menyusun.
        $this->get('/laporan/draf-bulanan?bulan=1')
            ->assertOk()
            ->assertSee('bukan masa pajak Anda')
            ->assertDontSee('Buka Draf Bulanan');
    }

    public function test_judul_rekap_pdf_mengikuti_jumlah_bulan_yang_dirangkum(): void
    {
        // Budi mencatat sejak Januari, jadi draf tahunannya merangkum 12 bulan.
        $this->get('/laporan/draf-tahunan/pratinjau?tahun=2025')
            ->assertOk()
            ->assertSee('Rekap Peredaran Bruto 12 Bulan');
    }

    public function test_peredaran_bruto_tampil_walau_drafnya_belum_dapat_disusun(): void
    {
        Carbon::setTestNow('2026-10-21');
        $profil = $this->akunBaru();

        // Nominalnya sengaja dibuat berbeda jauh agar tidak bertabrakan dengan
        // angka akumulasi, sehingga kemunculannya menunjuk satu sel tertentu.
        $this->catat($profil, '2026-06-11', 1_000_000);    // gilirannya
        $this->catat($profil, '2026-09-10', 20_000_000);   // menunggu giliran
        $this->catat($profil, '2026-10-11', 300_000_000);  // menunggu giliran

        $halaman = $this->get('/draf-bulanan')->assertOk()->getContent();

        // Sebelum diperbaiki, dua bulan terakhir tertulis Rp 0 — bukan sekadar
        // disembunyikan, tetapi dinyatakan nol padahal datanya ada.
        $this->assertStringContainsString(rupiah(20_000_000), $halaman);
        $this->assertStringContainsString(rupiah(300_000_000), $halaman);

        // Akumulasi berjalan ikut tampil walau drafnya belum disusun.
        $this->assertStringContainsString(rupiah(21_000_000), $halaman);

        // Totalnya menjumlahkan seluruh bulan, bukan hanya yang drafnya tersusun.
        $this->assertStringContainsString(rupiah(321_000_000), $halaman);
    }

    public function test_omzet_kena_pajak_dan_pph_diperkirakan_sebelum_drafnya_disusun(): void
    {
        Carbon::setTestNow('2026-12-10');
        $profil = $this->akunBaru();

        $bebas = (int) KonfigurasiPajak::berlakuUntukTahun(2026)->ambang_bebas;

        // Akumulasi menembus ambang bebas tepat pada bulan kedua.
        $this->catat($profil, '2026-10-05', $bebas);
        $this->catat($profil, '2026-11-05', 30_000_000);

        $rekap = collect(app(PerhitunganPajak::class)->rekapTahun(2026))->keyBy('bulan');

        // Bulan yang masih di dalam ambang bebas belum dikenai pajak.
        $this->assertSame(0, (int) $rekap[10]['omzet_kena_pajak']);
        $this->assertSame(0, (int) $rekap[10]['pph_final']);

        // Hanya kelebihan di atas ambang yang dikenai tarif, walau drafnya belum disusun.
        $this->assertSame(30_000_000, (int) $rekap[11]['omzet_kena_pajak']);
        $this->assertSame(150_000, (int) $rekap[11]['pph_final']);

        $this->get('/draf-bulanan')->assertOk()->assertSee(rupiah(150_000));
    }

    public function test_bulan_tersusun_memakai_angka_bekunya_bukan_hitungan_ulang(): void
    {
        // Budi: enam bulan 2026 sudah tersusun.
        $rekap = collect(app(PerhitunganPajak::class)->rekapTahun(2026))->keyBy('bulan');

        foreach (DrafBulanan::where('tahun_pajak', 2026)->get() as $draf) {
            $this->assertSame(
                (int) $draf->pph_final,
                (int) $rekap[(int) $draf->bulan]['pph_final'],
                'Bulan tersusun harus memakai angka beku drafnya.',
            );
        }
    }
}
