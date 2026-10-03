<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class Controller
{
    /** Banyak baris per halaman pada tabel daftar. */
    protected const PER_HALAMAN = 10;

    /**
     * Memotong koleksi menjadi satu halaman. Query string ikut terbawa ke
     * tautan halaman berikutnya agar pencarian dan filter tidak hilang.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function halaman(Collection $baris, Request $request): LengthAwarePaginator
    {
        $halaman = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $baris->forPage($halaman, static::PER_HALAMAN)->values(),
            $baris->count(),
            static::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
