<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melepas keunikan global pada kode_draf.
     *
     * Kodenya hanya memuat masa pajak, misalnya DB-2026-06, sehingga wajib
     * pajak kedua yang menyusun masa pajak yang sama menabrak kunci milik
     * wajib pajak pertama dan penyusunannya gagal. Aturan yang sebenarnya,
     * yaitu satu draf per periode per wajib pajak, sudah dijaga indeks
     * gabungan (id_profil, tahun_pajak, bulan) dan (id_profil, tahun_pajak).
     */
    public function up(): void
    {
        foreach (['draf_bulanan', 'draf_tahunan'] as $tabel) {
            if (! Schema::hasIndex($tabel, $tabel.'_kode_draf_unique')) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) use ($tabel): void {
                $table->dropUnique($tabel.'_kode_draf_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (['draf_bulanan', 'draf_tahunan'] as $tabel) {
            if (Schema::hasIndex($tabel, $tabel.'_kode_draf_unique')) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table): void {
                $table->unique('kode_draf');
            });
        }
    }
};
