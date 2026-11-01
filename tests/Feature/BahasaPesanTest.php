<?php

namespace Tests\Feature;

use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Utang;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Seluruh pesan yang dibaca pengguna harus berbahasa Indonesia.
 *
 * Pesan validasi disusun dua lapis: yang khusus ditulis di pengendalinya,
 * sisanya jatuh ke lang/id. Lapisan kedua itu mudah terlewat — kolom baru
 * cukup ditambahkan tanpa pesan dan kalimat bawaan Laravel yang berbahasa
 * Inggris langsung muncul di layar. Pengujian ini yang menangkapnya.
 */
class BahasaPesanTest extends TestCase
{
    /**
     * Kata yang tidak mungkin muncul dalam kalimat Indonesia, tetapi hampir
     * selalu ada pada kalimat bawaan Laravel.
     *
     * @var list<string>
     */
    private const KATA_INGGRIS = [
        'the ', ' must ', ' field ', ' may not ', ' greater ', ' least ',
        ' characters', ' invalid', ' required', ' does not', ' already ',
        'please ', ' selected ', ' taken', ' match ',
    ];

    /**
     * Kiriman yang sengaja salah jenis dan di luar batas, supaya aturan selain
     * 'required' ikut menyala — justru aturan itulah yang jarang diberi pesan.
     *
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function kiriman(): array
    {
        $panjang = str_repeat('a', 300);
        $tahunDepan = (string) (now()->year + 1);

        return [
            'penghasilan' => ['post', '/penghasilan', [
                'tanggal' => 'bukan-tanggal', 'nominal' => 'abc', 'keterangan' => $panjang,
            ]],
            'harta' => ['post', '/harta/kas', [
                'kode' => ['bukan teks'], 'nilai' => 'abc', 'tahun' => $tahunDepan,
                'tahun_pelepasan' => '18', 'keterangan' => $panjang, 'nilai_kini' => 'abc',
            ]],
            'utang' => ['post', '/utang', [
                'kode' => ['bukan teks'], 'deskripsi' => $panjang, 'saldo' => 'abc',
                'tahun_peminjaman' => $tahunDepan, 'nama_kreditur' => $panjang,
                'cicilan_bulanan' => 'abc', 'keterangan' => $panjang,
            ]],
            'profil' => ['put', '/profil', [
                'nama' => $panjang, 'email' => 'bukan-email', 'tempat_lahir' => $panjang,
                'tanggal_lahir' => 'bukan-tanggal', 'alamat' => $panjang,
                'kelurahan' => $panjang, 'kecamatan' => $panjang, 'kota' => $panjang,
                'provinsi' => $panjang,
            ]],
            'kata sandi' => ['put', '/profil/kata-sandi', [
                'kata_sandi_lama' => 'salah', 'kata_sandi' => 'a', 'kata_sandi_confirmation' => 'b',
            ]],
            'simulasi' => ['post', '/simulasi', [
                'nama_aset' => str_repeat('a', 300), 'harga_aset' => 'abc', 'jangka_waktu' => '0',
            ]],
            'draf bulanan' => ['post', '/draf-bulanan', ['bulan' => 'abc', 'tahun' => 'abc']],
        ];
    }

    #[DataProvider('kiriman')]
    public function test_pesan_validasi_tidak_berbahasa_inggris(string $metode, string $uri, array $data): void
    {
        $pesan = $this->{$metode}($uri, $data)
            ->assertSessionHasErrors()
            ->getSession()
            ->get('errors')
            ->getBag('default')
            ->all();

        $this->assertNotEmpty($pesan, "Tidak ada pesan kesalahan dari $uri.");

        foreach ($pesan as $baris) {
            foreach (self::KATA_INGGRIS as $kata) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $kata, $baris, "Pesan dari $uri masih berbahasa Inggris: \"$baris\""
                );
            }
        }
    }

    /**
     * Perubahan data juga diperiksa, karena aturannya sebagian berbeda dari
     * penambahan — misalnya baris yang sudah terkunci draf tahunan.
     */
    public function test_pesan_perubahan_tidak_berbahasa_inggris(): void
    {
        // Baris yang belum terkunci draf, supaya kirimannya sampai ke
        // pemeriksaan kolom dan bukan ditolak lebih dulu sebagai 403.
        $harta = Harta::whereNull('id_draf_tahunan')->with('kodeHarta')->firstOrFail();

        $jalur = [
            '/penghasilan/'.Penghasilan::whereNull('id_draf_bulanan')->value('id_penghasilan') => ['nominal' => 'abc'],
            "/harta/{$harta->kodeHarta->kategori}/{$harta->id_harta}" => ['nilai' => 'abc', 'tahun' => 'abc'],
            '/utang/'.Utang::whereNull('id_draf_tahunan')->value('id_utang') => ['saldo' => 'abc', 'tahun_peminjaman' => 'abc'],
        ];

        foreach ($jalur as $uri => $data) {
            $pesan = $this->put($uri, $data)
                ->assertSessionHasErrors()
                ->getSession()
                ->get('errors')
                ->getBag('default')
                ->all();

            foreach ($pesan as $baris) {
                foreach (self::KATA_INGGRIS as $kata) {
                    $this->assertStringNotContainsStringIgnoringCase(
                        $kata, $baris, "Pesan dari $uri masih berbahasa Inggris: \"$baris\""
                    );
                }
            }
        }
    }

    /**
     * Halaman kesalahan bawaan Laravel memakai __(), jadi kalimatnya datang
     * dari lang/id.json.
     */
    public function test_halaman_kesalahan_berbahasa_indonesia(): void
    {
        $this->assertSame('Halaman Tidak Ditemukan', __('Not Found'));
        $this->assertSame('Akses Ditolak', __('Forbidden'));
        $this->assertSame('Halaman Kedaluwarsa', __('Page Expired'));
        $this->assertSame('Terjadi Kesalahan pada Server', __('Server Error'));

        // Kalimat bawaan untuk masuk dan penyetelan ulang kata sandi ikut
        // diterjemahkan, supaya jalur yang belum ada pun tidak lahir Inggris.
        $this->assertStringNotContainsString('credentials', trans('auth.failed'));
        $this->assertStringNotContainsString('Previous', trans('pagination.previous'));
        $this->assertStringNotContainsString('password', trans('passwords.sent'));
    }

    /**
     * Nama bulan dan hari ikut bahasa aplikasi, bukan bawaan PHP.
     */
    public function test_tanggal_berbahasa_indonesia(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('01 November 2026', tanggal_id('2026-11-01'));
        $this->assertStringContainsString('Minggu', now()->parse('2026-11-01')->translatedFormat('l'));
    }
}
