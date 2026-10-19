<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Induk harta untuk keenam kategori. Kolom yang dipakai semua kategori ada
 * di sini, sehingga total lintas kategori cukup membaca satu tabel.
 * Rincian khasnya berada di tabel anak, satu baris per harta.
 */
class Harta extends Model
{
    use BerstatusAktif;

    protected $table = 'harta';

    protected $primaryKey = 'id_harta';

    protected $guarded = ['id_harta'];

    /**
     * Kategori yang dikenali beserta model rinciannya.
     *
     * @var array<string, class-string<Model>>
     */
    public const RINCIAN = [
        'kas' => HartaKas::class,
        'piutang' => HartaPiutang::class,
        'investasi' => HartaInvestasi::class,
        'bergerak' => HartaBergerak::class,
        'tidak-bergerak' => HartaTidakBergerak::class,
        'lainnya' => HartaLainnya::class,
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    /** @return BelongsTo<KodeHarta, $this> */
    public function kodeHarta(): BelongsTo
    {
        return $this->belongsTo(KodeHarta::class, 'id_kode_harta');
    }

    /** @return BelongsTo<DrafTahunan, $this> */
    public function drafTahunan(): BelongsTo
    {
        return $this->belongsTo(DrafTahunan::class, 'id_draf_tahunan');
    }

    /** @return HasOne<HartaKas, $this> */
    public function kas(): HasOne
    {
        return $this->hasOne(HartaKas::class, 'id_harta');
    }

    /** @return HasOne<HartaPiutang, $this> */
    public function piutang(): HasOne
    {
        return $this->hasOne(HartaPiutang::class, 'id_harta');
    }

    /** @return HasOne<HartaInvestasi, $this> */
    public function investasi(): HasOne
    {
        return $this->hasOne(HartaInvestasi::class, 'id_harta');
    }

    /** @return HasOne<HartaBergerak, $this> */
    public function bergerak(): HasOne
    {
        return $this->hasOne(HartaBergerak::class, 'id_harta');
    }

    /** @return HasOne<HartaTidakBergerak, $this> */
    public function tidakBergerak(): HasOne
    {
        return $this->hasOne(HartaTidakBergerak::class, 'id_harta');
    }

    /** @return HasOne<HartaLainnya, $this> */
    public function lainnya(): HasOne
    {
        return $this->hasOne(HartaLainnya::class, 'id_harta');
    }

    /**
     * Rincian khas milik baris ini, mengikuti kategori kodenya.
     */
    public function rincian(): ?Model
    {
        $kategori = $this->kodeHarta?->kategori;

        return match ($kategori) {
            'kas' => $this->kas,
            'piutang' => $this->piutang,
            'investasi' => $this->investasi,
            'bergerak' => $this->bergerak,
            'tidak-bergerak' => $this->tidakBergerak,
            'lainnya' => $this->lainnya,
            default => null,
        };
    }

    /** Harta pada satu kategori saja, dipakai tiap tab halaman Data Harta. */
    public function scopeKategori(Builder $kueri, string $kategori): Builder
    {
        return $kueri->whereHas('kodeHarta', fn (Builder $k) => $k->where('kategori', $kategori));
    }

    /**
     * Harta yang dimiliki pada akhir satu tahun pajak: sudah diperoleh sampai
     * tahun itu dan belum dilepas.
     */
    public function scopeDimilikiPadaTahun(Builder $kueri, int $tahun): Builder
    {
        return $kueri->where('tahun_perolehan', '<=', $tahun)
            ->where(fn (Builder $k) => $k->whereNull('tahun_pelepasan')->orWhere('tahun_pelepasan', '>', $tahun));
    }

    /** Sudah masuk draf tahunan, sehingga terkunci. */
    public function sudahDirekap(): bool
    {
        return $this->id_draf_tahunan !== null;
    }
}
