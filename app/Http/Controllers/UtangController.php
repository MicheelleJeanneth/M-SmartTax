<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtangController extends Controller
{
    public function index(): View
    {
        return view('utang.index', ['utang' => MockData::utang()]);
    }

    public function create(): View
    {
        return view('utang.create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('utang.index')->with('sukses', 'Data utang tersimpan.');
    }

    public function show(int $id): View
    {
        return view('utang.show', ['utang' => $this->cari($id)]);
    }

    public function edit(int $id): View
    {
        $utang = $this->cari($id);

        if ($utang['terkunci']) {
            abort(403, 'Data utang ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.');
        }

        return view('utang.edit', ['utang' => $utang]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        return redirect()->route('utang.show', $id)->with('sukses', 'Perubahan data utang tersimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('utang.index')->with('sukses', 'Data utang dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function cari(int $id): array
    {
        return collect(MockData::utang())->firstWhere('id', $id) ?? abort(404);
    }
}
