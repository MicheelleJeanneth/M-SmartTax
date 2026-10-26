<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Memindahkan identitas masuk dari email ke username.
     *
     * Tidak semua pelaku UMKM memiliki email, sedangkan pada aplikasi ini
     * email hanya berperan sebagai nama untuk masuk: tidak ada surel yang
     * dikirim dan tidak ada pemulihan kata sandi yang memakainya. Email
     * karena itu tetap disimpan, tetapi menjadi opsional dan boleh kosong.
     *
     * Akun yang sudah ada menerima username dari bagian depan emailnya agar
     * tetap dapat masuk tanpa dibuat ulang. Aturan susunannya sengaja
     * ditulis ulang di sini, bukan memanggil model, supaya migrasi ini tetap
     * bekerja walaupun aturan di aplikasi berubah kemudian.
     */
    public function up(): void
    {
        if (Schema::hasColumn('profil', 'username')) {
            return;
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('username', 20)->nullable()->after('id_profil');
        });

        $terpakai = [];

        foreach (DB::table('profil')->orderBy('id_profil')->get(['id_profil', 'email']) as $baris) {
            $username = $this->usernameUnik((string) $baris->email, $terpakai);
            $terpakai[] = $username;

            DB::table('profil')->where('id_profil', $baris->id_profil)->update(['username' => $username]);
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('username', 20)->nullable(false)->change();
        });

        Schema::table('profil', function (Blueprint $table): void {
            $table->unique('username');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profil', 'username')) {
            return;
        }

        Schema::table('profil', function (Blueprint $table): void {
            $table->dropUnique('profil_username_unique');
            $table->dropColumn('username');
        });

        // Email kembali wajib, jadi baris yang mengosongkannya diberi alamat
        // penanda lebih dahulu supaya kolomnya dapat dijadikan NOT NULL.
        DB::table('profil')
            ->whereNull('email')
            ->update(['email' => DB::raw("CONCAT('akun', id_profil, '@m-smarttax.test')")]);

        Schema::table('profil', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }

    /**
     * Username dari bagian depan email, dirapikan agar memenuhi susunan yang
     * diterima aplikasi, lalu diberi nomor urut bila sudah terpakai.
     *
     * @param  array<int, string>  $terpakai
     */
    private function usernameUnik(string $email, array $terpakai): string
    {
        $dasar = preg_replace('/[^a-z0-9._]/', '', mb_strtolower(Str::before($email, '@'))) ?? '';
        $dasar = rtrim(substr(ltrim($dasar, '0123456789._'), 0, 20), '._');

        if (strlen($dasar) < 3) {
            $dasar = 'pengguna';
        }

        $username = $dasar;
        $nomor = 1;

        while (in_array($username, $terpakai, true)) {
            $nomor++;
            $username = substr($dasar, 0, 20 - strlen((string) $nomor)).$nomor;
        }

        return $username;
    }
};
