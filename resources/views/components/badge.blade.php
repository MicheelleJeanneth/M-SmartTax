@props(['status' => 'belum'])
@php
    [$gaya, $teks] = match ($status) {
        'tersusun', 'lengkap', 'normal' => ['bg-ok-bg text-ok-ink', ['tersusun' => 'Tersusun', 'lengkap' => 'Profil Lengkap', 'normal' => 'Normal'][$status]],
        'belum', 'tinjau' => ['bg-warn-bg text-warn-ink', ['belum' => 'Belum', 'tinjau' => 'Perlu Ditinjau'][$status]],
        'periksa' => ['bg-danger/10 text-danger', 'Perlu Diperiksa'],
        'nihil' => ['bg-frame text-ink-2', 'Nihil'],
        'terkunci' => ['bg-frame text-ink-2', 'Terkunci'],
        default => ['bg-frame text-ink-2', ucfirst($status)],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-[13px] font-medium whitespace-nowrap $gaya"]) }}>
    {{ $slot->isEmpty() ? $teks : $slot }}
</span>
