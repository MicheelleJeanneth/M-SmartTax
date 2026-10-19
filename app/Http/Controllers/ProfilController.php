<?php

namespace App\Http\Controllers;

use App\Rules\NikCocokKelahiran;
use App\Support\PenggunaSaatIni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $this->sahkanNik($request);

        $request->session()->put('profil_lengkap', true);

        return redirect()->route('dashboard')->with('sukses', 'Profil tersimpan. Selamat datang di M-SmartTax.');
    }

    public function show(): View
    {
        return view('profil.show', ['profil' => PenggunaSaatIni::profil()]);
    }

    public function edit(): View
    {
        return view('profil.edit', ['profil' => PenggunaSaatIni::profil()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->sahkanNik($request);

        return redirect()->route('profil.show')->with('sukses', 'Perubahan profil tersimpan.');
    }

    public function kataSandi(): View
    {
        return view('profil.kata-sandi');
    }

    /**
     * Mockup: kata sandi belum benar-benar disimpan, tetapi aturannya sudah
     * ditegakkan agar sama dengan yang berlaku nanti.
     */
    public function simpanKataSandi(Request $request): RedirectResponse
    {
        $request->validate([
            'kata_sandi_lama' => ['required'],
            'kata_sandi' => ['required', 'min:8', 'confirmed', 'different:kata_sandi_lama'],
        ], [
            'kata_sandi_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'kata_sandi.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Ulangi kata sandi tidak sama dengan kata sandi baru.',
            'kata_sandi.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        return redirect()->route('profil.show')->with('sukses', 'Kata sandi berhasil diubah.');
    }

    /**
     * NIK tercetak pada setiap dokumen laporan, karena itu diperiksa silang
     * dengan tanggal lahir dan jenis kelamin sebelum tersimpan. Pemeriksaan
     * ini menggantikan penguncian: salah ketik tertangkap lebih awal, dan
     * yang terlanjur salah tetap dapat diperbaiki.
     */
    private function sahkanNik(Request $request): void
    {
        $request->validate([
            'nik' => ['required', 'digits:16', new NikCocokKelahiran(
                $request->input('tanggal_lahir'),
                $request->input('jenis_kelamin'),
            )],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', Rule::in(['Pria', 'Wanita'])],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh hari ini atau setelahnya.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
        ]);
    }
}
