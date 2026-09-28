<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Setelah masuk, kelengkapan profil diperiksa: belum lengkap menuju Lengkapi Profil,
     * sudah lengkap menuju Dashboard.
     *
     * Mockup: belum ada autentikasi, kelengkapan profil dibaca dari session.
     */
    public function store(Request $request): RedirectResponse
    {
        return $request->session()->get('profil_lengkap', true) === false
            ? redirect()->route('profil.lengkapi')
            : redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('profil_lengkap');

        return redirect()->route('login');
    }
}
