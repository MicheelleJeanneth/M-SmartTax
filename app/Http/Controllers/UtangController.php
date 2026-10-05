<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtangController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $kode = trim((string) $request->query('kode', ''));
        $tahun = trim((string) $request->query('tahun', ''));

        $semua = collect(MockData::utang());

        $terpilih = $semua
            ->filter(function (array $u) use ($cari, $kode, $tahun): bool {
                $kataKunci = mb_strtolower($cari);

                $cocokCari = $cari === ''
                    || str_contains(mb_strtolower($u['deskripsi']), $kataKunci)
                    || str_contains(mb_strtolower($u['kreditur']), $kataKunci);

                return $cocokCari
                    && ($kode === '' || $u['kode'] === $kode)
                    && ($tahun === '' || (int) $u['tahun'] === (int) $tahun);
            })
            // Terbaru di atas: tahun peminjaman terbaru dulu, lalu catatan yang paling akhir dibuat.
            ->sortByDesc(fn (array $u): array => [$u['tahun'], $u['id']])
            ->values();

        return view('utang.index', [
            'utang' => $this->halaman($terpilih, $request),
            'total' => $terpilih->sum('saldo'),
            'jumlah' => $terpilih->count(),
            'cari' => $cari,
            'kode' => $kode,
            'tahun' => $tahun,
            // Hanya tahun yang benar-benar punya utang tercatat.
            'daftarTahun' => $semua->pluck('tahun')->unique()->sortDesc()->values()->all(),
        ]);
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
        return redirect()->route('utang.index')->with('sukses', 'Perubahan data utang tersimpan.');
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
