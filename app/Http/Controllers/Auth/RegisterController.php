<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profil;
use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Akun baru tersimpan dengan profil_lengkap bernilai false, lalu pengguna
     * diarahkan ke halaman masuk beserta pesan sukses (panduan 8.1).
     */
    public function store(Request $request): RedirectResponse
    {
        $sah = $request->validate([
            // Email dibandingkan dengan seluruh baris, termasuk akun yang
            // dinonaktifkan, karena kolomnya unik di basis data.
            'email' => ['required', 'email', 'max:255', Rule::unique('profil', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak sah.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi tidak sama dengan kata sandi baru.',
        ]);

        Profil::create([
            'email' => $sah['email'],
            // Cast 'hashed' pada model yang mengacaknya, bukan controller ini.
            'password' => $sah['password'],
            'profil_lengkap' => false,
            'kewarganegaraan' => MockData::KEWARGANEGARAAN,
            'negara' => 'Indonesia',
            'status' => true,
        ]);

        return redirect()->route('login')->with('sukses', 'Akun berhasil dibuat. Silakan masuk.');
    }
}
