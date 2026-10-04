<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HartaController extends Controller
{
    public function index(Request $request, string $kategori = 'kas'): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $kode = trim((string) $request->query('kode', ''));
        $tahun = trim((string) $request->query('tahun', ''));

        $semua = collect(MockData::harta($kategori));

        $terpilih = $semua
            ->filter(function (array $h) use ($cari, $kode, $tahun): bool {
                $kataKunci = mb_strtolower($cari);

                $cocokCari = $cari === ''
                    || str_contains(mb_strtolower($h['nama']), $kataKunci)
                    // Kolom khas tiap kategori ikut dicari: nama bank, nomor polisi, dan seterusnya.
                    || collect($h['khas'])->contains(
                        fn (mixed $nilai): bool => str_contains(mb_strtolower((string) $nilai), $kataKunci)
                    );

                return $cocokCari
                    && ($kode === '' || $h['kode'] === $kode)
                    && ($tahun === '' || (int) $h['tahun'] === (int) $tahun);
            })
            // Terbaru di atas, sama seperti Data Penghasilan dan Data Utang.
            ->sortByDesc(fn (array $h): array => [$h['tahun'], $h['id']])
            ->values();

        return view('harta.index', [
            ...$this->dataKategori($kategori),
            'harta' => $this->halaman($terpilih, $request),
            'total' => $terpilih->sum('nilai'),
            'jumlah' => $terpilih->count(),
            'cari' => $cari,
            'kode' => $kode,
            'tahun' => $tahun,
            'daftarKode' => $semua->pluck('kode')->unique()->sort()->values()->all(),
            'daftarTahun' => $semua->pluck('tahun')->unique()->sortDesc()->values()->all(),
        ]);
    }

    public function create(string $kategori): View
    {
        return view('harta.create', $this->dataKategori($kategori));
    }

    public function store(Request $request, string $kategori): RedirectResponse
    {
        return redirect()->route('harta.index', $kategori)->with('sukses', 'Data harta tersimpan.');
    }

    public function show(string $kategori, int $id): View
    {
        return view('harta.show', [
            ...$this->dataKategori($kategori),
            'item' => $this->cari($kategori, $id),
        ]);
    }

    public function edit(string $kategori, int $id): View
    {
        $item = $this->cari($kategori, $id);

        if ($item['terkunci']) {
            abort(403, 'Data harta ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.');
        }

        return view('harta.edit', [...$this->dataKategori($kategori), 'item' => $item]);
    }

    public function update(Request $request, string $kategori, int $id): RedirectResponse
    {
        return redirect()->route('harta.show', [$kategori, $id])->with('sukses', 'Perubahan data harta tersimpan.');
    }

    public function destroy(string $kategori, int $id): RedirectResponse
    {
        return redirect()->route('harta.index', $kategori)->with('sukses', 'Data harta dihapus.');
    }

    /**
     * @return array{kategori: string, semuaKategori: array<string, array<string, mixed>>, info: array<string, mixed>}
     */
    private function dataKategori(string $kategori): array
    {
        $semua = MockData::kategoriHarta();

        return [
            'kategori' => $kategori,
            'semuaKategori' => $semua,
            'info' => $semua[$kategori] ?? abort(404),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cari(string $kategori, int $id): array
    {
        return collect(MockData::harta($kategori))->firstWhere('id', $id) ?? abort(404);
    }
}
