<?php

namespace App\Http\Controllers;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\Harta;
use App\Models\KodeUtang;
use App\Models\Utang;
use App\Services\AnalisisKeuangan;
use App\Services\FilterTahun;
use App\Services\PenyusunDrafTahunan;
use App\Services\PerhitunganPajak;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const JUDUL = [
        'draf-bulanan' => 'Laporan Draf Pajak Penghasilan Bulanan',
        'draf-tahunan' => 'Laporan Draf Pajak Penghasilan Tahunan',
        'harta' => 'Laporan Data Harta',
        'utang' => 'Laporan Data Utang',
    ];

    public function __construct(
        private FilterTahun $filterTahun,
        private AnalisisKeuangan $analisis,
        private PerhitunganPajak $pajak,
        private PenyusunDrafTahunan $penyusun,
    ) {}

    public function show(Request $request, string $jenis): View
    {
        $filter = $this->filter($request, $jenis);

        return view('laporan.show', [
            'jenis' => $jenis,
            'judul' => self::JUDUL[$jenis],
            ...$filter,
            'daftarTahun' => $this->filterTahun->daftar(),
            'tersedia' => $this->tersedia($jenis, $filter),
            'tanpaPenghasilan' => $this->tanpaPenghasilan($filter['tahun']),
            'tahunPenghasilanPertama' => min($this->filterTahun->daftarPenghasilan()),
        ]);
    }

    /**
     * Isi HTML laporan untuk iframe pratinjau. Memakai view PDF yang sama dengan unduhan.
     */
    public function pratinjau(Request $request, string $jenis): View
    {
        $filter = $this->filter($request, $jenis);
        abort_unless($this->tersedia($jenis, $filter), 404);

        return view("pdf.$jenis", [
            ...$this->dataLaporan($jenis, $filter),
            'pratinjau' => true,
        ]);
    }

    public function unduh(Request $request, string $jenis): Response
    {
        $filter = $this->filter($request, $jenis);
        abort_unless($this->tersedia($jenis, $filter), 404);

        $namaBerkas = $jenis.'-'.$filter['tahun'].($jenis === 'draf-bulanan' ? '-'.$filter['bulan'] : '').'.pdf';

        return Pdf::loadView("pdf.$jenis", $this->dataLaporan($jenis, $filter))
            ->setPaper('a4', 'portrait')
            ->download($namaBerkas);
    }

    /**
     * @return array{tahun: int, bulan: int}
     */
    private function filter(Request $request, string $jenis): array
    {
        $tahun = $request->filled('tahun')
            ? $this->filterTahun->pilih($request->query('tahun'))
            : $this->tahunBawaan($jenis);

        return [
            'tahun' => $tahun,
            'bulan' => $request->filled('bulan')
                ? max(1, min(12, (int) $request->query('bulan')))
                : $this->bulanBawaan($tahun),
        ];
    }

    /**
     * Laporan tahunan membuka tahun terbaru yang drafnya sudah tersusun, karena
     * tahun berjalan belum punya laporan untuk ditampilkan.
     */
    private function tahunBawaan(string $jenis): int
    {
        if ($jenis !== 'draf-tahunan') {
            return $this->filterTahun->tahunBerjalan();
        }

        $terbaru = DrafTahunan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->max('tahun_pajak');

        return $terbaru === null ? $this->filterTahun->tahunBerjalan() : (int) $terbaru;
    }

    /** Bulan terakhir yang drafnya tersusun pada tahun itu; Januari bila belum ada satu pun. */
    private function bulanBawaan(int $tahun): int
    {
        $terakhir = DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->max('bulan');

        return $terakhir === null ? 1 : (int) $terakhir;
    }

    /**
     * Laporan draf hanya ada bila drafnya memang sudah tersusun, dan itu
     * diperiksa per periode supaya laporan tahun lalu tidak ikut tertutup.
     * Laporan harta dan utang selalu ada, karena menggambarkan posisi akhir
     * tahun apa adanya, termasuk saat posisinya kosong.
     *
     * @param  array{tahun: int, bulan: int}  $filter
     */
    private function tersedia(string $jenis, array $filter): bool
    {
        $profil = PenggunaSaatIni::id();

        return match ($jenis) {
            'draf-bulanan' => DrafBulanan::query()
                ->where('id_profil', $profil)
                ->where('tahun_pajak', $filter['tahun'])
                ->where('bulan', $filter['bulan'])
                ->exists(),
            'draf-tahunan' => $this->drafTahunan($filter['tahun']) !== null,
            default => $filter['tahun'] <= $this->filterTahun->tahunBerjalan(),
        };
    }

    /**
     * Benar bila tahun itu memang tidak mungkin punya draf, yaitu belum ada
     * satu pun penghasilan tercatat. Keadaan kosongnya lalu tidak menawarkan
     * tombol menyusun, karena tidak ada yang bisa disusun.
     */
    private function tanpaPenghasilan(int $tahun): bool
    {
        return $this->analisis->peredaranBruto($tahun) === 0;
    }

    /**
     * @param  array{tahun: int, bulan: int}  $filter
     * @return array<string, mixed>
     */
    private function dataLaporan(string $jenis, array $filter): array
    {
        $dasar = [
            'judul' => self::JUDUL[$jenis],
            'profil' => PenggunaSaatIni::profil(),
            'tanggalCetak' => now(),
            ...$filter,
        ];

        return match ($jenis) {
            // Draf bulanan dihitung ulang dari transaksi yang sudah terkunci,
            // sedangkan draf tahunan dibaca dari salinan bekunya.
            'draf-bulanan' => [...$dasar, ...$this->pajak->perhitungan($filter['bulan'], $filter['tahun'])],
            'draf-tahunan' => [...$dasar, ...$this->penyusun->tersimpan(
                $this->drafTahunan($filter['tahun'])->load(['lampiranHarta', 'lampiranUtang'])
            )],
            'harta' => [
                ...$dasar,
                'kategori' => collect(MockData::kategoriHarta())
                    ->map(fn (array $info, string $kunci): array => [...$info, 'baris' => $this->harta($kunci, $filter['tahun'])]),
            ],
            'utang' => [
                ...$dasar,
                'utang' => $this->utang($filter['tahun']),
                'kodeUtang' => KodeUtang::daftarSingkat(),
            ],
        };
    }

    /** Draf tahunan milik pengguna untuk satu tahun pajak. */
    private function drafTahunan(int $tahun): ?DrafTahunan
    {
        return DrafTahunan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->first();
    }

    /**
     * Harta satu kategori yang masih dimiliki pada akhir tahun itu.
     *
     * @return Collection<int, Harta>
     */
    private function harta(string $kategori, int $tahun): Collection
    {
        return Harta::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->kategori($kategori)
            ->dimilikiPadaTahun($tahun)
            ->with(['kodeHarta', Harta::relasiRincian($kategori)])
            ->orderBy('tahun_perolehan')
            ->orderBy('id_harta')
            ->get();
    }

    /**
     * Utang yang masih berjalan pada akhir tahun itu.
     *
     * @return Collection<int, Utang>
     */
    private function utang(int $tahun): Collection
    {
        return Utang::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->berlakuPadaTahun($tahun)
            ->with('kodeUtang')
            ->orderBy('tahun_peminjaman')
            ->orderBy('id_utang')
            ->get();
    }
}
