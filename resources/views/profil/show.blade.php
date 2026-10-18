@extends('layouts.app')
@section('judul', 'Profil')
@section('keterangan', 'Data wajib pajak yang digunakan pada seluruh dokumen laporan')

@section('aksi-header')
    <x-button :href="route('profil.edit')"><x-icon name="pencil" :size="16" /> Ubah Profil</x-button>
@endsection

@section('isi')
    <x-card padat>
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[15px] font-medium text-primary-ink">{{ $profil['inisial'] }}</span>
                <div class="min-w-0">
                    <p class="text-base font-medium text-ink">{{ $profil['nama'] }}</p>
                    <p class="mt-0.5 text-sm text-ink-2">{{ $profil['peran'] }} &middot; {{ $profil['nik'] }}</p>
                </div>
            </div>
            <x-badge status="lengkap" />
        </div>
    </x-card>

    {{-- Label berdiri pada kolom tetap agar nilainya rata satu garis. --}}
    @php
        $dataDiri = [
            'NIK' => $profil['nik'],
            'Nama Lengkap' => $profil['nama'],
            'Tempat, Tanggal Lahir' => $profil['tempat_lahir'].', '.tanggal_id($profil['tanggal_lahir']),
            'Jenis Kelamin' => $profil['jenis_kelamin'],
            'Kewarganegaraan' => \App\Support\MockData::kewarganegaraanTampil(),
            'Nomor Handphone' => $profil['telepon'],
            'Email' => $profil['email'],
        ];

        $alamat = [
            'Alamat' => $profil['alamat'],
            'RT / RW' => $profil['rt'].' / '.$profil['rw'],
            'Kelurahan' => $profil['kelurahan'],
            'Kecamatan' => $profil['kecamatan'],
            'Kota' => $profil['kota'],
            'Provinsi' => $profil['provinsi'],
            'Negara' => $profil['negara'],
        ];
    @endphp

    @foreach(['Data Diri' => $dataDiri, 'Alamat' => $alamat] as $judul => $isi)
        <x-card :judul="$judul" class="mt-4">
            <dl class="text-[15px]">
                @foreach($isi as $label => $nilai)
                    <div class="grid grid-cols-1 gap-x-4 gap-y-0.5 py-1 sm:grid-cols-[220px_1fr]">
                        <dt class="text-ink-2">{{ $label }}</dt>
                        <dd class="min-w-0 text-ink">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    @endforeach

    <x-card judul="Keamanan" class="mt-4">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[15px] text-ink">Kata Sandi</p>
                <p class="mt-0.5 text-sm text-ink-3">Perbarui kata sandi secara berkala untuk menjaga keamanan akun.</p>
            </div>
            <x-button varian="secondary" :href="route('profil.kata-sandi')" class="shrink-0">Ubah Kata Sandi</x-button>
        </div>
    </x-card>

    <x-info varian="biru-muda" class="mt-4">
        NIK dan alamat tercetak pada seluruh dokumen laporan. Pastikan keduanya sesuai dengan data yang terdaftar di Direktorat Jenderal Pajak.
    </x-info>
@endsection
