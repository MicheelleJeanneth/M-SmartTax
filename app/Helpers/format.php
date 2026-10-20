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

if (! function_exists('tanggal_singkat')) {
    /** 03 Jun 2026 */
    function tanggal_singkat(DateTimeInterface|string|null $tanggal): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        return Carbon::parse($tanggal)->translatedFormat('d M Y');
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

if (! function_exists('telepon_angka')) {
    /**
     * Nomor handphone dalam bentuk bakunya: hanya angka, berawalan 0.
     *
     * Nomor yang disalin dari kontak sering datang sebagai "+62 812-3456-7890"
     * atau "0812 3456 7890". Semuanya diseragamkan di sini supaya yang
     * tersimpan di basis data selalu satu bentuk.
     */
    function telepon_angka(?string $nomor): string
    {
        $angka = preg_replace('/\D/', '', (string) $nomor);

        return str_starts_with($angka, '62') ? '0'.substr($angka, 2) : $angka;
    }
}

if (! function_exists('telepon')) {
    /**
     * 0812 3456 7890 — dikelompokkan 4-4-sisa, cara nomor Indonesia dibaca.
     *
     * Spasinya dibubuhkan saat ditampilkan, bukan saat diketik: panjang nomor
     * Indonesia berkisar 10 sampai 13 angka, jadi pengelompokan yang kaku akan
     * pecah di nomor yang lebih pendek atau lebih panjang.
     */
    function telepon(?string $nomor): string
    {
        $angka = telepon_angka($nomor);

        if ($angka === '') {
            return '-';
        }

        return implode(' ', array_filter([
            substr($angka, 0, 4),
            substr($angka, 4, 4),
            substr($angka, 8),
        ], 'strlen'));
    }
}

if (! function_exists('titik')) {
    /**
     * Menutup kalimat keterangan dengan titik bila belum ada.
     *
     * Dipakai semua teks bantuan di bawah kolom isian supaya tidak perlu
     * diingat satu per satu saat menulis teks baru. Tanda baca penutup lain
     * seperti tanda tanya dibiarkan apa adanya.
     */
    function titik(?string $teks): ?string
    {
        $bersih = trim((string) $teks);

        if ($bersih === '') {
            return $teks;
        }

        return str_contains('.!?:', mb_substr($bersih, -1)) ? $bersih : $bersih.'.';
    }
}

if (! function_exists('tanggal_isian')) {
    /**
     * Tanggal dalam bentuk Y-m-d untuk kolom isian bertipe date.
     */
    function tanggal_isian(DateTimeInterface|string|null $tanggal): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '';
        }

        return $tanggal instanceof DateTimeInterface
            ? $tanggal->format('Y-m-d')
            : substr($tanggal, 0, 10);
    }
}
