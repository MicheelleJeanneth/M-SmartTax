<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Induk harta untuk keenam kategori. Kolom yang dipakai semua kategori
     * berdiri di sini, sehingga total lintas kategori cukup membaca satu tabel.
     * Rincian khas tiap kategori ada di tabel anaknya masing-masing.
     */
    public function up(): void
    {
        Schema::create('harta', function (Blueprint $table) {
            $table->id('id_harta');
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();
            $table->foreignId('id_kode_harta')->constrained('kode_harta', 'id_kode_harta');
            // Terisi saat draf tahunan menguncinya.
            $table->foreignId('id_draf_tahunan')->nullable()->constrained('draf_tahunan', 'id_draf_tahunan')->nullOnDelete();

            // Disalin dari kode_harta saat disimpan, supaya uraian pada laporan
            // lama tidak ikut berubah bila daftar kode resmi diperbarui.
            $table->string('deskripsi');
            $table->string('keterangan')->nullable();

            $table->unsignedBigInteger('nilai');
            $table->unsignedBigInteger('nilai_saat_ini')->nullable();
            $table->year('tahun_perolehan');
            // Kosong berarti masih dimiliki; terisi berarti tidak dihitung lagi
            // mulai tahun setelahnya.
            $table->year('tahun_pelepasan')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['id_profil', 'tahun_perolehan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harta');
    }
};
