<x-dialog :id="$id" judul="Hapus data ini?">
    {{ $pesan }}
    <x-slot:aksi>
        <form method="POST" action="{{ $action }}">
            @csrf
            @method('DELETE')
            <x-button type="submit" varian="danger">Hapus</x-button>
        </form>
    </x-slot:aksi>
</x-dialog>
