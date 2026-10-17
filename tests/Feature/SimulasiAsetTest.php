<?php

namespace Tests\Feature;

use App\Services\SimulasiAset;
use App\Support\MockData;
use Tests\TestCase;

class SimulasiAsetTest extends TestCase
{
    /**
     * Rencana kredit yang sah, dipakai sebagai dasar lalu diubah sebagian.
     *
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function rencana(array $ubah = []): array
    {
        return array_merge([
            'jenis_aset' => 'tidak-bergerak',
            'nama_aset' => 'Ruko cabang ketiga',
            'cara_pembayaran' => 'Kredit',
            'harga_aset' => '450.000.000',
            'uang_muka' => '90.000.000',
            'jangka_waktu' => '60',
            'suku_bunga' => '8,5',
        ], $ubah);
    }

    public function test_kondisi_keuangan_diambil_dari_data_yang_tercatat(): void
    {
        $this->get('/simulasi')
            ->assertOk()
            // Rata-rata penghasilan bulanan, cicilan berjalan, total utang, kekayaan bersih.
            ->assertSee('Rp 76.333.333')
            ->assertSee('Rp 5.850.000')
            ->assertSee('Rp 238.000.000')
            ->assertSee('Rp 412.000.000');
    }

    public function test_angsuran_dihitung_dengan_metode_anuitas(): void
    {
        $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Rp 7.385.951')      // angsuran per bulan
            ->assertSee('Rp 13.235.951')     // total kewajiban bulanan
            ->assertSee('17,3%')             // rasio angsuran terhadap penghasilan
            ->assertSee('65,3%');            // rasio total utang terhadap penghasilan
    }

    public function test_koma_desimal_pada_bunga_dibaca_sama_dengan_titik(): void
    {
        $koma = $this->post('/simulasi', $this->rencana(['suku_bunga' => '8,5']))->getContent();
        $titik = $this->post('/simulasi', $this->rencana(['suku_bunga' => '8.5']))->getContent();

        $this->assertSame($koma, $titik);
    }

    public function test_bunga_nol_membagi_pokok_secara_rata(): void
    {
        $this->assertSame(6_000_000, app(SimulasiAset::class)->hitungCicilan(360_000_000, 60, 0));
    }

    public function test_kesimpulan_hanya_menimbang_rasio_angsuran(): void
    {
        // Rasio utang 65,3% tergolong besar, tetapi rasio angsuran 17,3% tetap
        // menentukan kesimpulannya. Kalimat penjelas tidak boleh memvonis.
        $halaman = $this->post('/simulasi', $this->rencana())
            ->assertOk()
            ->assertSee('Layak')
            ->assertSee('tidak ikut menentukan kesimpulan di atas')
            ->getContent();

        foreach (['tergolong aman', 'tergolong tinggi', 'perlu diperhatikan'] as $vonis) {
            $this->assertStringNotContainsString($vonis, $halaman);
        }
    }

    public function test_rasio_angsuran_menentukan_ketiga_kesimpulan(): void
    {
        $kondisi = ['penghasilan_bulanan' => 10_000_000, 'penghasilan_tahun' => 120_000_000,
            'cicilan_berjalan' => 0, 'total_utang' => 0];
        $simulasi = app(SimulasiAset::class);

        $this->assertSame('Layak', $simulasi->hitungKelayakan($kondisi, 2_900_000, 0)['kesimpulan']);
        $this->assertSame('Perlu Dipertimbangkan', $simulasi->hitungKelayakan($kondisi, 3_500_000, 0)['kesimpulan']);
        $this->assertSame('Belum Disarankan', $simulasi->hitungKelayakan($kondisi, 4_100_000, 0)['kesimpulan']);
    }

    public function test_panduan_memakai_ambang_yang_sama_dengan_perhitungan(): void
    {
        $tingkat = SimulasiAset::tingkatKelayakan();

        $this->assertSame(['Layak', 'Perlu Dipertimbangkan', 'Belum Disarankan'], array_column($tingkat, 'kesimpulan'));
        $this->assertSame(['di bawah 30%', '30% sampai 40%', 'di atas 40%'], array_column($tingkat, 'batas'));

        // Halaman panduan menampilkan daftar yang sama, bukan angka yang ditulis ulang.
        $this->get('/panduan/simulasi')
            ->assertOk()
            ->assertSee('di bawah 30%')
            ->assertSee('30% sampai 40%')
            ->assertSee('di atas 40%')
            ->assertSee('Tekan Hitung Kelayakan');
    }

    public function test_cicilan_berjalan_ikut_membentuk_rasio_angsuran(): void
    {
        $kondisi = ['penghasilan_bulanan' => 10_000_000, 'penghasilan_tahun' => 120_000_000,
            'cicilan_berjalan' => 2_000_000, 'total_utang' => 0];

        $hasil = app(SimulasiAset::class)->hitungKelayakan($kondisi, 2_000_000, 0);

        $this->assertSame(4_000_000, $hasil['total_kewajiban']);
        $this->assertSame(40.0, $hasil['rasio']);
    }

    public function test_pembelian_tunai_tidak_menimbulkan_angsuran(): void
    {
        $this->post('/simulasi', $this->rencana(['cara_pembayaran' => 'Tunai']))
            ->assertOk()
            ->assertSee('Pembelian dilakukan secara tunai')
            // Rasio dihitung dari cicilan berjalan saja: 5.850.000 dari 76.333.333.
            ->assertSee('7,7%');
    }

    public function test_penghasilan_nol_tidak_menghitung_rasio_dan_bukan_galat(): void
    {
        $kondisi = ['penghasilan_bulanan' => 0, 'penghasilan_tahun' => 0,
            'cicilan_berjalan' => 1_000_000, 'total_utang' => 0];

        $hasil = app(SimulasiAset::class)->hitungKelayakan($kondisi, 2_000_000, 0);

        $this->assertFalse($hasil['dapat_dihitung']);
        $this->assertNull($hasil['rasio']);
        $this->assertNull($hasil['kesimpulan']);
        $this->assertSame(3_000_000, $hasil['total_kewajiban']);
    }

    public function test_dampak_konsistensi_memakai_perhitungan_draf_tahunan(): void
    {
        // Pembelian kredit menambah kekayaan bersih sebesar uang mukanya saja,
        // sehingga rasio naik dari 95 juta menjadi 185 juta dibagi bruto.
        $dampak = app(SimulasiAset::class)->dampakKonsistensi(MockData::TAHUN, 450_000_000, 360_000_000);

        $this->assertSame(95_000_000, $dampak['sekarang']['pertambahan_bersih']);
        $this->assertSame(185_000_000, $dampak['perkiraan']['pertambahan_bersih']);
        $this->assertSame($dampak['sekarang']['bruto'], $dampak['perkiraan']['bruto']);
    }

    public function test_uang_muka_tidak_boleh_melebihi_harga_aset(): void
    {
        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana(['uang_muka' => '500.000.000']))
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors(['uang_muka' => 'Uang muka tidak boleh melebihi harga aset.']);
    }

    public function test_masukan_yang_tidak_sah_ditolak(): void
    {
        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana([
                'nama_aset' => '',
                'harga_aset' => '0',
                'jangka_waktu' => '400',
                'suku_bunga' => '120',
            ]))
            ->assertRedirect('/simulasi')
            ->assertSessionHasErrors(['nama_aset', 'harga_aset', 'jangka_waktu', 'suku_bunga']);

        $this->from('/simulasi')
            ->post('/simulasi', $this->rencana(['nama_aset' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('nama_aset');
    }

    public function test_peraga_penghasilan_nol_menghentikan_hasil_sebelum_rasio(): void
    {
        $this->post('/simulasi', $this->rencana(['peraga' => 'penghasilan-nol']))
            ->assertOk()
            ->assertSee('Rp 13.235.951')   // total kewajiban bulanan tetap tampil
            ->assertSee('Rasio tidak dapat dihitung karena belum ada data penghasilan pada tahun ini.')
            ->assertDontSee('Rasio angsuran terhadap penghasilan')
            ->assertDontSee('Kesimpulan')
            // Draf tahunan pembandingnya ada; yang nol brutonya. Keterangan
            // keadaan 5.3 tidak boleh muncul di sini karena menyesatkan.
            ->assertSee('Rasio belum dapat dihitung karena belum ada peredaran bruto pada tahun ini.')
            ->assertDontSee('setelah draf tahunan pertama tersusun');
    }

    public function test_peraga_tanpa_draf_tahunan_mengganti_kartu_konsistensi(): void
    {
        $this->post('/simulasi', $this->rencana(['peraga' => 'tanpa-draf-tahunan']))
            ->assertOk()
            ->assertSee('Dampak terhadap konsistensi harta akan muncul setelah draf tahunan pertama tersusun.')
            ->assertDontSee('Status Sekarang');
    }

    public function test_peraga_yang_tidak_dikenali_diabaikan(): void
    {
        $this->get('/simulasi?peraga=apa-saja')
            ->assertOk()
            ->assertSee('Rp 76.333.333');
    }

    public function test_hasil_simulasi_tidak_disimpan(): void
    {
        $this->post('/simulasi', $this->rencana())->assertOk();

        // Halaman dibuka kembali tanpa membawa rencana apa pun.
        $this->get('/simulasi')
            ->assertOk()
            ->assertDontSee('Hasil Simulasi')
            ->assertSee('tidak dapat diubah di halaman ini');
    }
}
