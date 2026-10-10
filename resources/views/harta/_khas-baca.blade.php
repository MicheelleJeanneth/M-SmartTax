{{-- Kolom khas kategori untuk halaman detail. Susunannya mengikuti formulir. --}}
@if($khasPenuh)
    <x-kolom-baca :label="$info['kolom'][0]">{{ $item['khas'][0] ?? '—' }}</x-kolom-baca>
@endif

<div class="grid grid-cols-2 gap-x-6">
    @foreach($info['kolom'] as $i => $kolom)
        @continue($khasPenuh && $i === 0)
        <x-kolom-baca :label="$kolom">{{ $item['khas'][$i] ?? '—' }}</x-kolom-baca>
    @endforeach

    @if($info['label_lokasi'])
        <x-kolom-baca :label="$info['label_lokasi']">{{ $item['negara'] }}</x-kolom-baca>
    @endif
</div>
