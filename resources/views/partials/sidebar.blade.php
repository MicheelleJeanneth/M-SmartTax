{{-- Sidebar sesuai Figma: tanpa ikon, garis pemisah antar grup, menu aktif berlatar biru muda dengan garis kiri. --}}
@php
    $profil = \App\Support\MockData::profil();

    $menu = [
        'HALAMAN UTAMA' => [
            ['Dashboard', 'dashboard', 'dashboard'],
        ],
        'INPUT DATA' => [
            ['Data Penghasilan', 'penghasilan.index', 'penghasilan.*'],
            ['Data Harta', 'harta.index', 'harta.*'],
            ['Data Utang', 'utang.index', 'utang.*'],
        ],
        'DRAF PAJAK' => [
            ['Draf Pajak Penghasilan Bulanan', 'draf-bulanan.index', 'draf-bulanan.*'],
            ['Draf Pajak Penghasilan Tahunan', 'draf-tahunan.index', 'draf-tahunan.*'],
        ],
        'ANALISIS KEUANGAN' => [
            ['Simulasi Kelayakan Pembelian Aset', 'simulasi.index', 'simulasi.*'],
        ],
        'LAPORAN' => [
            ['Laporan Draf Pajak Penghasilan Bulanan', 'laporan.draf-bulanan', 'laporan.draf-bulanan'],
            ['Laporan Draf Pajak Penghasilan Tahunan', 'laporan.draf-tahunan', 'laporan.draf-tahunan'],
            ['Laporan Data Harta', 'laporan.harta', 'laporan.harta'],
            ['Laporan Data Utang', 'laporan.utang', 'laporan.utang'],
        ],
        'AKUN' => [
            ['Panduan Pengguna', 'panduan', 'panduan'],
            ['Profil', 'profil.show', 'profil.show|profil.edit'],
        ],
    ];

    $tautan = 'block border-l-[3px] py-2 pr-6 pl-[21px] text-[15px] leading-snug transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-accent';
@endphp
<aside class="sticky top-0 flex h-screen w-sidebar shrink-0 flex-col border-r border-line bg-white">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-6 pt-7 pb-5">
        <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
        <span class="text-lg font-medium text-ink">M-SmartTax</span>
    </a>

    <nav class="flex-1 overflow-y-auto pb-4" aria-label="Menu utama">
        @foreach($menu as $grup => $item)
            @unless($loop->first)
                <div class="mx-6 my-3 border-t border-line-soft" aria-hidden="true"></div>
            @endunless
            <p class="mb-1 px-6 text-[11px] font-medium tracking-wider text-ink-3">{{ $grup }}</p>
            <ul>
                @foreach($item as [$teks, $rute, $pola])
                    @php $aktif = request()->routeIs(...explode('|', $pola)); @endphp
                    <li>
                        <a href="{{ route($rute) }}" @if($aktif) aria-current="page" @endif
                           class="{{ $tautan }} {{ $aktif ? 'border-primary bg-primary-soft/50 text-primary-ink' : 'border-transparent text-ink hover:bg-page' }}">
                            {{ $teks }}
                        </a>
                    </li>
                @endforeach
                @if($grup === 'AKUN')
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="{{ $tautan }} w-full border-transparent text-left text-ink hover:bg-page hover:text-danger">Keluar</button>
                        </form>
                    </li>
                @endif
            </ul>
        @endforeach
    </nav>

    <div class="flex items-center gap-3 px-6 py-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[13px] font-medium text-primary-ink">
            {{ $profil['inisial'] }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm text-ink">{{ $profil['nama'] }}</p>
            <p class="text-xs text-ink-3">{{ $profil['peran'] }}</p>
        </div>
    </div>
</aside>
