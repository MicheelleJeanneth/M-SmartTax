{{--
    Dialog konfirmasi 520px, dibuka lewat Alpine:
    <button @click="$dispatch('buka-dialog', 'hapus-3')">…</button>
    <x-dialog id="hapus-3" judul="…"> … <x-slot:aksi>…</x-slot:aksi></x-dialog>
--}}
@props(['id', 'judul'])
<div x-data="{ buka: false }"
     x-on:buka-dialog.window="if ($event.detail === '{{ $id }}') buka = true"
     x-on:keydown.escape.window="buka = false"
     x-show="buka" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-6"
     role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-judul">
    <div x-show="buka" x-transition @click.outside="buka = false"
         class="w-full max-w-[520px] rounded-card bg-white p-6 shadow-xl">
        <h2 id="{{ $id }}-judul" class="text-lg font-medium text-ink">{{ $judul }}</h2>
        <div class="mt-3 text-[15px] leading-relaxed text-ink-2">{{ $slot }}</div>
        <div class="mt-6 flex justify-end gap-3">
            <x-button varian="secondary" @click="buka = false">Batal</x-button>
            {{ $aksi ?? '' }}
        </div>
    </div>
</div>
