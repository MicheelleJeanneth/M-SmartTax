<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian khas tiap kategori harta. Kunci utamanya sekaligus kunci asing
     * ke tabel induk, jadi satu baris harta punya paling banyak satu rincian.
     *
     * Kolom lokasi pada kas, piutang, dan investasi selalu berisi Indonesia:
     * terlihat pada formulir tetapi tidak dapat diubah.
     */
    public function up(): void
    {
        $anak = function (string $nama, callable $kolom): void {
            Schema::create($nama, function (Blueprint $table) use ($kolom) {
                $table->unsignedBigInteger('id_harta')->primary();
                $kolom($table);
                $table->foreign('id_harta')->references('id_harta')->on('harta')->cascadeOnDelete();
            });
        };

        $anak('harta_kas', function (Blueprint $table) {
            $table->string('nama_bank')->nullable();
            $table->string('nomor_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('lokasi_negara')->default('Indonesia');
        });

        $anak('harta_piutang', function (Blueprint $table) {
            $table->string('nik_penerima', 16)->nullable();
            $table->string('nama_penerima')->nullable();
            $table->string('lokasi_penerima')->default('Indonesia');
        });

        $anak('harta_investasi', function (Blueprint $table) {
            $table->string('nama_penerbit')->nullable();
            $table->string('nomor_akun')->nullable();
            $table->string('nik_penerbit', 16)->nullable();
            $table->string('lokasi_penerbit')->default('Indonesia');
        });

        $anak('harta_bergerak', function (Blueprint $table) {
            // Boleh kosong untuk harta tanpa registrasi, misalnya mesin atau gerobak.
            $table->string('nomor_polisi')->nullable();
            $table->string('kepemilikan')->nullable();
        });

        $anak('harta_tidak_bergerak', function (Blueprint $table) {
            // Alamat hartanya, bukan negara.
            $table->string('lokasi_harta')->nullable();
            $table->decimal('ukuran_tanah', 10, 2)->nullable();
            $table->decimal('ukuran_bangunan', 10, 2)->nullable();
            $table->string('sumber_kepemilikan')->nullable();
            $table->string('nomor_sertifikat')->nullable();
        });

        $anak('harta_lainnya', function (Blueprint $table) {
            $table->string('nomor_kepemilikan')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['harta_lainnya', 'harta_tidak_bergerak', 'harta_bergerak', 'harta_investasi', 'harta_piutang', 'harta_kas'] as $nama) {
            Schema::dropIfExists($nama);
        }
    }
};
