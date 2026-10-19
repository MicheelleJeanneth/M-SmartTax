<?php

namespace App\Models;

use App\Models\Concerns\BerstatusAktif;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'profil_lengkap' => 'boolean',
            'status' => 'boolean',
            'password' => 'hashed',
        ];
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

    /**
     * Dua huruf awal nama, dipakai pada lingkaran identitas di sidebar.
     */
    public function inisial(): string
    {
        return collect(explode(' ', (string) $this->nama))
            ->take(2)
            ->map(fn (string $kata): string => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }
}
