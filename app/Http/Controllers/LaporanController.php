<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const JUDUL = [
        'draf-bulanan' => 'Laporan Draf Pajak Penghasilan Bulanan',
        'draf-tahunan' => 'Laporan Draf Pajak Penghasilan Tahunan',
        'harta' => 'Laporan Data Harta',
        'utang' => 'Laporan Data Utang',
    ];

    public function show(Request $request, string $jenis): View
    {
        $filter = $this->filter($request, $jenis);

        return view('laporan.show', [
            'jenis' => $jenis,
            'judul' => self::JUDUL[$jenis],
            ...$filter,
            'tersedia' => $this->tersedia($jenis, $filter),
        ]);
    }

    /**
     * Isi HTML laporan untuk iframe pratinjau. Memakai view PDF yang sama dengan unduhan.
     */
    public function pratinjau(Request $request, string $jenis): View
    {
        return view("pdf.$jenis", [
            ...$this->dataLaporan($jenis, $this->filter($request, $jenis)),
            'pratinjau' => true,
        ]);
    }

    public function unduh(Request $request, string $jenis): Response
    {
        $filter = $this->filter($request, $jenis);
        abort_unless($this->tersedia($jenis, $filter), 404);

        $namaBerkas = $jenis.'-'.$filter['tahun'].($jenis === 'draf-bulanan' ? '-'.$filter['bulan'] : '').'.pdf';

        return Pdf::loadView("pdf.$jenis", $this->dataLaporan($jenis, $filter))
            ->setPaper('a4', 'portrait')
            ->download($namaBerkas);
    }

    /**
     * @return array{tahun: int, bulan: int}
     */
    private function filter(Request $request, string $jenis): array
    {
        return [
            'tahun' => (int) $request->query('tahun', $jenis === 'draf-tahunan' ? 2025 : MockData::TAHUN),
            'bulan' => (int) $request->query('bulan', MockData::BULAN_TERSUSUN),
        ];
    }

    /**
     * Mockup: laporan bulanan hanya ada untuk bulan tersusun tahun berjalan,
     * laporan tahunan hanya untuk tahun dengan draf tersusun.
     *
     * @param  array{tahun: int, bulan: int}  $filter
     */
    private function tersedia(string $jenis, array $filter): bool
    {
        return match ($jenis) {
            'draf-bulanan' => $filter['tahun'] === MockData::TAHUN && $filter['bulan'] <= MockData::BULAN_TERSUSUN,
            'draf-tahunan' => collect(MockData::drafTahunan())->where('tahun', $filter['tahun'])->where('status', 'tersusun')->isNotEmpty(),
            default => $filter['tahun'] <= MockData::TAHUN,
        };
    }

    /**
     * @param  array{tahun: int, bulan: int}  $filter
     * @return array<string, mixed>
     */
    private function dataLaporan(string $jenis, array $filter): array
    {
        $dasar = [
            'judul' => self::JUDUL[$jenis],
            'profil' => MockData::profil(),
            'tanggalCetak' => now(),
            ...$filter,
        ];

        return match ($jenis) {
            'draf-bulanan' => [...$dasar, ...MockData::perhitunganBulanan($filter['bulan'])],
            'draf-tahunan' => [...$dasar, ...MockData::drafTahunanRinci($filter['tahun'])],
            'harta' => [
                ...$dasar,
                'kategori' => collect(MockData::kategoriHarta())
                    ->map(fn (array $info, string $kunci): array => [...$info, 'baris' => MockData::harta($kunci)]),
            ],
            'utang' => [...$dasar, 'utang' => MockData::utang()],
        };
    }
}
