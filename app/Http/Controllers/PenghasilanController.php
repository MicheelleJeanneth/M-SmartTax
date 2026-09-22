<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenghasilanController extends Controller
{
    public function index(): View
    {
        return view('penghasilan.index', ['penghasilan' => MockData::penghasilan()]);
    }

    public function create(): View
    {
        return view('penghasilan.create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan tersimpan.');
    }

    public function edit(int $id): View
    {
        $penghasilan = collect(MockData::penghasilan())->firstWhere('id', $id) ?? abort(404);

        if ($penghasilan['terkunci']) {
            abort(403, 'Data penghasilan ini sudah masuk draf. Batalkan draf bulan tersebut terlebih dahulu untuk mengubahnya.');
        }

        return view('penghasilan.edit', ['penghasilan' => $penghasilan]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('penghasilan.index')->with('sukses', 'Perubahan data penghasilan tersimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan dihapus.');
    }
}
