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

// Jaring pengaman bila kolom dikirim tanpa pernah kehilangan fokus, misalnya lewat tombol Enter.
document.addEventListener('submit', (peristiwa) => {
    peristiwa.target.querySelectorAll?.('input[type="date"]').forEach(koreksiTanggal);
}, true);
