<?php

namespace App\Services;

use App\Models\DrafBulanan;
use App\Support\MockData;
use App\Support\PenggunaSaatIni;
use Illuminate\Support\Carbon;

/**
 * Bulan yang drafnya sudah disusun tidak boleh lagi menerima transaksi,
 * baik transaksi baru maupun transaksi lama yang tanggalnya dipindahkan
 * ke dalamnya. Tanpa aturan ini, peredaran bruto di draf yang sudah
 * disusun berubah diam-diam tanpa dihitung ulang.
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
}
