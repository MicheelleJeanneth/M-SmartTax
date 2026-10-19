<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draf PPh Final satu masa pajak. Akumulasi sebelum dan sesudah bulan ini
     * ikut disimpan karena ambang bebas pajak dihitung menumpuk sepanjang tahun.
     */
    public function up(): void
    {
        Schema::create('draf_bulanan', function (Blueprint $table) {
            $table->id('id_draf_bulanan');
            $table->string('kode_draf', 30)->unique();
            $table->foreignId('id_profil')->constrained('profil', 'id_profil')->cascadeOnDelete();
            $table->foreignId('id_konfigurasi')->constrained('konfigurasi_pajak', 'id_konfigurasi');
            // Terisi saat draf tahunan menguncinya; kosong berarti masih dapat dibatalkan.
            $table->foreignId('id_draf_tahunan')->nullable()->constrained('draf_tahunan', 'id_draf_tahunan')->nullOnDelete();

            $table->year('tahun_pajak');
            $table->unsignedTinyInteger('bulan');
            $table->timestamp('tanggal_dibuat')->useCurrent();

            $table->unsignedBigInteger('peredaran_bruto');
            $table->unsignedBigInteger('akumulasi_sebelumnya');
            $table->unsignedBigInteger('akumulasi_sekarang');
            $table->unsignedBigInteger('omzet_kena_pajak');
            $table->unsignedBigInteger('pph_final');

            $table->boolean('status')->default(true);
            $table->timestamps();

            // Satu bulan hanya boleh punya satu draf per wajib pajak.
            $table->unique(['id_profil', 'tahun_pajak', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draf_bulanan');
    }
};
