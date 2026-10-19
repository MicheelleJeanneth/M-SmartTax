<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utang yang masih berjalan: lampiran draf tahunan sekaligus pengurang
 * kekayaan bersih.
 */
class Utang extends Model
{
    use BerstatusAktif;

    protected $table = 'utang';

    protected $primaryKey = 'id_utang';

    protected $guarded = ['id_utang'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    /** @return BelongsTo<KodeUtang, $this> */
    public function kodeUtang(): BelongsTo
    {
        return $this->belongsTo(KodeUtang::class, 'id_kode_utang');
    }

    /** @return BelongsTo<DrafTahunan, $this> */
    public function drafTahunan(): BelongsTo
    {
        return $this->belongsTo(DrafTahunan::class, 'id_draf_tahunan');
    }

    /**
     * Masih berlaku pada akhir satu tahun pajak: sudah dipinjam sampai tahun
     * itu dan belum lunas.
     */
    public function scopeBerlakuPadaTahun(Builder $kueri, int $tahun): Builder
    {
        return $kueri->where('tahun_peminjaman', '<=', $tahun)
            ->where(fn (Builder $k) => $k->whereNull('tahun_pelunasan')->orWhere('tahun_pelunasan', '>', $tahun));
    }

    public function sudahDirekap(): bool
    {
        return $this->id_draf_tahunan !== null;
    }
}
