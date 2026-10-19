<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transaksi penerimaan bruto. Begitu masuk draf bulanan, barisnya terkunci
 * dan tidak dapat diubah sampai drafnya dibatalkan.
 */
class Penghasilan extends Model
{
    use BerstatusAktif;

    protected $table = 'penghasilan';

    protected $primaryKey = 'id_penghasilan';

    protected $guarded = ['id_penghasilan'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'status' => 'boolean',
        ];
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    /** @return BelongsTo<DrafBulanan, $this> */
    public function drafBulanan(): BelongsTo
    {
        return $this->belongsTo(DrafBulanan::class, 'id_draf_bulanan');
    }

    /** Sudah masuk draf bulanan, sehingga terkunci. */
    public function sudahDirekap(): bool
    {
        return $this->id_draf_bulanan !== null;
    }

    public function scopeTahun(Builder $kueri, int $tahun): Builder
    {
        return $kueri->whereYear('tanggal', $tahun);
    }

    public function scopeBulan(Builder $kueri, int $bulan): Builder
    {
        return $kueri->whereMonth('tanggal', $bulan);
    }
}
