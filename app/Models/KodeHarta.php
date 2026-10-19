<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kode harta resmi pada lampiran SPT Tahunan, dikelompokkan per kategori. */
class KodeHarta extends Model
{
    protected $table = 'kode_harta';

    protected $primaryKey = 'id_kode_harta';

    protected $guarded = ['id_kode_harta'];

    /** @return HasMany<Harta, $this> */
    public function hartas(): HasMany
    {
        return $this->hasMany(Harta::class, 'id_kode_harta');
    }
}
