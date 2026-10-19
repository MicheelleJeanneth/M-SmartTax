<?php

namespace App\Services;

use App\Models\DrafTahunan;
use App\Models\Harta;
use App\Models\Penghasilan;
use App\Models\Utang;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;

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

    /**
     * Tahun pajak berjalan.
     *
     * Masih diambil dari tetapan supaya data contoh yang berpusat di 2026
     * tetap utuh saat didemokan. Begitu aplikasinya dipakai sungguhan,
     * nilainya diganti dengan tahun kalender sekarang.
     */
    public function tahunBerjalan(): int
    {
        return MockData::TAHUN;
    }

    /**
     * Tahun terkecil dari penghasilan, harta, utang, dan draf tahunan.
     */
    private function tahunTerlama(): int
    {
        $profil = PenggunaSaatIni::id();

        $tahun = collect([
            $this->tahunTerlamaPenghasilan(),
            Harta::query()->where('id_profil', $profil)->min('tahun_perolehan'),
            Utang::query()->where('id_profil', $profil)->min('tahun_peminjaman'),
            DrafTahunan::query()->where('id_profil', $profil)->min('tahun_pajak'),
        ])->filter();

        return (int) $tahun->min();
    }

    /**
     * Tahun transaksi penghasilan paling awal; tahun berjalan bila belum ada catatan.
     */
    private function tahunTerlamaPenghasilan(): int
    {
        $paling = Penghasilan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->min('tanggal');

        return $paling === null
            ? $this->tahunBerjalan()
            : min((int) substr((string) $paling, 0, 4), $this->tahunBerjalan());
    }
}
