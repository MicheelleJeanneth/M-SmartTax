<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tarif dan ambang yang berlaku pada suatu tahun. Perhitungan draf maupun
 * teks panduan sama-sama membaca dari sini, bukan dari angka tetap di kode.
 */
class KonfigurasiPajak extends Model
{
    use BerstatusAktif;

    protected $table = 'konfigurasi_pajak';

    protected $primaryKey = 'id_konfigurasi';

    protected $guarded = ['id_konfigurasi'];

    protected function casts(): array
    {
        return [
            'tanggal_berlaku' => 'date',
            'tarif_final' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    /** @return HasMany<DrafBulanan, $this> */
    public function drafBulanans(): HasMany
    {
        return $this->hasMany(DrafBulanan::class, 'id_konfigurasi');
    }

    /** @return HasMany<DrafTahunan, $this> */
    public function drafTahunans(): HasMany
    {
        return $this->hasMany(DrafTahunan::class, 'id_konfigurasi');
    }

    /**
     * Konfigurasi yang berlaku untuk satu tahun pajak, yaitu aturan terbaru
     * yang mulai berlaku sebelum tahun itu berakhir.
     */
    public static function berlakuUntukTahun(int $tahun): ?self
    {
        return static::query()
            ->whereDate('tanggal_berlaku', '<=', "{$tahun}-12-31")
            ->orderByDesc('tanggal_berlaku')
            ->first();
    }
}
