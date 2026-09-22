@extends('layouts.center')
@section('judul', 'Lengkapi Profil')

@section('isi')
    <div class="mb-8 flex justify-center">@include('partials.logo')</div>

    {{-- Penanda dua langkah --}}
    <ol class="mb-8 flex items-center justify-center gap-3 text-sm" aria-label="Langkah pendaftaran">
        <li class="flex items-center gap-2 text-ink-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-white"><x-icon name="check" :size="14" :stroke="2.5" /></span>
            Buat Akun
        </li>
        <li class="h-px w-12 bg-primary" aria-hidden="true"></li>
        <li class="flex items-center gap-2 font-medium text-primary" aria-current="step">
            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-primary bg-white text-[13px]">2</span>
            Lengkapi Profil
        </li>
    </ol>

    <div class="mb-6 text-center">
        <h1 class="text-[28px] leading-tight font-medium text-ink">Lengkapi profil Anda</h1>
        <p class="mt-2 text-base text-ink-2">Data ini dicetak pada kop setiap laporan. Pastikan sesuai KTP.</p>
    </div>

    <form method="POST" action="{{ route('profil.lengkapi') }}">
        @csrf
        @include('profil._form-data-diri', ['profil' => []])
        <x-button type="submit" class="mt-6 w-full">Simpan dan Lanjutkan</x-button>
    </form>
@endsection
