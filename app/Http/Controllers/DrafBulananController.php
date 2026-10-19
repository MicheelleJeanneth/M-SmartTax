<?php

namespace App\Http\Controllers;

use App\Models\DrafBulanan;
use App\Models\Penghasilan;
use App\Services\FilterTahun;
use App\Services\PerhitunganPajak;
use App\Support\PenggunaSaatIni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DrafBulananController extends Controller
{
    public function __construct(
        private readonly FilterTahun $filterTahun,
        private readonly PerhitunganPajak $pajak,
    ) {}

    public function index(Request $request): View
    {
        // Draf bulanan dihitung dari data penghasilan, jadi tahun yang ditawarkan
        // sama dengan di Data Penghasilan: sejak catatan pertama sampai tahun berjalan.
        $daftarTahun = $this->filterTahun->daftarPenghasilan();
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $daftarTahun);

        $draf = $this->pajak->rekapTahun($tahun);
        $tersusun = collect($draf)->whereIn('status', ['tersusun', 'nihil']);

        return view('draf-bulanan.index', [
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
            'draf' => $draf,
            'jumlahTersusun' => $tersusun->count(),
            'akumulasi' => $tersusun->sum('bruto'),
            'totalPph' => $tersusun->sum('pph_final'),
        ]);
    }

    public function create(Request $request): View
    {
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan());
        $bulan = (int) $request->query('bulan', $this->bulanBerikutnya($tahun));
        abort_unless($bulan >= 1 && $bulan <= 12, 404);

        return view('draf-bulanan.susun', $this->pajak->perhitungan($bulan, $tahun));
    }

    /**
     * Menyimpan draf satu bulan lalu mengunci transaksi bulan itu.
     */
    public function store(Request $request): RedirectResponse
    {
        [$bulan, $tahun] = $this->periode($request);

        // Draf disusun berurutan: hanya bulan tepat setelah yang terakhir tersusun.
        abort_unless($bulan === $this->bulanBerikutnya($tahun), 403, 'Susun draf bulan sebelumnya terlebih dahulu.');

        $hitung = $this->pajak->perhitungan($bulan, $tahun);

        DB::transaction(function () use ($bulan, $tahun, $hitung): void {
            $draf = DrafBulanan::create([
                'kode_draf' => sprintf('DB-%d-%02d', $tahun, $bulan),
                'id_profil' => PenggunaSaatIni::id(),
                'id_konfigurasi' => $this->pajak->konfigurasi($tahun)->id_konfigurasi,
                'tahun_pajak' => $tahun,
                'bulan' => $bulan,
                'tanggal_dibuat' => now(),
                'peredaran_bruto' => $hitung['brutoBulanIni'],
                'akumulasi_sebelumnya' => $hitung['akumulasiSebelum'],
                'akumulasi_sekarang' => $hitung['akumulasi'],
                'omzet_kena_pajak' => $hitung['omzetKenaPajak'],
                'pph_final' => $hitung['pphFinal'],
            ]);

            Penghasilan::query()
                ->where('id_profil', PenggunaSaatIni::id())
                ->tahun($tahun)->bulan($bulan)
                ->update(['id_draf_bulanan' => $draf->id_draf_bulanan]);
        });

        return redirect()->route('draf-bulanan.index', ['tahun' => $tahun])
            ->with('sukses', 'Draf PPh Final tersimpan. Data penghasilan bulan tersebut kini terkunci.');
    }

    public function show(Request $request, int $bulan): View
    {
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan());
        abort_unless($this->pajak->drafTahun($tahun)->has($bulan), 404);

        return view('draf-bulanan.lihat', $this->pajak->perhitungan($bulan, $tahun));
    }

    /**
     * Membatalkan draf: barisnya dihapus dan transaksinya terbuka kembali.
     *
     * Draf dihapus sungguhan, bukan ditandai tidak aktif, supaya bulan itu
     * dapat disusun ulang tanpa bertabrakan dengan aturan satu draf per bulan.
     */
    public function destroy(Request $request, int $bulan): RedirectResponse
    {
        $tahun = $this->filterTahun->pilih($request->input('tahun'), $this->filterTahun->daftarPenghasilan());
        $draf = $this->pajak->drafTahun($tahun)->get($bulan) ?? abort(404);

        abort_if($draf->id_draf_tahunan !== null, 403, 'Draf ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu.');
        abort_unless($bulan === $this->pajak->drafTahun($tahun)->keys()->max(), 403, 'Batalkan draf bulan setelahnya terlebih dahulu.');

        DB::transaction(function () use ($draf): void {
            Penghasilan::query()
                ->where('id_draf_bulanan', $draf->id_draf_bulanan)
                ->update(['id_draf_bulanan' => null]);

            $draf->delete();
        });

        return redirect()->route('draf-bulanan.index', ['tahun' => $tahun])
            ->with('sukses', 'Draf dibatalkan. Data penghasilan bulan tersebut dapat diubah kembali.');
    }

    /** Bulan yang giliran disusun berikutnya pada satu tahun. */
    private function bulanBerikutnya(int $tahun): int
    {
        return ($this->pajak->drafTahun($tahun)->keys()->max() ?? 0) + 1;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function periode(Request $request): array
    {
        $sah = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'digits:4', 'integer'],
        ]);

        return [(int) $sah['bulan'], $this->filterTahun->pilih($sah['tahun'], $this->filterTahun->daftarPenghasilan())];
    }
}
