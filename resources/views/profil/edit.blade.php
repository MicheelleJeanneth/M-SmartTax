@extends('layouts.app')
@section('judul', 'Ubah Profil')
@section('keterangan', 'NIK dan email tidak dapat diubah.')

@section('isi')
    <x-back :href="route('profil.show')">Kembali ke Profil</x-back>
    <form method="POST" action="{{ route('profil.update') }}" class="max-w-[720px]">
        @csrf
        @method('PUT')
        <x-card judul="Akun" class="mb-6">
            <x-input-locked label="Email" name="email" :value="$profil['email']" bantuan="Email dipakai untuk masuk dan tidak dapat diubah." />
        </x-card>
        @include('profil._form-data-diri', ['profil' => $profil, 'terkunci' => true])
        <div class="mt-6 flex justify-end gap-3">
            <x-button varian="secondary" :href="route('profil.show')">Batal</x-button>
            <x-button type="submit">Simpan Perubahan</x-button>
        </div>
    </form>
@endsection
