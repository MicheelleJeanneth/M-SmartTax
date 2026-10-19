<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengingat di Dashboard: batas setor mendekat, draf belum disusun,
     * dan peringatan ambang peredaran bruto.
     */
    public function up(): void
    {
        Schema::create('pengingat', function (Blueprint $table) {
            $table->id('id_pengingat');
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();

            $table->string('jenis', 30);
            $table->string('judul');
            $table->text('pesan');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->boolean('dibaca')->default(false);

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['id_profil', 'dibaca']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat');
    }
};
