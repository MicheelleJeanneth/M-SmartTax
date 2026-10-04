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

/*
 | Kolom bertanda data-digit hanya menerima angka, misalnya tahun empat digit
 | dan NIK. Panjangnya sendiri sudah dijaga oleh maxlength.
 */
document.addEventListener('input', (peristiwa) => {
    const kolom = peristiwa.target;

    if (!(kolom instanceof HTMLInputElement) || kolom.dataset.digit === undefined) {
        return;
    }

    const bersih = kolom.value.replace(/\D/g, '');

    if (bersih === kolom.value) {
        return;
    }

    // Kursor dimundurkan sebanyak karakter yang dibuang agar tidak melompat ke ujung.
    const kursor = (kolom.selectionStart ?? kolom.value.length) - (kolom.value.length - bersih.length);
    kolom.value = bersih;
    kolom.setSelectionRange(kursor, kursor);
});

/*
 | Peringatan dini untuk tanggal yang jatuh pada bulan yang drafnya sudah
 | disusun. Pemeriksaan yang sesungguhnya tetap di server, karena yang di
 | browser bisa dilewati; bagian ini hanya supaya ketahuan sebelum tombol
 | Simpan ditekan dan isian yang sudah diketik tidak terbuang percuma.
 */
const peringatkanBulanTerkunci = (kolom) => {
    if (!(kolom instanceof HTMLInputElement) || kolom.dataset.kunciSebelum === undefined) {
        return;
    }

    const batas = kolom.dataset.kunciSebelum;
    const melanggar = kolom.value !== '' && kolom.value < batas;

    let pesan = '';

    if (melanggar) {
        // Tanggal ISO tanpa zona waktu supaya tidak bergeser sehari.
        const bulan = new Date(`${kolom.value}T00:00:00`)
            .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

        pesan = `Draf pajak ${bulan} sudah disusun, sehingga transaksi tidak dapat `
            + 'dicatat pada bulan itu. Batalkan draf bulan tersebut terlebih dahulu.';
    }

    // setCustomValidity membuat kolom ini ikut terhitung :invalid, sehingga
    // tombol kirim otomatis mati lewat perbaruiTombolKirim di bawah.
    kolom.setCustomValidity(pesan);

    const baris = document.getElementById(`${kolom.id}-kunci`);

    if (baris) {
        baris.textContent = pesan;
        baris.classList.toggle('hidden', pesan === '');
    }

    perbaruiTombolKirim();
};

document.addEventListener('input', (peristiwa) => peringatkanBulanTerkunci(peristiwa.target));
document.addEventListener('change', (peristiwa) => peringatkanBulanTerkunci(peristiwa.target));

// Berkas ini dimuat sebagai modul, jadi DOM sudah siap saat baris ini berjalan.
document.querySelectorAll('input[data-kunci-sebelum]').forEach(peringatkanBulanTerkunci);
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
