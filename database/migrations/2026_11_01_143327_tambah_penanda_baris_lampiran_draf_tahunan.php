<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghubungkan salinan beku ke baris aslinya, supaya laporan tahun lama
     * dapat mengambil angkanya dari salinan itu.
     *
     * Salinan sudah ada sejak awal, tetapi tidak menyimpan baris mana yang
     * disalin, sehingga Laporan Data Harta dan Laporan Data Utang terpaksa
     * membaca tabel aslinya. Akibatnya memperbarui saldo utang tahun ini ikut
     * mengubah laporan tahun lalu.
     *
     * 'nilai_kini' ikut ditambahkan karena laporan piutang mencetak saldo saat
     * ini, sedangkan salinannya baru memuat nilai perolehan.
     */
    public function up(): void
    {
        if (Schema::hasColumn('draf_tahunan_utang', 'id_utang')) {
            return;
        }

        Schema::table('draf_tahunan_harta', function (Blueprint $table): void {
            // Boleh kosong: draf yang disusun sebelum migrasi ini berjalan
            // tidak punya penandanya, dan barisnya tidak boleh ikut terhapus
            // bila hartanya dihapus kemudian.
            $table->foreignId('id_harta')->nullable()->after('id_draf_tahunan')
                ->constrained('harta', 'id_harta')->nullOnDelete();
            $table->unsignedBigInteger('nilai_kini')->nullable()->after('nilai');
        });

        Schema::table('draf_tahunan_utang', function (Blueprint $table): void {
            $table->foreignId('id_utang')->nullable()->after('id_draf_tahunan')
                ->constrained('utang', 'id_utang')->nullOnDelete();
        });

        $this->cocokkanSalinanLama();
    }

    public function down(): void
    {
        Schema::table('draf_tahunan_harta', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_harta');
            $table->dropColumn('nilai_kini');
        });

        Schema::table('draf_tahunan_utang', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_utang');
        });
    }

    /**
     * Mengisi penanda untuk draf yang sudah telanjur tersusun.
     *
     * Dicocokkan lewat jati dirinya, karena kolom itu terkunci begitu drafnya
     * disusun sehingga tidak mungkin berubah. Baris yang kecocokannya lebih
     * dari satu dilewati: lebih baik laporannya memakai angka sekarang seperti
     * sebelumnya daripada menautkannya ke baris yang salah.
     *
     * Ditulis sebagai perulangan PHP, bukan UPDATE ... JOIN, karena pengujian
     * berjalan di atas SQLite yang tidak mengenal bentuk itu.
     */
    private function cocokkanSalinanLama(): void
    {
        $draf = DB::table('draf_tahunan')->get()->keyBy('id_draf_tahunan');

        foreach (DB::table('draf_tahunan_utang')->get() as $baris) {
            $induk = $draf[$baris->id_draf_tahunan] ?? null;

            $cocok = DB::table('utang')
                ->where('id_profil', $induk?->id_profil ?? 0)
                ->where('deskripsi', $baris->deskripsi)
                ->where('nama_kreditur', $baris->nama_kreditur)
                // Hanya yang memang berjalan pada tahun pajak drafnya. Dua
                // utang boleh sama kode dan krediturnya, dan tahunlah yang
                // membedakannya.
                ->where('tahun_peminjaman', '<=', $induk?->tahun_pajak ?? 0)
                ->where(fn ($k) => $k->whereNull('tahun_pelunasan')->orWhere('tahun_pelunasan', '>', $induk?->tahun_pajak ?? 0))
                ->pluck('id_utang');

            if ($cocok->count() === 1) {
                DB::table('draf_tahunan_utang')->where('id', $baris->id)->update(['id_utang' => $cocok->first()]);
            }
        }

        foreach (DB::table('draf_tahunan_harta')->get() as $baris) {
            $induk = $draf[$baris->id_draf_tahunan] ?? null;

            $cocok = DB::table('harta')
                ->join('kode_harta', 'kode_harta.id_kode_harta', '=', 'harta.id_kode_harta')
                ->where('harta.id_profil', $induk?->id_profil ?? 0)
                ->where('harta.deskripsi', $baris->deskripsi)
                ->where('kode_harta.kode', $baris->kode)
                ->where('kode_harta.kategori', $baris->kategori)
                // Hanya yang memang dimiliki pada tahun pajak drafnya.
                ->where('harta.tahun_perolehan', '<=', $induk?->tahun_pajak ?? 0)
                ->where(fn ($k) => $k->whereNull('harta.tahun_pelepasan')->orWhere('harta.tahun_pelepasan', '>', $induk?->tahun_pajak ?? 0))
                ->pluck('harta.nilai_saat_ini', 'harta.id_harta');

            if ($cocok->count() !== 1) {
                continue;
            }

            // Nilai saat ini yang tersimpan sekarang adalah satu-satunya angka
            // yang pernah ada untuk draf lama; itu pula yang selama ini dicetak
            // laporannya, jadi menyalinnya tidak mengubah apa pun hari ini.
            DB::table('draf_tahunan_harta')->where('id', $baris->id)->update([
                'id_harta' => $cocok->keys()->first(),
                'nilai_kini' => $cocok->first(),
            ]);
        }
    }
};
