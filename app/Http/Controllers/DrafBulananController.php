<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrafBulananController extends Controller
{
    public function index(): View
    {
        $draf = MockData::drafBulanan();
        $tersusun = collect($draf)->whereIn('status', ['tersusun', 'nihil']);

        return view('draf-bulanan.index', [
            'tahun' => MockData::TAHUN,
            'draf' => $draf,
            'jumlahTersusun' => $tersusun->count(),
            'akumulasi' => $tersusun->sum('bruto'),
            'totalPph' => $tersusun->sum('pph_final'),
        ]);
    }

    /**
     * Mockup: bulan dipilih lewat ?bulan=. Coba ?bulan=8 untuk melihat keadaan draf nihil.
     */
    public function create(Request $request): View
    {
        $bulan = (int) $request->query('bulan', MockData::BULAN_TERSUSUN + 1);
        abort_unless($bulan >= 1 && $bulan <= 12, 404);

        return view('draf-bulanan.susun', MockData::perhitunganBulanan($bulan));
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('draf-bulanan.index')->with('sukses', 'Draf PPh Final tersimpan. Data penghasilan bulan tersebut kini terkunci.');
    }

    public function show(int $bulan): View
    {
        abort_unless($bulan >= 1 && $bulan <= MockData::BULAN_TERSUSUN, 404);

        return view('draf-bulanan.lihat', MockData::perhitunganBulanan($bulan));
    }

    public function destroy(int $bulan): RedirectResponse
    {
        return redirect()->route('draf-bulanan.index')->with('sukses', 'Draf dibatalkan. Data penghasilan bulan tersebut dapat diubah kembali.');
    }
}
