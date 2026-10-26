<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Wajib pajak sekaligus akun. Menggantikan model User bawaan Laravel:
 * satu orang punya satu akun, jadi tidak perlu dipisah dua tabel.
 */
#[Hidden(['password', 'remember_token'])]
class Profil extends Authenticatable
{
    use BerstatusAktif;

    protected $table = 'profil';

    protected $primaryKey = 'id_profil';

    protected $guarded = ['id_profil'];

    /**
     * Susunan username yang diterima: huruf kecil, angka, titik, dan garis
     * bawah; diawali huruf dan diakhiri huruf atau angka.
     */
    public const POLA_USERNAME = '/^[a-z][a-z0-9._]*[a-z0-9]$/';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'profil_lengkap' => 'boolean',
            'status' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Username selalu disimpan dan dibandingkan dalam huruf kecil, supaya
     * "Budi" dan "budi" tidak pernah menjadi dua akun yang berbeda.
     */
    public static function bakukanUsername(?string $username): string
    {
        return mb_strtolower(trim((string) $username));
    }

    /**
     * Sebutan tetap pada sidebar dan kartu identitas. Bukan kolom basis data
     * karena aplikasi ini hanya melayani satu jenis wajib pajak.
     */
    protected function peran(): Attribute
    {
        return Attribute::get(fn (): string => 'Wajib Pajak Orang Pribadi UMKM');
    }

    /** Dua huruf awal nama, untuk lingkaran identitas. */
    protected function inisial(): Attribute
    {
        return Attribute::get(fn (): string => collect(explode(' ', (string) $this->nama))
            ->take(2)
            ->map(fn (string $kata): string => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode(''));
    }

    /** @return HasMany<Penghasilan, $this> */
    public function penghasilans(): HasMany
    {
        return $this->hasMany(Penghasilan::class, 'id_profil');
    }

    /** @return HasMany<Harta, $this> */
    public function hartas(): HasMany
    {
        return $this->hasMany(Harta::class, 'id_profil');
    }

    /** @return HasMany<Utang, $this> */
    public function utangs(): HasMany
    {
        return $this->hasMany(Utang::class, 'id_profil');
    }

    /** @return HasMany<DrafBulanan, $this> */
    public function drafBulanans(): HasMany
    {
        return $this->hasMany(DrafBulanan::class, 'id_profil');
    }

    /** @return HasMany<DrafTahunan, $this> */
    public function drafTahunans(): HasMany
    {
        return $this->hasMany(DrafTahunan::class, 'id_profil');
    }

    /** @return HasMany<Pengingat, $this> */
    public function pengingats(): HasMany
    {
        return $this->hasMany(Pengingat::class, 'id_profil');
    }

    /**
     * Alamat lengkap dalam satu baris, dipakai pada kop dokumen laporan.
     */
    public function alamatLengkap(): string
    {
        return collect([
            $this->alamat,
            $this->rt && $this->rw ? "RT {$this->rt}/RW {$this->rw}" : null,
            $this->kelurahan,
            $this->kecamatan,
            $this->kota,
            $this->provinsi,
        ])->filter()->implode(', ');
    }
}
