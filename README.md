# HRIS Laravel

Sistem Informasi Kepegawaian (HRIS) berbasis Laravel untuk mengelola data
pegawai, organisasi, absensi, cuti, payroll, rekrutmen, KPI, pelatihan, aset,
perjalanan dinas, reimbursement, pengumuman, dan laporan.

## Persyaratan

Pastikan perangkat sudah memiliki:

- PHP **8.3** atau lebih baru
- Composer
- Node.js dan npm
- MySQL atau MariaDB
- Git (opsional, jika project diambil dari repository)

## Instalasi

Jalankan semua perintah berikut dari folder project `hris-laravel`.

### 1. Install dependency

```bash
composer install
npm install
```

### 2. Buat file environment

Linux/macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Buat application key:

```bash
php artisan key:generate
```

### 3. Siapkan database MySQL

Buat database kosong, misalnya dengan nama `hris`:

```sql
CREATE DATABASE hris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import schema legacy yang disediakan project:

```bash
mysql -u root -p hris < database/legacy-schema.sql
```

Pada Windows, jika perintah `mysql` belum terdaftar di `PATH`, jalankan
`mysql.exe` dari folder instalasi MySQL, contohnya:

```powershell
Get-Content database/legacy-schema.sql | & "C:\xampp\mysql\bin\mysql.exe" -u root -p hris
```

### 4. Atur koneksi database

Edit bagian database di file `.env` sesuai instalasi lokal:

```dotenv
APP_NAME=HRIS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hris
DB_USERNAME=root
DB_PASSWORD=
```

Untuk pengembangan lokal, gunakan driver yang tidak membutuhkan tabel
tambahan Laravel:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Jika sebelumnya pernah menjalankan aplikasi dengan konfigurasi berbeda, bersihkan
cache konfigurasi:

```bash
php artisan config:clear
```

### 5. Isi data awal HRIS

Jalankan seeder setelah schema MySQL selesai diimpor:

```bash
php artisan db:seed --force
```

Seeder bersifat idempotent dan menyiapkan data organisasi, role dan permission,
akun administrator, jenis cuti dan izin, komponen payroll, jadwal, hari libur,
alur persetujuan, kategori aset/reimbursement, serta pengaturan sistem.

## Menjalankan aplikasi

### Opsi A: dua terminal (direkomendasikan)

Terminal 1, jalankan server Laravel:

```bash
php artisan serve
```

Terminal 2, jalankan Vite agar perubahan frontend ter-update otomatis:

```bash
npm run dev
```

Buka aplikasi pada [http://localhost:8000](http://localhost:8000).

### Opsi B: build asset untuk penggunaan tanpa Vite watcher

```bash
npm run build
php artisan serve
```

## Login awal

Setelah seeder berhasil dijalankan:

- **Username:** `admin`
- **Password:** `Admin@123`

Segera ganti password tersebut setelah login pertama.

## Perintah yang sering digunakan

```bash
# Bersihkan cache aplikasi
php artisan optimize:clear

# Lihat daftar route
php artisan route:list

# Jalankan test
php artisan test --compact

# Format kode PHP yang berubah
vendor/bin/pint --dirty --format agent
```

> Jangan menjalankan `php artisan migrate:fresh` pada database `hris` karena
> aplikasi menggunakan schema MySQL legacy. Jika membutuhkan database baru,
> buat database kosong lalu import kembali `database/legacy-schema.sql`.

## Troubleshooting

### Error koneksi database

Pastikan MySQL/MariaDB sedang berjalan, database `hris` sudah dibuat, dan nilai
`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` di `.env`
sesuai konfigurasi lokal.

### Asset Vite tidak ditemukan

Jalankan:

```bash
npm install
npm run build
```

Untuk development, gunakan `npm run dev` pada terminal terpisah dari
`php artisan serve`.

### Perubahan `.env` tidak terbaca

```bash
php artisan optimize:clear
```

## Struktur penting

```text
app/                    Kode aplikasi Laravel
database/legacy-schema.sql
                        Schema database MySQL legacy
database/seeders/       Seeder data awal
resources/views/        Template Blade
resources/js/           Source JavaScript
routes/web.php          Route aplikasi web
public/                 Asset publik
```
