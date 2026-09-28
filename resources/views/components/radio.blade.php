@props(['label' => null, 'name', 'pilihan' => [], 'terpilih' => null, 'wajib' => false])
<div class="mb-5">
    @if($label)
        <p class="mb-2 text-[15px] text-label">{{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif</p>
    @endif
    <div class="flex flex-wrap items-center gap-8">
        @foreach($pilihan as $nilai)
            <label class="flex items-center gap-2.5 text-[15px] text-ink">
                <input type="radio" name="{{ $name }}" value="{{ $nilai }}" @checked(old($name, $terpilih) === $nilai)
                    class="h-[18px] w-[18px] border-line text-primary focus:ring-2 focus:ring-accent">
                {{ $nilai }}
            </label>
        @endforeach
    </div>
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
