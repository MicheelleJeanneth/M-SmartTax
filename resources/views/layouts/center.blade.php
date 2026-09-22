<!DOCTYPE html>
<html lang="id" class="bg-page">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'M-SmartTax') · M-SmartTax</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page font-sans text-ink antialiased">
    <div class="mx-auto w-full max-w-[560px] px-6 py-14">
        @yield('isi')
    </div>
</body>
</html>
