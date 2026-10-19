<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rincian khas harta kategori ini; satu baris untuk satu harta. */
class HartaKas extends Model
{
    protected $table = 'harta_kas';

    protected $primaryKey = 'id_harta';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /** @return BelongsTo<Harta, $this> */
    public function harta(): BelongsTo
    {
        return $this->belongsTo(Harta::class, 'id_harta');
    }
}
