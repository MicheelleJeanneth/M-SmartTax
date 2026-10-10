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

        $data = $this->dataKategori($kategori);
        $semua = collect(MockData::harta($kategori));

        // Pencarian hanya menjangkau kolom teks yang benar-benar tampil di tabel.
        // Mencari kolom yang tersembunyi membuat pengguna tidak bisa melihat
        // bagian mana dari barisnya yang cocok.
        $kolomDicari = collect($data['info']['tabel'])
            ->pluck('isi')
            ->filter(fn (string $isi): bool => in_array($isi, ['deskripsi', 'keterangan'], true) || str_starts_with($isi, 'khas:'))
            ->values();

        $terpilih = $semua
            ->filter(function (array $h) use ($cari, $kode, $tahun, $kolomDicari): bool {
                $kataKunci = mb_strtolower($cari);

                $cocokCari = $cari === '' || $kolomDicari->contains(function (string $isi) use ($h, $kataKunci): bool {
                    $nilai = match (true) {
                        $isi === 'deskripsi' => $h['nama'],
                        $isi === 'keterangan' => $h['keterangan'],
                        default => $h['khas'][(int) substr($isi, 5)] ?? '',
                    };

                    return str_contains(mb_strtolower((string) $nilai), $kataKunci);
                });

                return $cocokCari
                    && ($kode === '' || $h['kode'] === $kode)
                    && ($tahun === '' || (int) $h['tahun'] === (int) $tahun);
            })
            // Terbaru di atas, sama seperti Data Penghasilan dan Data Utang.
            ->sortByDesc(fn (array $h): array => [$h['tahun'], $h['id']])
            ->values();

        // Petunjuk pencarian menyebut tepat kolom yang dijangkau, misalnya
        // "Cari deskripsi, nama bank, atau nomor rekening".
        $judul = collect($data['info']['tabel'])
            ->filter(fn (array $k): bool => $kolomDicari->contains($k['isi']))
            ->pluck('judul')
            ->map(fn (string $j): string => mb_strtolower($j));

        return view('harta.index', [
            ...$data,
            'petunjukCari' => 'Cari '.($judul->count() > 1
                ? $judul->slice(0, -1)->implode(', ').', atau '.$judul->last()
                : $judul->first()),
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
        return redirect()->route('harta.index', $kategori)->with('sukses', 'Perubahan data harta tersimpan.');
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
