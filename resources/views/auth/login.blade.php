@extends('layouts.guest')
@section('judul', 'Masuk')

@section('isi')
    @include('partials.logo')

    <h1 class="mt-8 text-[28px] leading-tight font-medium text-ink">Masuk ke akun Anda</h1>
    <p class="mt-2 text-base text-ink-2">Lanjutkan mengelola draf pajak dan catatan keuangan usaha Anda.</p>

    @if(session('sukses'))
        <div role="status" class="mt-6 flex items-center gap-3 rounded-field bg-ok-bg px-4 py-3 text-sm text-ok-ink">
            <x-icon name="circle-check" :size="18" /> {{ session('sukses') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8">
        @csrf
        <x-input label="Email" name="email" type="email" wajib autocomplete="email" placeholder="nama@email.com" />
        <x-input-password label="Kata Sandi" name="password" autocomplete="current-password" />

        <x-button type="submit" class="mt-2 w-full">Masuk</x-button>
    </form>

    <p class="mt-6 text-center text-[15px] text-ink-2">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">Daftar sekarang</a>
    </p>
@endsection
