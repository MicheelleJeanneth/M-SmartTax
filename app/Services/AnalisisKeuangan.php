<?php

namespace App\Services;

use App\Support\MockData;
use Illuminate\Support\Carbon;

/**
 * Perhitungan harta, utang, dan analisis keuangan untuk Dashboard,
 * Draf Tahunan, dan Simulasi Aset. Seluruhnya dihitung di sisi server.
 */
class AnalisisKeuangan
{
    /** Batas rasio konsistensi: di bawah 0,60 normal; sampai 0,90 perlu ditinjau. */
    private const BATAS_NORMAL = 0.60;

    private const BATAS_TINJAU = 0.90;

    /**
     * Harta dihitung bila diperoleh sampai tahun itu dan belum dilepas.
     */
    public function totalHarta(int $tahun): int
    {
        return collect($this->harta($tahun))->sum('nilai');
    }

    /**
     * Utang dihitung bila dipinjam sampai tahun itu dan belum lunas.
     */
    public function totalUtang(int $tahun): int
    {
        return collect(MockData::utang())
            ->filter(fn (array $u): bool => $u['tahun'] <= $tahun
                && ($u['tahun_pelunasan'] === null || $u['tahun_pelunasan'] > $tahun))
            ->sum('saldo');
    }

    public function kekayaanBersih(int $tahun): int
    {
        return $this->totalHarta($tahun) - $this->totalUtang($tahun);
    }

    /** Peredaran bruto tahun terpilih, dijumlahkan dari transaksi penghasilan. */
    public function peredaranBruto(int $tahun): int
    {
        return collect(MockData::penghasilan())
            ->filter(fn (array $p): bool => (int) Carbon::parse($p['tanggal'])->year === $tahun)
            ->sum('nominal');
    }

    /**
     * Peredaran bruto per bulan, selalu dua belas bulan termasuk yang bernilai nol.
     *
     * @return array<int, int>
     */
    public function brutoPerBulan(int $tahun): array
    {
        $bulan = array_fill(1, 12, 0);

        foreach (MockData::penghasilan() as $p) {
            $tanggal = Carbon::parse($p['tanggal']);

            if ((int) $tanggal->year === $tahun) {
                $bulan[(int) $tanggal->month] += $p['nominal'];
            }
        }

        return $bulan;
    }

    /**
     * Nilai harta per kategori, kategori kosong tidak disertakan.
     *
     * @return array<string, int>
     */
    public function hartaPerKategori(int $tahun): array
    {
        $kategori = [];

        foreach (MockData::kategoriHarta() as $kunci => $info) {
            $nilai = collect($this->harta($tahun, $kunci))->sum('nilai');

            if ($nilai > 0) {
                $kategori[$info['singkat']] = $nilai;
            }
        }

        arsort($kategori);

        return $kategori;
    }

    /**
     * Pertumbuhan kekayaan bersih dibanding draf tahunan tahun sebelumnya.
     *
     * @return array{ada_pembanding: bool, kekayaan_lalu: int|null, kekayaan_kini: int, selisih: int|null, persen: float|null}
     */
    public function pertumbuhanKekayaan(int $tahun): array
    {
        $lalu = $this->drafTahunLalu($tahun);
        $kini = $this->kekayaanBersih($tahun);

        if ($lalu === null) {
            return ['ada_pembanding' => false, 'kekayaan_lalu' => null, 'kekayaan_kini' => $kini, 'selisih' => null, 'persen' => null];
        }

        $selisih = $kini - $lalu['kekayaan_bersih'];

        return [
            'ada_pembanding' => true,
            'kekayaan_lalu' => $lalu['kekayaan_bersih'],
            'kekayaan_kini' => $kini,
            'selisih' => $selisih,
            // Persentase hanya dihitung bila penyebutnya positif.
            'persen' => $lalu['kekayaan_bersih'] > 0 ? $selisih / $lalu['kekayaan_bersih'] * 100 : null,
        ];
    }

    /**
     * Pertambahan kekayaan bersih dibanding peredaran bruto tahun terpilih.
     *
     * @return array{ada_pembanding: bool, pertambahan_harta: int|null, pertambahan_utang: int|null, pertambahan_bersih: int|null, bruto: int, rasio: float|null, status: string|null}
     */
    public function rasioKonsistensi(int $tahun): array
    {
        $lalu = $this->drafTahunLalu($tahun);
        $bruto = $this->peredaranBruto($tahun);

        if ($lalu === null) {
            return ['ada_pembanding' => false, 'pertambahan_harta' => null, 'pertambahan_utang' => null,
                'pertambahan_bersih' => null, 'bruto' => $bruto, 'rasio' => null, 'status' => null];
        }

        $pertambahanHarta = $this->totalHarta($tahun) - $lalu['total_harta'];
        $pertambahanUtang = $this->totalUtang($tahun) - $lalu['total_utang'];
        $pertambahanBersih = $pertambahanHarta - $pertambahanUtang;
        $rasio = $bruto > 0 ? $pertambahanBersih / $bruto : null;

        return [
            'ada_pembanding' => true,
            'pertambahan_harta' => $pertambahanHarta,
            'pertambahan_utang' => $pertambahanUtang,
            'pertambahan_bersih' => $pertambahanBersih,
            'bruto' => $bruto,
            'rasio' => $rasio,
            'status' => $rasio === null ? null : $this->status($rasio),
        ];
    }

    public function status(float $rasio): string
    {
        return match (true) {
            $rasio < self::BATAS_NORMAL => 'normal',
            $rasio <= self::BATAS_TINJAU => 'tinjau',
            default => 'periksa',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function harta(int $tahun, ?string $kategori = null): array
    {
        $kunci = $kategori !== null ? [$kategori] : array_keys(MockData::kategoriHarta());
        $baris = collect();

        foreach ($kunci as $k) {
            $baris = $baris->merge(MockData::harta($k));
        }

        return $baris
            ->filter(fn (array $h): bool => $h['tahun'] <= $tahun
                && ($h['tahun_pelepasan'] === null || $h['tahun_pelepasan'] > $tahun))
            ->values()
            ->all();
    }

    /**
     * Draf tahunan tahun sebelumnya, dipakai sebagai pembanding.
     *
     * @return array<string, mixed>|null
     */
    private function drafTahunLalu(int $tahun): ?array
    {
        return collect(MockData::drafTahunan())
            ->firstWhere(fn (array $d): bool => $d['tahun'] === $tahun - 1 && $d['status'] === 'tersusun');
    }
}
