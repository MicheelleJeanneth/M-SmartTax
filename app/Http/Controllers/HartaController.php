<?php

namespace App\Http\Controllers;

use App\Models\Harta;
use App\Models\KodeHarta;
use App\Models\Profil;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HartaController extends Controller
{
    public function index(Request $request, string $kategori = 'kas'): View
    {
        $data = $this->dataKategori($kategori);
        $cari = trim((string) $request->query('cari', ''));
        $kode = trim((string) $request->query('kode', ''));
        $tahun = trim((string) $request->query('tahun', ''));

        // Pencarian hanya menjangkau kolom teks yang benar-benar tampil di tabel.
        // Mencari kolom yang tersembunyi membuat pengguna tidak bisa melihat
        // bagian mana dari barisnya yang cocok.
        $kolomDicari = collect($data['info']['tabel'])
            ->pluck('isi')
            ->filter(fn (string $isi): bool => in_array($isi, ['deskripsi', 'keterangan'], true) || str_starts_with($isi, 'khas:'))
            ->values();

        $kueri = $this->milik()
            ->kategori($kategori)
            ->with(['kodeHarta', Harta::relasiRincian($kategori)])
            ->when($kode !== '', fn (Builder $k) => $k->whereHas('kodeHarta', fn (Builder $q) => $q->where('kode', $kode)))
            ->when($tahun !== '', fn (Builder $k) => $k->where('tahun_perolehan', (int) $tahun))
            ->when($cari !== '', fn (Builder $k) => $k->where(
                fn (Builder $q) => $this->terapkanPencarian($q, $kategori, $kolomDicari->all(), $cari)
            ))
            // Terbaru di atas, sama seperti Data Penghasilan dan Data Utang.
            ->orderByDesc('tahun_perolehan')
            ->orderByDesc('id_harta');

        $semua = $this->milik()->kategori($kategori)->with('kodeHarta')->get();

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
            'harta' => $kueri->clone()->paginate(self::PER_HALAMAN)->withQueryString(),
            'total' => (int) $kueri->clone()->sum('nilai'),
            'jumlah' => $kueri->clone()->count(),
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
        $this->dataKategori($kategori);
        [$induk, $rincian] = $this->sahkan($request, $kategori);

        $harta = $this->milik()->create($induk);
        Harta::RINCIAN[$kategori]::create(['id_harta' => $harta->id_harta] + $rincian);

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

        if ($item->terkunci) {
            abort(403, 'Data harta ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.');
        }

        return view('harta.edit', [...$this->dataKategori($kategori), 'item' => $item]);
    }

    public function update(Request $request, string $kategori, int $id): RedirectResponse
    {
        $harta = $this->cari($kategori, $id);

        if ($harta->terkunci) {
            abort(403, 'Data harta ini sudah masuk draf tahunan dan tidak dapat diubah.');
        }

        [$induk, $rincian] = $this->sahkan($request, $kategori);

        $harta->update($induk);
        Harta::RINCIAN[$kategori]::updateOrCreate(['id_harta' => $harta->id_harta], $rincian);

        return redirect()->route('harta.index', $kategori)->with('sukses', 'Perubahan data harta tersimpan.');
    }

    public function destroy(string $kategori, int $id): RedirectResponse
    {
        $harta = $this->cari($kategori, $id);

        if ($harta->terkunci) {
            abort(403, 'Data harta ini sudah masuk draf tahunan dan tidak dapat dihapus.');
        }

        $harta->hapus();

        return redirect()->route('harta.index', $kategori)->with('sukses', 'Data harta dihapus.');
    }

    /**
     * Memeriksa isian lalu memisahkannya menjadi kolom induk dan kolom khas.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function sahkan(Request $request, string $kategori): array
    {
        $request->merge([
            'nilai' => $this->bilangan($request->input('nilai')),
            'nilai_kini' => $this->bilangan($request->input('nilai_kini')),
        ]);

        $aturan = [
            'kode' => ['required', 'string'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'nilai' => ['required', 'integer', 'min:1'],
            'nilai_kini' => ['nullable', 'integer', 'min:0'],
            'tahun' => ['required', 'digits:4', 'integer', 'min:1900', 'max:'.now()->year],
            'tahun_pelepasan' => ['nullable', 'digits:4', 'integer', 'gte:tahun', 'max:'.now()->year],
        ];

        foreach (Harta::KOLOM_KHAS[$kategori] as $urutan => $kolom) {
            $aturan['khas_'.$urutan] = ['nullable', 'string', 'max:255'];
        }

        $sah = $request->validate($aturan, [
            'kode.required' => 'Kode harta wajib dipilih.',
            'nilai.required' => 'Nilai wajib diisi.',
            'nilai.min' => 'Nilai harus lebih besar dari nol.',
            'tahun.required' => 'Tahun perolehan wajib diisi.',
            'tahun.digits' => 'Tahun perolehan ditulis empat angka.',
            'tahun_pelepasan.gte' => 'Tahun pelepasan tidak boleh sebelum tahun perolehan.',
            'tahun_pelepasan.digits' => 'Tahun pelepasan ditulis empat angka.',
        ]);

        $kode = KodeHarta::where('kategori', $kategori)->where('kode', $sah['kode'])->firstOr(
            fn () => throw ValidationException::withMessages(['kode' => 'Kode harta tidak dikenali.'])
        );

        $induk = [
            'id_kode_harta' => $kode->id_kode_harta,
            // Deskripsi disalin dari kodenya, bukan diketik pengguna.
            'deskripsi' => $kode->nama_kode,
            'keterangan' => $sah['keterangan'] ?? null,
            'nilai' => $sah['nilai'],
            'nilai_saat_ini' => $sah['nilai_kini'] ?? null,
            'tahun_perolehan' => $sah['tahun'],
            'tahun_pelepasan' => $sah['tahun_pelepasan'] ?? null,
        ];

        $rincian = [];

        foreach (Harta::KOLOM_KHAS[$kategori] as $urutan => $kolom) {
            $rincian[$kolom] = $sah['khas_'.$urutan] ?? null;
        }

        // Negara selalu Indonesia; nilainya tidak diambil dari kiriman pengguna.
        if ($negara = Harta::KOLOM_NEGARA[$kategori] ?? null) {
            $rincian[$negara] = 'Indonesia';
        }

        return [$induk, $rincian];
    }

    /**
     * Menyusun pencarian pada kolom induk maupun kolom khas di tabel anak.
     *
     * @param  array<int, string>  $kolomDicari
     */
    private function terapkanPencarian(Builder $kueri, string $kategori, array $kolomDicari, string $cari): void
    {
        foreach ($kolomDicari as $isi) {
            if (in_array($isi, ['deskripsi', 'keterangan'], true)) {
                $kueri->orWhere($isi, 'like', '%'.$cari.'%');

                continue;
            }

            $kolom = Harta::KOLOM_KHAS[$kategori][(int) substr($isi, 5)] ?? null;

            if ($kolom !== null) {
                $kueri->orWhereHas(Harta::relasiRincian($kategori), fn (Builder $q) => $q->where($kolom, 'like', '%'.$cari.'%'));
            }
        }
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

    /** @return HasMany<Harta, Profil> */
    private function milik(): HasMany
    {
        return PenggunaSaatIni::profil()->hartas();
    }

    private function cari(string $kategori, int $id): Harta
    {
        return $this->milik()->kategori($kategori)->with(['kodeHarta', Harta::relasiRincian($kategori)])->findOrFail($id);
    }
}
