<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulasiController extends Controller
{
    /**
     * Form memakai GET: hasil simulasi tidak disimpan ke basis data (panduan 8.11).
     */
    public function index(Request $request): View
    {
        $data = MockData::simulasi();
        $kondisi = $data['kondisi'];
        $rencana = array_merge($data['rencana'], array_filter($request->only(array_keys($data['rencana'])), 'filled'));

        return view('simulasi.index', [
            'kondisi' => $kondisi,
            'rencana' => $rencana,
            'hasil' => $request->has('harga_aset') ? $this->hitung($kondisi, $rencana) : null,
        ]);
    }

    /**
     * @param  array<string, int|float>  $kondisi
     * @param  array<string, int|float|string>  $rencana
     * @return array<string, int|float|string>
     */
    private function hitung(array $kondisi, array $rencana): array
    {
        $pokok = max(0, (float) $rencana['harga_aset'] - (float) $rencana['uang_muka']);
        $bulan = max(1, (int) $rencana['jangka_waktu']);
        $bungaBulanan = (float) $rencana['suku_bunga'] / 100 / 12;

        $cicilan = $bungaBulanan > 0
            ? $pokok * $bungaBulanan / (1 - (1 + $bungaBulanan) ** -$bulan)
            : $pokok / $bulan;

        $totalCicilan = $cicilan + $kondisi['cicilan_berjalan'];
        $rasio = $totalCicilan / $kondisi['penghasilan_bulanan'] * 100;

        [$kesimpulan, $nada] = match (true) {
            $rasio < 30 => ['Layak', 'ok'],
            $rasio <= 40 => ['Perlu Dipertimbangkan', 'warn'],
            default => ['Belum Disarankan', 'danger'],
        };

        $hartaBaru = $kondisi['total_harta'] + (float) $rencana['harga_aset'] - (float) $rencana['uang_muka'];
        $utangBaru = $kondisi['total_utang'] + $pokok;

        return [
            'pokok' => $pokok,
            'cicilan' => $cicilan,
            'total_cicilan' => $totalCicilan,
            'total_bayar' => $cicilan * $bulan,
            'rasio' => $rasio,
            'kesimpulan' => $kesimpulan,
            'nada' => $nada,
            'harta_baru' => $hartaBaru,
            'utang_baru' => $utangBaru,
            'rasio_utang_lama' => $kondisi['total_utang'] / $kondisi['total_harta'] * 100,
            'rasio_utang_baru' => $utangBaru / $hartaBaru * 100,
        ];
    }
}
