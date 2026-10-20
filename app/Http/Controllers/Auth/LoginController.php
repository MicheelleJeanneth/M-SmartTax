<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Setelah masuk, kelengkapan profil diperiksa: belum lengkap menuju
     * Lengkapi Profil, sudah lengkap menuju Dashboard.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $sah = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak sah.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Akun yang dinonaktifkan tersaring sendiri oleh lingkup global 'aktif'
        // pada model Profil, jadi tidak perlu diperiksa lagi di sini.
        if (! Auth::attempt($sah, $request->boolean('remember'))) {
            // Pesannya tidak menyebut bagian mana yang salah, supaya tidak
            // membocorkan email mana yang terdaftar.
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        // Mencegah pembajakan sesi: pengenal sesi lama tidak boleh dipakai lagi.
        $request->session()->regenerate();

        return $request->user()->profil_lengkap
            ? redirect()->intended(route('dashboard'))
            : redirect()->route('profil.lengkapi');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
