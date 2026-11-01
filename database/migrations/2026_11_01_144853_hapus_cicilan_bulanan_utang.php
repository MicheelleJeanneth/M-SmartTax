<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cicilan bulanan dihapus dari Data Utang.
     *
     * Kolom ini tidak pernah masuk perhitungan pajak maupun lampiran draf
     * tahunan; satu-satunya pemakainya adalah Simulasi Kelayakan Pembelian
     * Aset. Sebagai gantinya, tahun pelunasan menjadi wajib diisi sehingga
     * jangka waktu utang tetap terekam.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('utang', 'cicilan_bulanan')) {
            return;
        }

        Schema::table('utang', function (Blueprint $table): void {
            $table->dropColumn('cicilan_bulanan');
        });
    }

    public function down(): void
    {
        Schema::table('utang', function (Blueprint $table): void {
            $table->unsignedBigInteger('cicilan_bulanan')->nullable()->after('saldo');
        });
    }
};
