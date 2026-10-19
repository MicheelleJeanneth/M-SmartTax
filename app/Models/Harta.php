<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    /**
     * Urutan kolom khas tiap kategori. Urutan inilah yang dipakai formulir dan
     * tabel, jadi satu-satunya tempat yang menentukannya ada di sini.
     *
     * @var array<string, array<int, string>>
     */
    public const KOLOM_KHAS = [
        'kas' => ['nama_bank', 'nomor_rekening', 'atas_nama'],
        'piutang' => ['nama_penerima', 'nik_penerima'],
        'investasi' => ['nama_penerbit', 'nomor_akun', 'nik_penerbit'],
        'bergerak' => ['nomor_polisi', 'kepemilikan'],
        'tidak-bergerak' => ['lokasi_harta', 'ukuran_tanah', 'ukuran_bangunan', 'sumber_kepemilikan', 'nomor_sertifikat'],
        'lainnya' => ['nomor_kepemilikan'],
    ];

    /**
     * Kategori yang punya kolom negara terkunci, beserta nama kolomnya.
     *
     * @var array<string, string>
     */
    public const KOLOM_NEGARA = [
        'kas' => 'lokasi_negara',
        'piutang' => 'lokasi_penerima',
        'investasi' => 'lokasi_penerbit',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    /**
     * Nama-nama turunan di bawah ini menjaga agar tampilan yang sudah jadi
     * tetap bekerja tanpa perlu tahu nama kolom di basis data.
     */
    protected function id(): Attribute
    {
        return Attribute::get(fn (): int => $this->id_harta);
    }

    protected function nama(): Attribute
    {
        return Attribute::get(fn (): string => $this->deskripsi);
    }

    protected function tahun(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->tahun_perolehan);
    }

    protected function nilaiKini(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->nilai_saat_ini);
    }

    protected function kode(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->kodeHarta?->kode);
    }

    /**
     * Terkunci bila ada draf tahunan tersusun yang memuat baris ini.
     *
     * Statusnya diturunkan dari tahun kepemilikan, bukan dari kolom
     * id_draf_tahunan, karena satu harta dapat masuk draf beberapa tahun
     * sekaligus sedangkan kolom itu hanya mampu menunjuk satu draf.
     */
    protected function terkunci(): Attribute
    {
        return Attribute::get(fn (): bool => DrafTahunan::tahunTersusun((int) $this->id_profil)
            ->contains(fn (int $tahun): bool => $this->dimilikiPada($tahun)));
    }

    /**
     * Rincian khas sebagai daftar berurutan, sesuai KOLOM_KHAS kategorinya.
     * Angka desimal dirapikan supaya "120.00" tampil sebagai "120".
     *
     * @return Attribute<array<int, string|null>, never>
     */
    protected function khas(): Attribute
    {
        return Attribute::get(function (): array {
            $kategori = $this->kodeHarta?->kategori;
            $rincian = $this->rincian();

            return collect(self::KOLOM_KHAS[$kategori] ?? [])
                ->map(function (string $kolom) use ($rincian): ?string {
                    $nilai = $rincian?->{$kolom};

                    if ($nilai === null || $nilai === '') {
                        return null;
                    }

                    return is_numeric($nilai) && str_contains((string) $nilai, '.')
                        ? rtrim(rtrim((string) $nilai, '0'), '.')
                        : (string) $nilai;
                })
                ->all();
        });
    }

    /** Negara harta, hanya untuk kategori yang memang punya kolomnya. */
    protected function negara(): Attribute
    {
        return Attribute::get(function (): ?string {
            $kolom = self::KOLOM_NEGARA[$this->kodeHarta?->kategori] ?? null;

            return $kolom === null ? null : $this->rincian()?->{$kolom};
        });
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

    /** Nama relasi rincian untuk satu kategori, misalnya tidak-bergerak jadi tidakBergerak. */
    public static function relasiRincian(string $kategori): string
    {
        return Str::camel(str_replace('-', '_', $kategori));
    }

    /** Harta pada satu kategori saja, dipakai tiap tab halaman Data Harta. */
    public function scopeKategori(Builder $kueri, string $kategori): Builder
    {
        return $kueri->whereHas('kodeHarta', fn (Builder $k) => $k->where('kategori', $kategori));
    }

    /** Aturan yang sama dengan scopeDimilikiPadaTahun, untuk satu baris. */
    public function dimilikiPada(int $tahun): bool
    {
        return (int) $this->tahun_perolehan <= $tahun
            && ($this->tahun_pelepasan === null || (int) $this->tahun_pelepasan > $tahun);
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
}
