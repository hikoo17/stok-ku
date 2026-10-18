# Stok Ku — Aplikasi Pengelolaan Persediaan

Stok Ku adalah aplikasi web untuk mengelola persediaan barang pada usaha kecil. Aplikasi mencatat data barang, kategori, satuan, transaksi barang masuk dan keluar, riwayat stok, serta laporan persediaan. Seluruh antarmuka pengguna menggunakan Bahasa Indonesia.

Backend Laravel menyediakan REST API dan frontend Vue 3 berjalan sebagai aplikasi terpisah. Tidak ada Inertia.js dan tidak ada halaman Vue yang dirender melalui Blade.

## Fitur

- Dashboard dengan ringkasan stok, barang menipis, barang habis, grafik pergerakan 7 hari, dan transaksi terbaru.
- Pengelolaan barang lengkap: tambah, lihat detail, edit, cari, filter, urutkan, arsipkan, dan aktifkan kembali.
- Stok awal dicatat sebagai transaksi barang masuk yang dapat diaudit.
- Pengelolaan kategori dan satuan yang dapat diubah, dinonaktifkan, dan dihapus bila tidak digunakan produk.
- Transaksi barang masuk dan barang keluar dengan validasi stok di server.
- Pencegahan stok negatif dan perlindungan dari pengiriman ganda menggunakan idempotency key.
- Riwayat stok dengan filter tanggal, barang, jenis transaksi, pencarian, pengurutan, dan pagination.
- Laporan stok saat ini, pergerakan stok, dan stok menipis.
- Ekspor laporan ke CSV, PDF, dan DOCX dengan filter yang sama seperti tampilan laporan.
- Autentikasi menggunakan Laravel Sanctum (token API).
- Tampilan responsif untuk desktop dan perangkat seluler.

## Teknologi

**Backend**
- PHP 8.3
- Laravel 13
- MySQL 8 / MariaDB
- Laravel Sanctum
- DomPDF dan PHPWord untuk ekspor PDF/DOCX
- PHPUnit

**Frontend**
- Vue 3 (Composition API, `<script setup>`)
- Vite 8
- Tailwind CSS 4
- Vue Router 4
- Axios
- Lucide Vue Next
- Chart.js dan vue-chartjs

## Panduan Antarmuka

Seluruh teks antarmuka menggunakan Bahasa Indonesia. Ketentuan visual utama:

- Tinggi kontrol standar: 40px untuk input, select, dan tombol utama.
- Tombol kompak: 32px (`.btn-sm`) untuk toolbar filter dan 36px (`.btn-icon-sm`) untuk aksi tabel.
- Tombol besar: 44px (`.btn-lg`) untuk aksi utama halaman login.
- Label field berada di atas input, memakai ukuran dan warna yang sama di seluruh formulir.
- Radius, border, shadow, dan focus ring mengikuti kelas utilitas di `client/src/style.css`.
- Status stok memakai badge yang sama: Stok Aman, Stok Menipis, dan Stok Habis.
- Halaman menggunakan komponen bersama: `PageHeader`, `DataTable`, `Pagination`, `SearchInput`, `FilterDropdown`, `ProductCombobox`, `AppModal`, `ConfirmationDialog`, dan toast.
- Pemilihan barang pada transaksi memakai combobox yang dapat dicari dengan keyboard.
- Aksi muat ulang dan unduh laporan berada di atas tabel laporan; format tersedia: CSV, PDF, dan DOCX.
- Loading awal memakai skeleton (`Skeleton`, `SkeletonTable`, `SkeletonStatCard`); aksi singkat memakai `LoadingSpinner`.
- Dialog menangkap fokus keyboard, mendukung tombol Escape, dan mengembalikan fokus ke pemicu setelah ditutup.
- Animasi dinonaktifkan otomatis ketika pengguna memilih `prefers-reduced-motion`.

## Struktur Proyek

```
stok-ku/
├── client/                       # Frontend Vue 3
│   ├── src/
│   │   ├── assets/
│   │   ├── components/           # Komponen UI yang dapat digunakan ulang
│   │   ├── composables/          # Auth, toast, format, penanganan error
│   │   ├── layouts/              # Kerangka aplikasi
│   │   ├── pages/                # Halaman aplikasi
│   │   ├── router/
│   │   ├── services/             # Modul Axios per domain API
│   │   ├── App.vue
│   │   ├── main.js
│   │   └── style.css
│   ├── .env.example
│   ├── package.json
│   └── vite.config.js
├── server/                       # REST API Laravel
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/api.php
│   ├── tests/Feature/
│   ├── .env.example
│   └── composer.json
└── README.md
```

