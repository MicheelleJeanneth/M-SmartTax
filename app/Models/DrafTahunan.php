<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Draf SPT Tahunan. Angkanya dibekukan saat disusun, termasuk salinan harta
 * dan utang, supaya laporan tahun lama tidak ikut berubah.
 */
class DrafTahunan extends Model
{
    use BerstatusAktif;

    protected $table = 'draf_tahunan';

    protected $primaryKey = 'id_draf_tahunan';

    protected $guarded = ['id_draf_tahunan'];

    /**
     * Tahun pajak yang drafnya sudah tersusun, per wajib pajak. Disimpan
     * sekali per permintaan karena setiap baris harta dan utang membacanya
     * untuk menentukan status terkuncinya.
     *
     * @var array<int, Collection<int, int>>
     */
    private static array $ingatanTahun = [];

    protected function casts(): array
    {
        return [
            'tanggal_dibuat' => 'datetime',
            'pertumbuhan_kekayaan' => 'decimal:2',
            'rasio_konsistensi' => 'decimal:4',
            'status' => 'boolean',
        ];
    }

    /**
     * Tahun pajak yang drafnya sudah tersusun untuk satu wajib pajak.
     *
     * @return Collection<int, int>
     */
    public static function tahunTersusun(int $profil): Collection
    {
        return self::$ingatanTahun[$profil] ??= static::query()
            ->where('id_profil', $profil)
            ->orderByDesc('tahun_pajak')
            ->pluck('tahun_pajak')
            ->map(fn ($tahun): int => (int) $tahun);
    }

    /** Dipanggil setelah draf disusun atau dibatalkan, agar daftarnya dibaca ulang. */
    public static function lupakanTahunTersusun(): void
    {
        self::$ingatanTahun = [];
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

    /** @return HasMany<DrafBulanan, $this> */
    public function drafBulanans(): HasMany
    {
        return $this->hasMany(DrafBulanan::class, 'id_draf_tahunan');
    }

    /** @return HasMany<Harta, $this> */
    public function hartas(): HasMany
    {
        return $this->hasMany(Harta::class, 'id_draf_tahunan');
    }

    /** @return HasMany<Utang, $this> */
    public function utangs(): HasMany
    {
        return $this->hasMany(Utang::class, 'id_draf_tahunan');
    }

    /** @return HasMany<DrafTahunanHarta, $this> */
    public function lampiranHarta(): HasMany
    {
        return $this->hasMany(DrafTahunanHarta::class, 'id_draf_tahunan');
    }

    /** @return HasMany<DrafTahunanUtang, $this> */
    public function lampiranUtang(): HasMany
    {
        return $this->hasMany(DrafTahunanUtang::class, 'id_draf_tahunan');
    }
}
