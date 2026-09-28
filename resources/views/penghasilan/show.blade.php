@extends('layouts.app')
@section('judul', 'Rincian Penghasilan')
@section('keterangan', tanggal_id($penghasilan['tanggal']))

@section('isi')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>

    <div class="grid max-w-[960px] gap-6 lg:grid-cols-3">
        <x-card judul="Rincian" class="lg:col-span-2">
            <div class="divide-y divide-line-soft">
                <x-row label="Tanggal">{{ tanggal_id($penghasilan['tanggal']) }}</x-row>
                <x-row label="Keterangan">{{ $penghasilan['keterangan'] }}</x-row>
                <x-row label="Status">{{ $penghasilan['terkunci'] ? 'Terkunci' : 'Aktif' }}</x-row>
                <x-row label="Nominal" tebal>{{ rupiah($penghasilan['nominal']) }}</x-row>
            </div>
        </x-card>

        <div class="space-y-6">
            @if($penghasilan['terkunci'])
                <x-info varian="abu">Penghasilan ini sudah masuk draf pajak bulanan. Batalkan draf bulan tersebut terlebih dahulu untuk mengubahnya.</x-info>
            @else
                <div class="flex gap-3">
                    <x-button varian="secondary" :href="route('penghasilan.edit', $penghasilan['id'])" class="flex-1"><x-icon name="pencil" :size="16" /> Ubah</x-button>
                    <x-button varian="secondary" class="flex-1 !text-danger" @click="$dispatch('buka-dialog', 'hapus-penghasilan')"><x-icon name="trash-2" :size="16" /> Hapus</x-button>
                </div>
                @include('partials.dialog-hapus', [
                    'id' => 'hapus-penghasilan',
                    'pesan' => 'Penghasilan ' . tanggal_id($penghasilan['tanggal']) . ' sebesar ' . rupiah($penghasilan['nominal']) . ' akan dihapus permanen.',
                    'action' => route('penghasilan.destroy', $penghasilan['id']),
                ])
            @endif
        </div>
    </div>
@endsection
