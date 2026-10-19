<?php

namespace App\Http\Controllers;

use App\Services\AnalisisKeuangan;
use App\Services\FilterTahun;
use App\Support\MockData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        return view("pdf.$jenis", [
            ...$this->dataLaporan($jenis, $this->filter($request, $jenis)),
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
        // Laporan tahunan membuka tahun terbaru yang drafnya sudah tersusun,
        // karena tahun berjalan belum punya laporan untuk ditampilkan.
        $bawaan = $jenis === 'draf-tahunan'
            ? MockData::tahunBolehDibatalkan() ?? MockData::TAHUN
            : MockData::TAHUN;

        return [
            'tahun' => $request->filled('tahun')
                ? $this->filterTahun->pilih($request->query('tahun'))
                : $bawaan,
            'bulan' => (int) $request->query('bulan', MockData::BULAN_TERSUSUN),
        ];
    }

    /**
     * Laporan draf hanya ada bila drafnya memang sudah tersusun. Jumlah bulan
     * tersusun dibaca per tahun, bukan hanya tahun berjalan, supaya laporan
     * bulanan tahun lalu tidak ikut tertutup.
     *
     * @param  array{tahun: int, bulan: int}  $filter
     */
    private function tersedia(string $jenis, array $filter): bool
    {
        return match ($jenis) {
            'draf-bulanan' => $filter['bulan'] <= MockData::bulanTersusun($filter['tahun']),
            'draf-tahunan' => collect(MockData::drafTahunan())->where('tahun', $filter['tahun'])->where('status', 'tersusun')->isNotEmpty(),
            default => $filter['tahun'] <= MockData::TAHUN,
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
            'profil' => MockData::profil(),
            'tanggalCetak' => now(),
            ...$filter,
        ];

        return match ($jenis) {
            'draf-bulanan' => [...$dasar, ...MockData::perhitunganBulanan($filter['bulan'])],
            'draf-tahunan' => [...$dasar, ...MockData::drafTahunanRinci($filter['tahun'])],
            'harta' => [
                ...$dasar,
                'kategori' => collect(MockData::kategoriHarta())
                    ->map(fn (array $info, string $kunci): array => [...$info, 'baris' => MockData::harta($kunci)]),
            ],
            'utang' => [...$dasar, 'utang' => MockData::utang()],
        };
    }
}
