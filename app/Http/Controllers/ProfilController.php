<?php

namespace App\Http\Controllers;

use App\Rules\NikCocokKelahiran;
use App\Support\MockData;
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
        return view('profil.show', ['profil' => MockData::profil()]);
    }

    public function edit(): View
    {
        return view('profil.edit', ['profil' => MockData::profil()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->sahkanNik($request);

        return redirect()->route('profil.show')->with('sukses', 'Perubahan profil tersimpan.');
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