## Prasyarat

Pastikan perangkat lunak berikut sudah terpasang:

- PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, dan `zip`.
- Composer 2.
- MySQL 8 atau MariaDB 10.6 atau lebih baru.
- Node.js 20.19+ atau 22.12+ dan npm 10+.
- Git.

Perintah di bawah menggunakan PowerShell pada Windows. Sesuaikan perintah salin file jika menggunakan macOS/Linux (`cp` alih-alih `copy`).

## Instalasi Backend

1. Masuk ke direktori backend dan pasang dependensi:

```powershell
cd server
composer install
```

2. Salin konfigurasi lingkungan:

```powershell
copy .env.example .env
php artisan key:generate
```

3. Buat database MySQL:

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS stok_ku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

4. Sesuaikan koneksi database di `server/.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stok_ku
DB_USERNAME=root
DB_PASSWORD=
```

Pastikan juga:

```dotenv
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
FRONTEND_URL=http://localhost:5173
```

5. Jalankan migrasi dan seeder:

```powershell
php artisan migrate --seed
```

6. Jalankan server API:

```powershell
php artisan serve
```

API berjalan di `http://localhost:8000`. Endpoint kesehatan tersedia di `http://localhost:8000/up`.

### Akun Demo

Seeder membuat akun berikut untuk lingkungan non-produksi:

- Email: `admin@stokku.test`
- Kata sandi: `password`

**Ganti kata sandi ini sebelum aplikasi digunakan untuk data sungguhan.** Pada lingkungan produksi, `UserSeeder` tidak membuat akun dengan kata sandi bawaan. Isi variabel berikut di `server/.env` sebelum menjalankan seeder:

```dotenv
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@perusahaan-anda.com
ADMIN_PASSWORD=kata-sandi-yang-kuat
```

Jika `ADMIN_PASSWORD` kosong saat `APP_ENV=production`, seeder pengguna akan dilewati dan menampilkan peringatan.

### Data Demo

`DatabaseSeeder` menjalankan kategori, satuan, akun demo, dan data contoh (produk serta transaksi) hanya ketika `APP_ENV` bukan `production`. Data contoh bersifat ilustratif, bukan catatan persediaan bisnis yang terverifikasi.

Untuk produksi, jalankan seeder master tanpa data demo:

```powershell
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=UnitSeeder
php artisan db:seed --class=UserSeeder
```

Semua seeder kategori dan satuan menggunakan `updateOrCreate`, sehingga aman dijalankan berulang tanpa membuat duplikat.

## Instalasi Frontend

1. Masuk ke direktori frontend dan pasang dependensi:

```powershell
cd client
npm install
```

2. Salin konfigurasi lingkungan:

```powershell
copy .env.example .env
```

3. Pastikan `client/.env` berisi alamat API:

```dotenv
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

4. Jalankan server pengembangan:

```powershell
npm run dev
```

Buka `http://localhost:5173`.

## Menjalankan Aplikasi

Gunakan dua terminal terpisah:

```powershell
# Terminal 1
cd server
php artisan serve

# Terminal 2
cd client
npm run dev
```

Login menggunakan akun demo yang tercantum di atas.

## Pengujian

Backend menggunakan PHPUnit dan SQLite in-memory untuk pengujian, sehingga tidak mengubah database pengembangan.

```powershell
cd server
php artisan test
```

Cakupan pengujian penting:

- Pembuatan barang dan validasi input.
- Penolakan SKU duplikat.
- Relasi kategori dan satuan, termasuk penolakan penghapusan data yang masih dipakai.
- Pencatatan stok awal sebagai transaksi.
- Barang masuk dan barang keluar beserta snapshot stok sebelum/sesudah.
- Penolakan jumlah nol, negatif, dan melebihi stok.
- Pencegahan stok negatif dan rollback saat transaksi gagal.
- Idempotensi transaksi.
- Produk yang diarsipkan tidak dapat ditransaksikan dan riwayatnya tetap tersimpan.
- Perhitungan dashboard dan laporan.
- Autentikasi, login, logout, dan pencabutan token.
- Ekspor CSV, PDF, dan DOCX serta pencegahan formula injection pada CSV.

Pemeriksaan build frontend:

```powershell
cd client
npm run build
```

