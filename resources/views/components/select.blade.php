@props(['rapat' => false, 'label' => null, 'name', 'wajib' => false, 'pilihan' => [], 'terpilih' => null, 'bantuan' => null, 'kosong' => 'Pilih salah satu'])
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}"
        {{ $attributes->merge(['class' =>
            'h-11 w-full rounded-field border border-line bg-white px-3 text-[15px] text-ink
             focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary']) }}>
        @if($kosong)<option value="">{{ $kosong }}</option>@endif
        {{-- Daftar biasa ['A', 'B'] memakai teks sebagai nilai; array berkunci memakai kuncinya. --}}
        @foreach($pilihan as $nilai => $teks)
            @php $nilai = array_is_list($pilihan) ? $teks : $nilai; @endphp
            <option value="{{ $nilai }}" @selected((string) old($name, $terpilih) === (string) $nilai)>{{ $teks }}</option>
        @endforeach
    </select>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
