<?php

namespace App\Services;

use App\Support\MockData;
use Illuminate\Support\Carbon;

/**
 * Daftar tahun untuk filter di Dashboard, Draf Bulanan, Draf Tahunan, dan Laporan.
 *
 * Rentangnya dari tahun data terlama milik pengguna sampai tahun berjalan,
 * tanpa tahun yang dilewati, diurutkan dari yang terbaru.
 */
class FilterTahun
{
    /**
     * @return array<int, int>
     */
    public function daftar(): array
    {
        return range($this->tahunBerjalan(), $this->tahunTerlama());
    }

    /**
     * Tahun dari query string. Bila kosong atau di luar rentang, dipakai tahun berjalan.
     */
    public function pilih(mixed $tahun): int
    {
        $bersih = is_string($tahun) || is_int($tahun) ? (string) $tahun : '';

        if (preg_match('/^\d{4}$/', $bersih) !== 1) {
            return $this->tahunBerjalan();
        }

        return in_array((int) $bersih, $this->daftar(), true)
            ? (int) $bersih
            : $this->tahunBerjalan();
    }

    public function tahunBerjalan(): int
    {
        return MockData::TAHUN;
    }

    /**
     * Tahun terkecil dari penghasilan, keenam kategori harta, utang, draf bulanan, dan draf tahunan.
     */
    private function tahunTerlama(): int
    {
        $tahun = collect(MockData::penghasilan())
            ->map(fn (array $baris): int => (int) Carbon::parse($baris['tanggal'])->year);

        foreach (array_keys(MockData::kategoriHarta()) as $kategori) {
            $tahun = $tahun->merge(collect(MockData::harta($kategori))->pluck('tahun'));
        }

        $tahun = $tahun
            ->merge(collect(MockData::utang())->pluck('tahun'))
            ->merge(collect(MockData::drafTahunan())->pluck('tahun'))
            ->push($this->tahunBerjalan())
            ->filter();

        return (int) $tahun->min();
    }
}
