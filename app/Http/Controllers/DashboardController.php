<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $ringkasan = MockData::ringkasan();

        $komposisiHarta = collect(MockData::kategoriHarta())
            ->mapWithKeys(fn (array $kategori, string $kunci): array => [
                $kategori['singkat'] => collect(MockData::harta($kunci))->sum('nilai'),
            ])
            ->filter()
            ->sortDesc();

        return view('dashboard', [
            'profil' => MockData::profil(),
            'tahun' => (int) $request->query('tahun', MockData::TAHUN),
            'ringkasan' => $ringkasan,
            'bulan' => MockData::bulan(),
            'bruto' => MockData::bruto(),
            'komposisiHarta' => $komposisiHarta->map(fn (int $nilai): float => $nilai / $ringkasan['harta'] * 100),
            'analisis' => MockData::analisis(),
            'pengingat' => MockData::pengingat(),
        ]);
    }
}
