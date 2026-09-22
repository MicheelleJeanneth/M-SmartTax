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
     * Setelah registrasi berhasil, pengguna diarahkan ke login (panduan 8.1).
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('login')->with('sukses', 'Akun berhasil dibuat. Silakan masuk.');
    }
}
