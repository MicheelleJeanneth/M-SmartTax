<?php

namespace App\Http\Controllers;

use App\Services\Pengingat;
use Illuminate\Http\RedirectResponse;

class PengingatController extends Controller
{
    public function __construct(private readonly Pengingat $pengingat) {}

    /**
     * Menekan pengingat menandainya sudah dibaca lalu membuka halaman terkait.
     */
    public function buka(string $jenis): RedirectResponse
    {
        $this->pengingat->tandaiDibaca($jenis);

        return redirect()->route($this->pengingat->rute($jenis));
    }
}
