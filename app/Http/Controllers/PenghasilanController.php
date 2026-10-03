<?php

namespace App\Http\Controllers;

use App\Services\FilterTahun;
use App\Services\KunciPencatatan;
use App\Support\MockData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenghasilanController extends Controller
{
    /** Banyak baris per halaman pada tabel. */
    private const PER_HALAMAN = 10;

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

        $terpilih = collect(MockData::penghasilan())
            ->filter(function (array $p) use ($tahun, $bulan, $cari): bool {
                $tanggal = Carbon::parse($p['tanggal']);

                return (int) $tanggal->year === $tahun
                    && ($bulan === 0 || (int) $tanggal->month === $bulan)
                    && ($cari === '' || str_contains(mb_strtolower($p['keterangan']), mb_strtolower($cari)));
            })
            // Terbaru di atas: transaksi terbaru yang paling sering diubah.
            ->sortByDesc('tanggal')
            ->values();

        return view('penghasilan.index', [
            'penghasilan' => $this->halaman($terpilih, $request),
            'total' => $terpilih->sum('nominal'),
            'jumlahTransaksi' => $terpilih->count(),
            'tahun' => $tahun,
            'bulan' => $bulan,
            'cari' => $cari,
            'daftarTahun' => $daftarTahun,
        ]);
    }

    public function create(): View
    {
        return view('penghasilan.create', ['awalTerbuka' => $this->kunci->awalTerbuka()->toDateString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->pastikanBulanTerbuka($request);

        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan tersimpan.');
    }

    public function show(int $id): View
    {
        return view('penghasilan.show', ['penghasilan' => $this->cari($id)]);
    }

    public function edit(int $id): View
    {
        $penghasilan = $this->cari($id);

        if ($penghasilan['terkunci']) {
            abort(403, 'Data penghasilan ini sudah masuk draf. Batalkan draf bulan tersebut terlebih dahulu untuk mengubahnya.');
        }

        return view('penghasilan.edit', [
            'penghasilan' => $penghasilan,
            'awalTerbuka' => $this->kunci->awalTerbuka()->toDateString(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        if ($this->cari($id)['terkunci']) {
            abort(403, 'Data penghasilan ini sudah masuk draf dan tidak dapat diubah.');
        }

        $this->pastikanBulanTerbuka($request);

        return redirect()->route('penghasilan.index')->with('sukses', 'Perubahan data penghasilan tersimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        return redirect()->route('penghasilan.index')->with('sukses', 'Data penghasilan dihapus.');
    }

    /**
     * Menolak tanggal yang jatuh pada bulan yang drafnya sudah disusun, baik saat
     * menambah transaksi baru maupun saat memindahkan tanggal transaksi lama.
     *
     * @throws ValidationException
     */
    private function pastikanBulanTerbuka(Request $request): void
    {
        $tanggal = (string) $request->input('tanggal', '');

        if ($tanggal !== '' && $this->kunci->terkunci($tanggal)) {
            throw ValidationException::withMessages(['tanggal' => $this->kunci->pesan($tanggal)]);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function halaman(Collection $baris, Request $request): LengthAwarePaginator
    {
        $halaman = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $baris->forPage($halaman, self::PER_HALAMAN)->values(),
            $baris->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function cari(int $id): array
    {
        return collect(MockData::penghasilan())->firstWhere('id', $id) ?? abort(404);
    }
}
