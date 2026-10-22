@extends('layouts.app')
@section('judul', 'Simulasi Kelayakan Pembelian Aset')
@section('keterangan', 'Perkirakan kesiapan keuangan sebelum membeli aset baru')

@section('isi')
    <x-card judul="Kondisi Keuangan {{ $tahun }}">
        {{-- Cicilan berjalan wajib tampil di sini: nilainya ikut membentuk rasio
             angsuran, sehingga pengguna dapat menelusuri asal persentasenya. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach([
                'Rata-rata Penghasilan per Bulan' => $kondisi['penghasilan_bulanan'],
                'Cicilan Berjalan per Bulan' => $kondisi['cicilan_berjalan'],
                'Total Utang' => $kondisi['total_utang'],
                'Kekayaan Bersih' => $kondisi['kekayaan_bersih'],
            ] as $label => $nilai)
                <div class="rounded-field bg-page px-5 py-4">
                    <p class="text-sm text-ink-2">{{ $label }}</p>
                    <p class="mt-1 text-[26px] leading-tight font-medium tracking-tight tabular-nums text-ink">{{ rupiah($nilai) }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-sm text-ink-3">
            Terisi otomatis dari data penghasilan, harta, dan utang yang sudah tercatat, dan tidak dapat diubah di halaman ini.
        </p>
    </x-card>

    <x-card judul="Rencana Pembelian" class="mt-4">
        {{-- Tiga kolom kredit dimatikan saat Tunai dipilih: uang muka, jangka waktu,
             dan bunga tidak berlaku untuk pembelian tunai. --}}
        @php $kredit = old('cara_pembayaran', $rencana['cara_pembayaran']) !== 'Tunai'; @endphp
        <form method="POST" action="{{ route('simulasi.hitung') }}"
            x-data="{ bayar: '{{ old('cara_pembayaran', $rencana['cara_pembayaran']) }}' }">
            @csrf
            {{-- Kumpulan data peraga ikut terbawa agar keadaan khusus tetap tampil setelah Hitung. --}}
            @if($peraga)<input type="hidden" name="peraga" value="{{ $peraga }}">@endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                {{-- Tidak ada jenis aset yang terpilih lebih dulu: pilihannya
                     harus datang dari pengguna, bukan dari bawaan formulir. --}}
                <x-select label="Jenis Aset" name="jenis_aset" wajib kosong="Pilih jenis aset"
                    :pilihan="collect(\App\Support\MockData::kategoriHarta())->map(fn ($k) => $k['nama'])"
                    :terpilih="$rencana['jenis_aset']" />
                <x-input label="Harga Aset" name="harga_aset" wajib uang placeholder="Contoh: 450.000.000"
                    :value="$rencana['harga_aset'] ? angka($rencana['harga_aset']) : ''" />
            </div>

            <x-input label="Nama Aset" name="nama_aset" wajib maxlength="100"
                placeholder="Contoh: Ruko cabang ketiga" :value="$rencana['nama_aset']" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                <x-select label="Cara Pembayaran" name="cara_pembayaran" wajib :kosong="false"
                    :pilihan="['Tunai', 'Kredit']" :terpilih="$rencana['cara_pembayaran']" x-model="bayar" />
                <div x-show="bayar !== 'Tunai'" x-cloak>
                    {{-- Tidak wajib: kosong berarti tanpa uang muka, dan itu
                         membuat angsurannya lebih besar, bukan lebih ringan. --}}
                    <x-input label="Uang Muka" name="uang_muka" uang placeholder="0"
                        :value="$kredit && $rencana['uang_muka'] ? angka($rencana['uang_muka']) : ''"
                        bantuan="Kosongkan bila tidak ada uang muka." />
                </div>
            </div>

            {{-- Tunai menyembunyikan ketiga kolom kredit; uang muka lalu diisi
                 sama dengan harga aset di sisi server, sehingga rumusnya satu. --}}
            <div x-show="bayar !== 'Tunai'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                    <x-input label="Jangka Waktu (bulan)" name="jangka_waktu" wajib inputmode="numeric" maxlength="3"
                        pattern="\d{1,3}" data-digit placeholder="Contoh: 60"
                        :value="$kredit ? ($rencana['jangka_waktu'] ?: '') : ''"
                        x-bind:required="bayar !== 'Tunai'" />
                    <x-input label="Bunga per Tahun (%)" name="suku_bunga" wajib inputmode="decimal" placeholder="Contoh: 8,5"
                        :value="$kredit ? str_replace('.', ',', (string) $rencana['suku_bunga']) : ''"
                        bantuan="Boleh ditulis dengan koma maupun titik, misalnya 8,5."
                        x-bind:required="bayar !== 'Tunai'" />
                </div>
            </div>

            {{-- Tombol rata kiri, sama seperti formulir lain. --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">Hitung Kelayakan</x-button>
                <x-button varian="secondary" :href="route('simulasi.index', $peraga ? ['peraga' => $peraga] : [])" class="w-full sm:w-auto sm:min-w-39">Atur Ulang</x-button>
            </div>
        </form>
    </x-card>

    @if($hasil)
        {{-- Jenis aset tidak masuk perhitungan apa pun; nilainya hanya label
             pada judul hasil dan pada kalimat yang menyarankan pencatatan. --}}
        @php $kategori = \App\Support\MockData::kategoriHarta()[$rencana['jenis_aset']]['nama']; @endphp

        <x-card judul="Hasil Simulasi" :keterangan="$rencana['nama_aset'] . ' · ' . $kategori" class="mt-4">
            <div class="text-[15px]">
                <x-row label="Pokok pinjaman">{{ rupiah($pokok) }}</x-row>
                <x-row label="Angsuran per bulan" tebal>{{ rupiah($hasil['cicilan']) }}</x-row>
                @if($pokok === 0)
                    <p class="-mt-1.5 pb-2 text-sm text-ink-3">Pembelian dilakukan secara tunai.</p>
                @endif
                <x-row label="Cicilan berjalan">{{ rupiah($kondisi['cicilan_berjalan']) }}</x-row>
                <x-row label="Total kewajiban bulanan">{{ rupiah($hasil['total_kewajiban']) }}</x-row>

                @if($hasil['dapat_dihitung'])
                    <div class="my-3 border-t border-line-soft"></div>

                    {{-- Nama kedua rasio ditulis lengkap: hanya yang pertama
                         menentukan kesimpulan, yang kedua sekadar keterangan. --}}
                    <x-row label="Rasio angsuran terhadap penghasilan">{{ persen($hasil['rasio']) }}</x-row>
                    <x-row label="Rasio total utang terhadap penghasilan">{{ persen($hasil['rasio_utang']) }}</x-row>
                @endif
            </div>

            @if($hasil['dapat_dihitung'])
                @php
                    $gaya = ['ok' => 'bg-ok-bg text-ok-ink', 'warn' => 'bg-warn-bg text-warn-ink', 'danger' => 'bg-warn-bg text-danger'][$hasil['nada']];
                @endphp
                <div class="mt-3 rounded-field px-5 py-4 {{ $gaya }}">
                    <div class="flex items-start justify-between gap-6">
                        <p class="text-[15px] font-medium">Kesimpulan</p>
                        <span class="shrink-0 rounded-full bg-white/70 px-4 py-1 text-sm font-medium">{{ $hasil['kesimpulan'] }}</span>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed">{{ $hasil['penjelasan'] }}</p>
                    <p class="mt-1.5 text-sm leading-relaxed">
                        Rasio total utang terhadap penghasilan membandingkan sisa utang {{ rupiah($hasil['utang_setelah']) }} dengan peredaran bruto {{ rupiah($kondisi['penghasilan_tahun']) }} pada tahun ini, dan tidak ikut menentukan kesimpulan di atas.
                    </p>
                </div>
            @else
                <div class="mt-3 rounded-field bg-warn-bg px-5 py-4 text-sm leading-relaxed text-warn-ink">
                    Rasio tidak dapat dihitung karena belum ada data penghasilan pada tahun ini.
                </div>
            @endif
        </x-card>

        <x-card judul="Dampak pada Analisis Konsistensi" class="mt-4">
            @if($dampak['dapat_dihitung'])
                {{-- Keduanya memakai perhitungan konsistensi draf tahunan,
                     dipanggil dua kali, sehingga angkanya benar-benar sebanding. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['Status Sekarang' => $dampak['sekarang'], 'Perkiraan Setelah Pembelian' => $dampak['perkiraan']] as $label => $k)
                        <div class="rounded-field bg-page px-5 py-4">
                            <p class="text-sm text-ink-2">{{ $label }}</p>
                            <div class="mt-2 flex items-center gap-3">
                                <x-badge :status="$k['status']" />
                                <span class="text-[15px] tabular-nums text-ink-3">Rasio {{ angka($k['rasio'], 2) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-ink-3">Pertambahan bersih {{ rupiah($k['pertambahan_bersih']) }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-ink-3">
                    Perkiraan ini baru berlaku setelah asetnya benar-benar dibeli dan dicatat sendiri lewat menu Data Harta pada kategori {{ $kategori }}{{ $pokok > 0 ? ', beserta utangnya di Data Utang' : '' }}.
                </p>
            @elseif(! $dampak['ada_pembanding'])
                <p class="text-[15px] text-ink-2">
                    Dampak terhadap konsistensi harta akan muncul setelah draf tahunan pertama tersusun.
                </p>
            @else
                {{-- Drafnya ada, tetapi pembaginya nol. Sebabnya berbeda dari
                     keadaan di atas, sehingga keterangannya pun berbeda. --}}
                <p class="text-[15px] text-ink-2">
                    Rasio belum dapat dihitung karena belum ada peredaran bruto pada tahun ini.
                </p>
            @endif
        </x-card>

        <x-info varian="biru-muda" class="mt-4">
            Hasil simulasi merupakan perkiraan berdasarkan data yang tercatat pada sistem dan tidak disimpan. Keputusan pembelian tetap berada pada pengguna.
        </x-info>
    @endif
@endsection
