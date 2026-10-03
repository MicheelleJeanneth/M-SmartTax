<?php

namespace App\Services;

use App\Support\MockData;
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
     * Hari pertama bulan yang drafnya belum disusun.
     */
    public function awalTerbuka(): Carbon
    {
        return Carbon::create(MockData::TAHUN, 1, 1)->addMonths(MockData::BULAN_TERSUSUN);
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
        $bulan = MockData::bulan()[(int) Carbon::parse($tanggal)->month];

        return "Draf pajak {$bulan} ".Carbon::parse($tanggal)->year.' sudah disusun, '
            .'sehingga transaksi tidak dapat dicatat pada bulan itu. '
            .'Batalkan draf bulan tersebut terlebih dahulu.';
    }
}
