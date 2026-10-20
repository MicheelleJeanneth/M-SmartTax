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
        $terakhir = $this->drafTerakhir();

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
     * Pesan yang menyebut draf yang sebenarnya menahan tanggal itu, beserta
     * jalan keluar yang memang bisa dijalankan.
     *
     * Ada tiga keadaan, dan ketiganya pernah salah disebut sebagai satu hal:
     * drafnya sudah masuk draf tahunan sehingga lapisan itu yang harus dibuka
     * lebih dahulu; drafnya berdiri sendiri sehingga cukup draf bulan itu yang
     * dibatalkan; atau bulan itu tidak punya draf sama sekali dan yang menahan
     * adalah batas global, yaitu draf terakhir yang sudah disusun.
     */
    public function pesan(string $tanggal): string
    {
        $waktu = Carbon::parse($tanggal);
        $bulan = MockData::bulan()[(int) $waktu->month].' '.$waktu->year;
        $draf = $this->draf((int) $waktu->year, (int) $waktu->month);

        if ($draf === null) {
            return $this->pesanBatas();
        }

        if ($draf->id_draf_tahunan !== null) {
            return "Draf pajak tahunan {$waktu->year} sudah disusun, sehingga transaksi tidak dapat "
                ."dicatat pada {$bulan}. Batalkan draf tahunan tersebut terlebih dahulu.";
        }

        return "Draf pajak {$bulan} sudah disusun, sehingga transaksi tidak dapat dicatat "
            .'pada bulan itu. Batalkan draf bulan tersebut terlebih dahulu.';
    }

    /**
     * Pesan untuk tanggal yang bulannya tidak berdraf, tetapi tertahan batas
     * global. Menyebut draf Mei 2023 "sudah disusun" padahal tidak ada hanya
     * menyesatkan, jadi yang disebut draf penahannya.
     */
    private function pesanBatas(): string
    {
        $awal = $this->awalTerbuka();
        $penahan = $this->drafTerakhir();

        $sejak = MockData::bulan()[(int) $awal->month].' '.$awal->year;

        if ($penahan === null) {
            return "Transaksi hanya dapat dicatat mulai {$sejak}.";
        }

        $namaPenahan = MockData::bulan()[(int) $penahan->bulan].' '.$penahan->tahun_pajak;

        return "Transaksi hanya dapat dicatat mulai {$sejak}, karena draf pajak {$namaPenahan} "
            .'sudah disusun. Batalkan draf tersebut terlebih dahulu bila ingin mencatat tanggal sebelumnya.';
    }

    /** Draf satu bulan tertentu milik wajib pajak yang sedang masuk. */
    private function draf(int $tahun, int $bulan): ?DrafBulanan
    {
        return DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->where('tahun_pajak', $tahun)
            ->where('bulan', $bulan)
            ->first();
    }

    /** Draf paling akhir yang sudah disusun; batas global diturunkan dari sini. */
    private function drafTerakhir(): ?DrafBulanan
    {
        return DrafBulanan::query()
            ->where('id_profil', PenggunaSaatIni::id())
            ->orderByDesc('tahun_pajak')
            ->orderByDesc('bulan')
            ->first();
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
