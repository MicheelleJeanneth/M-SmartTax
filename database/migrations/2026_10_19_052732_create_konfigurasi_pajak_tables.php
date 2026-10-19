<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan pajak yang berlaku pada suatu tahun. Tarif dan ambang tidak pernah
     * ditulis langsung di kode; draf dan panduan sama-sama membacanya dari sini.
     */
    public function up(): void
    {
        Schema::create('konfigurasi_pajak', function (Blueprint $table) {
            $table->id('id_konfigurasi');
            $table->year('tahun_berlaku');
            $table->date('tanggal_berlaku');
            // Tarif PPh Final dalam persen, misalnya 0,50.
            $table->decimal('tarif_final', 5, 2);
            // Omzet pertama yang tidak dikenai pajak, dan batas atas skema final.
            $table->unsignedBigInteger('ambang_bebas');
            $table->unsignedBigInteger('ambang_maksimal');
            $table->string('dasar_hukum');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_pajak');
    }
};
