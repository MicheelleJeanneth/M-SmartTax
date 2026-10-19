<?php

namespace App\Support;

use App\Models\Profil;

/**
 * Wajib pajak yang sedang memakai aplikasi.
 *
 * Selama autentikasi belum dipasang, dipakai satu-satunya profil yang ada di
 * basis data. Begitu login sungguhan menyala, cukup berkas ini yang diubah —
 * seluruh service dan controller membacanya dari sini.
 */
class PenggunaSaatIni
{
    private static ?Profil $ingatan = null;

    public static function profil(): ?Profil
    {
        if (auth()->check()) {
            return auth()->user();
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
