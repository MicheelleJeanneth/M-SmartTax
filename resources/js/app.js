import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/*
 | Kolom tanggal: browser membolehkan tahun diketik lebih dari empat digit,
 | misalnya 222222. Batasnya dibaca dari data-min/data-max, bukan atribut
 | min/max asli, karena Chrome menimpa tahun di tengah pengetikan bila
 | atribut itu dipasang. Peristiwa change baru menyala setelah tanggalnya
 | lengkap, jadi koreksi ini tidak pernah mengganggu saat mengetik.
 */
document.addEventListener('change', (peristiwa) => {
    const kolom = peristiwa.target;

    if (!(kolom instanceof HTMLInputElement) || kolom.type !== 'date' || kolom.value === '') {
        return;
    }

    const paling = { awal: kolom.dataset.min ?? kolom.min, akhir: kolom.dataset.max ?? kolom.max };

    if (paling.akhir && kolom.value > paling.akhir) {
        kolom.value = paling.akhir;
    } else if (paling.awal && kolom.value < paling.awal) {
        kolom.value = paling.awal;
    }
});
