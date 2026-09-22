@extends('layouts.app')
@section('judul', $item['nama'])
@section('keterangan', $info['nama'] . ' · Kode ' . $item['kode'])

@section('isi')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>

    <div class="grid max-w-[960px] gap-6 lg:grid-cols-3">
        <x-card judul="Rincian Harta" class="lg:col-span-2">
            <div class="divide-y divide-line-soft">
                <x-row label="Kode">{{ $item['kode'] }}</x-row>
                <x-row label="Nama Harta">{{ $item['nama'] }}</x-row>
                <x-row label="Tahun Perolehan">{{ $item['tahun'] }}</x-row>
                @foreach($info['kolom'] as $i => $kolom)
                    <x-row :label="$kolom">{{ $item['khas'][$i] }}</x-row>
                @endforeach
                <x-row :label="$info['label_nilai']" tebal>{{ rupiah($item['nilai']) }}</x-row>
            </div>
        </x-card>

        <div class="space-y-6">
            <x-stat label="Nilai Saat Ini" :nilai="rupiah($item['nilai_kini'])"
                :catatan="($item['nilai_kini'] >= $item['nilai'] ? 'Naik ' : 'Turun ') . persen(abs($item['nilai_kini'] - $item['nilai']) / $item['nilai'] * 100) . ' dari perolehan'" />

            @if($item['terkunci'])
                <x-info varian="abu">Harta ini sudah masuk draf tahunan. Batalkan draf tahunan terlebih dahulu untuk mengubahnya.</x-info>
            @else
                <div class="flex gap-3">
                    <x-button varian="secondary" :href="route('harta.edit', [$kategori, $item['id']])" class="flex-1"><x-icon name="pencil" :size="16" /> Ubah</x-button>
                    <x-button varian="secondary" class="flex-1 !text-danger" @click="$dispatch('buka-dialog', 'hapus-harta')"><x-icon name="trash-2" :size="16" /> Hapus</x-button>
                </div>
                @include('partials.dialog-hapus', [
                    'id' => 'hapus-harta',
                    'pesan' => $item['nama'] . ' akan dihapus dari daftar harta.',
                    'action' => route('harta.destroy', [$kategori, $item['id']]),
                ])
            @endif
        </div>
    </div>
@endsection
