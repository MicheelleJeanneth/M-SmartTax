<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salinan beku pada saat draf tahunan disusun. Laporan tahun lama membaca
 * baris ini, bukan data sekarang, sehingga angkanya tidak pernah berubah.
 */
class DrafTahunanUtang extends Model
{
    protected $table = 'draf_tahunan_utang';

    protected $guarded = ['id'];

    /** @return BelongsTo<DrafTahunan, $this> */
    public function drafTahunan(): BelongsTo
    {
        return $this->belongsTo(DrafTahunan::class, 'id_draf_tahunan');
    }
}
