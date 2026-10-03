{{--
    Dialog konfirmasi hapus.

    'judul'   Pertanyaan di bagian atas, misalnya "Hapus data penghasilan?".
    'pesan'   Keterangan di bawah judul.
    'rincian' Ringkasan data yang akan dihapus, ['Tanggal' => '15 Juli 2026', ...],
              supaya pengguna yakin tidak salah baris sebelum menekan Hapus.
--}}
@php
    $judul = $judul ?? 'Hapus data ini?';
    $pesan = $pesan ?? 'Data ini akan dihapus dan tidak muncul lagi di daftar. Tindakan ini tidak dapat dibatalkan.';
    $rincian = $rincian ?? [];
@endphp
<x-dialog :id="$id" :judul="$judul">
    {{ $pesan }}

    @if($rincian)
        <dl class="mt-4 rounded-field bg-page px-4 py-3 text-[15px]">
            @foreach($rincian as $label => $nilai)
                <div class="flex items-baseline justify-between gap-6 py-1">
                    <dt class="text-ink-2">{{ $label }}</dt>
                    <dd class="text-right text-ink">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <x-slot:aksi>
        <form method="POST" action="{{ $action }}">
            @csrf
            @method('DELETE')
            <x-button type="submit" varian="danger">Hapus</x-button>
        </form>
    </x-slot:aksi>
</x-dialog>
