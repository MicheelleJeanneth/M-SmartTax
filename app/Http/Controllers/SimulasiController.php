<?php

namespace App\Http\Controllers;

use App\Services\SimulasiAset;
use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SimulasiController extends Controller
{
    public function __construct(private SimulasiAset $simulasi) {}

    /**
     * Kartu kondisi keuangan dan formulir rencana, belum ada hasil.
     */
    public function index(Request $request): View
    {
        $peraga = $this->simulasi->pakaiPeraga($request->query('peraga'));

        return view('simulasi.index', [
            'tahun' => MockData::TAHUN,
            'peraga' => $peraga,
            'kondisi' => $this->simulasi->kondisi(MockData::TAHUN),
            'rencana' => SimulasiAset::RENCANA_BAWAAN,
            'hasil' => null,
            'dampak' => null,
        ]);
    }

    /**
     * Menghitung kelayakan. Tidak satu pun hasilnya disimpan.
     */
    public function hitung(Request $request): View
    {
        $tahun = MockData::TAHUN;
        $peraga = $this->simulasi->pakaiPeraga($request->input('peraga'));
        $rencana = $this->sahkan($request);
        $kondisi = $this->simulasi->kondisi($tahun);

        $pokok = $rencana['harga_aset'] - $rencana['uang_muka'];
        $cicilan = $this->simulasi->hitungCicilan($pokok, $rencana['jangka_waktu'], $rencana['suku_bunga']);

        return view('simulasi.index', [
            'tahun' => $tahun,
            'peraga' => $peraga,
            'kondisi' => $kondisi,
            'rencana' => $rencana,
            'pokok' => $pokok,
            'hasil' => $this->simulasi->hitungKelayakan($kondisi, $cicilan, $pokok),
            'dampak' => $this->simulasi->dampakKonsistensi($tahun, $rencana['harga_aset'], $pokok),
        ]);
    }

    /**
     * Menyeragamkan masukan lalu memvalidasinya.
     *
     * @return array{jenis_aset: string, nama_aset: string, cara_pembayaran: string, harga_aset: int, uang_muka: int, jangka_waktu: int, suku_bunga: float}
     */
    private function sahkan(Request $request): array
    {
        $tunai = $request->input('cara_pembayaran') === 'Tunai';

        $request->merge([
            // Kolom uang dikirim dengan pemisah ribuan, misalnya "450.000.000".
            'harga_aset' => $this->bilangan($request->input('harga_aset')),
            // Pembelian tunai berarti seluruh harga dibayar di muka, sehingga
            // pokok pinjamannya nol dan jangka waktu serta bunga tidak berlaku.
            'uang_muka' => $tunai
                ? $this->bilangan($request->input('harga_aset'))
                : $this->bilangan($request->input('uang_muka')),
            'jangka_waktu' => $tunai ? 0 : $request->input('jangka_waktu'),
            // Di Indonesia desimal ditulis dengan koma. Tanpa penyeragaman ini
            // "8,5" terbaca 8 persen dan hasilnya salah tanpa peringatan apa pun.
            'suku_bunga' => $tunai ? 0 : str_replace(',', '.', (string) $request->input('suku_bunga')),
        ]);

        $sah = $request->validate([
            'jenis_aset' => ['required', Rule::in(array_keys(MockData::kategoriHarta()))],
            'nama_aset' => ['required', 'string', 'max:100'],
            'cara_pembayaran' => ['required', Rule::in(['Tunai', 'Kredit'])],
            'harga_aset' => ['required', 'integer', 'min:1'],
            'uang_muka' => ['required', 'integer', 'min:0', 'lte:harga_aset'],
            'jangka_waktu' => [Rule::requiredIf(! $tunai), 'integer', $tunai ? 'min:0' : 'between:1,360'],
            'suku_bunga' => [Rule::requiredIf(! $tunai), 'numeric', 'between:0,100'],
        ], [
            'jenis_aset.required' => 'Jenis aset wajib dipilih.',
            'jenis_aset.in' => 'Jenis aset tidak dikenali.',
            'nama_aset.required' => 'Nama aset wajib diisi.',
            'nama_aset.max' => 'Nama aset maksimal 100 karakter.',
            'cara_pembayaran.required' => 'Cara pembayaran wajib dipilih.',
            'cara_pembayaran.in' => 'Cara pembayaran tidak dikenali.',
            'harga_aset.required' => 'Harga aset wajib diisi.',
            'harga_aset.min' => 'Harga aset harus lebih besar dari nol.',
            'uang_muka.required' => 'Uang muka wajib diisi.',
            'uang_muka.integer' => 'Uang muka hanya boleh berisi angka.',
            'uang_muka.min' => 'Uang muka tidak boleh kurang dari nol.',
            'uang_muka.lte' => 'Uang muka tidak boleh melebihi harga aset.',
            'jangka_waktu.required' => 'Jangka waktu wajib diisi.',
            'jangka_waktu.integer' => 'Jangka waktu hanya boleh berisi angka.',
            'jangka_waktu.between' => 'Jangka waktu antara 1 dan 360 bulan.',
            'suku_bunga.required' => 'Bunga per tahun wajib diisi.',
            'suku_bunga.numeric' => 'Bunga per tahun hanya boleh berisi angka.',
            'suku_bunga.between' => 'Bunga per tahun antara 0 dan 100 persen.',
        ]);

        return [
            'jenis_aset' => $sah['jenis_aset'],
            'nama_aset' => $sah['nama_aset'],
            'cara_pembayaran' => $sah['cara_pembayaran'],
            'harga_aset' => (int) $sah['harga_aset'],
            'uang_muka' => (int) $sah['uang_muka'],
            'jangka_waktu' => (int) $sah['jangka_waktu'],
            'suku_bunga' => (float) $sah['suku_bunga'],
        ];
    }
}
