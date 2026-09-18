# Matriks Hak Akses User HRIS

Dokumen ini menjelaskan role, hak akses, akun demo, dan aturan otorisasi
aplikasi HRIS Bank Syariah Amanah Nusantara.

## 1. Cara kerja hak akses

Hak akses menggunakan kombinasi:

1. **User**: akun untuk login.
2. **Role**: jabatan akses fungsional yang diberikan kepada user.
3. **Permission**: hak akses teknis dengan format `modul.aksi`.
4. **Middleware permission**: route akan menolak akses dengan HTTP 403 jika
   user tidak memiliki permission yang diperlukan.

User dengan role `super-administrator` memiliki bypass seluruh permission.
Role lain hanya mendapatkan permission yang dipetakan melalui tabel
`role_permissions`.

Hak akses tidak ditentukan hanya dari nama jabatan atau department. Setiap
perubahan akses harus dilakukan melalui role dan permission agar dapat diaudit.

## 2. Role yang tersedia

| Role | Nama | Tujuan |
|---|---|---|
| `super-administrator` | Super Administrator | Pengelolaan penuh aplikasi dan seluruh data |
| `hr-administrator` | HR Administrator | Administrasi HR operasional dan pengelolaan data HR |
| `hr-manager` | HR Manager | Review dan persetujuan proses HR serta KPI |
| `finance` | Finance | Payroll, reimbursement, dan pelaporan finansial |
| `manager` | Manager | Persetujuan proses unit kerja dan supervisi |
| `supervisor` | Supervisor | Supervisi operasional dan persetujuan tingkat pertama |
| `employee` | Employee | Self-service absensi, cuti, KPI, dan reimbursement |
| `auditor` | Auditor | Akses baca dan export untuk audit |

## 3. Matriks permission

Keterangan aksi:

- `view`: melihat data atau membuka halaman.
- `create`: membuat transaksi atau data baru.
- `update`: mengubah data atau memproses pembaruan.
- `delete`: menghapus atau menonaktifkan data.
- `approve`: menyetujui transaksi atau permintaan.
- `correct`: memproses koreksi absensi.
- `generate`: membuat proses payroll.
- `review`: melakukan review/finalisasi kinerja.
- `export`: mengunduh laporan.
- `manage`: mengelola konfigurasi sistem.

| Modul | Permission yang tersedia | Fungsi bisnis |
|---|---|---|
| Employee | `employee.view`, `create`, `update`, `delete`, `export` | Data induk karyawan, detail, perubahan, penghapusan, export |
| Organization | `organization.view`, `create`, `update`, `delete` | Perusahaan, cabang, department, divisi, section, level, jabatan, lokasi |
| Attendance | `attendance.view`, `create`, `update`, `delete`, `correct`, `approve` | Kehadiran, clock in/out, koreksi, dan keputusan koreksi |
| Leave | `leave.view`, `create`, `update`, `delete`, `approve` | Pengajuan cuti, saldo, pembatalan, dan persetujuan |
| Payroll | `payroll.view`, `create`, `update`, `generate`, `approve`, `delete` | Periode payroll, komponen, kalkulasi, approval, dan payslip |
| Recruitment | `recruitment.view`, `create`, `update`, `delete` | Vacancy, kandidat, interview, assessment, dan hiring |
| Onboarding | `onboarding.view`, `create`, `update` | Tugas dan progres onboarding karyawan |
| Offboarding | `offboarding.view`, `create`, `update`, `approve` | Resign, exit interview, clearance, dan approval |
| Performance/KPI | `performance.view`, `create`, `update`, `delete`, `review` | Periode, indikator, assignment KPI, actual, review, dan score |
| Training | `training.view`, `create`, `update`, `delete` | Program training, peserta, dan attendance training |
| Asset | `asset.view`, `create`, `update`, `delete` | Asset kantor, assignment, pengembalian, dan maintenance |
| Business Trip | `business_trip.view`, `create`, `update`, `approve` | Perjalanan dinas, expense, budget, dan settlement |
| Reimbursement | `reimbursement.view`, `create`, `update`, `approve` | Pengajuan, verifikasi, dan pembayaran reimbursement |
| Announcement | `announcement.view`, `create`, `update`, `delete` | Pengumuman, target penerima, publish, dan read tracking |
| Report | `report.view`, `report.export` | Dashboard laporan dan export CSV/XLSX/PDF |
| System | `system.manage` | Pengaturan sistem dan administrasi aplikasi |
| Audit | `audit.view` | Audit log dan pelacakan aktivitas |

## 4. Hak akses setiap role

### 4.1 Super Administrator

Role `super-administrator` secara otomatis memiliki **seluruh permission**
yang tersedia, termasuk permission yang ditambahkan kemudian.

Kewenangan utama:

- Mengelola semua master data organisasi dan karyawan.
- Mengelola attendance, leave, payroll, recruitment, HR, KPI, dan laporan.
- Mengelola approval, konfigurasi sistem, role/permission, serta audit log.
- Melakukan troubleshooting dan validasi end-to-end.

Gunakan role ini hanya untuk administrator aplikasi. Jangan digunakan sebagai
akun harian operasional.

### 4.2 HR Administrator

Role ini disediakan untuk administrasi HR. Seeder memberikan permission administrasi HR operasional secara eksplisit.
Role ini tidak mendapatkan bypass seperti `super-administrator`.

Rekomendasi akses:

