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
        $request->merge(['username' => Profil::bakukanUsername($request->input('username'))]);

        // Username dan email dibandingkan dengan seluruh baris, termasuk akun
        // yang dinonaktifkan, karena kedua kolomnya unik di basis data.
        $sah = $request->validate([
            'username' => [
                'required',
                'string',
                'between:3,20',
                'regex:'.Profil::POLA_USERNAME,
                Rule::unique('profil', 'username'),
            ],
            // Email tidak wajib: tidak semua wajib pajak memilikinya, dan
            // sistem tidak pernah mengirim surel ke alamat tersebut.
            'email' => ['nullable', 'email', 'max:255', Rule::unique('profil', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.between' => 'Username terdiri dari 3 sampai 20 karakter.',
            'username.regex' => 'Username diawali huruf, diakhiri huruf atau angka, dan hanya boleh berisi huruf, angka, titik, serta garis bawah.',
            'username.unique' => 'Username ini sudah dipakai. Silakan pilih yang lain.',
            'email.email' => 'Format email tidak sah.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi tidak sama dengan kata sandi baru.',
        ]);

        Profil::create([
            'username' => $sah['username'],
            // Kolom email unik, jadi yang dikosongkan harus tersimpan sebagai
            // null dan bukan sebagai untaian kosong.
            'email' => $sah['email'] ?? null,
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
