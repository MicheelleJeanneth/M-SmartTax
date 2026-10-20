<?php

namespace Tests\Feature;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\Penghasilan;
use App\Services\KunciPencatatan;
use App\Support\MockData;
use Tests\TestCase;

/**
 * Pembatalan berantai draf bulanan.
 *
 * Akumulasi peredaran bruto dihitung berurutan, jadi draf bulan sesudahnya
 * tidak lagi sah begitu bulan sebelumnya dibuka. Sebelum ada rantai ini, bulan
 * yang jauh ke belakang mustahil dibatalkan padahal pesan kesalahan pada Data
 * Penghasilan menyuruh membatalkannya.
 */
class PembatalanBerantaiTest extends TestCase
{
    /** @return array<int, int> */
    private function bulanTersusun(int $tahun): array
    {
        return DrafBulanan::where('tahun_pajak', $tahun)
            ->orderBy('bulan')
            ->pluck('bulan')
            ->map(fn ($bulan): int => (int) $bulan)
            ->all();
    }

    public function test_membatalkan_draf_bulan_tengah_ikut_membatalkan_bulan_sesudahnya(): void
    {
        $tahun = MockData::TAHUN;
        $this->assertSame([1, 2, 3, 4, 5, 6], $this->bulanTersusun($tahun));

        $this->delete('/draf-bulanan/3', ['tahun' => $tahun])
            ->assertRedirect('/draf-bulanan?tahun='.$tahun);

        $this->assertSame([1, 2], $this->bulanTersusun($tahun));

        // Transaksi Maret sampai Juni terbuka kembali, Januari dan Februari tetap terkunci.
        $this->assertSame(0, Penghasilan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', '>=', 3)->whereNotNull('id_draf_bulanan')->count());
        $this->assertGreaterThan(0, Penghasilan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', '<=', 2)->whereNotNull('id_draf_bulanan')->count());
    }

    public function test_transaksi_dapat_dicatat_setelah_draf_bulannya_dibatalkan(): void
    {
        $tahun = MockData::TAHUN;
        $kirim = ['tanggal' => "{$tahun}-02-10", 'nominal' => '7.500.000', 'keterangan' => 'Penjualan tambahan'];

        // Inilah keluhan aslinya: pesannya menyuruh membatalkan draf bulan itu.
        $this->from('/penghasilan/create')->post('/penghasilan', $kirim)
            ->assertRedirect('/penghasilan/create')
            ->assertSessionHasErrors('tanggal');

        $this->delete('/draf-bulanan/2', ['tahun' => $tahun])
            ->assertRedirect('/draf-bulanan?tahun='.$tahun);

        // Sesudah pesannya diikuti, transaksinya memang dapat dicatat.
        $this->post('/penghasilan', $kirim)->assertRedirect('/penghasilan')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('penghasilan', ['keterangan' => 'Penjualan tambahan', 'nominal' => 7_500_000]);
    }

    public function test_draf_bulanan_yang_terkunci_draf_tahunan_tidak_dapat_dibatalkan_langsung(): void
    {
        $tahun = (int) DrafTahunan::max('tahun_pajak');

        $this->delete('/draf-bulanan/6', ['tahun' => $tahun])->assertForbidden();

        $this->assertCount(12, $this->bulanTersusun($tahun));
    }

    public function test_pesan_kunci_menyebut_draf_tahunan_bila_bulannya_terkunci_tahunan(): void
    {
        $tahun = (int) DrafTahunan::max('tahun_pajak');

        // Menyuruh membatalkan draf bulanannya akan ditolak, jadi yang disebut
        // harus lapisan tahunannya.
        $this->assertStringContainsString(
            "Draf pajak tahunan {$tahun}",
            app(KunciPencatatan::class)->pesan("{$tahun}-06-10"),
        );
    }

    public function test_pesan_kunci_menyebut_draf_penahan_bila_bulannya_tidak_berdraf(): void
    {
        $terlama = min($this->bulanTersusunTahun()) - 1;
        $pesan = app(KunciPencatatan::class)->pesan("{$terlama}-05-10");

        // Tidak ada draf Mei tahun itu, jadi pesannya tidak boleh mengaku ada.
        $this->assertStringNotContainsString("Draf pajak Mei {$terlama}", $pesan);
        $this->assertStringContainsString('Transaksi hanya dapat dicatat mulai', $pesan);
    }

    /** @return array<int, int> */
    private function bulanTersusunTahun(): array
    {
        return DrafBulanan::distinct()->pluck('tahun_pajak')->map(fn ($t): int => (int) $t)->all();
    }
}
