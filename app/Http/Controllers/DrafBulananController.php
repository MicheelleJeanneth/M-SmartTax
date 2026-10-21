<?php

namespace App\Http\Controllers;

use App\Models\DrafBulanan;
use App\Models\Penghasilan;
use App\Services\FilterTahun;
use App\Services\PerhitunganPajak;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            // Seluruh penghasilan tercatat tahun itu, terlepas dari apakah
            // drafnya sudah disusun. Angka ini fakta dari data penghasilan.
            'brutoTahun' => (int) collect($draf)->sum('bruto'),
            'pphTahun' => (int) collect($draf)->sum('pph_final'),
            // Tahun yang dimulai di tengah lengkap dengan draf lebih sedikit dari dua belas.
            'jumlahWajib' => $this->pajak->jumlahWajib($tahun),
            'akumulasi' => $tersusun->sum('bruto'),
            'totalPph' => $tersusun->sum('pph_final'),
        ]);
    }

    public function create(Request $request): View
    {
        $tahun = $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan());
        // bulanBerikutnya bernilai null karena dua sebab: tahun itu belum
        // menjadi kewajiban sama sekali, atau drafnya sudah lengkap. Hanya
        // sebab pertama yang perlu ditolak di sini.
        abort_if(
            $this->pajak->jumlahWajib($tahun) === 0,
            403,
            'Belum ada penghasilan tercatat pada tahun ini, jadi belum ada draf yang dapat disusun.',
        );

        // Tanpa bulan di alamatnya, dibuka bulan yang giliran disusun. Tahun
        // yang sudah lengkap tidak punya giliran, jadi berhenti sebagai 404.
        $bulan = (int) $request->query('bulan', $this->pajak->bulanBerikutnya($tahun) ?? 13);
        abort_unless($bulan >= 1 && $bulan <= 12, 404);
        $this->tolakMasaBerjalan($tahun, $bulan);

        return view('draf-bulanan.susun', $this->pajak->perhitungan($bulan, $tahun));
    }

    /**
     * Menyimpan draf satu bulan lalu mengunci transaksi bulan itu.
     */
    public function store(Request $request): RedirectResponse
    {
        [$bulan, $tahun] = $this->periode($request);

        abort_if(
            $this->pajak->jumlahWajib($tahun) === 0,
            403,
            'Belum ada penghasilan tercatat pada tahun ini, jadi belum ada draf yang dapat disusun.',
        );

        $this->tolakMasaBerjalan($tahun, $bulan);

        // Draf disusun berurutan, mulai dari bulan pertama yang wajib.
        abort_unless($bulan === $this->pajak->bulanBerikutnya($tahun), 403, 'Susun draf bulan sebelumnya terlebih dahulu.');

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
     * Membatalkan draf satu bulan beserta seluruh bulan sesudahnya.
     *
     * Akumulasi peredaran bruto dihitung berurutan, jadi draf bulan sesudahnya
     * tidak lagi sah begitu bulan sebelumnya dibuka. Tanpa pembatalan berantai,
     * bulan yang jauh ke belakang mustahil dibatalkan padahal pesan kesalahan
     * pada Data Penghasilan menyuruh membatalkannya.
     *
     * Draf dihapus sungguhan, bukan ditandai tidak aktif, supaya bulan itu
     * dapat disusun ulang tanpa bertabrakan dengan aturan satu draf per bulan.
     */
    public function destroy(Request $request, int $bulan): RedirectResponse
    {
        $tahun = $this->filterTahun->pilih($request->input('tahun'), $this->filterTahun->daftarPenghasilan());
        $draf = $this->pajak->drafTahun($tahun);

        abort_unless($draf->has($bulan), 404);

        $ikut = $draf->filter(fn (DrafBulanan $d): bool => (int) $d->bulan >= $bulan);

        // Draf tahunan mengunci kedua belas bulannya sekaligus, jadi lapisan
        // itu harus dibuka lebih dahulu dari halaman Draf Tahunan.
        abort_if(
            $ikut->contains(fn (DrafBulanan $d): bool => $d->id_draf_tahunan !== null),
            403,
            'Draf ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu.',
        );

        DB::transaction(function () use ($ikut): void {
            $id = $ikut->pluck('id_draf_bulanan');

            Penghasilan::query()->whereIn('id_draf_bulanan', $id)->update(['id_draf_bulanan' => null]);
            DrafBulanan::query()->whereIn('id_draf_bulanan', $id)->delete();
        });

        $nama = MockData::bulan()[$bulan];

        return redirect()->route('draf-bulanan.index', ['tahun' => $tahun])
            ->with('sukses', $ikut->count() === 1
                ? "Draf {$nama} dibatalkan. Data penghasilan bulan tersebut dapat diubah kembali."
                : "{$ikut->count()} draf dibatalkan, mulai {$nama}. Data penghasilannya dapat diubah kembali.");
    }

    /**
     * Menolak penyusunan draf untuk bulan yang masa pajaknya belum berakhir.
     */
    private function tolakMasaBerjalan(int $tahun, int $bulan): void
    {
        if ($this->pajak->masaBerakhir($tahun, $bulan)) {
            return;
        }

        $akhir = Carbon::create($tahun, $bulan)->endOfMonth();

        abort(403, sprintf(
            'Draf %s %d baru dapat disusun setelah masa pajaknya berakhir pada %s.',
            MockData::bulan()[$bulan], $tahun, $akhir->translatedFormat('j F Y'),
        ));
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
