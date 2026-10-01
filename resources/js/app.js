import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/*
 | Kolom tanggal: browser membolehkan tahun diketik lebih dari empat digit,
 | misalnya 222222. Batasnya dibaca dari data-min/data-max, bukan atribut
 | min/max asli, karena Chrome menimpa tahun di tengah pengetikan bila
 | atribut itu dipasang.
 |
 | Koreksi dijalankan saat fokus meninggalkan kolom, bukan pada peristiwa
 | change. Digit pertama tahun sudah membuat tanggalnya lengkap sehingga
 | change menyala di tengah pengetikan: mengetik 2005 akan terpotong menjadi
 | 0002 lalu ditimpa ke batas bawah sebelum tiga digit sisanya masuk.
 */
const koreksiTanggal = (kolom) => {
    if (!(kolom instanceof HTMLInputElement) || kolom.type !== 'date' || kolom.value === '') {
        return;
    }

    const awal = kolom.dataset.min ?? kolom.min;
    const akhir = kolom.dataset.max ?? kolom.max;

    if (akhir && kolom.value > akhir) {
        kolom.value = akhir;
    } else if (awal && kolom.value < awal) {
        kolom.value = awal;
    }
};

document.addEventListener('focusout', (peristiwa) => koreksiTanggal(peristiwa.target));

/*
 | Tombol kirim dimatikan selama kolom bertanda wajib di formulir yang sama
 | masih kosong atau belum sah. Hanya formulir yang memang punya kolom wajib
 | yang terpengaruh, jadi tombol seperti Hapus dan Keluar tidak ikut mati.
 */
const perbaruiTombolKirim = () => {
    document.querySelectorAll('form').forEach((formulir) => {
        const tombol = formulir.querySelector('button[type="submit"]');

        if (!tombol || formulir.querySelectorAll('[required]').length === 0) {
            return;
        }

        tombol.disabled = formulir.querySelectorAll(':invalid').length > 0;
    });
};

document.addEventListener('input', perbaruiTombolKirim);
document.addEventListener('change', perbaruiTombolKirim);

// Berkas ini dimuat sebagai modul, jadi DOM sudah siap saat baris ini berjalan.
perbaruiTombolKirim();

/*
 | Kolom uang: pemisah ribuan dibubuhkan sambil mengetik, misalnya 16500000
 | menjadi 16.500.000. Hanya angka yang disimpan; "Rp" adalah awalan tetap
 | di dalam kotak, bukan bagian dari nilai yang dikirim.
 */
document.addEventListener('input', (peristiwa) => {
    const kolom = peristiwa.target;

    if (!(kolom instanceof HTMLInputElement) || kolom.dataset.uang === undefined) {
        return;
    }

    // Hitung posisi kursor dalam satuan digit supaya tidak terlempar ke ujung.
    const kursor = kolom.selectionStart ?? kolom.value.length;
    const digitSebelumKursor = kolom.value.slice(0, kursor).replace(/\D/g, '').length;

    const digit = kolom.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    kolom.value = digit === '' ? '' : digit.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    let terhitung = 0;
    let posisiBaru = digitSebelumKursor === 0 ? 0 : kolom.value.length;

    for (let i = 0; i < kolom.value.length && digitSebelumKursor > 0; i++) {
        if (/\d/.test(kolom.value[i])) {
            terhitung++;
        }

        if (terhitung === digitSebelumKursor) {
            posisiBaru = i + 1;
            break;
        }
    }

    kolom.setSelectionRange(posisiBaru, posisiBaru);
});

// Jaring pengaman bila kolom dikirim tanpa pernah kehilangan fokus, misalnya lewat tombol Enter.
document.addEventListener('submit', (peristiwa) => {
    peristiwa.target.querySelectorAll?.('input[type="date"]').forEach(koreksiTanggal);
}, true);
