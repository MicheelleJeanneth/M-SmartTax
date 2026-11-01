<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pesan Autentikasi
|--------------------------------------------------------------------------
|
| LoginController menyusun pesannya sendiri, tetapi baris-baris ini tetap
| disediakan supaya tidak ada kalimat bawaan Laravel yang berbahasa Inggris
| bila suatu saat dipakai, misalnya saat pembatasan percobaan masuk dinyalakan.
|
*/

return [
    'failed' => 'Username atau kata sandi tidak sesuai.',
    'password' => 'Kata sandi salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',
];
