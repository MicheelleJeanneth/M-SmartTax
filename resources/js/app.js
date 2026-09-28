import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/*
 | Kolom tanggal: browser membolehkan tahun diketik lebih dari empat digit,
 | misalnya 222222. Nilai di luar rentang min/max dikembalikan ke batasnya.
 */
document.addEventListener('change', (peristiwa) => {
    const kolom = peristiwa.target;

    if (!(kolom instanceof HTMLInputElement) || kolom.type !== 'date' || kolom.value === '') {
        return;
    }

    if (kolom.max && kolom.value > kolom.max) {
        kolom.value = kolom.max;
    } else if (kolom.min && kolom.value < kolom.min) {
        kolom.value = kolom.min;
    }
});
