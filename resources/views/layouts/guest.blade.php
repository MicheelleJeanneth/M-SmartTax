<!DOCTYPE html>
<html lang="id" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Masuk') · M-SmartTax</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
    <div class="flex min-h-screen">
        {{-- Panel kiri: form. Lebar isi 440, rata tengah. --}}
        <div class="flex w-full items-center justify-center bg-white px-8 py-12 lg:w-1/2">
            <div class="w-full max-w-[440px]">
                @yield('isi')
            </div>
        </div>

        {{-- Panel kanan: hanya tampil mulai lebar laptop. --}}
        <div class="hidden lg:block lg:w-1/2">
            @include('partials.panel-fitur')
        </div>
    </div>
</body>
</html>
