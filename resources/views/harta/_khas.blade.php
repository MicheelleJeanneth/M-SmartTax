{{--
    Kolom khas kategori. Dipakai dua kali oleh formulir: di tengah untuk kategori
    tanpa seksi, atau di bawah judul seksi untuk kategori yang memakainya.

    'digit_kolom' membuat kolom hanya menerima angka sebanyak digit yang ditentukan,
    'bawaan_kolom' mengisi nilai awal, keduanya boleh tidak didaftarkan.
--}}
@php
    $batasDigit = function (int $i) use ($info): array {
        $digit = $info['digit_kolom'][$i] ?? null;

        return $digit === null ? [] : [
            'inputmode' => 'numeric',
            'maxlength' => $digit,
            'pattern' => '\d{'.$digit.'}',
            'data-digit' => true,
        ];
    };

    $isiKhas = fn (int $i): string => $h['khas'][$i] ?? $info['bawaan_kolom'][$i] ?? '';
@endphp

@if($khasPenuh)
    <x-input :label="$info['kolom'][0]" name="khas_0" :placeholder="$info['contoh'][0]"
        :wajib="$info['wajib_kolom'][0]" :value="$isiKhas(0)" :bantuan="$info['bantuan_kolom'][0]"
        :attributes="new \Illuminate\View\ComponentAttributeBag($batasDigit(0))" />
@endif

<div class="grid grid-cols-2 gap-x-6">
    @foreach($info['kolom'] as $i => $kolom)
        @continue($khasPenuh && $i === 0)
        <x-input :label="$kolom" :name="'khas_' . $i" :placeholder="$info['contoh'][$i]"
            :wajib="$info['wajib_kolom'][$i]" :value="$isiKhas($i)" :bantuan="$info['bantuan_kolom'][$i]"
            :attributes="new \Illuminate\View\ComponentAttributeBag($batasDigit($i))" />
    @endforeach

    @if($info['label_lokasi'])
        {{-- Harta selalu dalam negeri: terlihat tetapi tidak dapat diubah, sama seperti Negara Kreditur. --}}
        <x-input-locked :label="$info['label_lokasi']" name="negara" value="Indonesia" :bantuan="false" />
    @endif
</div>

@if($info['bantuan_khas'] ?? null)
    {{-- Keterangan selebar kartu, dipakai bila kalimatnya terlalu panjang untuk satu kolom. --}}
    <p class="-mt-3 mb-6 text-sm text-ink-3">{{ titik($info['bantuan_khas']) }}</p>
@endif
