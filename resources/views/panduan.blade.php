@extends('layouts.app')
@section('judul', 'Panduan Pengguna')
@section('keterangan', 'Cara menggunakan M-SmartTax dari awal sampai mencetak dokumen')

@section('isi')
    <div class="flex items-start gap-10">
        {{-- Daftar isi berpindah halaman, bukan menggulir. Bagian yang sedang
             dibuka disorot seperti menu aktif di sidebar. --}}
        <nav class="sticky top-10 w-[330px] shrink-0 overflow-hidden rounded-card border border-line bg-white py-4" aria-label="Daftar isi panduan">
            <p class="mb-2 px-5 text-[11px] font-medium tracking-wider text-ink-3">DAFTAR ISI</p>
            <ul class="text-[15px]">
                @foreach($daftar as $slug => $b)
                    <li>
                        <a href="{{ route('panduan', $slug) }}" @if($slug === $bagian) aria-current="page" @endif
                            class="block border-l-[3px] py-1.5 pr-5 pl-4.25 transition {{ $slug === $bagian ? 'border-primary bg-primary-soft/50 font-medium text-primary-ink' : 'border-transparent text-ink-2 hover:bg-page hover:text-primary' }}">
                            {{ $b['judul'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0 flex-1 space-y-4">
            @foreach($isi['kartu'] as $kartu)
                {{-- Kartu pertama memakai judul bagian; selebihnya menyebut judulnya sendiri. --}}
                <x-card :judul="$kartu['judul'] ?? $isi['judul']" :keterangan="$kartu['keterangan'] ?? null"
                    :judulWarna="$kartu['warna'] ?? 'ink'">
                    @isset($kartu['pengantar'])
                        <p class="text-[15px] leading-relaxed text-ink-2">{{ $kartu['pengantar'] }}</p>
                    @endisset

                    @isset($kartu['langkah'])
                        <ol class="mt-5 space-y-4">
                            @foreach($kartu['langkah'] as $langkah)
                                <li class="flex gap-3">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-soft/60 text-[13px] font-medium text-primary-ink" aria-hidden="true">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[15px] font-medium text-ink">{{ $langkah['judul'] }}</p>
                                        <p class="mt-0.5 text-sm leading-relaxed text-ink-2">{{ $langkah['keterangan'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endisset

                    @isset($kartu['butir'])
                        <ul class="list-disc space-y-2 pl-5 text-[15px] leading-relaxed text-ink">
                            @foreach($kartu['butir'] as $baris)<li>{{ $baris }}</li>@endforeach
                        </ul>
                    @endisset

                    @isset($kartu['isi'])
                        @foreach($kartu['isi'] as $bagian)
                            @switch($bagian[0])
                                @case('peringatan')
                                    <x-info varian="kuning-polos" class="my-4">{{ $bagian[1] }}</x-info>
                                    @break
                                @case('sub')
                                    {{-- Subjudul beserta paragrafnya, dipisah garis tipis dari bagian sebelumnya. --}}
                                    <div class="border-t border-line-soft pt-4 first:border-0 first:pt-0 [&+&]:mt-4">
                                        <p class="text-[15px] text-ink">{{ $bagian[1] }}</p>
                                        <p class="mt-1 text-sm leading-relaxed text-ink-2">{{ $bagian[2] }}</p>
                                    </div>
                                    @break
                                @default
                                    <p class="text-[15px] leading-relaxed text-ink-2">{{ $bagian[1] }}</p>
                            @endswitch
                        @endforeach
                    @endisset

                    @isset($kartu['tabel'])
                        @php
                            $tabel = $kartu['tabel'];
                            $jumlahKolom = count($tabel['kepala']);
                            // Tanpa lebar yang disebut, kolom dibagi rata.
                            $lebar = $tabel['lebar'] ?? array_fill(0, $jumlahKolom, 'w-1/'.$jumlahKolom);
                            // Kolom awal yang berperan sebagai penanda ditulis lebih gelap.
                            $tegas = $tabel['tegas'] ?? 1;
                        @endphp
                        <table class="w-full text-left {{ isset($kartu['isi']) ? 'mt-4' : '' }}">
                            <thead>
                                <tr class="border-b border-line-soft">
                                    @foreach($tabel['kepala'] as $i => $kolom)
                                        <th scope="col" class="{{ $lebar[$i] }} pr-4 pb-2.5 text-sm font-normal text-ink-2 last:pr-0">{{ $kolom }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tabel['baris'] as $baris)
                                    <tr class="border-b border-line-soft last:border-0">
                                        @foreach($baris as $i => $sel)
                                            <td class="py-2 pr-4 align-top text-[15px] last:pr-0 {{ $i < $tegas ? 'text-ink' : 'text-ink-2' }}">
                                                @if(is_array($sel) && isset($sel['ikon']))
                                                    <x-icon :name="$sel['ikon']" :size="18" :class="$sel['kelas']" />
                                                @elseif(is_array($sel))
                                                    {{-- Lencana memakai komponen yang sama dengan halaman draf, jadi artinya tidak pernah berbeda. --}}
                                                    <x-badge :status="$sel['lencana']">{{ $sel['teks'] ?? '' }}</x-badge>
                                                @else
                                                    {{ $sel }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endisset

                    @isset($kartu['tanya'])
                        <dl>
                            @foreach($kartu['tanya'] as $t)
                                <div class="border-b border-line-soft py-3 first:pt-0 last:border-0 last:pb-0">
                                    <dt class="text-[15px] text-ink">{{ $t['tanya'] }}</dt>
                                    <dd class="mt-1 text-sm leading-relaxed text-ink-2">{{ $t['jawab'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endisset
                </x-card>
            @endforeach

            @isset($isi['catatan'])
                <x-info varian="biru-muda">{{ $isi['catatan'] }}</x-info>
            @endisset
        </div>
    </div>
@endsection
