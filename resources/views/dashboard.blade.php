@extends('layouts.app')
@section('judul', 'Dashboard')
@section('sapaan', 'Hi, ' . $profil['nama'])
@section('keterangan', 'Ringkasan perpajakan dan keuangan')

@php
    // Pie komposisi harta: gradasi biru sesuai Figma, dari yang terbesar ke terkecil.
    $warnaHarta = ['#1E5E70', '#3A93B3', '#5DBBD8', '#BDE9F7', '#E1F5FB', '#EEF9FC'];
    $labelBulan = collect($bulan)->values();
@endphp

@section('aksi-header')
    <form method="GET" action="{{ route('dashboard') }}">
        <label for="tahun" class="sr-only">Tahun pajak</label>
        <select id="tahun" name="tahun" onchange="this.form.submit()"
            class="h-11 rounded-field border border-line bg-white pr-9 pl-4 text-[15px] text-ink focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none">
            @foreach([2026, 2025, 2024] as $t)
                <option value="{{ $t }}" @selected($t === $tahun)>Tahun Pajak {{ $t }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('isi')
    <p class="mb-3 text-xs font-medium tracking-wider text-ink-3">TAHUN PAJAK {{ $tahun }}</p>
    <div class="grid grid-cols-2 gap-4">
        <x-stat label="Akumulasi Penghasilan" :nilai="rupiah($ringkasan['penghasilan'])" />
        <x-stat label="PPh Final Terutang" :nilai="rupiah($ringkasan['pph_final'])" />
    </div>

    <p class="mt-7 mb-3 text-xs font-medium tracking-wider text-ink-3">POSISI PER AKHIR TAHUN PAJAK</p>
    <div class="grid grid-cols-3 gap-4">
        <x-stat varian="putih" label="Total Harta" :nilai="rupiah($ringkasan['harta'])" />
        <x-stat varian="putih" label="Total Utang" :nilai="rupiah($ringkasan['utang'])" />
        <x-stat varian="putih" label="Kekayaan Bersih" :nilai="rupiah($ringkasan['kekayaan_bersih'])" />
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <x-card judul="Grafik Penghasilan Bulanan" judul-ink padat>
            <div class="h-[124px]"><canvas id="grafik-penghasilan" role="img" aria-label="Grafik batang penghasilan bulanan Januari sampai Desember"></canvas></div>
            <p class="mt-2 text-sm text-ink-3">Januari sampai Desember</p>
        </x-card>

        <x-card judul="Komposisi Harta" judul-ink padat>
            <div class="flex items-center gap-8">
                <div class="h-[150px] w-[150px] shrink-0">
                    <canvas id="grafik-harta" role="img" aria-label="Grafik lingkaran komposisi harta"></canvas>
                </div>
                <ul class="space-y-1.5 text-[13px] text-ink-2">
                    @foreach($komposisiHarta as $nama => $persen)
                        <li class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-[2px]" style="background: {{ $warnaHarta[$loop->index] }}"></span>
                            {{ $nama }} {{ persen($persen, 0) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-card>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <x-card judul="Analisis Pertumbuhan Kekayaan" judul-ink padat>
            <div class="-mt-1 text-[15px] text-ink-2">
                <div class="flex justify-between py-1.5"><span>Akhir {{ $tahun - 1 }}</span><span class="tabular-nums">{{ rupiah($analisis['kekayaan_lalu']) }}</span></div>
                <div class="flex justify-between py-1.5"><span>Akhir {{ $tahun }}</span><span class="tabular-nums">{{ rupiah($analisis['kekayaan_kini']) }}</span></div>
            </div>
            <div class="mt-2 flex items-center gap-4 border-t border-line-soft pt-3">
                <span class="inline-flex min-w-[110px] justify-center rounded-field bg-ok-bg px-4 py-2 text-sm font-medium text-ok-ink">+ {{ persen($analisis['pertumbuhan_persen'], 2) }}</span>
                <span class="text-[15px] text-ink-3 tabular-nums">{{ rupiah($analisis['pertumbuhan']) }}</span>
            </div>
        </x-card>

        <x-card judul="Analisis Konsistensi Harta" judul-ink padat>
            <div class="-mt-2 text-[15px] text-ink-2">
                <div class="flex justify-between py-1"><span>Pertambahan harta</span><span class="tabular-nums">{{ rupiah($analisis['pertambahan_harta']) }}</span></div>
                <div class="flex justify-between py-1"><span>Pertambahan utang</span><span class="tabular-nums">{{ rupiah($analisis['pertambahan_utang']) }}</span></div>
                <div class="flex justify-between py-1"><span>Selisih bersih</span><span class="tabular-nums">{{ rupiah($analisis['selisih_bersih']) }}</span></div>
            </div>
            @php
                [$gayaStatus, $teksStatus] = match ($analisis['status']) {
                    'tinjau' => ['bg-warn-bg text-warn-ink', 'Perlu Ditinjau'],
                    'periksa' => ['bg-danger/10 text-danger', 'Perlu Diperiksa'],
                    default => ['bg-ok-bg text-ok-ink', 'Normal'],
                };
            @endphp
            <div class="mt-2 flex items-center gap-4 border-t border-line-soft pt-3">
                <span class="inline-flex min-w-[110px] justify-center rounded-field px-4 py-2 text-sm font-medium {{ $gayaStatus }}">{{ $teksStatus }}</span>
                <span class="text-[15px] text-ink-3">Rasio {{ persen($analisis['rasio']) }}</span>
            </div>
        </x-card>
    </div>

    <x-card judul="Pengingat" judul-ink padat class="mt-4">
        <x-slot:aksi>
            <span class="rounded-full bg-warn-bg px-3 py-1 text-sm text-warn-ink">{{ count($pengingat) }} belum dibaca</span>
        </x-slot:aksi>
        <ul class="-mt-1 divide-y divide-line-soft">
            @foreach($pengingat as $p)
                <li class="flex items-center gap-4 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-field {{ $p['nada'] === 'kuning' ? 'bg-warn-bg text-warn-ink' : 'bg-primary-soft text-primary-ink' }}">
                        <x-icon :name="$p['ikon']" :size="18" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] text-ink">{{ $p['judul'] }}</p>
                        <p class="text-[13px] text-ink-3">{{ $p['keterangan'] }}</p>
                    </div>
                    <a href="{{ route($p['rute']) }}" class="rounded text-[15px] text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">{{ $p['aksi'] }}</a>
                </li>
            @endforeach
        </ul>
    </x-card>
@endsection

@push('skrip')
<script type="module">
    const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // Grafik batang: tanpa sumbu dan garis bantu, sesuai Figma. Nilai tampil saat disorot.
    new Chart(document.getElementById('grafik-penghasilan'), {
        type: 'bar',
        data: {
            labels: @json($labelBulan),
            datasets: [{
                data: @json(array_values($bruto)),
                backgroundColor: '#4191B0',
                hoverBackgroundColor: '#0E5F73',
                borderRadius: { topLeft: 3, topRight: 3 },
                borderSkipped: 'bottom',
                categoryPercentage: 0.9,
                barPercentage: 0.85,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => rupiah(c.parsed.y) } },
            },
            scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
        },
    });

    new Chart(document.getElementById('grafik-harta'), {
        type: 'pie',
        data: {
            labels: @json($komposisiHarta->keys()),
            datasets: [{
                data: @json($komposisiHarta->values()),
                backgroundColor: @json(array_slice($warnaHarta, 0, $komposisiHarta->count())),
                borderWidth: 0,
            }],
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + c.parsed.toLocaleString('id-ID', { maximumFractionDigits: 0 }) + '%' } },
            },
        },
    });
</script>
@endpush
