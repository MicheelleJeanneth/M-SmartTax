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

    /**
     * Daftar per kategori, disimpan sekali per permintaan karena formulir
     * membacanya berulang kali.
     *
     * @var array<string, array<string, string>>
     */
    private static array $ingatan = [];

    /**
     * Pilihan kode satu kategori: kode => uraian.
     *
     * @return array<string, string>
     */
    public static function daftar(string $kategori): array
    {
        return self::$ingatan[$kategori] ??= static::query()
            ->where('kategori', $kategori)
            ->orderBy('kode')
            ->pluck('nama_kode', 'kode')
            ->all();
    }

    /** @return HasMany<Harta, $this> */
    public function hartas(): HasMany
    {
        return $this->hasMany(Harta::class, 'id_kode_harta');
    }
}
