<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kode utang resmi: 101, 102, 103, dan 109. */
    public function up(): void
    {
        Schema::create('kode_utang', function (Blueprint $table) {
            $table->id('id_kode_utang');
            $table->string('kode', 3)->unique();
            $table->string('nama_kode');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_utang');
    }
};