## Build Produksi

Backend:

```powershell
cd server
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

Atur `APP_ENV=production` dan `APP_DEBUG=false` pada `server/.env` produksi.

Frontend:

```powershell
cd client
npm ci
npm run build
```

Hasil build berada di `client/dist`. Sajikan direktori tersebut melalui web server statis dan arahkan `VITE_API_BASE_URL` ke URL API produksi. Karena aplikasi menggunakan Vue Router history mode, konfigurasikan fallback semua rute ke `index.html`.

Pastikan `FRONTEND_URL` di backend berisi origin frontend produksi agar CORS berjalan dengan benar.

## Ringkasan API

Semua endpoint menggunakan prefix `/api/v1`. Endpoint selain login memerlukan header:

```
Authorization: Bearer <token>
Accept: application/json
```

| Metode | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/login` | Masuk dan memperoleh token |
| GET | `/me` | Data pengguna aktif |
| POST | `/logout` | Mencabut token aktif |
| GET | `/dashboard` | Ringkasan dashboard |
| GET | `/products` | Daftar barang (pagination, filter, pencarian, sort) |
| GET | `/products/options` | Pilihan barang untuk form/filter |
| POST | `/products` | Tambah barang beserta stok awal |
| GET | `/products/{id}` | Detail barang |
| PUT | `/products/{id}` | Perbarui barang tanpa mengubah stok |
| DELETE | `/products/{id}` | Arsipkan barang |
| POST | `/products/{id}/restore` | Aktifkan kembali barang |
| GET | `/categories` | Daftar kategori |
| GET | `/categories/options` | Pilihan kategori aktif |
| POST | `/categories` | Tambah kategori |
| PUT | `/categories/{id}` | Perbarui kategori |
| DELETE | `/categories/{id}` | Hapus kategori bila tidak dipakai |
| GET | `/units` | Daftar satuan |
| GET | `/units/options` | Pilihan satuan aktif |
| POST | `/units` | Tambah satuan |
| PUT | `/units/{id}` | Perbarui satuan |
| DELETE | `/units/{id}` | Hapus satuan bila tidak dipakai |
| GET | `/stock-transactions` | Riwayat stok |
| GET | `/stock-transactions/{id}` | Detail transaksi |
| POST | `/stock-in` | Catat barang masuk |
| POST | `/stock-out` | Catat barang keluar |
| GET | `/reports/inventory` | Laporan stok saat ini |
| GET | `/reports/inventory/export` | Ekspor stok saat ini (`format=csv\|pdf\|docx`) |
| GET | `/reports/stock-movements` | Laporan pergerakan stok |
| GET | `/reports/stock-movements/export` | Ekspor pergerakan stok (`format=csv\|pdf\|docx`) |
| GET | `/reports/low-stock` | Laporan stok menipis |
| GET | `/reports/low-stock/export` | Ekspor stok menipis (`format=csv\|pdf\|docx`) |

Response daftar menggunakan format pagination Laravel (`data`, `meta`, `links`). Validasi gagal mengembalikan HTTP `422` dengan struktur `errors` per field. Pesan validasi disediakan dalam Bahasa Indonesia melalui `server/lang/id/validation.php`.

### Idempotensi Transaksi

Frontend mengirim `idempotency_key` (UUID) pada setiap transaksi barang masuk/keluar. Backend menyimpannya pada kolom unik `stock_transactions.idempotency_key`. Pengiriman ulang dengan kunci yang sama mengembalikan transaksi yang sudah tersimpan tanpa menambah stok dua kali.

## Aturan Bisnis

- Setiap barang memiliki satu kategori dan satu satuan utama.
- Stok awal dicatat sebagai transaksi bertipe `in` dengan keterangan `Stok awal produk`.
- `current_stock` hanya berubah melalui service `App\Services\InventoryService` di dalam database transaction.
- Transaksi barang keluar menggunakan row lock dan validasi ulang stok di server agar tidak terjadi overselling.
- Jumlah transaksi selalu positif; pengaruhnya ditentukan oleh jenis transaksi.
- Stok tidak boleh negatif.
- Barang diarsipkan (`is_active = false`), bukan dihapus, sehingga riwayat tetap tersedia.
- Kategori atau satuan tidak dapat dihapus selama masih digunakan produk.
- Kategori dan satuan dapat dinonaktifkan; data tersebut tidak dapat dipilih untuk barang baru, tetapi barang lama tetap menyimpan referensinya.
- Status stok:
  - `out_of_stock`: stok sama dengan nol.
  - `low_stock`: stok lebih dari nol dan kurang dari atau sama dengan batas minimum.
  - `in_stock`: stok di atas batas minimum.
