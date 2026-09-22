@extends('layouts.app')
@section('judul', $utang['kreditur'])
@section('keterangan', $utang['jenis'] . ' · Kode ' . $utang['kode'])

@section('isi')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>

    <div class="grid max-w-[960px] gap-6 lg:grid-cols-3">
        <x-card judul="Rincian Utang" class="lg:col-span-2">
            <div class="divide-y divide-line-soft">
                <x-row label="Kode">{{ $utang['kode'] }}</x-row>
                <x-row label="Nama Kreditur">{{ $utang['kreditur'] }}</x-row>
                <x-row label="Jenis Utang">{{ $utang['jenis'] }}</x-row>
                <x-row label="Tahun Peminjaman">{{ $utang['tahun'] }}</x-row>
                <x-row label="Saldo Akhir Tahun" tebal>{{ rupiah($utang['saldo']) }}</x-row>
            </div>
        </x-card>
        <div class="space-y-6">
            @if($utang['terkunci'])
                <x-info varian="abu">Utang ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.</x-info>
            @else
                <div class="flex gap-3">
                    <x-button varian="secondary" :href="route('utang.edit', $utang['id'])" class="flex-1"><x-icon name="pencil" :size="16" /> Ubah</x-button>
                    <x-button varian="secondary" class="flex-1 !text-danger" @click="$dispatch('buka-dialog', 'hapus-utang')"><x-icon name="trash-2" :size="16" /> Hapus</x-button>
                </div>
                @include('partials.dialog-hapus', [
                    'id' => 'hapus-utang',
                    'pesan' => 'Utang kepada ' . $utang['kreditur'] . ' akan dihapus.',
                    'action' => route('utang.destroy', $utang['id']),
                ])
            @endif
        </div>
    </div>
@endsection
