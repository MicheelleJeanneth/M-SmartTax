<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draf SPT Tahunan. Angkanya dibekukan saat draf disusun supaya laporan
     * tahun lama tidak ikut berubah ketika data sekarang diperbarui.
     */
    public function up(): void
    {
        Schema::create('draf_tahunan', function (Blueprint $table) {
            $table->id('id_draf_tahunan');
            $table->string('kode_draf', 30)->unique();
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();
            $table->foreignId('id_konfigurasi')->constrained('konfigurasi_pajak', 'id_konfigurasi');
            $table->year('tahun_pajak');
            $table->timestamp('tanggal_dibuat')->useCurrent();

            $table->unsignedBigInteger('total_peredaran_bruto');
            $table->unsignedBigInteger('total_pph_final');
            $table->unsignedBigInteger('total_harta');
            $table->unsignedBigInteger('total_utang');
            // Dapat bernilai negatif bila utang melampaui harta.
            $table->bigInteger('kekayaan_bersih');

            // Pertumbuhan dalam persen terhadap tahun sebelumnya; boleh negatif.
            $table->decimal('pertumbuhan_kekayaan', 8, 2)->nullable();
            // Pertambahan harta bersih dibagi peredaran bruto, disimpan sebagai pecahan.
            $table->decimal('rasio_konsistensi', 8, 4)->nullable();
            $table->string('status_konsistensi', 20)->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();

            // Satu tahun pajak hanya boleh punya satu draf per wajib pajak.
            $table->unique(['id_profil', 'tahun_pajak']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draf_tahunan');
    }
};
