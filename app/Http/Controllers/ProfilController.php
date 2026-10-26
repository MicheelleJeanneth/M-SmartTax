<?php

namespace App\Http\Controllers;

use App\Models\Profil;
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
        $profil = $request->user();

        $profil->update([...$this->sahkan($request, $profil), 'profil_lengkap' => true]);

        return redirect()->route('dashboard')->with('sukses', 'Profil tersimpan. Selamat datang di M-SmartTax.');
    }

    public function show(): View
    {
        return view('profil.show', ['profil' => $this->profil()]);
    }

    public function edit(): View
    {
        return view('profil.edit', ['profil' => $this->profil()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profil = $request->user();

        $profil->update($this->sahkan($request, $profil, denganEmail: true));

        return redirect()->route('profil.show')->with('sukses', 'Perubahan profil tersimpan.');
    }

    public function kataSandi(): View
    {
        return view('profil.kata-sandi');
    }

    public function simpanKataSandi(Request $request): RedirectResponse
    {
        $sah = $request->validate([
            // current_password membandingkan dengan kata sandi pengguna yang
            // sedang masuk, jadi tidak ada perbandingan acakan di sini.
            'kata_sandi_lama' => ['required', 'current_password'],
            'kata_sandi' => ['required', 'min:8', 'confirmed', 'different:kata_sandi_lama'],
        ], [
            'kata_sandi_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'kata_sandi_lama.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'kata_sandi.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Ulangi kata sandi tidak sama dengan kata sandi baru.',
            'kata_sandi.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        // Cast 'hashed' pada model yang mengacaknya.
        $request->user()->update(['password' => $sah['kata_sandi']]);

        return redirect()->route('profil.show')->with('sukses', 'Kata sandi berhasil diubah.');
    }

    /**
     * Memeriksa isian data diri dan alamat, dipakai bersama oleh Lengkapi
     * Profil dan Ubah Profil.
     *
     * NIK tercetak pada setiap dokumen laporan, karena itu diperiksa silang
     * dengan tanggal lahir dan jenis kelamin sebelum tersimpan. Pemeriksaan
     * ini menggantikan penguncian: salah ketik tertangkap lebih awal, dan
     * yang terlanjur salah tetap dapat diperbaiki.
     *
     * Email hanya diperiksa bila $denganEmail bernilai benar, yaitu pada Ubah
     * Profil, satu-satunya halaman yang memuat kolomnya. Tanpa pemisahan ini
     * Lengkapi Profil akan mengosongkan email yang sudah diisi saat mendaftar.
     *
     * @return array<string, mixed>
     */
    private function sahkan(Request $request, Profil $profil, bool $denganEmail = false): array
    {
        // Nomor yang ditempel dari kontak boleh berisi spasi, tanda hubung,
        // atau awalan +62. Semuanya diseragamkan dulu, baru diperiksa.
        $request->merge(['nomor_handphone' => telepon_angka($request->input('nomor_handphone'))]);

        $aturanEmail = $denganEmail ? [
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('profil', 'email')->ignore($profil->id_profil, 'id_profil'),
            ],
        ] : [];

        $sah = $request->validate([
            ...$aturanEmail,
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('profil', 'nik')->ignore($profil->id_profil, 'id_profil'),
                new NikCocokKelahiran(
                    $request->input('tanggal_lahir'),
                    $request->input('jenis_kelamin'),
                ),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', Rule::in(['Pria', 'Wanita'])],
            // Nomor handphone Indonesia: berawalan 08, seluruhnya 10 sampai 13 angka.
            'nomor_handphone' => ['required', 'regex:/^08\d{8,11}$/'],
            'alamat' => ['required', 'string', 'max:255'],
            'rt' => ['required', 'digits_between:1,3'],
            'rw' => ['required', 'digits_between:1,3'],
            'kelurahan' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:255'],
            'provinsi' => ['required', 'string', 'max:255'],
        ], [
            'email.email' => 'Format email tidak sah.',
            'email.unique' => 'Email ini sudah terdaftar pada akun lain.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 angka.',
            'nik.unique' => 'NIK ini sudah terdaftar pada akun lain.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh hari ini atau setelahnya.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'nomor_handphone.required' => 'Nomor handphone wajib diisi.',
            'nomor_handphone.regex' => 'Nomor handphone diawali 08 dan terdiri dari 10 sampai 13 angka.',
            'alamat.required' => 'Alamat wajib diisi.',
            'rt.required' => 'RT wajib diisi.',
            'rt.digits_between' => 'RT maksimal 3 angka.',
            'rw.required' => 'RW wajib diisi.',
            'rw.digits_between' => 'RW maksimal 3 angka.',
            'kelurahan.required' => 'Kelurahan wajib diisi.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'kota.required' => 'Kota wajib diisi.',
            'provinsi.required' => 'Provinsi wajib diisi.',
        ]);

        // Kewarganegaraan dan negara tidak diambil dari kiriman pengguna:
        // aplikasi ini hanya melayani wajib pajak WNI dalam negeri, dan kedua
        // kolomnya tampil terkunci pada formulir.
        $hasil = [
            ...$sah,
            'kewarganegaraan' => MockData::KEWARGANEGARAAN,
            'negara' => 'Indonesia',
        ];

        // Kolom email unik, jadi yang dikosongkan harus tersimpan sebagai null
        // dan bukan sebagai untaian kosong.
        if ($denganEmail) {
            $hasil['email'] = $sah['email'] ?? null;
        }

        return $hasil;
    }

    private function profil(): Profil
    {
        return request()->user();
    }
}
