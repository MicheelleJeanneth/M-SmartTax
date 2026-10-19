<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Utang yang masih berjalan, dipakai sebagai lampiran draf tahunan dan
     * sebagai pengurang kekayaan bersih.
     */
    public function up(): void
    {
        Schema::create('utang', function (Blueprint $table) {
            $table->id('id_utang');
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();
            $table->foreignId('id_kode_utang')->constrained('kode_utang', 'id_kode_utang');
            $table->foreignId('id_draf_tahunan')->nullable()->constrained('draf_tahunan', 'id_draf_tahunan')->nullOnDelete();

            $table->string('deskripsi');
            $table->string('keterangan')->nullable();

            // Sisa kewajiban pada akhir tahun pajak, bukan jumlah pinjaman semula.
            $table->unsignedBigInteger('saldo');
            // Kosong untuk utang yang dibayar sekaligus saat jatuh tempo.
            $table->unsignedBigInteger('cicilan_bulanan')->nullable();
            $table->year('tahun_peminjaman');
            // Kosong berarti belum lunas.
            $table->year('tahun_pelunasan')->nullable();

            $table->string('nama_kreditur');
            $table->string('nik_kreditur', 16)->nullable();
            $table->string('negara_kreditur')->default('Indonesia');

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['id_profil', 'tahun_peminjaman']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utang');
    }
};
