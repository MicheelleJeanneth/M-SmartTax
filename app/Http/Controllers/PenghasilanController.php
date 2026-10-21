<?php

namespace App\Http\Controllers;

use App\Models\Penghasilan;
use App\Services\FilterTahun;
use App\Services\KunciPencatatan;
use App\Support\PenggunaSaatIni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenghasilanController extends Controller
{
    public function __construct(
        private readonly FilterTahun $filterTahun,
        private readonly KunciPencatatan $kunci,
    ) {}

    public function index(Request $request): View
    {
        // Hanya tahun yang sudah punya catatan penghasilan sampai tahun berjalan.
        $daftarTahun = $this->filterTahun->daftarPenghasilan();
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $daftarTahun);
        $bulan = (int) $request->query('bulan', 0);
        $cari = trim((string) $request->query('cari', ''));

        $kueri = $this->milik()
            ->tahun($tahun)
            ->when($bulan > 0, fn (Builder $k) => $k->bulan($bulan))
            ->when($cari !== '', fn (Builder $k) => $k->where('keterangan', 'like', '%'.$cari.'%'))
            // Terbaru di atas: transaksi terbaru yang paling sering diubah.
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penghasilan');

        return view('penghasilan.index', [
            'penghasilan' => $kueri->clone()->paginate(self::PER_HALAMAN)->withQueryString(),
            'total' => (int) $kueri->clone()->sum('nominal'),
            'jumlahTransaksi' => $kueri->clone()->count(),
            'tahun' => $tahun,
            'bulan' => $bulan,
            'cari' => $cari,
            'daftarTahun' => $daftarTahun,
        ]);
    }

    public function create(): View
    {
        return view('penghasilan.create', [
            'awalTerbuka' => $this->kunci->awalTerbuka()->toDateString(),
            'pesanKunci' => $this->kunci->pesanBatas(),
            'pesanKunci' => $this->kunci->pesanBatas(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sah = $this->sahkan($request);

        $this->milik()->create($sah);

        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan tersimpan.');
    }

    public function show(int $id): View
    {
        return view('penghasilan.show', ['penghasilan' => $this->cari($id)]);
    }

    public function edit(int $id): View
    {
        $penghasilan = $this->cari($id);

        if ($penghasilan->terkunci) {
            abort(403, 'Data penghasilan ini sudah masuk draf. Batalkan draf bulan tersebut terlebih dahulu untuk mengubahnya.');
        }

        return view('penghasilan.edit', [
            'penghasilan' => $penghasilan,
            'awalTerbuka' => $this->kunci->awalTerbuka()->toDateString(),
            'pesanKunci' => $this->kunci->pesanBatas(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $penghasilan = $this->cari($id);

        if ($penghasilan->terkunci) {
            abort(403, 'Data penghasilan ini sudah masuk draf dan tidak dapat diubah.');
        }

        $penghasilan->update($this->sahkan($request));

        return redirect()->route('penghasilan.index')->with('sukses', 'Perubahan data penghasilan tersimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $penghasilan = $this->cari($id);

        if ($penghasilan->terkunci) {
            abort(403, 'Data penghasilan ini sudah masuk draf dan tidak dapat dihapus.');
        }

        $penghasilan->hapus();

        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan dihapus.');
    }

    /**
     * Memeriksa isian sekaligus menegakkan aturan bulan terkunci, baik saat
     * menambah transaksi baru maupun saat memindahkan tanggal transaksi lama.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function sahkan(Request $request): array
    {
        $request->merge(['nominal' => $this->bilangan($request->input('nominal'))]);

        $sah = $request->validate([
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nominal' => ['required', 'integer', 'min:1'],
            'keterangan' => ['required', 'string', 'max:255'],
        ], [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.integer' => 'Nominal hanya boleh berisi angka.',
            'nominal.min' => 'Nominal harus lebih besar dari nol.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ]);

        if ($this->kunci->terkunci($sah['tanggal'])) {
            throw ValidationException::withMessages(['tanggal' => $this->kunci->pesan($sah['tanggal'])]);
        }

        return $sah;
    }

    /** Transaksi milik wajib pajak yang sedang masuk. */
    private function milik(): Builder|HasMany
    {
        return PenggunaSaatIni::profil()->penghasilans();
    }

    private function cari(int $id): Penghasilan
    {
        return $this->milik()->findOrFail($id);
    }
}
