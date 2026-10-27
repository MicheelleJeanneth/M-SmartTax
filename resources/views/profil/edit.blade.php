@extends('layouts.app')
@section('judul', 'Ubah Profil')
@section('keterangan', 'Perbarui data wajib pajak dan data usaha')

@section('kembali')
    <x-back :href="route('profil.show')">Kembali ke Profil</x-back>
@endsection

@section('isi')
    <form id="form-profil" method="POST" action="{{ route('profil.update') }}">
        @csrf
        @method('PUT')

        @include('profil._form-data-diri', ['profil' => $profil, 'terkunci' => true])

        <x-info varian="biru-muda" class="mt-4">
            Perubahan berlaku pada dokumen laporan yang dicetak setelah ini. Dokumen yang sudah diunduh sebelumnya tidak berubah.
        </x-info>

        {{-- Tombol rata kiri, sama seperti formulir lain. --}}
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button type="submit" class="w-full sm:w-auto sm:min-w-48">Simpan Perubahan</x-button>
            <x-button varian="secondary" :href="route('profil.show')" class="w-full sm:w-auto sm:min-w-48">Batal</x-button>
        </div>
    </form>
@endsection
