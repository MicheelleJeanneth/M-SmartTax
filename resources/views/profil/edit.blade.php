@extends('layouts.app')
@section('judul', 'Ubah Profil')
@section('keterangan', 'Perbarui data wajib pajak dan data usaha')

@section('kembali')
    <x-back :href="route('profil.show')">Kembali ke Profil</x-back>
@endsection

@section('isi')
    <form id="form-profil" method="POST" action="{{ route('profil.update') }}" data-konfirmasi="simpan-profil">
        @csrf
        @method('PUT')

        @include('profil._form-data-diri', ['profil' => $profil, 'terkunci' => true])

        <x-info varian="biru-muda" class="mt-4">
            Perubahan berlaku pada dokumen laporan yang dicetak setelah ini. Dokumen yang sudah diunduh sebelumnya tidak berubah.
        </x-info>

        {{-- Tombol rata kiri, sama seperti formulir lain. --}}
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button type="submit" class="w-full sm:w-auto sm:min-w-48">Simpan</x-button>
            <x-button varian="secondary" :href="route('profil.show')" class="w-full sm:w-auto sm:min-w-48"
                x-data @click.prevent="$dispatch('buka-dialog', 'batal-profil')">Batal</x-button>
        </div>
    </form>

    {{-- Dialognya di luar formulir, sejajar dengan halaman lain: @include
         mewarisi cakupan pemanggilnya, jadi labelnya dikirim eksplisit. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-profil',
        'href' => route('profil.show'),
        'judul' => 'Batalkan pengisian?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Mengisi',
    ])

    {{-- Nama dan alamat tercetak pada seluruh dokumen laporan, jadi keduanya
         yang diringkas, bersama kontak yang dipakai memulihkan akun. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-profil',
        'form' => 'form-profil',
        'judul' => 'Simpan perubahan profil?',
        'pesan' => 'Nama dan alamat berikut tercetak pada dokumen laporan yang dibuat setelah ini. Dokumen yang sudah diunduh sebelumnya tidak berubah.',
        'rincian' => [
            'Nama' => 'nama',
            'Nomor Handphone' => 'nomor_handphone',
            'Email' => 'email',
            'Alamat' => 'alamat',
            'Kota' => 'kota',
        ],
    ])
@endsection
