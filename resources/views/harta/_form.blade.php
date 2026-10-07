@php
    $h = $item ?? [];
    $kodeHarta = \App\Support\MockData::kodeHarta($kategori);

    // Kolom khas ditambah Lokasi harus genap agar grid dua kolom terisi penuh.
    // Bila ganjil, kolom khas pertama dibuat selebar penuh lebih dulu.
    $khasPenuh = (count($info['kolom']) + 1) % 2 === 1;
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

    @if($info['label_nilai_kini'])
        {{-- Kategori yang membedakan nilai awal dan sisa, misalnya piutang. --}}
        <div class="grid grid-cols-2 gap-x-6">
            <x-input :label="$info['label_nilai']" name="nilai" wajib uang placeholder="0"
                :value="isset($h['nilai']) ? angka($h['nilai']) : ''" />
            <x-input :label="$info['label_nilai_kini']" name="nilai_kini" wajib uang placeholder="0"
                :value="isset($h['nilai_kini']) ? angka($h['nilai_kini']) : ''" />
        </div>
        <p class="-mt-3 mb-6 text-sm text-ink-3">{{ $info['bantuan_nilai'] }}</p>
    @else
        <x-input :label="$info['label_nilai']" name="nilai" wajib uang placeholder="0"
            :value="isset($h['nilai']) ? angka($h['nilai']) : ''" />
    @endif

    {{-- Tahun diketik langsung, bukan dipilih, supaya pengguna bebas mengisi tahun berapa pun. --}}
    <div class="grid grid-cols-2 gap-x-6">
        <x-input :label="$info['label_tahun']" name="tahun" wajib inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Contoh: 2026" :value="$h['tahun'] ?? ''" />
        <x-input label="Tahun Pelepasan" name="tahun_pelepasan" inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Contoh: 2029" :value="$h['tahun_pelepasan'] ?? ''" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">{{ $info['bantuan_pelepasan'] }}</p>

    {{-- Garis pemisah memakai jarak yang sama dengan antar kotak. --}}
    <div class="mb-5 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">{{ $info['judul_rincian'] }}</h2>

    @if($khasPenuh)
        <x-input :label="$info['kolom'][0]" name="khas_0" :placeholder="$info['contoh'][0]"
            :wajib="$info['wajib_kolom'][0]" :value="$h['khas'][0] ?? ''" :bantuan="$info['bantuan_kolom'][0]" />
    @endif

    <div class="grid grid-cols-2 gap-x-6">
        @foreach($info['kolom'] as $i => $kolom)
            @continue($khasPenuh && $i === 0)
            <x-input :label="$kolom" :name="'khas_' . $i" :placeholder="$info['contoh'][$i]"
                :wajib="$info['wajib_kolom'][$i]" :value="$h['khas'][$i] ?? ''" :bantuan="$info['bantuan_kolom'][$i]" />
        @endforeach
        {{-- Harta selalu dalam negeri: terlihat tetapi tidak dapat diubah, sama seperti Negara Kreditur. --}}
        <x-input-locked :label="$info['label_lokasi']" name="negara" value="Indonesia" :bantuan="false" />
    </div>

    {{-- Tombol berada di dalam kartu dan rata kiri, sama seperti formulir Penghasilan dan Utang. --}}
    <div class="flex flex-wrap gap-3">
        <x-button type="submit" class="min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('harta.index', $kategori)" class="min-w-39">Batal</x-button>
    </div>
</x-card>
