<?php

namespace Database\Seeders;

use App\Models\KodeHarta;
use App\Models\KodeUtang;
use App\Support\MockData;
use Illuminate\Database\Seeder;

/**
 * Kode harta dan utang resmi. Daftarnya dibaca dari MockData, yang sampai
 * sekarang menjadi satu-satunya sumber kode yang dipakai formulir.
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
                // Daftar aslinya memuat kodenya di depan; yang disimpan hanya namanya.
                ['nama_kode' => trim(preg_replace('/^\d+\s*–\s*/u', '', $nama))],
            );
        }
    }
}
