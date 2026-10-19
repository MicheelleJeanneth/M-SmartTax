<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode harta resmi pada lampiran SPT Tahunan, misalnya 0101 Uang Tunai.
     * Kategori menentukan kode mana yang muncul pada formulir tiap tab.
     */
    public function up(): void
    {
        Schema::create('kode_harta', function (Blueprint $table) {
            $table->id('id_kode_harta');
            $table->string('kategori', 20);
            $table->string('kode', 4);
            $table->string('nama_kode');
            $table->timestamps();

            $table->unique('kode');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_harta');
    }
};
