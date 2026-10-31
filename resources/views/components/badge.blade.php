@props(['status' => 'belum'])
@php
    [$gaya, $teks] = match ($status) {
        'tersusun', 'lengkap', 'normal', 'aktif' => ['bg-ok-bg text-ok-ink', ['tersusun' => 'Tersusun', 'lengkap' => 'Profil Lengkap', 'normal' => 'Normal', 'aktif' => 'Aktif'][$status]],
        'belum', 'tinjau' => ['bg-warn-bg text-warn-ink', ['belum' => 'Belum', 'tinjau' => 'Perlu Ditinjau'][$status]],
        'periksa' => ['bg-danger/10 text-danger', 'Perlu Diperiksa'],
        // Nada tanpa teks baku: teksnya datang dari slot, dipakai vonis simulasi.
        'ok' => ['bg-ok-bg text-ok-ink', ''],
        'warn' => ['bg-warn-bg text-warn-ink', ''],
        'danger' => ['bg-warn-bg text-danger', ''],
        'terkunci' => ['bg-frame text-ink-2', 'Terkunci'],
        default => ['bg-frame text-ink-2', ucfirst($status)],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-[13px] font-medium whitespace-nowrap $gaya"]) }}>
    {{ $slot->isEmpty() ? $teks : $slot }}
</span>
