<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Akun baru tersimpan dengan profil_lengkap bernilai false, lalu pengguna
     * diarahkan ke halaman login beserta pesan sukses.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->session()->put('profil_lengkap', false);

        return redirect()->route('login')->with('sukses', 'Akun berhasil dibuat. Silakan masuk.');
    }
}
