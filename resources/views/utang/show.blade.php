@extends('layouts.app')
@section('judul', $utang['deskripsi'])
@section('keterangan', \App\Support\MockData::kodeUtang()[$utang['kode']] . ' · ' . $utang['kreditur'])

@section('isi')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>

    <div class="grid max-w-[960px] gap-6 lg:grid-cols-3">
        <x-card judul="Rincian Utang" class="lg:col-span-2">
            <div class="divide-y divide-line-soft">
                <x-row label="Kode Utang">{{ \App\Support\MockData::kodeUtang()[$utang['kode']] }}</x-row>
                <x-row label="Deskripsi Utang">{{ $utang['deskripsi'] }}</x-row>
                <x-row label="Tahun Peminjaman">{{ $utang['tahun'] }}</x-row>
                <x-row label="Tahun Pelunasan">{{ $utang['tahun_pelunasan'] ?? 'Belum lunas' }}</x-row>
                <x-row label="Nama Kreditur">{{ $utang['kreditur'] }}</x-row>
                <x-row label="NIK / NPWP Kreditur">{{ $utang['nik_kreditur'] ?? '-' }}</x-row>
                <x-row label="Negara Kreditur">{{ $utang['negara'] }}</x-row>
                <x-row label="Cicilan per Bulan">{{ $utang['cicilan'] ? rupiah($utang['cicilan']) : 'Tidak diangsur berkala' }}</x-row>
                <x-row label="Saldo Akhir Tahun" tebal>{{ rupiah($utang['saldo']) }}</x-row>
                @if($utang['keterangan'])<x-row label="Keterangan">{{ $utang['keterangan'] }}</x-row>@endif
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
