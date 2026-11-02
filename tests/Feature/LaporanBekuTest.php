<?php

namespace Tests\Feature;

use App\Models\DrafTahunan;
use App\Models\DrafTahunanHarta;
use App\Models\DrafTahunanUtang;
use App\Models\Harta;
use App\Models\Utang;
use App\Support\MockData;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Laporan Data Harta dan Laporan Data Utang tahun yang sudah berdraf harus
 * memakai salinan beku, bukan angka sekarang.
 *
 * Saldo utang wajib diperbarui tiap tahun supaya draf tahun berikutnya benar.
 * Sebelum ini, pembaruan itu ikut mengubah laporan tahun-tahun sebelumnya —
 * laporan 2024 berbunyi "Posisi 31 Desember 2024" tetapi mencetak saldo hari
 * ini. Pengujian ini yang menangkapnya.
 */
class LaporanBekuTest extends TestCase
{
    private function tahunBerdraf(): int
    {
        return (int) DrafTahunan::query()->max('tahun_pajak');
    }

    public function test_saldo_utang_laporan_tahun_berdraf_tidak_ikut_berubah(): void
    {
        $tahun = $this->tahunBerdraf();

        $salinan = DrafTahunanUtang::whereHas('drafTahunan', fn ($k) => $k->where('tahun_pajak', $tahun))
            ->whereNotNull('id_utang')
            ->firstOrFail();

        $utang = Utang::findOrFail($salinan->id_utang);

        // Halaman /laporan/utang hanya kerangka beserta iframe; tabelnya
        // dirender oleh pratinjau, yang memakai view PDF yang sama.
        // Saldonya diperbarui seperti yang memang harus dilakukan tiap tahun.
        $utang->update(['saldo' => 1_000_000]);

        $halaman = $this->get("/laporan/utang/pratinjau?tahun={$tahun}")->assertOk()->getContent();

        $this->assertStringContainsString(rupiah((int) $salinan->saldo), $halaman,
            'Laporan tahun berdraf harus memakai saldo dari salinan beku.');
        $this->assertStringNotContainsString(rupiah(1_000_000), $halaman,
            'Saldo sekarang tidak boleh bocor ke laporan tahun berdraf.');

        // Jati dirinya tetap dibaca dari baris asli, bukan dari salinan.
        $this->assertStringContainsString($utang->nama_kreditur, $halaman);
        $this->assertStringContainsString((string) $utang->tahun_peminjaman, $halaman);
    }

    public function test_nilai_harta_laporan_tahun_berdraf_tidak_ikut_berubah(): void
    {
        $tahun = $this->tahunBerdraf();

        $salinan = DrafTahunanHarta::whereHas('drafTahunan', fn ($k) => $k->where('tahun_pajak', $tahun))
            ->whereNotNull('id_harta')
            ->firstOrFail();

        $harta = Harta::findOrFail($salinan->id_harta);

        $harta->update(['nilai' => 7_777_777]);

        $halaman = $this->get("/laporan/harta/pratinjau?tahun={$tahun}")->assertOk()->getContent();

        $this->assertStringContainsString(rupiah((int) $salinan->nilai), $halaman);
        $this->assertStringNotContainsString(rupiah(7_777_777), $halaman);
    }

    public function test_tahun_belum_berdraf_memakai_angka_sekarang(): void
    {
        $tahun = $this->tahunBerdraf() + 1;
        $this->assertFalse(DrafTahunan::where('tahun_pajak', $tahun)->exists());

        $utang = Utang::get()->first(fn (Utang $u): bool => $u->berlakuPada($tahun));
        $utang->update(['saldo' => 3_333_333]);

        // Belum ada yang dibekukan, jadi laporannya memang angka terkini.
        $this->get("/laporan/utang/pratinjau?tahun={$tahun}")
            ->assertOk()
            ->assertSee(rupiah(3_333_333), false);
    }

    /**
     * Halaman Susun Draf Tahunan harus menyebut urutannya dengan benar:
     * perbarui angkanya dulu, baru simpan drafnya. Kalimat lamanya mengaku
     * harta dan utang "tidak dapat diubah" setelah draf disimpan, padahal
     * nilainya justru harus terus diperbarui untuk tahun berikutnya.
     */
    public function test_halaman_susun_mengingatkan_memperbarui_angkanya_dulu(): void
    {
        $tahun = MockData::TAHUN;
        Carbon::setTestNow(($tahun + 1).'-01-05');

        for ($bulan = 7; $bulan <= 12; $bulan++) {
            $this->post('/draf-bulanan', ['bulan' => $bulan, 'tahun' => $tahun])->assertRedirect();
        }

        $halaman = $this->get('/draf-tahunan/susun?tahun='.$tahun)->assertOk();

        // Tombol Simpan menanyakan dulu, dengan ringkasan angka yang akan
        // dibekukan — totalnya tidak dapat diubah lagi setelah draf tersimpan.
        $halaman
            ->assertSee('data-konfirmasi="simpan-draf-tahunan"', false)
            ->assertSee('id="simpan-draf-tahunan"', false)
            ->assertSee('data-kirim="formulir-draf-tahunan"', false)
            ->assertSee('Simpan draf tahunan '.$tahun.'?')
            ->assertSee('Total Harta')
            ->assertSee('Total Utang')
            ->assertSee("buka-dialog', 'batal-susun'", false)
            ->assertSee('Batalkan penyusunan draf?')
            ->assertSee('Lanjut Memeriksa');

        $halaman
            ->assertSee('Pastikan saldo utang dan nilai harta sudah sesuai posisi 31 Desember '.$tahun.' sebelum menyimpan.')
            ->assertSee('Draf menyalin angka yang ada saat ini')
            // Kalimat lama yang menyesatkan tidak boleh kembali.
            ->assertDontSee('data harta, dan data utang tahun '.$tahun.' tidak dapat diubah')
            ->assertSee('Nilai dan saldonya tetap dapat diperbarui untuk keperluan tahun berikutnya');

        Carbon::setTestNow();
    }

    public function test_penyusun_menyimpan_penanda_baris_aslinya(): void
    {
        $tahun = $this->tahunBerdraf();
        $draf = DrafTahunan::where('tahun_pajak', $tahun)->firstOrFail();

        // Tanpa penanda ini, salinan beku tidak dapat dicocokkan ke barisnya
        // dan laporannya diam-diam jatuh kembali ke angka sekarang.
        $this->assertGreaterThan(0, $draf->lampiranUtang->count());
        $this->assertTrue($draf->lampiranUtang->every(fn (DrafTahunanUtang $u): bool => $u->id_utang !== null));
        $this->assertTrue($draf->lampiranHarta->every(fn (DrafTahunanHarta $h): bool => $h->id_harta !== null));
    }
}
