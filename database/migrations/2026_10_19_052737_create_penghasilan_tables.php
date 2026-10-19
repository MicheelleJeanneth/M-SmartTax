<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaksi penerimaan bruto. Begitu id_draf_bulanan terisi, barisnya
     * terkunci: tidak dapat diubah maupun dihapus sampai drafnya dibatalkan.
     */
    public function up(): void
    {
        Schema::create('penghasilan', function (Blueprint $table) {
            $table->id('id_penghasilan');
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();
            $table->foreignId('id_draf_bulanan')->nullable()->constrained('draf_bulanan', 'id_draf_bulanan')->nullOnDelete();

            $table->date('tanggal');
            $table->unsignedBigInteger('nominal');
            $table->string('keterangan')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();

            // Daftar dan filter selalu diurutkan per pemilik dan tanggal.
            $table->index(['id_profil', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penghasilan');
    }
};
