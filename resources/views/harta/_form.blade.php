@php
    $h = $item ?? [];
    $kodeHarta = \App\Support\MockData::kodeHarta($kategori);
@endphp
<x-card>
    <h2 class="mb-4 text-base font-medium text-subjudul">Data Harta</h2>

    @if($kodeHarta)
        {{-- Kode dan uraiannya tampil sebagai satu pilihan. Deskripsi tidak ditampilkan
             sebagai kolom tersendiri, tetapi tetap ikut tersimpan lewat kolom tersembunyi
             yang isinya diperbarui app.js setiap kode diganti. --}}
        <x-select label="Kode Harta" name="kode" wajib :kosong="false"
            :pilihan="collect($kodeHarta)->mapWithKeys(fn ($uraian, $kode) => [$kode => $kode . ' - ' . $uraian])"
            :terpilih="$h['kode'] ?? array_key_first($kodeHarta)"
            data-isi-uraian="nama" :data-uraian="json_encode($kodeHarta)" />
        <input type="hidden" id="nama" name="nama" value="{{ $h['nama'] ?? reset($kodeHarta) }}">
    @else
        {{-- Kategori yang daftar kodenya belum tersedia: kode dan deskripsi masih diketik. --}}
        <div class="grid grid-cols-2 gap-x-6">
            <x-input label="Kode Harta" name="kode" wajib inputmode="numeric" maxlength="4" pattern="\d{3,4}"
                data-digit placeholder="Contoh: 0101" :value="$h['kode'] ?? ''" />
            <x-input label="Deskripsi" name="nama" placeholder="Contoh: Tabungan (Bank/Lembaga Keuangan)"
                :value="$h['nama'] ?? ''" />
        </div>
    @endif

    <x-input label="Keterangan" name="keterangan" :placeholder="'Contoh: ' . $info['contoh_keterangan']"
        :value="$h['keterangan'] ?? ''" />

    <x-input :label="$info['label_nilai']" name="nilai" wajib uang placeholder="0"
        :value="isset($h['nilai']) ? angka($h['nilai']) : ''" />

    {{-- Tahun diketik langsung, bukan dipilih, supaya pengguna bebas mengisi tahun berapa pun. --}}
    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="Tahun Perolehan" name="tahun" wajib inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Contoh: 2026" :value="$h['tahun'] ?? ''" />
        <x-input label="Tahun Pelepasan" name="tahun_pelepasan" inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Contoh: 2029" :value="$h['tahun_pelepasan'] ?? ''" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">{{ $info['bantuan_pelepasan'] }}</p>

    {{-- Garis pemisah memakai jarak yang sama dengan antar kotak. --}}
    <div class="mb-5 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">{{ $info['judul_rincian'] }}</h2>

    <div class="grid grid-cols-2 gap-x-6">
        @foreach($info['kolom'] as $i => $kolom)
            <x-input :label="$kolom" :name="'khas_' . $i" :placeholder="'Contoh: ' . $info['contoh'][$i]"
                :value="$h['khas'][$i] ?? ''" :bantuan="$info['bantuan_kolom'][$i]" />
        @endforeach
        {{-- Harta selalu dalam negeri: terlihat tetapi tidak dapat diubah, sama seperti Negara Kreditur. --}}
        <x-input-locked label="Lokasi / Negara" name="negara" value="Indonesia" />
    </div>

    {{-- Tombol berada di dalam kartu dan rata kiri, sama seperti formulir Penghasilan dan Utang. --}}
    <div class="flex flex-wrap gap-3">
        <x-button type="submit" class="min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('harta.index', $kategori)" class="min-w-39">Batal</x-button>
    </div>
</x-card>
