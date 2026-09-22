{{-- Aksi baris tabel: lihat / ubah / hapus, atau gembok bila terkunci. --}}
@php $lihat = $lihat ?? null; @endphp
<div class="flex items-center justify-end gap-1">
    @if($lihat)
        <a href="{{ $lihat }}" class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Lihat detail">
            <x-icon name="eye" :size="18" />
        </a>
    @endif
    @if($terkunci)
        <span class="p-2 text-locked" title="{{ $alasanKunci ?? 'Sudah masuk draf' }}" aria-label="{{ $alasanKunci ?? 'Sudah masuk draf' }}">
            <x-icon name="lock" :size="18" />
        </span>
    @else
        <a href="{{ $ubah }}" class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Ubah">
            <x-icon name="pencil" :size="18" />
        </a>
        <button type="button" @click="$dispatch('buka-dialog', '{{ $dialog }}')"
            class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Hapus">
            <x-icon name="trash-2" :size="18" />
        </button>
    @endif
</div>