- Nilai uang disimpan sebagai `decimal(15,2)` dan kuantitas sebagai `decimal(15,3)` untuk mendukung satuan seperti meter.
- Tanggal transaksi menggunakan tanggal yang dipilih pengguna. Bila hanya tanggal yang diisi, waktu tersimpan sebagai awal hari dan antarmuka menampilkan tanggal saja.
- Laporan dan dashboard dihitung dari data transaksi/stok yang sama.

## Asumsi yang Belum Dikonfirmasi

Asumsi berikut diterapkan pada versi pertama dan dapat diubah tanpa membongkar seluruh aplikasi:

1. Hanya ada satu lokasi persediaan.
2. Satu pengguna admin tunggal; belum ada pembagian peran.
3. Belum ada modul pemasok, pelanggan, pesanan pembelian, atau akuntansi.
4. Tidak ada konversi satuan. Setiap barang memakai satu satuan utama. Kartu **Total Stok** pada dashboard adalah penjumlahan kuantitas lintas satuan tanpa konversi, sehingga angka tersebut hanya indikator operasional.
5. Harga pada transaksi disimpan sebagai snapshot dan tidak mengubah harga master barang.
6. Kategori dan satuan boleh dinonaktifkan meskipun masih dipakai produk lama.
7. Penghapusan transaksi yang sudah selesai tidak disediakan; koreksi stok harus dilakukan melalui alur transaksi baru yang dapat diaudit.
8. Data contoh hanya untuk demonstrasi pengembangan.

Jika kebutuhan bisnis berubah, kategori dan satuan dapat disesuaikan melalui antarmuka tanpa mengubah kode. Aturan stok terpusat di `InventoryService`; laporan terpusat di `ReportService`; struktur database dapat diperluas melalui migrasi baru.

## Keamanan

- Token Sanctum disimpan di `localStorage` pada frontend. Gunakan HTTPS di produksi dan pertimbangkan migrasi ke autentikasi cookie SPA Sanctum bila aplikasi akan diakses publik dalam skala besar.
- Jangan commit file `.env` atau kredensial apa pun.
- Ganti kata sandi akun demo sebelum penggunaan nyata.
- Set `APP_DEBUG=false` dan `APP_ENV=production` di produksi.
- Batasi origin CORS melalui `FRONTEND_URL`.
- Frontend tidak menyimpan kata sandi, hanya token akses.
- Ekspor CSV menetralkan nilai yang diawali karakter formula (`=`, `+`, `-`, `@`) untuk mengurangi risiko formula injection.
- Ekspor PDF dihasilkan dengan DomPDF; ekspor DOCX dihasilkan dengan PHPWord. Keduanya memakai filter dan data yang sama dengan tabel laporan.

## Pemecahan Masalah

**Tidak dapat terhubung ke database**
- Pastikan MySQL/MariaDB berjalan.
- Periksa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
- Jalankan `php artisan config:clear` setelah mengubah `.env`.

**Frontend mendapat error CORS**
- Pastikan backend berjalan di `http://localhost:8000`.
- Pastikan `FRONTEND_URL` di `server/.env` sama dengan origin frontend (`http://localhost:5173`).
- Jalankan `php artisan config:clear` lalu restart `php artisan serve`.

**Halaman frontend 404 saat di-refresh**
- Konfigurasi web server produksi harus mengarahkan semua rute ke `index.html` (SPA fallback).

**Port sudah digunakan**
- Backend: `php artisan serve --port=8001`, lalu sesuaikan `VITE_API_BASE_URL`.
- Frontend: ubah `server.port` di `client/vite.config.js`, lalu sesuaikan `FRONTEND_URL`.

**Data demo tidak muncul**
- Pastikan `APP_ENV` bukan `production`.
- Jalankan `php artisan db:seed`.
- Periksa apakah data sudah ada; seeder tidak membuat duplikat.

**Token kedaluwarsa atau tidak valid**
- Logout lalu login kembali. Token disimpan di browser dan dicabut saat logout.

## Lisensi

Proyek ini dibuat untuk kebutuhan internal pengelolaan persediaan. Silakan sesuaikan lisensi sesuai kebutuhan organisasi.
