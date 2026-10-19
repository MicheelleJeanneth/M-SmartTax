<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** Kode utang resmi: 101, 102, 103, dan 109. */
class KodeUtang extends Model
{
    /**
     * Uraian ringkas untuk tempat yang sempit: penyaring, tabel, halaman
     * rincian, dan rekapitulasi laporan. Nama panjangnya tetap dibaca dari
     * kolom nama_kode.
     *
     * @var array<string, string>
     */
    public const SINGKATAN = [
        '101' => 'Utang Bank/LKBB',
        '102' => 'Kartu Kredit',
        '103' => 'Utang Afiliasi',
        '109' => 'Utang Lainnya',
    ];

    protected $table = 'kode_utang';

    protected $primaryKey = 'id_kode_utang';

    protected $guarded = ['id_kode_utang'];

    /**
     * Daftar kode berisi seluruh baris tabel, disimpan sekali per permintaan
     * karena tabel ini dibaca berulang kali di dalam perulangan tampilan.
     */
    private static ?Collection $ingatan = null;

    /** "101 – Utang Bank/Lembaga Keuangan Bukan Bank ..." */
    protected function namaLengkap(): Attribute
    {
        return Attribute::get(fn (): string => $this->kode.' – '.$this->nama_kode);
    }

    /** "101 – Utang Bank/LKBB" */
    protected function namaSingkat(): Attribute
    {
        return Attribute::get(fn (): string => $this->kode.' – '.(self::SINGKATAN[$this->kode] ?? $this->nama_kode));
    }

    /**
     * Pilihan kode utang dengan uraian lengkap, untuk formulir.
     *
     * @return Collection<string, string>
     */
    public static function daftarLengkap(): Collection
    {
        return self::semua()->mapWithKeys(fn (self $k): array => [$k->kode => $k->nama_lengkap]);
    }

    /**
     * Pilihan kode utang dengan uraian ringkas, untuk tempat yang sempit.
     *
     * @return Collection<string, string>
     */
    public static function daftarSingkat(): Collection
    {
        return self::semua()->mapWithKeys(fn (self $k): array => [$k->kode => $k->nama_singkat]);
    }

    /** @return HasMany<Utang, $this> */
    public function utangs(): HasMany
    {
        return $this->hasMany(Utang::class, 'id_kode_utang');
    }

    /** @return Collection<int, self> */
    private static function semua(): Collection
    {
        return self::$ingatan ??= static::query()->orderBy('kode')->get();
    }
}
