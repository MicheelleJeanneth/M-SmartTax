<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrafTahunanController extends Controller
{
    public function index(): View
    {
        return view('draf-tahunan.index', ['draf' => MockData::drafTahunan()]);
    }

    public function create(Request $request): View
    {
        return view('draf-tahunan.susun', MockData::drafTahunanRinci((int) $request->query('tahun', 2025)));
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('draf-tahunan.index')->with('sukses', 'Draf SPT Tahunan tersimpan. Data harta dan utang tahun tersebut kini terkunci.');
    }

    public function show(int $tahun): View
    {
        return view('draf-tahunan.lihat', MockData::drafTahunanRinci($tahun));
    }

    public function destroy(int $tahun): RedirectResponse
    {
        return redirect()->route('draf-tahunan.index')->with('sukses', 'Draf tahunan dibatalkan. Data harta, utang, dan draf bulanan dapat diubah kembali.');
    }
}
