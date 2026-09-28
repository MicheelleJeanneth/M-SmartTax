<?php

namespace App\Http\Controllers;

use App\Services\AnalisisKeuangan;
use App\Services\FilterTahun;
use App\Services\Pengingat;
use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FilterTahun $filterTahun,
        private readonly AnalisisKeuangan $analisis,
        private readonly Pengingat $pengingat,
    ) {}

    public function __invoke(Request $request): View
    {
        $tahun = $this->filterTahun->pilih($request->query('tahun'));

        $bruto = $this->analisis->peredaranBruto($tahun);
        $harta = $this->analisis->totalHarta($tahun);
        $utang = $this->analisis->totalUtang($tahun);
        $pphFinal = collect(MockData::drafBulanan())->sum('pph_final');

        return view('dashboard', [
            'profil' => MockData::profil(),
            'tahun' => $tahun,
            'daftarTahun' => $this->filterTahun->daftar(),
            'tahunBerjalan' => $this->filterTahun->tahunBerjalan(),
            'bruto' => $bruto,
            'pphFinal' => $tahun === MockData::TAHUN ? $pphFinal : 0,
            'harta' => $harta,
            'utang' => $utang,
            'kekayaanBersih' => $harta - $utang,
            'brutoPerBulan' => $this->analisis->brutoPerBulan($tahun),
            'komposisiHarta' => $this->analisis->hartaPerKategori($tahun),
            'pertumbuhan' => $this->analisis->pertumbuhanKekayaan($tahun),
            'konsistensi' => $this->analisis->rasioKonsistensi($tahun),
            // Pengingat dihitung ulang setiap Dashboard dibuka.
            'pengingat' => $this->pengingat->segarkan(),
            // Keterangan di bawah judul bila tahun terpilih sama sekali tidak punya data.
            'tanpaData' => $bruto === 0 && $harta === 0 && $utang === 0,
        ]);
    }
}
