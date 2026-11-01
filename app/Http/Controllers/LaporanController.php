<?php

namespace App\Http\Controllers;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\DrafTahunanHarta;
use App\Models\DrafTahunanUtang;
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
use Illuminate\Database\Eloquent\Model;
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

    /**
     * Draf tahunan yang lampirannya sedang dipakai, berkunci tahun pajak;
     * false bila tahun itu belum berdraf.
     *
     * @var array<int, DrafTahunan|false>
     */
    private array $drafBeku = [];

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
            'daftarTahun' => $this->filterTahun->daftarPenghasilan(),
            'tersedia' => $this->tersedia($jenis, $filter),
            'tanpaPenghasilan' => $this->tanpaPenghasilan($filter['tahun']),
            'diluarMasaPajak' => $this->diluarMasaPajak($jenis, $filter),
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
        // Seluruh laporan di sini adalah laporan pajak, jadi yang ditawarkan
        // hanya tahun pajak. Dulu daftarnya ikut memuat tahun perolehan harta
        // dan tahun peminjaman utang, sehingga tahun sebelum penghasilan
        // pertama pun dapat dipilih — lalu laporannya mencetak "Posisi 31
        // Desember" untuk tahun yang tidak pernah punya kewajiban pajak.
        $tahun = $request->filled('tahun')
            ? $this->filterTahun->pilih($request->query('tahun'), $this->filterTahun->daftarPenghasilan())
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

    /**
     * Bulan terakhir yang drafnya tersusun pada tahun itu.
     *
     * Bila belum ada satu pun, dibuka bulan pertama yang menjadi kewajiban
     * wajib pajak — bukan Januari, yang bagi pengguna baru justru bulan yang
     * tidak pernah bisa disusun.
     */
    private function bulanBawaan(int $tahun): int
    {
        $terakhir = DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->max('bulan');

        return $terakhir === null
            ? ($this->pajak->bulanPertama($tahun) ?? 1)
            : (int) $terakhir;
    }

    /**
     * Benar bila bulan yang dipilih berada sebelum penghasilan pertama
     * tercatat, sehingga bukan masa pajak pengguna dan tidak akan pernah
     * punya draf.
     *
     * @param  array{tahun: int, bulan: int}  $filter
     */
    private function diluarMasaPajak(string $jenis, array $filter): bool
    {
        if ($jenis !== 'draf-bulanan') {
            return false;
        }

        $pertama = $this->pajak->bulanPertama($filter['tahun']);

        return $pertama !== null && $filter['bulan'] < $pertama;
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
            // Lampiran harta memuat sampai dua belas kolom, jadi tabelnya
            // dirapatkan agar tetap muat pada A4 tegak.
            'padat' => $jenis === 'harta',
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
                // Kolom laporan berbeda dari kolom layar: lampiran SPT memuat
                // seluruh rincian, termasuk yang di layar disembunyikan.
                'kategori' => collect(MockData::kategoriHarta())
                    ->map(fn (array $info, string $kunci): array => [
                        ...$info,
                        'laporan' => MockData::kolomLaporanHarta()[$kunci],
                        'baris' => $this->harta($kunci, $filter['tahun']),
                    ]),
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
        $harta = Harta::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->kategori($kategori)
            ->dimilikiPadaTahun($tahun)
            ->with(['kodeHarta', Harta::relasiRincian($kategori)])
            ->orderBy('tahun_perolehan')
            ->orderBy('id_harta')
            ->get();

        $beku = $this->salinanBeku($tahun, 'lampiranHarta', 'id_harta');

        return $harta->each(function (Harta $h) use ($beku): void {
            $salinan = $beku->get($h->id_harta);

            if ($salinan !== null) {
                $h->nilai = $salinan->nilai;
                $h->nilai_saat_ini = $salinan->nilai_kini;
            }
        });
    }

    /**
     * Utang yang masih berjalan pada akhir tahun itu.
     *
     * @return Collection<int, Utang>
     */
    private function utang(int $tahun): Collection
    {
        $utang = Utang::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->berlakuPadaTahun($tahun)
            ->with('kodeUtang')
            ->orderBy('tahun_peminjaman')
            ->orderBy('id_utang')
            ->get();

        $beku = $this->salinanBeku($tahun, 'lampiranUtang', 'id_utang');

        return $utang->each(function (Utang $u) use ($beku): void {
            $salinan = $beku->get($u->id_utang);

            if ($salinan !== null) {
                $u->saldo = $salinan->saldo;
            }
        });
    }

    /**
     * Salinan beku lampiran draf tahunan, berkunci penanda baris aslinya.
     *
     * Tahun yang sudah berdraf memakai angka dari salinan ini, bukan angka
     * sekarang. Tanpa itu, memperbarui saldo utang tahun berjalan ikut
     * mengubah Laporan Data Utang tahun-tahun sebelumnya — padahal saldo
     * memang harus diperbarui agar draf tahun berikutnya benar.
     *
     * Hanya kolom uangnya yang diambil dari salinan. Jati diri dan periodenya
     * tetap dari baris asli, karena kolom itu terkunci begitu drafnya disusun
     * sehingga tidak mungkin berbeda.
     *
     * Kosong berarti tahun itu belum berdraf, atau drafnya disusun sebelum
     * penanda barisnya ada; keduanya jatuh ke angka sekarang seperti semula.
     *
     * @param  string  $lampiran  Nama relasi lampirannya.
     * @param  string  $penanda  Kolom penanda baris aslinya.
     * @return Collection<int, DrafTahunanHarta|DrafTahunanUtang>
     */
    private function salinanBeku(int $tahun, string $lampiran, string $penanda): Collection
    {
        // Laporan harta memanggil ini sekali per kategori, jadi drafnya dicari
        // dan lampirannya dimuat satu kali saja per tahun.
        $this->drafBeku[$tahun] ??= $this->drafTahunan($tahun)?->load(['lampiranHarta', 'lampiranUtang']) ?? false;

        if ($this->drafBeku[$tahun] === false) {
            return collect();
        }

        return $this->drafBeku[$tahun]->{$lampiran}
            ->filter(fn (Model $baris): bool => $baris->{$penanda} !== null)
            ->keyBy($penanda);
    }
}
