# M-SmartTax

Aplikasi web untuk mencatat penghasilan, harta, dan utang usaha, lalu menyusun draf Pajak
Penghasilan Final 0,5% bagi wajib pajak orang pribadi pelaku UMKM.

> **DISCLAIMER**
> Sistem ini merupakan sistem simulasi untuk keperluan Ujian Skripsi Mahasiswa Universitas
> Ciputra. Sistem ini **tidak terafiliasi** dengan Direktorat Jenderal Pajak (DJP) atau
> instansi pemerintah mana pun. Dokumen yang dihasilkan adalah **draf**, bukan bukti setor
> maupun bukti lapor. Penyetoran dan pelaporan tetap dilakukan melalui saluran resmi DJP.

## Tentang

Pelaku UMKM sering kesulitan menyiapkan data sebelum melapor: catatan penjualan terpencar,
akumulasi omzet tidak terpantau, dan daftar harta serta utang baru disusun mendadak saat
tenggat lapor tiba.

M-SmartTax mengumpulkan catatan itu sepanjang tahun, menghitung sendiri akumulasi omzet dan
PPh Final terutang, lalu menyusunnya menjadi draf siap salin beserta dokumen PDF-nya.

Ketentuan yang dipakai dalam perhitungan:

| Ketentuan | Nilai |
|---|---|
| Tarif PPh Final | 0,5% dari peredaran bruto |
| Ambang bebas pajak | Rp500.000.000 per tahun pajak |
| Batas peredaran bruto | Rp4.800.000.000 per tahun pajak |

Tarif dan ambang tidak ditulis di dalam kode, melainkan disimpan sebagai konfigurasi
bertanggal berlaku, sehingga perubahan ketentuan cukup ditambahkan sebagai data baru.

## Fitur

**Pencatatan**
- Data Penghasilan — penerimaan usaha beserta tanggal dan keterangannya
- Data Harta — enam kategori: kas dan setara kas, piutang, investasi, harta bergerak, harta tidak bergerak, dan harta lainnya
- Data Utang — mengikuti empat kode utang resmi

**Penyusunan draf**
- Draf Pajak Penghasilan Bulanan — akumulasi omzet, omzet kena pajak, dan PPh Final per masa pajak
- Draf Pajak Penghasilan Tahunan — rekapitulasi dua belas masa pajak beserta lampiran harta dan utang

Data yang sudah masuk draf otomatis terkunci agar angka yang telanjur tersusun tidak berubah
diam-diam. Draf tahunan menyimpan salinan beku lampirannya, sehingga laporan tahun lampau
tetap menampilkan posisi pada tahun tersebut walaupun datanya berubah kemudian.

**Laporan PDF**
- Laporan Draf Pajak Penghasilan Bulanan dan Tahunan
- Laporan Data Harta dan Laporan Data Utang

**Analisis**
- Dashboard — grafik penghasilan bulanan, komposisi harta, pertumbuhan dan konsistensi kekayaan
- Simulasi Kelayakan Pembelian Aset — menimbang rencana pembelian terhadap penghasilan dan kewajiban berjalan

**Pendukung**
- Panduan Pengguna di dalam aplikasi
- Pengingat masa pajak yang drafnya belum disusun

## Teknologi

| Bagian | Yang dipakai |
|---|---|
| Kerangka kerja | Laravel 13 |
| Bahasa | PHP 8.3 ke atas |
| Basis data | MySQL |
| Antarmuka | Blade, Tailwind CSS 4, Alpine.js 3 |
| Grafik | Chart.js 4 |
| Dokumen PDF | barryvdh/laravel-dompdf |
| Pembangun aset | Vite 8 |

## Menjalankan di komputer sendiri

Dibutuhkan PHP 8.3 ke atas, Composer, Node.js, dan MySQL.

```bash
git clone https://github.com/MicheelleJeanneth/M-SmartTax.git
cd M-SmartTax

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Buat basis data kosong bernama `msmarttax`, lalu sesuaikan `DB_USERNAME` dan `DB_PASSWORD`
pada berkas `.env` dengan pengguna MySQL Anda. Setelah itu:

```bash
php artisan migrate --seed
php artisan serve
```

Aplikasi berjalan di `http://localhost:8000`.

### Akun contoh

Penyemai menyertakan satu wajib pajak contoh beserta data tiga tahun pajak.

| Username | Kata sandi |
|---|---|
| `budi.santoso` | `rahasia123` |

Akun baru dapat dibuat sendiri melalui halaman Daftar. Pendaftaran hanya meminta username
dan kata sandi; email bersifat pilihan, dan NIK tidak diminta sama sekali.

## Pengujian

```bash
php artisan test
```

Seluruh pengujian berjalan di atas SQLite dalam memori, terpisah dari basis data
pengembangan, dan disemai dengan data contoh yang sama.

## Catatan

Berkas `.env` tidak disertakan dalam repositori karena memuat kunci aplikasi dan sandi basis
data. Kerangkanya ada pada `.env.example`, dan `php artisan key:generate` akan mengisi
`APP_KEY` secara otomatis.

Folder `vendor/`, `node_modules/`, dan `public/build/` juga tidak disertakan karena dihasilkan
oleh `composer install` serta `npm run build` pada langkah pemasangan di atas.

---

Disusun oleh Micheelle Jeanneth, Universitas Ciputra.
