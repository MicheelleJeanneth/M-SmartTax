<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Penghapusan lunak lewat kolom status.
 *
 * Data yang dihapus tidak dibuang dari basis data, hanya ditandai status false
 * lalu disembunyikan dari seluruh kueri. Penyaringnya dipasang sekali di sini,
 * jadi tidak ada satu pun halaman yang perlu mengingat-ingat sendiri.
 */
trait BerstatusAktif
{
    protected static function bootBerstatusAktif(): void
    {
        static::addGlobalScope(
            'aktif',
            fn (Builder $kueri) => $kueri->where($kueri->getModel()->getTable().'.status', true)
        );
    }

    /**
     * Menghapus baris: ditandai tidak aktif, barisnya tetap tersimpan.
     */
    public function hapus(): bool
    {
        return $this->forceFill(['status' => false])->save();
    }

    /**
     * Mengembalikan baris yang sebelumnya dihapus.
     */
    public function pulihkan(): bool
    {
        return $this->forceFill(['status' => true])->save();
    }

    /**
     * Membuka penyaring, misalnya untuk halaman pemulihan atau audit.
     */
    public function scopeTermasukTerhapus(Builder $kueri): Builder
    {
        return $kueri->withoutGlobalScope('aktif');
    }

    /**
     * Hanya baris yang sudah dihapus.
     */
    public function scopeHanyaTerhapus(Builder $kueri): Builder
    {
        return $kueri->withoutGlobalScope('aktif')->where($this->getTable().'.status', false);
    }
}
