@php $h = $item ?? []; @endphp
<x-card :judul="$info['nama']" class="max-w-[720px]">
    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="Kode Harta" name="kode" wajib :value="$h['kode'] ?? ''" bantuan="Kode harta sesuai lampiran SPT." />
        <x-input label="Tahun Perolehan" name="tahun" wajib inputmode="numeric" maxlength="4" :value="$h['tahun'] ?? ''" />
    </div>
    <x-input label="Nama Harta" name="nama" wajib :value="$h['nama'] ?? ''" />
    <div class="grid grid-cols-2 gap-x-4">
        @foreach($info['kolom'] as $i => $kolom)
            <x-input :label="$kolom" :name="'khas_' . $i" wajib :value="$h['khas'][$i] ?? ''"
                :bantuan="$kolom === 'Luas T/B' ? 'Luas tanah/bangunan dalam m², contoh 120/90' : null" />
        @endforeach
    </div>
    <div class="grid grid-cols-2 gap-x-4">
        <x-input :label="$info['label_nilai']" name="nilai" wajib inputmode="numeric" placeholder="Rp 0" :value="isset($h['nilai']) ? angka($h['nilai']) : ''" />
        <x-input label="Nilai Saat Ini" name="nilai_kini" inputmode="numeric" placeholder="Rp 0" :value="isset($h['nilai_kini']) ? angka($h['nilai_kini']) : ''"
            bantuan="Perkiraan nilai pasar. Hanya tampil di halaman detail." />
    </div>
    <x-textarea label="Keterangan" name="keterangan" :baris="2" />
</x-card>
<div class="mt-6 flex max-w-[720px] justify-end gap-3">
    <x-button varian="secondary" :href="route('harta.index', $kategori)">Batal</x-button>
    <x-button type="submit">{{ $tombol }}</x-button>
</div>
