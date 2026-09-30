<?php

namespace App\Services;

use App\Support\MockData;
use Illuminate\Support\Carbon;

/**
 * Daftar tahun untuk filter di Dashboard, Data Penghasilan, Draf Bulanan,
 * Draf Tahunan, dan Laporan.
 *
 * Rentangnya dari tahun data terlama milik pengguna sampai tahun berjalan,
 * tanpa tahun yang dilewati, diurutkan dari yang terbaru. Tahun sebelum
 * pengguna mulai mencatat tidak pernah muncul.
 */
class FilterTahun
{
    /**
     * Seluruh tahun yang punya data apa pun: penghasilan, harta, utang, atau draf.
     *
     * @return array<int, int>
     */
    public function daftar(): array
    {
        return range($this->tahunBerjalan(), $this->tahunTerlama());
    }

    /**
     * Khusus filter Data Penghasilan: mulai tahun transaksi penghasilan
     * pertama sampai tahun berjalan.
     *
     * @return array<int, int>
     */
    public function daftarPenghasilan(): array
    {
        return range($this->tahunBerjalan(), $this->tahunTerlamaPenghasilan());
    }

    /**
     * Tahun dari query string. Bila kosong atau di luar rentang, dipakai tahun berjalan.
     *
     * @param  array<int, int>|null  $daftar  Rentang yang dianggap sah; bawaannya seluruh tahun data.
     */
    public function pilih(mixed $tahun, ?array $daftar = null): int
    {
        $bersih = is_string($tahun) || is_int($tahun) ? (string) $tahun : '';

        if (preg_match('/^\d{4}$/', $bersih) !== 1) {
            return $this->tahunBerjalan();
        }

        return in_array((int) $bersih, $daftar ?? $this->daftar(), true)
            ? (int) $bersih
            : $this->tahunBerjalan();
    }

    public function tahunBerjalan(): int
    {
        return MockData::TAHUN;
    }

    /**
     * Tahun terkecil dari penghasilan, keenam kategori harta, utang, dan draf tahunan.
     */
    private function tahunTerlama(): int
    {
        $tahun = collect([$this->tahunTerlamaPenghasilan()]);

        foreach (array_keys(MockData::kategoriHarta()) as $kategori) {
            $tahun = $tahun->merge(collect(MockData::harta($kategori))->pluck('tahun'));
        }

        $tahun = $tahun
            ->merge(collect(MockData::utang())->pluck('tahun'))
            ->merge(collect(MockData::drafTahunan())->pluck('tahun'))
            ->filter();

        return (int) $tahun->min();
    }

    /**
     * Tahun transaksi penghasilan paling awal; tahun berjalan bila belum ada catatan.
     */
    private function tahunTerlamaPenghasilan(): int
    {
        return (int) collect(MockData::penghasilan())
            ->map(fn (array $baris): int => (int) Carbon::parse($baris['tanggal'])->year)
            ->push($this->tahunBerjalan())
            ->min();
    }
}
