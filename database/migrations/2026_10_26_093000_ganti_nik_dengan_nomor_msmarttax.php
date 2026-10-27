<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengganti NIK wajib pajak dengan nomor M-SmartTax.
     *
     * NIK tidak pernah masuk satu pun perhitungan; satu-satunya kegunaannya
     * adalah tercetak pada kop dokumen. Karena M-SmartTax sistem simulasi,
     * meminta data kependudukan untuk keperluan itu tidak sepadan: banyak
     * calon pengguna enggan menyerahkannya, dan dokumen keluarannya pun tidak
     * boleh menyerupai dokumen resmi. Penggantinya adalah penanda milik
     * sistem sendiri, berbentuk MS + tahun pendaftaran + nomor urut empat
     * digit pada tahun itu, misalnya MS20260001.
     *
     * Bentuknya sengaja bukan 16 angka supaya tidak mungkin tertukar dengan
     * NIK siapa pun, dan tidak menyandikan tanggal lahir supaya tidak ikut
     * menerbitkan data pribadi ke atas dokumen.
     *
     * NIK yang terlanjur tersimpan ikut terhapus dan tidak dapat dikembalikan
     * oleh down(); yang dipulihkan hanya kolomnya dalam keadaan kosong.
     */
    public function up(): void
    {
        if (Schema::hasColumn('profil', 'nomor_msmarttax')) {
            return;
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('nomor_msmarttax', 10)->nullable()->after('username');
        });

        $urut = [];

        foreach (DB::table('profil')->orderBy('id_profil')->get(['id_profil', 'created_at']) as $baris) {
            $tahun = $baris->created_at === null
                ? (int) date('Y')
                : (int) date('Y', strtotime((string) $baris->created_at));

            $urut[$tahun] = ($urut[$tahun] ?? 0) + 1;

            DB::table('profil')->where('id_profil', $baris->id_profil)->update([
                'nomor_msmarttax' => 'MS'.$tahun.str_pad((string) $urut[$tahun], 4, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('nomor_msmarttax', 10)->nullable(false)->change();
        });

        Schema::table('profil', function (Blueprint $table): void {
            $table->unique('nomor_msmarttax');
        });

        if (Schema::hasColumn('profil', 'nik')) {
            Schema::table('profil', function (Blueprint $table): void {
                $table->dropUnique('profil_nik_unique');
                $table->dropColumn('nik');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profil', 'nomor_msmarttax')) {
            return;
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('nik', 16)->nullable()->unique()->after('password');
            $table->dropUnique('profil_nomor_msmarttax_unique');
            $table->dropColumn('nomor_msmarttax');
        });
    }
};
