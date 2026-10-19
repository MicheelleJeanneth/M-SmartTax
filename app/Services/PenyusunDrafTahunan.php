<?php

namespace App\Services;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Models\DrafTahunanHarta;
use App\Models\DrafTahunanUtang;
use App\Models\Harta;
use App\Models\Utang;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun, membaca, dan membatalkan draf SPT Tahunan.
 *
 * Halaman Susun menghitung dari data yang hidup sekarang, sedangkan halaman
 * Rincian membaca salinan beku yang tersimpan saat draf disusun. Pemisahan
 * itulah yang membuat laporan tahun lama tidak ikut berubah ketika harta atau
 * utang diperbarui.
 */
class PenyusunDrafTahunan
{
    public function __construct(
        private AnalisisKeuangan $analisis,
        private PerhitunganPajak $pajak,
    ) {}

    /**
     * Perkiraan isi draf dari data yang hidup sekarang, untuk halaman Susun.
     *
     * @return array<string, mixed>
     */
    public function perkiraan(int $tahun): array
    {
        $harta = $this->hartaHidup($tahun);
        $utang = $this->utangHidup($tahun);

        return $this->rakit(
            $tahun,
            $harta->map(fn (Harta $h): array => [
                'kategori' => $h->kodeHarta->kategori,
                'nilai' => (int) $h->nilai,
            ]),
            $utang->map(fn (Utang $u): array => [
                'kode' => $u->kodeUtang->kode,
                'deskripsi' => $u->deskripsi,
                'kreditur' => $u->nama_kreditur,
                'saldo' => (int) $u->saldo,
            ]),
        );
    }

    /**
     * Isi draf yang sudah tersimpan, dibaca dari salinan bekunya.
     *
     * @return array<string, mixed>
     */
    public function tersimpan(DrafTahunan $draf): array
    {
        return $this->rakit(
            (int) $draf->tahun_pajak,
            $draf->lampiranHarta->map(fn (DrafTahunanHarta $h): array => [
                'kategori' => $h->kategori,
                'nilai' => (int) $h->nilai,
            ]),
            $draf->lampiranUtang->map(fn (DrafTahunanUtang $u): array => [
                'kode' => $u->kode,
                'deskripsi' => $u->deskripsi,
                'kreditur' => $u->nama_kreditur,
                'saldo' => (int) $u->saldo,
            ]),
            $draf,
        );
    }

