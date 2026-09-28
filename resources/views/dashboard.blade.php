@extends('layouts.app')
@section('judul', 'Dashboard')
@section('sapaan', 'Hi, ' . $profil['nama'])
@section('keterangan', 'Ringkasan perpajakan dan keuangan')

@php
    // Gradasi biru untuk keenam kategori harta, urutannya tetap.
    $warnaHarta = [
        'kas' => '#0E2A33',
        'piutang' => '#285F73',
        'investasi' => '#4294B5',
        'bergerak' => '#5FBFD8',
        'tidak-bergerak' => '#CBF2FD',
        'lainnya' => '#E7F9FE',
    ];
    $namaKategori = collect(\App\Support\MockData::kategoriHarta())->map(fn (array $k): string => $k['nama']);
    $totalKomposisi = array_sum($komposisiHarta);
    $bulanPenuh = collect(\App\Support\MockData::bulan())->values();
    $bulanSingkat = $bulanPenuh->map(fn (string $nama): string => mb_substr($nama, 0, 3));
    $adaPenghasilan = array_sum($brutoPerBulan) > 0;
@endphp

@section('aksi-header')
    <form method="GET" action="{{ route('dashboard') }}">
        <label for="tahun" class="sr-only">Tahun pajak</label>
        <select id="tahun" name="tahun" onchange="this.form.submit()" class="kolom-isian w-auto pr-9">
            @foreach($daftarTahun as $t)
                <option value="{{ $t }}" @selected($t === $tahun)>Tahun Pajak {{ $t }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('isi')
    @if($tanpaData)
        <p class="-mt-4 mb-6 text-[15px] text-ink-2">Belum ada data pada tahun {{ $tahun }}.</p>
    @endif

    <p class="mb-3 text-xs font-medium tracking-wider text-ink-3">TAHUN PAJAK {{ $tahun }}</p>
    <div class="grid grid-cols-2 gap-4">
        <x-stat label="Akumulasi Penghasilan" :nilai="rupiah($bruto)" />
        <x-stat label="PPh Final Terutang" :nilai="rupiah($pphFinal)" />
    </div>

    <p class="mt-7 mb-3 text-xs font-medium tracking-wider text-ink-3">POSISI PER AKHIR TAHUN PAJAK</p>
    <div class="grid grid-cols-3 gap-4">
        <x-stat varian="putih" label="Total Harta" :nilai="rupiah($harta)" />
        <x-stat varian="putih" label="Total Utang" :nilai="rupiah($utang)" />
        <x-stat varian="putih" label="Kekayaan Bersih" :nilai="rupiah($kekayaanBersih)" />
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <x-card judul="Grafik Penghasilan Bulanan" judul-warna="ink" padat>
            @if($adaPenghasilan)
                {{-- Judul sumbu Y ditulis mendatar di atas sumbu, bukan diputar tegak. --}}
                <p class="mb-1 text-xs font-medium text-ink-2">Penghasilan (juta rupiah)</p>
                <div class="h-[200px]"><canvas id="grafik-penghasilan" role="img" aria-label="Grafik batang penghasilan bulanan Januari sampai Desember"></canvas></div>
            @else
                <div class="flex h-[150px] flex-col items-center justify-center gap-2 text-center">
                    <p class="text-sm text-ink-2">Belum ada data penghasilan pada tahun ini</p>
                    <a href="{{ route('penghasilan.index') }}" class="rounded text-sm font-medium text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Buka Data Penghasilan</a>
                </div>
            @endif
        </x-card>

        <x-card judul="Komposisi Harta" judul-warna="ink" padat>
            @if($totalKomposisi > 0)
                <div class="flex items-center gap-8">
                    <div class="h-[150px] w-[150px] shrink-0">
                        <canvas id="grafik-harta" role="img" aria-label="Grafik lingkaran komposisi harta"></canvas>
                    </div>
                    {{-- Keenam kategori selalu tampil, termasuk yang bernilai nol. --}}
                    <ul class="space-y-2 text-[13px] text-ink-2">
                        @foreach($komposisiHarta as $kunci => $nilai)
                            <li class="flex items-center gap-2.5">
                                <span class="h-3 w-3 shrink-0 rounded-[3px]" style="background: {{ $warnaHarta[$kunci] }}"></span>
                                {{ $namaKategori[$kunci] }} {{ persen($nilai / $totalKomposisi * 100, 0) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="flex h-[150px] flex-col items-center justify-center gap-2 text-center">
                    <p class="text-sm text-ink-2">Belum ada data harta</p>
                    <a href="{{ route('harta.index') }}" class="rounded text-sm font-medium text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Buka Data Harta</a>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <x-card judul="Analisis Pertumbuhan Kekayaan" judul-warna="ink" padat>
            @php
                // Tahun berjalan memakai nama bulan sekarang, tahun yang sudah lewat memakai "Akhir".
                $labelKini = $tahun === $tahunBerjalan ? now()->translatedFormat('F Y') : 'Akhir ' . $tahun;
            @endphp

            @if($pertumbuhan['ada_pembanding'])
                <div class="-mt-1 text-[15px] text-ink-2">
                    <div class="flex justify-between py-1.5"><span>Akhir {{ $tahun - 1 }}</span><span class="tabular-nums">{{ rupiah($pertumbuhan['kekayaan_lalu']) }}</span></div>
                    <div class="flex justify-between py-1.5"><span>{{ $labelKini }}</span><span class="tabular-nums">{{ rupiah($pertumbuhan['kekayaan_kini']) }}</span></div>
                </div>
                <div class="mt-2 flex items-center gap-4 border-t border-line-soft pt-3">
                    @if($pertumbuhan['persen'] !== null)
                        @php $naik = $pertumbuhan['selisih'] >= 0; @endphp
                        <span class="inline-flex min-w-[110px] justify-center rounded-field px-4 py-2 text-sm font-medium {{ $naik ? 'bg-ok-bg text-ok-ink' : 'bg-warn-bg text-danger' }}">
                            {{ $naik ? '+' : '−' }} {{ persen(abs($pertumbuhan['persen']), 2) }}
                        </span>
                    @endif
                    <span class="text-[15px] tabular-nums {{ $pertumbuhan['selisih'] >= 0 ? 'text-ok-ink' : 'text-danger' }}">
                        {{ $pertumbuhan['selisih'] >= 0 ? '' : '− ' }}{{ rupiah(abs($pertumbuhan['selisih'])) }}
                    </span>
                </div>
            @else
                <div class="-mt-1 text-[15px] text-ink-2">
                    <div class="flex justify-between py-1.5"><span>{{ $labelKini }}</span><span class="tabular-nums">{{ rupiah($pertumbuhan['kekayaan_kini']) }}</span></div>
                </div>
                <p class="mt-2 border-t border-line-soft pt-3 text-sm text-ink-3">Pertumbuhan akan muncul setelah draf tahunan pertama tersusun</p>
            @endif
        </x-card>

        <x-card judul="Analisis Konsistensi Harta" judul-warna="ink" padat>
            @if($konsistensi['ada_pembanding'])
                <div class="-mt-2 text-[15px] text-ink-2">
                    <div class="flex justify-between py-1"><span>Pertambahan harta</span><span class="tabular-nums">{{ rupiah($konsistensi['pertambahan_harta']) }}</span></div>
                    <div class="flex justify-between py-1"><span>Pertambahan utang</span><span class="tabular-nums">{{ rupiah($konsistensi['pertambahan_utang']) }}</span></div>
                    <div class="flex justify-between py-1"><span>Selisih bersih</span><span class="tabular-nums">{{ rupiah($konsistensi['pertambahan_bersih']) }}</span></div>
                </div>
                @if($konsistensi['rasio'] !== null)
                    @php
                        [$gayaStatus, $teksStatus] = match ($konsistensi['status']) {
                            'tinjau' => ['bg-warn-bg text-warn-ink', 'Perlu Ditinjau'],
                            'periksa' => ['bg-warn-bg text-danger', 'Perlu Diperiksa'],
                            default => ['bg-ok-bg text-ok-ink', 'Normal'],
                        };
                    @endphp
                    <div class="mt-2 flex items-center gap-4 border-t border-line-soft pt-3">
                        <span class="inline-flex min-w-[110px] justify-center rounded-field px-4 py-2 text-sm font-medium {{ $gayaStatus }}">{{ $teksStatus }}</span>
                        <span class="text-[15px] text-ink-3">Rasio {{ angka($konsistensi['rasio'], 2) }}</span>
                    </div>
                @else
                    <p class="mt-2 border-t border-line-soft pt-3 text-sm text-ink-3">Rasio belum dapat dihitung karena belum ada peredaran bruto pada tahun ini</p>
                @endif
            @else
                <p class="text-sm text-ink-3">Analisis akan muncul setelah draf tahunan pertama tersusun</p>
            @endif
        </x-card>
    </div>

    <x-card judul="Pengingat" judul-warna="ink" padat class="mt-4">
        @php $belumDibaca = collect($pengingat)->where('dibaca', false)->count(); @endphp
        @if($belumDibaca)
            <x-slot:aksi>
                <span class="rounded-full bg-warn-bg px-3 py-1 text-sm text-warn-ink">{{ $belumDibaca }} belum dibaca</span>
            </x-slot:aksi>
        @endif

        @if($pengingat)
            {{--
                Daftar bergulir sendiri bila isinya lebih dari tiga sampai empat pengingat.
                Kepala kartu tetap di tempatnya karena berada di luar bagian ini.
                Memakai tinggi maksimal, bukan tinggi tetap, supaya kartu menyusut saat pengingatnya sedikit.
            --}}
            <div class="relative"
                 x-data="{ diBawah: true }"
                 x-init="$nextTick(() => { const d = $refs.daftar; diBawah = d.scrollHeight <= d.clientHeight + 2 })">
                <ul x-ref="daftar" tabindex="0" role="group" aria-label="Daftar pengingat"
                    @scroll="diBawah = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 2"
                    class="daftar-gulir -mt-1 max-h-[280px] divide-y divide-line-soft overflow-y-auto rounded-field
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    @foreach($pengingat as $p)
                    <li>
                        {{-- Menekan pengingat menandainya dibaca lalu membuka halaman terkait. --}}
                        <form method="POST" action="{{ route('pengingat.buka', $p['jenis']) }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-field py-3 pr-1 text-left transition hover:bg-page focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $p['dibaca'] ? 'bg-transparent' : 'bg-accent' }}" aria-hidden="true"></span>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-field {{ $p['dibaca'] ? 'bg-page text-ink-3' : 'bg-warn-bg text-warn-ink' }}">
                                    <x-icon :name="$p['ikon']" :size="18" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[15px] {{ $p['dibaca'] ? 'text-ink-2' : 'font-medium text-ink' }}">{{ $p['judul'] }}</span>
                                    <span class="block text-[13px] text-ink-3">
                                        {{ $p['pesan'] }}
                                        @if($p['jatuh_tempo'])· Jatuh tempo {{ tanggal_id($p['jatuh_tempo']) }}@endif
                                    </span>
                                </span>
                                <span class="shrink-0 pr-2 text-[15px] text-accent">{{ $p['aksi'] }}</span>
                            </button>
                        </form>
                    </li>
                    @endforeach
                </ul>

                {{-- Gradasi tipis sebagai penanda masih ada pengingat di bawah. --}}
                <div x-show="!diBawah" x-transition.opacity aria-hidden="true"
                     class="pointer-events-none absolute inset-x-0 -bottom-px h-10 bg-gradient-to-t from-white to-transparent"></div>
            </div>
        @else
            <p class="py-2 text-sm text-ink-2">Tidak ada pengingat saat ini</p>
        @endif
    </x-card>
@endsection

@push('skrip')
<script type="module">
    const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    @if($adaPenghasilan)
    const bulanPenuh = @json($bulanPenuh);
    const huruf = { family: getComputedStyle(document.body).fontFamily, size: 12 };

    // Grafik batang: dua belas bulan, sumbu Y dalam satuan juta rupiah.
    new Chart(document.getElementById('grafik-penghasilan'), {
        type: 'bar',
        data: {
            labels: @json($bulanSingkat),
            datasets: [{
                data: @json(array_values($brutoPerBulan)),
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
                tooltip: {
                    displayColors: false,
                    callbacks: {
                        title: () => '',
                        label: (c) => bulanPenuh[c.dataIndex] + ' {{ $tahun }} · ' + rupiah(c.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: '#E5E3DC' },
                    ticks: { color: '#7A7975', font: huruf, maxRotation: 0, autoSkip: false },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#E5E3DC' },
                    border: { display: false },
                    ticks: {
                        color: '#7A7975',
                        font: huruf,
                        maxTicksLimit: 5,
                        // Satuan juta: batang setinggi 150 berarti Rp 150.000.000.
                        callback: (nilai) => (nilai / 1e6).toLocaleString('id-ID'),
                    },
                },
            },
        },
    });
    @endif

    @if($totalKomposisi > 0)
    // Kategori bernilai nol tidak menggambar bagian apa pun pada diagram.
    new Chart(document.getElementById('grafik-harta'), {
        type: 'pie',
        data: {
            labels: @json($namaKategori->values()),
            datasets: [{
                data: @json(array_values($komposisiHarta)),
                backgroundColor: @json(array_values($warnaHarta)),
                borderWidth: 0,
            }],
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + rupiah(c.parsed) } },
            },
        },
    });
    @endif
</script>
@endpush
