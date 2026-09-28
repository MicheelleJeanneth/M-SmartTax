@extends('layouts.guest')
@section('judul', 'Masuk')

@section('isi')
    @include('partials.logo')

    <h1 class="mt-14 text-[34px] leading-tight font-medium text-ink">Selamat datang kembali</h1>
    <p class="mt-1.5 text-base text-ink-2">Masuk untuk melanjutkan draf pajak Anda</p>

    @if(session('sukses'))
        <div role="status" class="mt-6 flex items-center gap-3 rounded-field bg-ok-bg px-4 py-3 text-sm text-ok-ink">
            <x-icon name="circle-check" :size="18" /> {{ session('sukses') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8">
        @csrf
        <x-input label="Email" name="email" type="email" wajib tinggi="h-[52px]" autocomplete="email" placeholder="nama@email.com" />
        <x-input-password label="Kata Sandi" name="password" tinggi="h-[52px]" autocomplete="current-password" rapat />

        <label class="mt-3 mb-6 flex w-fit items-center gap-2.5 text-[15px] text-ink">
            <input type="checkbox" name="remember" value="1"
                class="h-[18px] w-[18px] rounded-[4px] border-line text-primary focus:ring-2 focus:ring-accent">
            Ingat saya
        </label>

        <x-button type="submit" class="!h-[52px] w-full">Masuk</x-button>
    </form>

    <p class="mt-7 text-center text-[15px] font-medium text-ink">
        Belum punya akun?
        <a href="{{ route('register') }}" class="rounded text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Daftar sekarang</a>
    </p>
@endsection
