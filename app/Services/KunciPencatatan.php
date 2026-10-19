<?php

namespace App\Services;

use App\Models\DrafBulanan;
use App\Models\DrafTahunan;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Carbon;

/**
 * Periode yang drafnya sudah disusun tidak boleh lagi menerima data baru,
 * baik data baru maupun data lama yang tahun atau tanggalnya dipindahkan
 * ke dalamnya. Tanpa aturan ini, angka di draf yang sudah disusun berubah
 * diam-diam tanpa dihitung ulang.
 *
 * Berlaku dua tingkat: bulanan untuk penghasilan, dan tahunan untuk harta
 * serta utang.
 */
class KunciPencatatan
{
    /**
     * Hari pertama bulan yang drafnya belum disusun, yaitu sehari setelah
     * bulan terakhir yang sudah berdraf.
     */
    public function awalTerbuka(): Carbon
    {
        $terakhir = DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->orderByDesc('tahun_pajak')
            ->orderByDesc('bulan')
            ->first();

        if ($terakhir === null) {
            // Belum ada draf sama sekali: seluruh tanggal masih terbuka.
            return Carbon::create(1900, 1, 1);
        }

        return Carbon::create($terakhir->tahun_pajak, $terakhir->bulan, 1)->addMonthNoOverflow();
    }

    /**
     * Benar bila tanggal jatuh pada bulan yang drafnya sudah disusun.
     */
    public function terkunci(string $tanggal): bool
    {
        return Carbon::parse($tanggal)->startOfDay()->lt($this->awalTerbuka());
    }

    /**
     * Pesan yang menyebut bulan tujuan beserta jalan keluarnya.
     */
    public function pesan(string $tanggal): string
    {
        $waktu = Carbon::parse($tanggal);
        $bulan = MockData::bulan()[(int) $waktu->month];

        return "Draf pajak {$bulan} ".$waktu->year.' sudah disusun, '
            .'sehingga transaksi tidak dapat dicatat pada bulan itu. '
            .'Batalkan draf bulan tersebut terlebih dahulu.';
    }

    /**
     * Tahun pajak berdraf paling awal yang akan termuat oleh satu baris harta
     * atau utang, atau null bila barisnya tidak menyentuh tahun berdraf.
     *
     * Harta dan utang ikut lampiran draf tahunan selama masih dimiliki, jadi
     * yang diperiksa bukan satu tahun melainkan seluruh rentangnya.
     *
     * @param  int  $mulai  Tahun perolehan atau tahun peminjaman.
     * @param  int|null  $selesai  Tahun pelepasan atau tahun pelunasan.
     */
    public function tahunTerkunci(int $mulai, ?int $selesai): ?int
    {
        $tahun = DrafTahunan::tahunTersusun(PenggunaSaatIni::id())
            ->filter(fn (int $tahun): bool => $tahun >= $mulai && ($selesai === null || $tahun < $selesai))
            ->min();

        return $tahun === null ? null : (int) $tahun;
    }

    /**
     * Pesan untuk periode tahunan yang sudah berdraf.
     */
    public function pesanTahun(int $tahun): string
    {
        return "Draf pajak tahunan {$tahun} sudah disusun, sehingga data pada tahun itu "
            .'tidak dapat dicatat maupun diubah. Batalkan draf tahunan tersebut terlebih dahulu.';
    }
}
