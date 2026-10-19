<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil sekaligus akun: satu wajib pajak, satu akun. Tabel ini menggantikan
     * tabel users bawaan Laravel, dan dipakai langsung untuk autentikasi.
     */
    public function up(): void
    {
        Schema::create('profil', function (Blueprint $table) {
            $table->id('id_profil');

            // Identitas akun.
            $table->string('email')->unique();
            $table->string('password');

            // Identitas wajib pajak. NIK berfungsi sebagai NPWP sejak 2024.
            $table->string('nik', 16)->unique()->nullable();
            $table->string('nama')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('kewarganegaraan', 10)->default('WNI');
            $table->string('nomor_handphone', 20)->nullable();

            // Alamat; kedelapan bagiannya digabung jadi satu baris pada laporan.
            $table->string('alamat')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('negara')->default('Indonesia');

            // Selama false, pengguna hanya boleh membuka Lengkapi Profil dan Keluar.
            $table->boolean('profil_lengkap')->default(false);

            // Penanda hidup: false berarti terhapus, barisnya tetap tersimpan.
            $table->boolean('status')->default(true);

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('profil');
    }
};
