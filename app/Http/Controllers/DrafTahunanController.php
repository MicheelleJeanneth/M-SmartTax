<?php

namespace App\Http\Controllers;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Services\AnalisisKeuangan;
use App\Services\FilterTahun;
use App\Services\PenyusunDrafTahunan;
use App\Services\PerhitunganPajak;
use App\Support\PenggunaSaatIni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DrafTahunanController extends Controller
{
    public function __construct(
        private readonly FilterTahun $filterTahun,
        private readonly AnalisisKeuangan $analisis,
        private readonly PenyusunDrafTahunan $penyusun,
        private readonly PerhitunganPajak $pajak,
    ) {}

    public function index(): View
    {
        $tersusun = $this->semuaDraf();

        $baris = collect($this->filterTahun->daftarPenghasilan())->map(function (int $tahun) use ($tersusun): array {
            $draf = $tersusun->get($tahun);

            return [
                'tahun' => $tahun,
                'draf_bulanan' => DrafBulanan::query()
                    ->where('id_profil', PenggunaSaatIni::id())
                    ->where('tahun_pajak', $tahun)
                    ->count(),
                'draf_bulanan_wajib' => $this->pajak->jumlahWajib($tahun),
                // Tahun yang drafnya tersusun memakai angka beku; sisanya keadaan sekarang.
                'bruto' => (int) ($draf?->total_peredaran_bruto ?? $this->analisis->peredaranBruto($tahun)),
                'pph_final' => (int) ($draf?->total_pph_final ?? 0),
                'kekayaan_bersih' => (int) ($draf?->kekayaan_bersih ?? $this->analisis->kekayaanBersih($tahun)),
                'status' => $draf === null ? 'belum' : 'tersusun',
            ];
        })->all();

        return view('draf-tahunan.index', [
            'draf' => $baris,
            // Hanya tahun terbaru yang tersusun yang boleh dibatalkan.
            'bolehDibatalkan' => $tersusun->keys()->max(),
        ]);
    }

    public function create(Request $request): View
    {
        $tahun = $this->tahun($request->query('tahun'));

        abort_if($this->semuaDraf()->has($tahun), 403, 'Draf tahunan untuk tahun ini sudah tersusun.');

        $lengkap = DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->count();

        // Tahun yang dimulai di tengah tidak butuh dua belas draf, melainkan
        // sebanyak bulan sejak penghasilan pertama sampai Desember.
        $wajib = $this->pajak->jumlahWajib($tahun);

        abort_if($wajib === 0 || $lengkap < $wajib, 403, 'Draf tahunan hanya dapat disusun setelah seluruh draf bulanan tahun ini selesai.');

        return view('draf-tahunan.susun', $this->penyusun->perkiraan($tahun));
    }

    public function store(Request $request): RedirectResponse
    {
        $tahun = $this->tahun($request->input('tahun'));

        abort_if($this->semuaDraf()->has($tahun), 403, 'Draf tahunan untuk tahun ini sudah tersusun.');

        $this->penyusun->susun($tahun);

        return redirect()->route('draf-tahunan.index')
            ->with('sukses', 'Draf SPT Tahunan tersimpan. Data harta dan utang tahun tersebut kini terkunci.');
    }

    public function show(int $tahun): View
    {
        $draf = $this->semuaDraf()->get($tahun) ?? abort(404);

        return view('draf-tahunan.lihat', $this->penyusun->tersimpan($draf->load(['lampiranHarta', 'lampiranUtang'])));
    }

    public function destroy(int $tahun): RedirectResponse
    {
        $semua = $this->semuaDraf();
        $draf = $semua->get($tahun) ?? abort(404);

        // Draf tahun berikutnya memakai tahun ini sebagai pembanding, jadi
        // pembatalan harus dimulai dari yang terbaru.
        abort_unless($tahun === $semua->keys()->max(), 403, 'Batalkan draf tahun setelahnya terlebih dahulu.');

        $this->penyusun->batalkan($draf);

        return redirect()->route('draf-tahunan.index')
            ->with('sukses', 'Draf tahunan dibatalkan. Data harta, utang, dan draf bulanan dapat diubah kembali.');
    }

    /** @return Collection<int, DrafTahunan> */
    private function semuaDraf()
    {
        return DrafTahunan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->get()
            ->keyBy('tahun_pajak');
    }

    private function tahun(mixed $tahun): int
    {
        return $this->filterTahun->pilih($tahun, $this->filterTahun->daftarPenghasilan());
    }
}
