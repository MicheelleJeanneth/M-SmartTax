<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kode utang resmi: 101, 102, 103, dan 109. */
class KodeUtang extends Model
{
    protected $table = 'kode_utang';

    protected $primaryKey = 'id_kode_utang';

    protected $guarded = ['id_kode_utang'];

    /** @return HasMany<Utang, $this> */
    public function utangs(): HasMany
    {
        return $this->hasMany(Utang::class, 'id_kode_utang');
    }
}
