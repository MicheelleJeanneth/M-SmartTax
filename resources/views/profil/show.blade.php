@extends('layouts.app')
@section('judul', 'Profil')
@section('keterangan', 'Data diri yang dicetak pada kop laporan.')

@section('isi')
    <div class="max-w-[960px] space-y-6">
        <x-card>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-soft text-lg font-semibold text-primary-ink">{{ $profil['inisial'] }}</span>
                    <div>
                        <div class="flex items-center gap-3">
                            <p class="text-lg font-medium text-ink">{{ $profil['nama'] }}</p>
                            <x-badge status="lengkap" />
                        </div>
                        <p class="text-sm text-ink-2">{{ $profil['email'] }} · {{ $profil['peran'] }}</p>
                    </div>
                </div>
                <x-button :href="route('profil.edit')"><x-icon name="pencil" :size="16" /> Ubah Profil</x-button>
            </div>
        </x-card>

        <x-info varian="biru">NIK dan alamat tercetak di setiap laporan. Pastikan keduanya sesuai KTP sebelum mengunduh laporan.</x-info>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card judul="Data Diri">
                <div class="divide-y divide-line-soft">
                    <x-row label="NIK">{{ $profil['nik'] }}</x-row>
                    <x-row label="NPWP">{{ $profil['npwp'] }}</x-row>
                    <x-row label="Tempat, tanggal lahir">{{ $profil['tempat_lahir'] }}, {{ tanggal_id($profil['tanggal_lahir']) }}</x-row>
                    <x-row label="Jenis kelamin">{{ $profil['jenis_kelamin'] }}</x-row>
                    <x-row label="Kewarganegaraan">{{ $profil['kewarganegaraan'] }}</x-row>
                    <x-row label="Nomor telepon">{{ $profil['telepon'] }}</x-row>
                </div>
            </x-card>
            <x-card judul="Alamat">
                <div class="divide-y divide-line-soft">
                    <x-row label="Alamat">{{ $profil['alamat'] }}</x-row>
                    <x-row label="RT / RW">{{ $profil['rt_rw'] }}</x-row>
                    <x-row label="Kelurahan">{{ $profil['kelurahan'] }}</x-row>
                    <x-row label="Kecamatan">{{ $profil['kecamatan'] }}</x-row>
                    <x-row label="Kota / Kabupaten">{{ $profil['kota'] }}</x-row>
                    <x-row label="Provinsi">{{ $profil['provinsi'] }} {{ $profil['kode_pos'] }}</x-row>
                    <x-row label="Negara">{{ $profil['negara'] }}</x-row>
                </div>
            </x-card>
        </div>

        <x-card judul="Keamanan">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-icon name="shield-check" :size="20" class="text-primary" />
                    <div>
                        <p class="text-[15px] text-ink">Kata sandi</p>
                        <p class="text-sm text-ink-3">Terakhir diubah 12 Maret 2026</p>
                    </div>
                </div>
                <x-button varian="secondary">Ubah Kata Sandi</x-button>
            </div>
        </x-card>
    </div>
@endsection
