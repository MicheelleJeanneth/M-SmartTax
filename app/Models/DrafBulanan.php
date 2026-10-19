<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Draf PPh Final satu masa pajak. Akumulasi sebelum dan sesudah bulan ini
 * ikut disimpan karena ambang bebas pajak dihitung menumpuk sepanjang tahun.
 */
class DrafBulanan extends Model
{
    use BerstatusAktif;

    protected $table = 'draf_bulanan';

    protected $primaryKey = 'id_draf_bulanan';

    protected $guarded = ['id_draf_bulanan'];

    protected function casts(): array
    {
        return [
            'tanggal_dibuat' => 'datetime',
            'status' => 'boolean',
        ];
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    /** @return BelongsTo<KonfigurasiPajak, $this> */
    public function konfigurasiPajak(): BelongsTo
    {
        return $this->belongsTo(KonfigurasiPajak::class, 'id_konfigurasi');
    }

    /** @return BelongsTo<DrafTahunan, $this> */
    public function drafTahunan(): BelongsTo
    {
        return $this->belongsTo(DrafTahunan::class, 'id_draf_tahunan');
    }

    /** @return HasMany<Penghasilan, $this> */
    public function penghasilans(): HasMany
    {
        return $this->hasMany(Penghasilan::class, 'id_draf_bulanan');
    }

    /** Sudah dikunci draf tahunan, sehingga tidak dapat dibatalkan sendiri. */
    public function terkunciTahunan(): bool
    {
        return $this->id_draf_tahunan !== null;
    }

    /**
     * Batas setor PPh Final: tanggal 15 bulan berikutnya.
     */
    public function batasSetor(): Carbon
    {
        return Carbon::create($this->tahun_pajak, $this->bulan, 1)
            ->addMonthNoOverflow()
            ->day(15);
    }
}
