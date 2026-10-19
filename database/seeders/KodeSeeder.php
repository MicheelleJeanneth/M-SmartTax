<?php

namespace Database\Seeders;

use App\Models\KodeHarta;
use App\Models\KodeUtang;
use App\Support\MockData;
use Illuminate\Database\Seeder;

/**
 * Kode harta dan utang resmi. Daftarnya dibaca dari MockData sebagai sumber
 * isi tabel; formulir dan laporan selanjutnya membaca dari tabel ini.
 */
class KodeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(MockData::kategoriHarta()) as $kategori) {
            foreach (MockData::kodeHarta($kategori) as $kode => $nama) {
                KodeHarta::updateOrCreate(
                    ['kode' => (string) $kode],
                    ['kategori' => $kategori, 'nama_kode' => $nama],
                );
            }
        }

        foreach (MockData::kodeUtang() as $kode => $nama) {
            KodeUtang::updateOrCreate(
                ['kode' => (string) $kode],
                ['nama_kode' => $nama],
            );
        }
    }
}