- Employee: `view`, `create`, `update`, `delete`, `export`
- Organization: `view`, `create`, `update`, `delete`
- Attendance: `view`, `correct`, `approve`
- Leave: `view`, `create`, `update`, `approve`
- Recruitment: seluruh aksi
- Onboarding dan offboarding: seluruh aksi yang relevan
- Performance/KPI: seluruh aksi kecuali perubahan konfigurasi teknis
- Training, asset, announcement: seluruh aksi operasional
- Report: `view`, `export`
- Audit: `view`

### 4.3 HR Manager

Permission default:

```text
employee.view
leave.view
leave.approve
attendance.view
attendance.approve
performance.view
performance.create
performance.update
performance.review
report.view
report.export
```

Fokus akses: review HR, persetujuan cuti/attendance, pengelolaan dan review
KPI, serta laporan HR.

### 4.4 Finance

Permission default:

```text
employee.view
payroll.view
payroll.create
payroll.update
payroll.generate
payroll.approve
reimbursement.view
reimbursement.approve
report.view
report.export
```

Fokus akses: payroll, komponen penghasilan/potongan, proses generate dan
approval payroll, reimbursement, serta laporan.

### 4.5 Manager

Role `manager` tersedia untuk kebutuhan approval dan supervisi unit kerja.
Seeder memberikan mapping permission default berikut:

- `employee.view`
- `attendance.view`, `attendance.approve`
- `leave.view`, `leave.approve`
- `performance.view`, `performance.update`, `performance.review`
- `business_trip.view`, `business_trip.approve`
- `reimbursement.view`, `reimbursement.approve`

Jangan memberikan `payroll.approve`, `system.manage`, atau akses audit penuh
kecuali terdapat kebijakan tertulis.

### 4.6 Supervisor

Permission default:

```text
employee.view
attendance.view
attendance.approve
leave.view
leave.approve
performance.view
performance.update
```

Fokus akses: memantau anggota tim, menyetujui attendance/leave tingkat
pertama, dan memperbarui atau memberi masukan terhadap KPI tim.

### 4.7 Employee

Permission default:

```text
employee.view
attendance.view
attendance.create
leave.view
leave.create
performance.view
performance.update
reimbursement.view
reimbursement.create
```

Fokus akses:

- Melihat profil dan data personal yang diizinkan.
- Clock in/out dan melihat attendance sendiri.
- Mengajukan cuti.
- Melihat KPI dan mengisi actual KPI miliknya.
- Membuat dan melihat reimbursement sendiri.

Controller tetap melakukan pemeriksaan kepemilikan untuk operasi tertentu;
permission saja tidak boleh dianggap sebagai izin mengubah data semua
employee.

### 4.8 Auditor

Permission default:

```text
report.view
report.export
employee.view
audit.view
```

Fokus akses: pemeriksaan data karyawan, audit log, dan export laporan.
Auditor tidak memiliki hak membuat, mengubah, menghapus, atau menyetujui
transaksi operasional.

## 5. Akun demo lokal

Akun berikut dibuat oleh `DatabaseSeeder` ketika `APP_ENV=local`:

| Username | Role | Password awal | Catatan |
|---|---|---|---|
| `admin` | `super-administrator` | `Admin@123` | Administrator aplikasi |
| `hr.manager` | `hr-manager` | `Demo@12345` | HR Manager |
| `finance.manager` | `finance` | `Demo@12345` | Finance |
| `supervisor.ops` | `supervisor` | `Demo@12345` | Supervisor Operasional |
| `employee.teller` | `employee` | `Demo@12345` | Teller/Customer Service |
| `employee.compliance` | `auditor` | `Demo@12345` | Auditor/Compliance |

Semua akun demo memiliki `must_change_password = true` pada saat pertama
dibuat. Ganti password sebelum digunakan di luar development.

## 6. Aturan production

- Seeder dummy hanya dijalankan saat `APP_ENV=local`.
- Pada `APP_ENV=production`, seeder tidak membuat user demo, divisi demo,
  jabatan demo, atau assignment demo.
- Administrator awal tetap dibuat agar instalasi dapat diakses. Password
  administrator wajib diganti segera setelah login pertama.
- Jangan menggunakan password contoh di production.
- Jangan memberikan role `super-administrator` kepada user operasional.
- Review role dan permission secara berkala, terutama setelah mutasi pegawai.
- Nonaktifkan user yang resign atau tidak lagi membutuhkan akses.
- Audit seluruh perubahan permission dan role.

## 7. Checklist pemberian akses user baru

1. Pastikan employee sudah dibuat dengan department, division, section,
   position, dan supervisor yang benar.
2. Buat user menggunakan email perusahaan.
3. Berikan role paling rendah yang sudah mencukupi kebutuhan kerja.
4. Verifikasi permission melalui menu yang terlihat dan uji route terkait.
5. Pastikan user tidak dapat membuka modul di luar tanggung jawabnya.
6. Minta user mengganti password awal.
7. Catat persetujuan pemberian akses dan lakukan review berkala.

## 8. Catatan konfigurasi

Mapping role dan permission berada di. Seeder melakukan sinkronisasi penuh
untuk role non-super-administrator: permission yang tidak tercantum pada
mapping resmi akan dihapus, sehingga perubahan permission lama tidak tertinggal
di database:

```text
database/seeders/DatabaseSeeder.php
```

Pengecekan permission user berada di:

```text
app/Models/User.php
app/Http/Middleware/RequirePermission.php
```

Route yang membutuhkan permission berada di:

```text
routes/web.php
```

Jika permission baru ditambahkan, update tiga area tersebut dan perbarui
dokumen ini agar konfigurasi dan dokumentasi tetap konsisten.
