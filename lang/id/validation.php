<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pesan Kesalahan Validasi
|--------------------------------------------------------------------------
|
| Pesan bawaan Laravel berbahasa Inggris. Berkas ini menerjemahkan aturan
| yang dipakai aplikasi ini, sehingga kolom yang belum punya pesan khusus di
| pengendalinya pun tetap berbahasa Indonesia.
|
| Aturan yang tidak tercantum di sini jatuh ke fallback_locale, jadi
| menambahkannya cukup dengan menulis barisnya, tanpa mengubah apa pun lagi.
|
*/

return [
    'accepted' => ':attribute harus disetujui.',
    'after' => ':attribute harus setelah :date.',
    'after_or_equal' => ':attribute tidak boleh sebelum :date.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus sebelum :date.',
    'before_or_equal' => ':attribute tidak boleh setelah :date.',
    'between' => [
        'array' => ':attribute harus berisi antara :min sampai :max item.',
        'file' => ':attribute harus berukuran antara :min sampai :max kilobita.',
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'string' => ':attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => ':attribute hanya dapat bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':attribute bukan tanggal yang sah.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
    'email' => ':attribute harus berupa alamat surel yang sah.',
    'exists' => ':attribute yang dipilih tidak dikenali.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih panjang dari :value karakter.',
    ],
    'gte' => [
        'numeric' => ':attribute tidak boleh kurang dari :value.',
        'string' => ':attribute minimal :value karakter.',
    ],
    'in' => ':attribute yang dipilih tidak sah.',
    'integer' => ':attribute harus berupa angka.',
    'lt' => [
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string' => ':attribute harus lebih pendek dari :value karakter.',
    ],
    'lte' => [
        'numeric' => ':attribute tidak boleh lebih dari :value.',
        'string' => ':attribute maksimal :value karakter.',
    ],
    'max' => [
        'array' => ':attribute tidak boleh berisi lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute harus berisi sedikitnya :min item.',
        'file' => ':attribute harus berukuran sedikitnya :min kilobita.',
        'numeric' => ':attribute tidak boleh kurang dari :min.',
        'string' => ':attribute harus terdiri dari sedikitnya :min karakter.',
    ],
    'not_in' => ':attribute yang dipilih tidak sah.',
    'numeric' => ':attribute harus berupa angka.',
    'regex' => 'Format :attribute tidak sesuai.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi bila :other bernilai :value.',
    'required_with' => ':attribute wajib diisi bila ada :values.',
    'same' => ':attribute dan :other harus sama.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => ':attribute harus berukuran :size kilobita.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus terdiri dari :size karakter.',
    ],
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',
    'url' => ':attribute harus berupa tautan yang sah.',

    /*
    |--------------------------------------------------------------------------
    | Nama Kolom
    |--------------------------------------------------------------------------
    |
    | Tanpa daftar ini, pesannya menyebut nama kolom apa adanya, misalnya
    | "tahun_pelepasan". Nama yang dipakai di sini sama dengan label pada
    | formulirnya, supaya pengguna tahu kotak mana yang dimaksud.
    |
    */
    'attributes' => [
        'alamat' => 'Alamat',
        'bulan' => 'Bulan pajak',
        'cicilan_bulanan' => 'Cicilan per bulan',
        'deskripsi' => 'Deskripsi',
        'email' => 'Email',
        'harga_aset' => 'Harga aset',
        'jangka_waktu' => 'Jangka waktu',
        'kecamatan' => 'Kecamatan',
        'kelurahan' => 'Kelurahan',
        'keterangan' => 'Keterangan',
        'kode' => 'Kode',
        'kota' => 'Kota',
        'nama' => 'Nama',
        'nama_aset' => 'Nama aset',
        'nama_kreditur' => 'Nama kreditur',
        'nilai' => 'Nilai',
        'nilai_kini' => 'Nilai saat ini',
        'nominal' => 'Nominal',
        'nomor_handphone' => 'Nomor handphone',
        'password' => 'Kata sandi',
        'provinsi' => 'Provinsi',
        'saldo' => 'Saldo',
        'tahun' => 'Tahun',
        'tahun_peminjaman' => 'Tahun peminjaman',
        'tahun_pelepasan' => 'Tahun pelepasan',
        'tahun_pelunasan' => 'Tahun pelunasan',
        'tanggal' => 'Tanggal',
        'tanggal_lahir' => 'Tanggal lahir',
        'tempat_lahir' => 'Tempat lahir',
        'username' => 'Username',
    ],
];
