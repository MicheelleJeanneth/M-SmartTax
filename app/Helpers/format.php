<?php

use Illuminate\Support\Carbon;

/*
 |---------------------------------------------------------------------------
 | Pembantu penulisan angka dan tanggal (panduan bagian 6)
 |---------------------------------------------------------------------------
 | Didaftarkan lewat composer.json bagian autoload.files.
 */

if (! function_exists('rupiah')) {
    /** Rp 4.800.000.000 */
    function rupiah(int|float|null $nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 0, ',', '.');
    }
}

if (! function_exists('persen')) {
    /** 0,5% */
    function persen(int|float|null $nilai, int $desimal = 1): string
    {
        return number_format((float) $nilai, $desimal, ',', '.').'%';
    }
}

if (! function_exists('angka')) {
    /** 1.234.567 tanpa awalan Rp */
    function angka(int|float|null $nilai, int $desimal = 0): string
    {
        return number_format((float) $nilai, $desimal, ',', '.');
    }
}

if (! function_exists('tanggal_id')) {
    /** 15 Agustus 2026 */
    function tanggal_id(DateTimeInterface|string|null $tanggal): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        return Carbon::parse($tanggal)->translatedFormat('d F Y');
    }
}

if (! function_exists('terbilang')) {
    /** "empat juta delapan ratus ribu rupiah" — dipakai pada kotak hasil draf. */
    function terbilang(int|float|null $nilai): string
    {
        $nilai = (int) abs((float) $nilai);

        $satuan = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
        ];

        $ubah = function (int $n) use (&$ubah, $satuan): string {
            if ($n < 12) {
                return $satuan[$n];
            }
            if ($n < 20) {
                return $ubah($n - 10).' belas';
            }
            if ($n < 100) {
                return $ubah(intdiv($n, 10)).' puluh '.$ubah($n % 10);
            }
            if ($n < 200) {
                return 'seratus '.$ubah($n - 100);
            }
            if ($n < 1000) {
                return $ubah(intdiv($n, 100)).' ratus '.$ubah($n % 100);
            }
            if ($n < 2000) {
                return 'seribu '.$ubah($n - 1000);
            }
            if ($n < 1_000_000) {
                return $ubah(intdiv($n, 1000)).' ribu '.$ubah($n % 1000);
            }
            if ($n < 1_000_000_000) {
                return $ubah(intdiv($n, 1_000_000)).' juta '.$ubah($n % 1_000_000);
            }

            return $ubah(intdiv($n, 1_000_000_000)).' miliar '.$ubah($n % 1_000_000_000);
        };

        $hasil = trim(preg_replace('/\s+/', ' ', $ubah($nilai)));

        return ($hasil === '' ? 'nol' : $hasil).' rupiah';
    }
}
