<?php

namespace Tests\Feature;

use App\Models\Penghasilan;
use App\Models\Profil;
use App\Services\Pengingat;
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

    private function catat(Profil $profil, string $tanggal): void
    {
        Penghasilan::create([
            'id_profil' => $profil->id_profil,
            'tanggal' => $tanggal,
            'nominal' => 5_000_000,
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
}
