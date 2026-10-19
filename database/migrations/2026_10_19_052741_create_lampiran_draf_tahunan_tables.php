<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salinan beku harta dan utang pada saat draf tahunan disusun.
     *
     * Tanpa salinan ini, laporan tahun lama akan ikut berubah setiap kali data
     * sekarang diperbarui. Saldo utang khususnya berganti tiap tahun, sedangkan
     * tabel utang hanya menyimpan saldo terkini.
     */
    public function up(): void
    {
        Schema::create('draf_tahunan_harta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_draf_tahunan')->constrained('draf_tahunan', 'id_draf_tahunan')->cascadeOnDelete();
            $table->string('kategori', 20);
            $table->string('kode', 4);
            $table->string('deskripsi');
            $table->unsignedBigInteger('nilai');
            $table->timestamps();

            $table->index(['id_draf_tahunan', 'kategori']);
        });

        Schema::create('draf_tahunan_utang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_draf_tahunan')->constrained('draf_tahunan', 'id_draf_tahunan')->cascadeOnDelete();
            $table->string('kode', 3);
            $table->string('deskripsi');
            $table->string('nama_kreditur');
            $table->unsignedBigInteger('saldo');
            $table->timestamps();

            $table->index('id_draf_tahunan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draf_tahunan_utang');
        Schema::dropIfExists('draf_tahunan_harta');
    }
};
