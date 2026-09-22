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
    <div class="flex min-h-screen">
        @include('partials.sidebar')

        <main class="min-w-0 flex-1 p-10">
            <header class="mb-8 flex items-start justify-between gap-6">
                <div>
                    <h1 class="text-[28px] leading-tight font-medium text-ink">@hasSection('sapaan')@yield('sapaan')@else@yield('judul')@endif</h1>
                    <p class="mt-1 text-base text-ink-2">@yield('keterangan')</p>
                </div>
                @hasSection('aksi-header')
                    <div class="shrink-0">@yield('aksi-header')</div>
                @endif
            </header>

            @include('partials.flash')

            @yield('isi')
        </main>
    </div>
    @stack('skrip')
</body>
</html>
