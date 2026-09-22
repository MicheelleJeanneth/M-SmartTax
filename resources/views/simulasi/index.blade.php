@extends('layouts.app')
@section('judul', 'Simulasi Kelayakan Pembelian Aset')
@section('keterangan', 'Hitung apakah cicilan aset baru masih sehat untuk kondisi keuangan Anda. Hasil tidak disimpan.')

@section('isi')
    <div class="grid gap-6 xl:grid-cols-5">
        <x-card judul="Kondisi Keuangan" keterangan="Terisi otomatis dari data Anda" class="xl:col-span-2">
            <div class="divide-y divide-line-soft">
                <x-row label="Rata-rata penghasilan bulanan">{{ rupiah($kondisi['penghasilan_bulanan']) }}</x-row>
                <x-row label="Total harta">{{ rupiah($kondisi['total_harta']) }}</x-row>
                <x-row label="Total utang">{{ rupiah($kondisi['total_utang']) }}</x-row>
                <x-row label="Kekayaan bersih">{{ rupiah($kondisi['kekayaan_bersih']) }}</x-row>
                <x-row label="Cicilan berjalan per bulan" tebal>{{ rupiah($kondisi['cicilan_berjalan']) }}</x-row>
            </div>
        </x-card>

        <x-card judul="Rencana Pembelian" class="xl:col-span-3">
            <form method="GET" action="{{ route('simulasi.index') }}">
                <div class="grid grid-cols-2 gap-x-4">
                    <x-input label="Harga Aset" name="harga_aset" wajib inputmode="numeric" :value="$rencana['harga_aset']" bantuan="Dalam rupiah, tanpa titik." />
                    <x-input label="Uang Muka" name="uang_muka" wajib inputmode="numeric" :value="$rencana['uang_muka']" />
                    <x-input label="Jangka Waktu (bulan)" name="jangka_waktu" wajib inputmode="numeric" :value="$rencana['jangka_waktu']" />
                    <x-input label="Suku Bunga per Tahun (%)" name="suku_bunga" wajib inputmode="decimal" :value="$rencana['suku_bunga']" />
                </div>
                <div class="flex justify-end">
                    <x-button type="submit"><x-icon name="calculator" :size="18" /> Hitung</x-button>
                </div>
            </form>
        </x-card>
    </div>

    @if($hasil)
        @php
            $gayaKesimpulan = ['ok' => 'bg-ok-bg text-ok-ink', 'warn' => 'bg-warn-bg text-warn-ink', 'danger' => 'bg-danger/10 text-danger'][$hasil['nada']];
        @endphp
        <x-card judul="Hasil Simulasi" class="mt-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-field px-5 py-5 {{ $gayaKesimpulan }}">
                    <p class="text-sm font-medium">Kesimpulan</p>
                    <p class="mt-1 text-[28px] leading-tight font-semibold">{{ $hasil['kesimpulan'] }}</p>
                    <p class="mt-1 text-sm">Rasio cicilan {{ persen($hasil['rasio']) }} dari penghasilan bulanan</p>
                </div>
                <div class="divide-y divide-line-soft lg:col-span-2">
                    <x-row label="Pokok pinjaman">{{ rupiah($hasil['pokok']) }}</x-row>
                    <x-row label="Cicilan aset baru per bulan">{{ rupiah($hasil['cicilan']) }}</x-row>
                    <x-row label="Total cicilan per bulan (termasuk berjalan)" tebal>{{ rupiah($hasil['total_cicilan']) }}</x-row>
                    <x-row label="Total pembayaran selama {{ $rencana['jangka_waktu'] }} bulan">{{ rupiah($hasil['total_bayar']) }}</x-row>
                </div>
            </div>
            <p class="mt-4 text-[13px] text-ink-3">Layak di bawah 30% · Perlu Dipertimbangkan 30–40% · Belum Disarankan di atas 40%</p>
        </x-card>

        <x-card judul="Dampak Konsistensi" keterangan="Perkiraan setelah aset dibeli" class="mt-6">
            <x-table :kepala="['', ['teks' => 'Sebelum', 'kanan' => true], ['teks' => 'Sesudah', 'kanan' => true]]">
                <tr><td class="text-ink-2">Total harta</td><td class="text-right tabular-nums">{{ rupiah($kondisi['total_harta']) }}</td><td class="text-right tabular-nums">{{ rupiah($hasil['harta_baru']) }}</td></tr>
                <tr><td class="text-ink-2">Total utang</td><td class="text-right tabular-nums">{{ rupiah($kondisi['total_utang']) }}</td><td class="text-right tabular-nums">{{ rupiah($hasil['utang_baru']) }}</td></tr>
                <tr><td class="text-ink-2">Kekayaan bersih</td><td class="text-right tabular-nums">{{ rupiah($kondisi['kekayaan_bersih']) }}</td><td class="text-right tabular-nums">{{ rupiah($hasil['harta_baru'] - $hasil['utang_baru']) }}</td></tr>
                <tr><td class="text-ink-2">Rasio utang terhadap harta</td><td class="text-right tabular-nums">{{ persen($hasil['rasio_utang_lama']) }}</td><td class="text-right tabular-nums font-medium">{{ persen($hasil['rasio_utang_baru']) }}</td></tr>
            </x-table>
            <x-info varian="biru" class="mt-4">
                Kekayaan bersih tidak berubah karena harta bertambah sebesar utang baru dan uang muka. Aset baru perlu dicatat di Data Harta setelah dibeli.
            </x-info>
        </x-card>
    @endif
@endsection
