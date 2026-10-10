{{--
    Kolom khas kategori untuk halaman detail.

    'bagian' memilih apa yang digambar: 'penuh' hanya kolom pertama yang selebar
    kartu, 'sisa' hanya kolom berikutnya. Tanpa 'bagian', keduanya digambar.
--}}
@php $bagian = $bagian ?? 'semua'; @endphp

@if($khasPenuh && in_array($bagian, ['semua', 'penuh'], true))
    <x-kolom-baca :label="$info['kolom'][0]">{{ $item['khas'][0] ?? '—' }}</x-kolom-baca>
@endif

@if(in_array($bagian, ['semua', 'sisa'], true))
    <div class="grid grid-cols-2 gap-x-6">
        @foreach($info['kolom'] as $i => $kolom)
            @continue($khasPenuh && $i === 0)
            <x-kolom-baca :label="$kolom">{{ $item['khas'][$i] ?? '—' }}</x-kolom-baca>
        @endforeach

        @if($info['label_lokasi'])
            <x-kolom-baca :label="$info['label_lokasi']">{{ $item['negara'] }}</x-kolom-baca>
        @endif
    </div>
@endif
