<?php

namespace App\Http\Controllers;

use App\Services\FilterTahun;
use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrafBulananController extends Controller
{
    public function __construct(private readonly FilterTahun $filterTahun) {}

    public function index(Request $request): View
    {
        // Draf bulanan dihitung dari data penghasilan, jadi tahun yang ditawarkan
        // sama dengan di Data Penghasilan: sejak catatan pertama sampai tahun berjalan.
        $daftarTahun = $this->filterTahun->daftarPenghasilan();
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $daftarTahun);

        $draf = MockData::drafBulanan($tahun);
        $tersusun = collect($draf)->whereIn('status', ['tersusun', 'nihil']);

        return view('draf-bulanan.index', [
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
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
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan());
        $bulan = (int) $request->query('bulan', MockData::bulanTersusun($tahun) + 1);
        abort_unless($bulan >= 1 && $bulan <= 12, 404);

        return view('draf-bulanan.susun', MockData::perhitunganBulanan($bulan, $tahun));
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('draf-bulanan.index')->with('sukses', 'Draf PPh Final tersimpan. Data penghasilan bulan tersebut kini terkunci.');
    }

    public function show(Request $request, int $bulan): View
    {
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan());
        abort_unless($bulan >= 1 && $bulan <= MockData::bulanTersusun($tahun), 404);

        return view('draf-bulanan.lihat', MockData::perhitunganBulanan($bulan, $tahun));
    }

    public function destroy(int $bulan): RedirectResponse
    {
        return redirect()->route('draf-bulanan.index')->with('sukses', 'Draf dibatalkan. Data penghasilan bulan tersebut dapat diubah kembali.');
    }
}
