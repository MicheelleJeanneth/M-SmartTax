<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Memastikan tanggal lahir yang tersandi di dalam NIK cocok dengan kolom
 * Tanggal Lahir dan Jenis Kelamin pada formulir yang sama.
 *
 * Susunan NIK: PP KK CC DDMMYY NNNN. Digit ke-7 sampai ke-12 adalah tanggal
 * lahir, dengan tanggal ditambah 40 untuk perempuan. Pemeriksaan silang ini
 * menangkap salah ketik sebelum NIK tercetak pada dokumen laporan.
 */
class NikCocokKelahiran implements ValidationRule
{
    /** Penambah tanggal pada NIK perempuan. */
    private const GESER_WANITA = 40;

    public function __construct(
        private ?string $tanggalLahir,
        private ?string $jenisKelamin,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Panjang dan jenis karakter sudah diperiksa aturan lain. Tanpa tanggal
        // lahir yang sah, tidak ada yang bisa dibandingkan.
        if (! is_string($value) || ! preg_match('/^\d{16}$/', $value) || ! $this->tanggalLahir) {
            return;
        }

        $lahir = $this->tanggalLahir();

        if ($lahir === null) {
            return;
        }

        $tanggal = (int) substr($value, 6, 2);
        $bulan = (int) substr($value, 8, 2);
        $tahun = (int) substr($value, 10, 2);
        $wanita = $tanggal > self::GESER_WANITA;

        if ($wanita) {
            $tanggal -= self::GESER_WANITA;
        }

        if ($tanggal !== $lahir->day || $bulan !== $lahir->month || $tahun !== $lahir->year % 100) {
            $fail('Tanggal lahir pada NIK ('.sprintf('%02d/%02d/%02d', $tanggal, $bulan, $tahun).') tidak cocok dengan kolom Tanggal Lahir. Periksa kembali keduanya.');

            return;
        }

        if ($this->jenisKelamin !== null && $wanita !== ($this->jenisKelamin === 'Wanita')) {
            $fail('NIK menunjukkan jenis kelamin '.($wanita ? 'wanita' : 'pria').', berbeda dengan pilihan pada kolom Jenis Kelamin.');
        }
    }

    private function tanggalLahir(): ?Carbon
    {
        try {
            return Carbon::parse($this->tanggalLahir);
        } catch (\Throwable) {
            return null;
        }
    }
}
