<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengingat di Dashboard: batas setor, draf belum disusun, ambang omzet. */
class Pengingat extends Model
{
    use BerstatusAktif;

    protected $table = 'pengingat';

    protected $primaryKey = 'id_pengingat';

    protected $guarded = ['id_pengingat'];

    protected function casts(): array
    {
        return [
            'tanggal_jatuh_tempo' => 'date',
            'dibaca' => 'boolean',
            'status' => 'boolean',
        ];
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    public function scopeBelumDibaca(Builder $kueri): Builder
    {
        return $kueri->where('dibaca', false);
    }
}
