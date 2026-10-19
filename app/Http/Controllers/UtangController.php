<?php

namespace App\Http\Controllers;

use App\Models\KodeUtang;
use App\Models\Profil;
use App\Models\Utang;
use App\Support\PenggunaSaatIni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UtangController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $kode = trim((string) $request->query('kode', ''));
        $tahun = trim((string) $request->query('tahun', ''));

        $kueri = $this->milik()
            ->with('kodeUtang')
            ->when($kode !== '', fn (Builder $k) => $k->whereHas('kodeUtang', fn (Builder $q) => $q->where('kode', $kode)))
            ->when($tahun !== '', fn (Builder $k) => $k->where('tahun_peminjaman', (int) $tahun))
            ->when($cari !== '', fn (Builder $k) => $k->where(
                fn (Builder $q) => $q->where('deskripsi', 'like', '%'.$cari.'%')
                    ->orWhere('nama_kreditur', 'like', '%'.$cari.'%')
            ))
            // Terbaru di atas: tahun peminjaman terbaru dulu, lalu catatan yang paling akhir dibuat.
            ->orderByDesc('tahun_peminjaman')
            ->orderByDesc('id_utang');

        return view('utang.index', [
            'utang' => $kueri->clone()->paginate(self::PER_HALAMAN)->withQueryString(),
            'total' => (int) $kueri->clone()->sum('saldo'),
            'jumlah' => $kueri->clone()->count(),
            'cari' => $cari,
            'kode' => $kode,
            'tahun' => $tahun,
            // Hanya tahun yang benar-benar punya utang tercatat.
            'daftarTahun' => $this->milik()->distinct()->orderByDesc('tahun_peminjaman')->pluck('tahun_peminjaman')->all(),
        ]);
    }

    public function create(): View
    {
        return view('utang.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->milik()->create($this->sahkan($request));

        return redirect()->route('utang.index')->with('sukses', 'Data utang tersimpan.');
    }

    public function show(int $id): View
    {
        return view('utang.show', ['utang' => $this->cari($id)]);
    }

    public function edit(int $id): View
    {
        $utang = $this->cari($id);

        if ($utang->terkunci) {
            abort(403, 'Data utang ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.');
        }

        return view('utang.edit', ['utang' => $utang]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $utang = $this->cari($id);

        if ($utang->terkunci) {
            abort(403, 'Data utang ini sudah masuk draf tahunan dan tidak dapat diubah.');
        }

        $utang->update($this->sahkan($request));

        return redirect()->route('utang.index')->with('sukses', 'Perubahan data utang tersimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $utang = $this->cari($id);

        if ($utang->terkunci) {
            abort(403, 'Data utang ini sudah masuk draf tahunan dan tidak dapat dihapus.');
        }

        $utang->hapus();

        return redirect()->route('utang.index')->with('sukses', 'Data utang dihapus.');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function sahkan(Request $request): array
    {
        $request->merge([
            'saldo' => $this->bilangan($request->input('saldo')),
            'cicilan_bulanan' => $this->bilangan($request->input('cicilan_bulanan')),
        ]);

        $sah = $request->validate([
            'kode' => ['required', 'string'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'saldo' => ['required', 'integer', 'min:0'],
            'cicilan_bulanan' => ['nullable', 'integer', 'min:0'],
            'tahun_peminjaman' => ['required', 'digits:4', 'integer', 'min:1900', 'max:'.now()->year],
            'tahun_pelunasan' => ['nullable', 'digits:4', 'integer', 'gte:tahun_peminjaman'],
            'nama_kreditur' => ['required', 'string', 'max:255'],
            'nik_kreditur' => ['nullable', 'digits:16'],
        ], [
            'kode.required' => 'Kode utang wajib dipilih.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'saldo.required' => 'Saldo wajib diisi.',
            'tahun_peminjaman.required' => 'Tahun peminjaman wajib diisi.',
            'tahun_peminjaman.digits' => 'Tahun peminjaman ditulis empat angka.',
            'tahun_pelunasan.digits' => 'Tahun pelunasan ditulis empat angka.',
            'tahun_pelunasan.gte' => 'Tahun pelunasan tidak boleh sebelum tahun peminjaman.',
            'nama_kreditur.required' => 'Nama kreditur wajib diisi.',
            'nik_kreditur.digits' => 'NIK kreditur terdiri dari 16 angka.',
        ]);

        $kode = KodeUtang::where('kode', $sah['kode'])->firstOr(
            fn () => throw ValidationException::withMessages(['kode' => 'Kode utang tidak dikenali.'])
        );

        return [
            'id_kode_utang' => $kode->id_kode_utang,
            'deskripsi' => $sah['deskripsi'],
            'keterangan' => $sah['keterangan'] ?? null,
            'saldo' => $sah['saldo'],
            'cicilan_bulanan' => $sah['cicilan_bulanan'] ?? null,
            'tahun_peminjaman' => $sah['tahun_peminjaman'],
            'tahun_pelunasan' => $sah['tahun_pelunasan'] ?? null,
            'nama_kreditur' => $sah['nama_kreditur'],
            'nik_kreditur' => $sah['nik_kreditur'] ?? null,
            // Kreditur selalu dalam negeri; nilainya tidak diambil dari kiriman pengguna.
            'negara_kreditur' => 'Indonesia',
        ];
    }

    /** @return HasMany<Utang, Profil> */
    private function milik(): HasMany
    {
        return PenggunaSaatIni::profil()->utangs();
    }

    private function cari(int $id): Utang
    {
        return $this->milik()->with('kodeUtang')->findOrFail($id);
    }
}
