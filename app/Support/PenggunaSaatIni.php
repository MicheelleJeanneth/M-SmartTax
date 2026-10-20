<?php

namespace App\Support;

use App\Models\Profil;

/**
 * Wajib pajak yang sedang memakai aplikasi.
 *
 * Seluruh service dan controller membaca pengguna aktif dari sini, bukan dari
 * auth() langsung, supaya hanya satu berkas yang perlu diubah bila sumbernya
 * berganti.
 */
class PenggunaSaatIni
{
    private static ?Profil $ingatan = null;

    /**
     * Pada permintaan HTTP, selalu pengguna yang sedang masuk.
     *
     * Di luar permintaan HTTP — seeder, perintah artisan, tinker — belum ada
     * sesi yang masuk, jadi dipakai profil pertama supaya skrip pemeriksaan
     * tetap bisa berjalan. Jalan mundur itu sengaja dibatasi ke konsol:
     * menebak pengguna pada permintaan web berarti membocorkan data orang lain
     * bila suatu halaman luput dari middleware 'auth'.
     */
    public static function profil(): ?Profil
    {
        if (auth()->check()) {
            return auth()->user();
        }

        if (! app()->runningInConsole()) {
            return null;
        }

        return self::$ingatan ??= Profil::query()->first();
    }

    public static function id(): ?int
    {
        return self::profil()?->id_profil;
    }

    /** Dipakai tes yang berganti pengguna di tengah jalan. */
    public static function lupakan(): void
    {
        self::$ingatan = null;
    }
}