    /**
     * Menyimpan draf beserta salinan harta dan utangnya, lalu mengunci draf
     * bulanan, harta, dan utang tahun itu.
     */
    public function susun(int $tahun): DrafTahunan
    {
        $bulanan = $this->bulanan($tahun);

        if ($bulanan->count() < 12) {
            abort(403, 'Draf tahunan hanya dapat disusun setelah seluruh dua belas draf bulanan selesai.');
        }

        $harta = $this->hartaHidup($tahun);
        $utang = $this->utangHidup($tahun);
        $isi = $this->perkiraan($tahun);

        return DB::transaction(function () use ($tahun, $bulanan, $harta, $utang, $isi): DrafTahunan {
            $draf = DrafTahunan::create([
                'kode_draf' => 'DT-'.$tahun,
                'id_profil' => PenggunaSaatIni::id(),
                'id_konfigurasi' => $this->pajak->konfigurasi($tahun)->id_konfigurasi,
                'tahun_pajak' => $tahun,
                'tanggal_dibuat' => now(),
                'total_peredaran_bruto' => $isi['bruto'],
                'total_pph_final' => $isi['pph'],
                'total_harta' => $isi['totalHarta'],
                'total_utang' => $isi['totalUtang'],
                'kekayaan_bersih' => $isi['kekayaanBersih'],
                'pertumbuhan_kekayaan' => $isi['pertumbuhanPersen'],
                // Disimpan sebagai pecahan; tampilan yang mengalikan seratus.
                'rasio_konsistensi' => $isi['rasioKonsistensi'] === null ? null : $isi['rasioKonsistensi'] / 100,
                'status_konsistensi' => $isi['statusKonsistensi'],
            ]);

            foreach ($harta as $h) {
                DrafTahunanHarta::create([
                    'id_draf_tahunan' => $draf->id_draf_tahunan,
                    'kategori' => $h->kodeHarta->kategori,
                    'kode' => $h->kodeHarta->kode,
                    'deskripsi' => $h->deskripsi,
                    'nilai' => $h->nilai,
                ]);
            }

            foreach ($utang as $u) {
                DrafTahunanUtang::create([
                    'id_draf_tahunan' => $draf->id_draf_tahunan,
                    'kode' => $u->kodeUtang->kode,
                    'deskripsi' => $u->deskripsi,
                    'nama_kreditur' => $u->nama_kreditur,
                    'saldo' => $u->saldo,
                ]);
            }

            DrafBulanan::whereIn('id_draf_bulanan', $bulanan->pluck('id_draf_bulanan'))
                ->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);
            Harta::whereIn('id_harta', $harta->pluck('id_harta'))
                ->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);
            Utang::whereIn('id_utang', $utang->pluck('id_utang'))
                ->update(['id_draf_tahunan' => $draf->id_draf_tahunan]);

            return $draf;
        });
    }

    /**
     * Membatalkan draf: salinannya ikut terhapus dan seluruh penguncian dibuka.
     */
    public function batalkan(DrafTahunan $draf): void
    {
        DB::transaction(function () use ($draf): void {
            DrafBulanan::where('id_draf_tahunan', $draf->id_draf_tahunan)->update(['id_draf_tahunan' => null]);
            Harta::where('id_draf_tahunan', $draf->id_draf_tahunan)->update(['id_draf_tahunan' => null]);
            Utang::where('id_draf_tahunan', $draf->id_draf_tahunan)->update(['id_draf_tahunan' => null]);

            // Lampiran ikut terhapus lewat kunci asing bercabang hapus.
            $draf->delete();
        });
    }

    /**
     * Menyusun payload yang sama bentuknya untuk halaman Susun dan Rincian.
     *
     * @param  Collection<int, array<string, mixed>>  $harta
     * @param  Collection<int, array<string, mixed>>  $utang
     * @return array<string, mixed>
     */
    private function rakit(int $tahun, $harta, $utang, ?DrafTahunan $draf = null): array
    {
        $rekap = collect($this->pajak->rekapTahun($tahun))
            ->whereIn('status', ['tersusun', 'nihil'])
            ->keyBy('bulan')
            ->all();

        $bruto = (int) collect($rekap)->sum('bruto');
        $pph = (int) collect($rekap)->sum('pph_final');

        $perKategori = $harta->groupBy('kategori');
        $ringkasanHarta = collect(MockData::kategoriHarta())->map(fn (array $info, string $kunci): array => [
            'nama' => $info['nama'],
            'jumlah' => $perKategori->get($kunci)?->count() ?? 0,
            'nilai' => (int) ($perKategori->get($kunci)?->sum('nilai') ?? 0),
        ]);

        $totalHarta = (int) $harta->sum('nilai');
        $totalUtang = (int) $utang->sum('saldo');
        $bersih = $totalHarta - $totalUtang;

        $lalu = DrafTahunan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun - 1)
            ->first();

        $kekayaanLalu = $lalu?->kekayaan_bersih === null ? null : (int) $lalu->kekayaan_bersih;
        $pertumbuhan = $kekayaanLalu === null ? null : $bersih - $kekayaanLalu;

        // Rasio konsistensi ditampilkan dalam persen, memakai penghasilan neto
        // (bruto dikurangi PPh) sebagai pembagi.
        $neto = $bruto - $pph;
        $rasio = $pertumbuhan === null || $neto <= 0 ? null : $pertumbuhan / $neto * 100;

        return [
            'tahun' => $tahun,
            'rekap' => $rekap,
            'bruto' => $bruto,
            'pph' => $pph,
            'harta' => $ringkasanHarta,
            'utang' => $utang,
            'totalHarta' => $totalHarta,
            'totalUtang' => $totalUtang,
            'kekayaanBersih' => $bersih,
            'kekayaanTahunLalu' => $kekayaanLalu,
            'pertumbuhan' => $pertumbuhan,
            'pertumbuhanPersen' => $kekayaanLalu > 0 ? $pertumbuhan / $kekayaanLalu * 100 : null,
            'rasioKonsistensi' => $rasio,
            'statusKonsistensi' => $rasio === null ? null : $this->analisis->status($rasio / 100),
            'draf' => $draf,
        ];
    }

    /** @return Collection<int, DrafBulanan> */
    private function bulanan(int $tahun)
    {
        return DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->get();
    }

    /** @return Collection<int, Harta> */
    private function hartaHidup(int $tahun)
    {
        return Harta::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->dimilikiPadaTahun($tahun)
            ->with('kodeHarta')
            ->get();
    }

    /** @return Collection<int, Utang> */
    private function utangHidup(int $tahun)
    {
        return Utang::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->berlakuPadaTahun($tahun)
            ->with('kodeUtang')
            ->get();
    }
}
