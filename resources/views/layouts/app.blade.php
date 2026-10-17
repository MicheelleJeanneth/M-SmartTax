<!DOCTYPE html>
<html lang="id" class="bg-page">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'M-SmartTax') · M-SmartTax</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page font-sans text-ink antialiased">
    {{-- menu menyalakan laci sidebar pada layar sempit; di layar lebar
         sidebar selalu tampak dan nilai ini tidak dipakai. --}}
    <div class="flex min-h-screen" x-data="{ menu: false }" @keydown.escape.window="menu = false">
        @include('partials.sidebar')

        <main class="min-w-0 flex-1">
            {{-- Batang atas khusus layar sempit, berisi tombol menu dan merek. --}}
            <div class="sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-white px-4 py-3 lg:hidden">
                <button type="button" @click="menu = true" aria-label="Buka menu"
                    class="rounded-field p-2 text-ink hover:bg-page focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <x-icon name="menu" :size="22" />
                </button>
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-8 w-8 object-contain">
                <span class="text-base font-medium text-merek">M-SmartTax</span>
            </div>

            <div class="p-5 sm:p-6 lg:p-10">
            {{-- Tautan kembali berdiri di atas judul halaman. --}}
            @hasSection('kembali')
                @yield('kembali')
            @endif

            <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                <div class="min-w-0">
                    <h1 class="text-2xl leading-tight font-medium text-ink sm:text-[28px]">
                        {{-- Dashboard memakai sapaan "Hi, nama"; halaman lain memakai judulnya. --}}
                        @hasSection('sapaan')
                            @yield('sapaan')
                        @else
                            @yield('judul')
                        @endif
                    </h1>
                    <p class="mt-1 text-base text-ink-2">@yield('keterangan')</p>
                </div>
                @hasSection('aksi-header')
                    <div class="shrink-0">@yield('aksi-header')</div>
                @endif
            </header>

            @include('partials.flash')

            @yield('isi')
            </div>
        </main>
    </div>
    @stack('skrip')
</body>
</html>
