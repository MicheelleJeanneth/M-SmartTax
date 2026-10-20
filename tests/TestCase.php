<?php

namespace Tests;

use App\Models\DrafTahunan;
use App\Models\Profil;
use App\Support\PenggunaSaatIni;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Seluruh tes berjalan di atas basis data SQLite dalam memori yang dimigrasikan
 * dan disemai sekali di awal, lalu tiap tes dibungkus transaksi sendiri.
 * Dengan begitu data contohnya sama persis dengan yang dilihat pengguna.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Ingatan di bawah ini hidup selama satu permintaan HTTP. Di dalam tes,
     * satu proses menjalankan banyak permintaan berturut-turut, jadi keduanya
     * dibersihkan agar keadaan satu tes tidak terbawa ke tes berikutnya.
     */
    protected function setUp(): void
    {
        parent::setUp();

        PenggunaSaatIni::lupakan();
        DrafTahunan::lupakanTahunTersusun();

        // Seluruh halaman aplikasi berada di balik middleware 'auth', jadi tes
        // berjalan sebagai wajib pajak contoh. Tes yang justru menguji
        // autentikasinya sendiri memakai keadaan tamu lewat Auth::logout().
        $this->actingAs(Profil::query()->firstOrFail());
    }
}
