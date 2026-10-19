<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utang yang masih berjalan: lampiran draf tahunan sekaligus pengurang
 * kekayaan bersih.
 */
class Utang extends Model
{
    use BerstatusAktif;

    protected $table = 'utang';

    protected $primaryKey = 'id_utang';

    protected $guarded = ['id_utang'];

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
        return Attribute::get(fn (): int => $this->id_utang);
    }

    protected function kreditur(): Attribute
    {
        return Attribute::get(fn (): string => $this->nama_kreditur);
    }

    protected function cicilan(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->cicilan_bulanan);
    }

    protected function tahun(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->tahun_peminjaman);
    }

    protected function negara(): Attribute
    {
        return Attribute::get(fn (): string => $this->negara_kreditur);
    }

    protected function kode(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->kodeUtang?->kode);
    }

    /**
     * Terkunci bila ada draf tahunan tersusun yang memuat baris ini.
     *
     * Sama seperti harta, statusnya diturunkan dari tahun berlakunya karena
     * satu utang dapat masuk draf beberapa tahun sekaligus.
     */
    protected function terkunci(): Attribute
    {
        return Attribute::get(fn (): bool => DrafTahunan::tahunTersusun((int) $this->id_profil)
            ->contains(fn (int $tahun): bool => $this->berlakuPada($tahun)));
    }

    /** @return BelongsTo<Profil, $this> */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'id_profil');
    }

    /** @return BelongsTo<KodeUtang, $this> */
    public function kodeUtang(): BelongsTo
    {
        return $this->belongsTo(KodeUtang::class, 'id_kode_utang');
    }

    /** @return BelongsTo<DrafTahunan, $this> */
    public function drafTahunan(): BelongsTo
    {
        return $this->belongsTo(DrafTahunan::class, 'id_draf_tahunan');
    }

    /** Aturan yang sama dengan scopeBerlakuPadaTahun, untuk satu baris. */
    public function berlakuPada(int $tahun): bool
    {
        return (int) $this->tahun_peminjaman <= $tahun
            && ($this->tahun_pelunasan === null || (int) $this->tahun_pelunasan > $tahun);
    }

    /**
     * Masih berlaku pada akhir satu tahun pajak: sudah dipinjam sampai tahun
     * itu dan belum lunas.
     */
    public function scopeBerlakuPadaTahun(Builder $kueri, int $tahun): Builder
    {
        return $kueri->where('tahun_peminjaman', '<=', $tahun)
            ->where(fn (Builder $k) => $k->whereNull('tahun_pelunasan')->orWhere('tahun_pelunasan', '>', $tahun));
    }
}
