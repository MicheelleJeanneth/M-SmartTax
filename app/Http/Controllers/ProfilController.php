<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function lengkapi(): View
    {
        return view('profil.lengkapi');
    }

    /**
     * Setelah disimpan, profil_lengkap menjadi benar dan pengguna ke Dashboard (panduan 8.2).
     */
    public function simpanLengkapi(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard')->with('sukses', 'Profil tersimpan. Selamat datang di M-SmartTax.');
    }

    public function show(): View
    {
        return view('profil.show', ['profil' => MockData::profil()]);
    }

    public function edit(): View
    {
        return view('profil.edit', ['profil' => MockData::profil()]);
    }

    public function update(Request $request): RedirectResponse
    {
        return redirect()->route('profil.show')->with('sukses', 'Perubahan profil tersimpan.');
    }
}
