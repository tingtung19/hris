# HRIS Laravel Migration Progress

Last updated: 2026-09-16 15:25

Dokumen ini adalah handoff resmi proyek migrasi HRIS. Baca dokumen ini sebelum
melanjutkan pekerjaan agar tidak perlu melakukan scan ulang seluruh project legacy.

## Penanda status

- `[x]` Selesai dan sudah divalidasi.
- `[~]` Sudah tersedia sebagian, tetapi belum parity penuh atau belum diuji
  end-to-end.
- `[ ]` Belum dilaksanakan.

## Checklist progres keseluruhan

### Sudah dilaksanakan

- [x] Membuat project Laravel 13 di `hris-laravel`.
- [x] Menghubungkan project ke database MySQL legacy `hris`.
- [x] Memindahkan authentication, dashboard, employee, organization,
  attendance, leave, payroll inti, dan recruitment inti.
- [x] Menyediakan route dan CRUD dasar untuk onboarding, offboarding,
  performance, training, assets, business trip, reimbursement, announcements,
  notifications, audit logs, settings, dan reports.
- [x] Memindahkan seeder legacy menjadi `DatabaseSeeder` Laravel yang idempotent.
- [x] Menambahkan RBAC dasar dan permission middleware.
- [x] Memperbarui Dompdf dan PhpSpreadsheet ke versi patched.
- [x] Memvalidasi test, Pint, Blade cache, config cache, route list, composer
  audit, koneksi MySQL, dan seeder.

### Sudah tersedia tetapi masih parsial

- [x] Attendance: clock in/out, shift, grace period, geolocation radius, late
  status, correction, dan work-minute recalculation sudah aktif.
- [x] Leave: saldo otomatis, overlap check, multi-step approval, approval
  snapshot, used balance, dan cancellation reversal sudah aktif.
- [x] Payroll parity: salary components, percentage components, BPJS, PPh21
  progressive, approved overtime, pending deductions, payroll details, payslip
  records, dan PDF payslip sudah aktif.
- [x] Recruitment: vacancy lifecycle, CV, candidate detail, stage history,
  interview scheduling/decision, assessment, quota/date validation,
  hire-to-employee, dan onboarding otomatis sudah aktif.
- [x] Modul HR: business rule onboarding/offboarding, performance KPI/review,
  training participant/attendance, asset assignment/return/maintenance,
  business-trip expense/budget/settlement, reimbursement payment, dan
  announcement targeting/publish/read sudah aktif.
- [x] KPI MVP: dashboard, periode, indikator BSC, assignment employee dengan
  validasi total bobot maksimal 100%, input actual, perhitungan score, audit,
  dan permission-aware UI tersedia pada `/kpi`.
- [x] RBAC: seluruh route fungsional utama sudah memiliki permission middleware;
  pemetaan permission setiap role masih perlu diuji lewat browser.

### Belum dilaksanakan

- [~] Automated UAT smoke test dan security headers sudah aktif; browser/UAT
  end-to-end dengan role employee, manager, finance, auditor, dan administrator
  masih membutuhkan akses pengguna/data nyata.
- [~] Approval engine terpusat sudah aktif untuk pembuatan request, validasi
  approver, multi-step decision, history, leave, business trip, dan
  reimbursement; UAT keputusan tiap role masih perlu dilakukan.
- [x] Audit logging otomatis untuk perubahan penting pada auth, employee,
  organization, attendance, leave, payroll, recruitment, settings,
  notifications, HR creation, dan approval.
- [x] Payroll detail, komponen salary, overtime approved, deduction pending,
  BPJS, PPh21 progressive, payslip record, dan PDF sudah aktif.
- [x] Business rule HR core: onboarding/offboarding, performance review,
  training participants, asset assignment/return, trip expense, reimbursement
  payment, dan announcement publish sudah aktif.
- [x] Reports dashboard dan export CSV, Excel, PDF untuk employee, attendance,
  leave, payroll, dan recruitment.
- [x] Laravel Scheduler untuk training reminder, birthday reminder, contract
  reminder/expiry, dan notification cleanup legacy.
- [x] Reports export CSV, Excel, dan PDF.
- [x] Security headers dan automated operational smoke tests.
- [x] `PRODUCTION-CHECKLIST.md` dibuat untuk deployment, backup, secrets,
  storage, queue, monitoring, dan manual UAT.
- [~] Production hardening code dan checklist sudah tersedia; backup,
  monitoring, HTTPS, queue, secrets, dan deployment masih membutuhkan
  konfigurasi environment produksi.

## 1. Tujuan dan keputusan arsitektur

- Project legacy adalah aplikasi PHP native dengan pola `Route -> Controller ->
  Service -> Model -> PDO`.
- Project baru berada di folder `hris-laravel`.
- Framework: Laravel 13.
- PHP target: 8.3.
- UI: Blade + Bootstrap 5 melalui layout utama.
- Database: tetap menggunakan schema MySQL legacy, bukan migrasi schema penuh ke
  tabel Laravel baru.
- Schema acuan: `database/legacy-schema.sql`.
- Database lokal yang sudah diuji: MySQL `hris` pada `127.0.0.1:3306`.
- Driver session/cache/queue menggunakan konfigurasi file/sync agar tidak
  membutuhkan tabel tambahan Laravel.

## 2. Status ringkas

| Status | Area | Catatan |
|---|---|---|
| [x] | Project Laravel baru | Laravel 13 sudah dibuat |
| [x] | Konfigurasi MySQL legacy | Database `hris` berhasil terhubung |
| [x] | Auth dan dashboard | Login username/email, lockout, logout |
| [x] | Employee | CRUD, search, filter, pagination, detail |
| [x] | Organization | Master data dan organization chart |
| [~] | Attendance | Clock in/out, history, correction; rule shift belum penuh |
| [~] | Leave | Request, hari kerja, cancel, decision; approval chain belum penuh |
| [~] | Payroll | Period, generate dasar, status, payslip view |
| [x] | Recruitment | Vacancy lifecycle, candidate detail, pipeline, interview, assessment, hire |
| [x] | Modul HR | Workflow domain utama sudah tersedia; browser/UAT tetap tersisa |
| [x] | Seeder Laravel | Idempotent dan sudah dijalankan dua kali |
| [x] | RBAC dasar | Permission middleware diterapkan pada route utama |
| [x] | Dependency security | `composer audit` bersih |
| [x] | Test dan lint | 2 test lulus, Pint lulus |
| [~] | End-to-end browser test | Automated smoke test selesai; UAT role nyata tersisa |
| [~] | Production hardening | Security headers/checklist selesai; infra production tersisa |

## 3. Pekerjaan yang sudah selesai

### 3.1 Fondasi dan konfigurasi

- Folder project baru dibuat: `hris-laravel`.
- `.env` diarahkan ke MySQL database `hris`.
- Schema legacy disalin ke `hris-laravel/database/legacy-schema.sql`.
- Asset dan folder upload legacy disalin sesuai kebutuhan.
- Dompdf dan PhpSpreadsheet ditambahkan.
- README project diperbarui dengan instruksi setup dan seeding.

### 3.2 Authentication

- `app/Models/User.php` memakai tabel legacy `users`.
- Login mendukung username atau email.
- Password, status aktif, `locked_until`, `failed_login_attempts`, remember
  session, dan metadata login dipetakan ke schema legacy.
- Lockout setelah kegagalan login diimplementasikan.
- Logout tersedia.
- Root route:
  - guest -> `/login`
  - user authenticated -> `/dashboard`

File utama:

- `app/Models/User.php`
- `app/Http/Controllers/AuthController.php`
- `resources/views/auth/login.blade.php`
- `routes/web.php`

### 3.3 Employee dan organization

- Employee list dengan search, filter, pagination.
- Create, edit, detail, soft delete manual melalui `deleted_at`.
- Generator nomor employee `EMP-0001`.
- Relasi department dan position.
- Organization master:
  - companies
  - branches
  - departments
  - divisions
  - sections
  - positions
  - job levels
  - job grades
  - work locations
  - cost centers
- Organization chart perusahaan -> cabang -> departemen.

File utama:

- `app/Models/Employee.php`
- `app/Models/Department.php`
- `app/Models/Position.php`
- `app/Http/Controllers/EmployeeController.php`
- `app/Http/Controllers/OrganizationController.php`
- `resources/views/employees/`
- `resources/views/organization/`

### 3.4 Attendance dan leave

Attendance:

- Clock in dan clock out.
- Capture lokasi, IP, device.
- Perhitungan menit kerja.
- History bulanan.
- Request koreksi.
- Approval atau rejection koreksi.

Leave:

- Daftar leave type dan balance.
- Request cuti.
- Perhitungan hari kerja.
- Weekend dan holiday exclusion.
- Deteksi bentrok.
- Cancellation.
- Approval atau rejection.

File utama:

- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/LeaveController.php`
- `resources/views/attendance/`
- `resources/views/leave/`

Catatan gap:

- Shift, grace period, geolocation radius, snapshot approval chain, dan
  automatic balance usage belum sepenuhnya memakai service legacy.

### 3.5 Payroll dan recruitment

Payroll yang sudah tersedia:

- Payroll period.
- Pembuatan periode.
- Generate payroll dasar dari `employee_salaries`.
- Status draft -> review -> approved -> paid -> locked.
- Daftar payroll.
- Payslip view.

Recruitment yang sudah tersedia:

- Vacancy.
- Candidate.
- Pipeline stage.
- Stage history.
- Candidate detail page.
- CV upload dengan validasi tipe dan ukuran.
- Interview schedule dan pass/fail decision.
- Assessment dengan score dan notes.
- Vacancy status, posted/closing date, update, dan soft delete.
- Validasi lowongan aktif, closing date, quota, dan duplicate hire.
- Hire menggunakan `join_date`, membuat employee, lalu onboarding.

File utama:

- `app/Http/Controllers/PayrollController.php`
- `app/Http/Controllers/RecruitmentController.php`
- `resources/views/payroll/`
- `resources/views/recruitment/`

Gap payroll:

- [x] Salary component detail.
- [x] BPJS.
- [x] PPh21.
- [x] Overtime.
- [x] Absence deduction.
- [x] Loan/kasbon.
- [x] Payroll details lengkap untuk komponen yang tersedia.
- [x] Payslip PDF Dompdf.

Gap recruitment:

- [x] CV upload dan validasi.
- [x] Interview scheduling dan decision.
- [x] Assessment.
- [x] Hire candidate menjadi employee dengan validasi offering/quota.
- [x] Otomatisasi onboarding setelah hire.
- [ ] Browser/UAT recruitment dengan role HR dan hiring manager.

### 3.6 Modul HR lainnya

`app/Http/Controllers/HrModuleController.php` dan
`resources/views/hr-modules/` menyediakan listing plus workflow domain untuk:

- onboarding
- offboarding
- performance
- training
- assets
- business trips
- reimbursements
- announcements

Business rule yang sudah diselesaikan:

- Performance: assignment KPI ke employee, actual-to-score calculation,
  reviewer score, status review, dan detail reviewer.
- Training: pendaftaran peserta, quota/duplicate validation, status peserta,
  dan attendance per tanggal.
- Asset: assignment/return, status availability, maintenance start/completion.
- Business trip: approval-backed creation, expense budget validation, dan
  settlement setelah trip approved/completed.
- Reimbursement: category/approval flow dan payment transition.
- Announcement: target audience, publish lifecycle, dan read tracking.
- Setiap mutation memiliki permission middleware dan audit log.
- notifications
- audit logs
- system settings

`ReportController` sudah menyediakan dashboard agregasi dasar untuk employee,
active employee, open vacancies, pending leave, dan pending reimbursement.

Status modul-modul ini: **route dan CRUD dasar tersedia, business rule legacy
belum lengkap**.

### 3.7 Seeder

Seeder utama sekarang berada di:

- `database/seeders/DatabaseSeeder.php`

Seeder bersifat idempotent dan sudah berhasil dijalankan dua kali. Seeder
mencakup:

- company, branch, department, job level, position, work location
- roles, permissions, role permissions
- admin employee dan user
- leave types dan permission types
- salary components
- shifts dan work schedules
- shift assignment
- holidays
- approval workflows dan approval steps
- asset categories
- reimbursement categories
- system settings

Akun awal:

```text
username: admin
password: Admin@123
```

Password awal wajib diganti setelah login pertama. Jangan memakai password
tersebut untuk deployment production.

### 3.8 RBAC dan dependency security

RBAC yang sudah dibuat:

- `app/Models/Permission.php`
- `Role::permissions()`
- `User::hasPermission()`
- `app/Http/Middleware/RequirePermission.php`
- alias middleware `permission` di `bootstrap/app.php`

Permission middleware saat ini sudah diterapkan pada:

- payroll view/create/generate/approve
- reports
- audit logs
- system settings

Dependency yang sudah diperbarui:

- `dompdf/dompdf` -> `3.1.6`
- `phpoffice/phpspreadsheet` -> `5.9.0`

Hasil terakhir:

```text
composer audit: No security vulnerability advisories found
```

## 4. Validasi terakhir yang sudah berhasil

Jalankan dari folder `hris-laravel`:

```powershell
php artisan test --no-coverage
vendor\bin\pint --test
php artisan view:cache
php artisan config:cache
php artisan route:list --no-ansi
composer audit --no-interaction
php artisan db:seed --force
```

Hasil terakhir:

- PHPUnit: 2 tests passed, 3 assertions.
- Pint: passed.
- Blade cache: passed.
- Config cache: passed.
- Routes: 69 routes.
- Composer audit: bersih.
- Seeder: berhasil dan idempotent.

Data seed yang terverifikasi:

| Tabel | Jumlah |
|---|---:|
| users | 1 |
| employees | 1 |
| roles | 8 |
| permissions | 84 |
| role_permissions | 316 |
| user_roles | 1 |
| leave_types | 8 |
| salary_components | 16 |
| shifts | 2 |
| work_schedules | 1 |
| work_schedule_days | 7 |
| shift_assignments | 1 |
| holidays | 4 |
| approval_workflows | 5 |
| approval_steps | 12 |
| system_settings | 10 |

## 5. Pekerjaan yang masih harus dilakukan

Prioritas dikerjakan berurutan.

### P0 - Validasi aplikasi dengan browser dan role nyata

- Jalankan server Laravel.
- Login sebagai `admin`.
- Uji dashboard, employee, organization, attendance, leave, payroll,
  recruitment, dan semua modul HR.
- Pastikan permission 403 untuk role yang tidak memiliki akses.
- Buat akun uji dengan role employee, manager, finance, dan auditor.
- Uji create, update, approval, rejection, cancel, dan logout.

### P1 - Approval engine penuh

Implementasikan service terpusat untuk:

- `approval_workflows`
- `approval_steps`
- `approval_requests`
- approval history/detail jika tersedia di schema

Integrasikan minimal pada:

- leave
- attendance correction
- reimbursement
- business trip
- offboarding
- payroll

Jangan hanya memakai perubahan status langsung dari controller. Status harus
mengikuti current step dan approver yang berwenang.

### P1 - Audit logging otomatis

`AuditService` sudah dibuat dan menyimpan:

- [x] user
- [x] action
- [x] module
- [x] reference id
- [x] before data
- [x] after data
- [x] IP dan user agent

Sudah dipasang pada login, employee, organization, attendance, leave, payroll,
recruitment, settings, notification, approval, dan HR module creation.

### P1 - Penyempurnaan payroll

- [x] Generate `payroll_details` per salary component.
- [x] Hitung income dan deduction sesuai component.
- [x] Tambahkan BPJS, PPh21, overtime, absence, loan/kasbon.
- [x] Buat PDF payslip dengan dompdf `3.1.6`.
- [x] Batasi akses payslip hanya untuk employee terkait atau role yang berwenang.

### P1 - Penyempurnaan recruitment

- Upload CV ke storage private.
- Validasi MIME, ukuran, nama file, dan authorization.
- Interview dan assessment.
- Candidate hire transaction.
- Buat employee dan onboarding secara atomik setelah hire.

### P2 - Penyempurnaan modul HR

- Onboarding task toggle dan progress calculation.
- Offboarding approval stages, exit interview, clearance.
- Performance KPI, review, scoring, dan period close.
- Training participant, attendance, certification, skill matrix.
- Asset category, assignment, return, maintenance.
- Business trip approval, expense, settlement.
- Reimbursement category, approval, payment.
- Announcement targeting dan read tracking.
- Notification cleanup dan bulk read.
- Settings RBAC dan company settings.

### P2 - Reports dan export

- Report employee dan attendance.
- Report leave dan payroll.
- Report recruitment dan training.
- Filter periode, company, branch, department, dan employee.
- Export CSV, Excel, dan PDF.
- Validasi authorization pada setiap export.

### P2 - Scheduler dan cron

Pindahkan cron legacy ke Laravel Scheduler/Commands untuk:

- contract reminder
- document expiry
- birthday notification
- training reminder
- leave balance reset
- notification cleanup
- approval reminder

Tambahkan scheduler tests dan dokumentasi cron production.

### P3 - Production readiness

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- Ganti `APP_KEY` dan password admin.
- Storage private untuk dokumen sensitif.
- Backup database dan upload.
- Queue worker bila notifikasi sudah asynchronous.
- HTTPS, session cookie secure, dan trusted proxy.
- Logging, monitoring, dan error notification.
- Review rate limit login.
- Jalankan full browser/UAT test.

## 6. File dan command penting untuk lanjutan

File pusat:

- `routes/web.php`
- `app/Http/Controllers/HrModuleController.php`
- `app/Http/Controllers/PayrollController.php`
- `app/Http/Controllers/RecruitmentController.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/LeaveController.php`
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Http/Middleware/RequirePermission.php`
- `database/seeders/DatabaseSeeder.php`
- `database/legacy-schema.sql`
- `.env`

Command setup:

```powershell
Set-Location hris-laravel
composer install
php artisan db:seed --force
php artisan serve
```

Command validasi:

```powershell
php artisan test --no-coverage
vendor\bin\pint --test
php artisan view:cache
php artisan config:cache
php artisan route:list --no-ansi
composer audit --no-interaction
```

## 7. Catatan database

- Jangan menjalankan `php artisan migrate:fresh` pada database `hris`.
- Database tersebut berisi schema legacy yang dipakai aplikasi.
- Seeder aman dijalankan ulang, tetapi tetap backup database sebelum mengubah
  data produksi.
- Saat ini database lokal sudah terhubung dan berisi data seed contoh, bukan
  data produksi.
- Jika memakai database baru, import `database/legacy-schema.sql` terlebih
  dahulu.

## 8. Riwayat task

Task migrasi yang sudah selesai:

- fondasi Laravel, auth, dashboard
- employee dan organization
- attendance dan leave
- payroll dan recruitment
- remaining HR modules
- dependency hardening
- route RBAC dasar
- Laravel legacy seeder

Progress tambahan terakhir:

- [x] Permission middleware diperluas ke employee, organization, attendance,
  leave, recruitment, modul HR, payroll, reports, audit, settings, dan
  notifications.
- [x] Attendance memakai shift assignment, grace period, late status, dan
  validasi radius work location jika koordinat dikirim.
- [x] Leave membuat saldo tahun berjalan bila belum ada, memvalidasi saldo,
  menambah `used_days` saat approve, dan mengembalikan saldo saat cancel.
- [x] Payroll membuat `payroll_details` dasar dan menyediakan endpoint PDF
  payslip dengan pembatasan akses.
- [x] Recruitment menerima CV private, mencatat interview dan assessment,
  menyediakan detail kandidat, lifecycle vacancy, validasi quota/date, serta
  membuat employee dan onboarding saat kandidat tahap `offering` di-hire.
- [x] Validasi terakhir: 2 test lulus, Pint lulus, Blade cache lulus, seeder
  berhasil, 68 route terkompilasi, dan Composer audit bersih.

Progress lanjutan:

- [x] `ApprovalService` dibuat untuk memulai approval request, memeriksa role,
  supervisor/manager, mencatat history, memproses multi-step approval, dan
  menutup request saat final decision.
- [x] Pengajuan leave sekarang membuat approval request dan hanya memperbarui
  status/saldo ketika approval final selesai.
- [x] Offboarding, business trip, dan reimbursement generic store sekarang
  membuat approval request sesuai workflow legacy.
- [x] Payroll generate sekarang membaca employee salary components, overtime
  approved, dan pending payroll deductions lalu menulis detail payroll.
- [x] Validasi ulang berhasil: tests 2 passed, Pint passed, seeder passed,
  cache passed, Composer audit bersih.

Audit logging finalization:

- [x] `app/Services/AuditService.php` dibuat dengan before/after JSON.
- [x] Login, employee, organization, attendance, leave, payroll, recruitment,
  settings, notifications, HR creation, dan approval dicatat.
- [x] Tabel `audit_logs` tersedia di MySQL.
- [x] Validasi akhir: tests 2 passed, Pint passed, cache passed, 69 routes,
  dan Composer audit bersih.

Attendance, leave, dan HR finalization:

- [x] Attendance correction menghitung ulang `work_minutes` saat approved.
- [x] Leave menyimpan snapshot step pada `leave_approvals` dan memproses
  approval multi-step melalui `ApprovalService`.
- [x] Performance review menyimpan score per peran dan detail reviewer.
- [x] Training participant dapat didaftarkan dan statusnya diperbarui.
- [x] Asset dapat ditugaskan dan dikembalikan dengan perubahan status asset.
- [x] Business trip expense dapat dicatat.
- [x] Reimbursement dapat ditandai paid setelah verifikasi.
- [x] Announcement dapat dipublikasikan.
- [x] Seluruh operasi baru menggunakan permission middleware dan AuditService.
- [x] Validasi terakhir: tests 5 passed, Pint passed, seeder passed, schedule
  terdaftar, reports export route terdaftar, route/config/view cache passed.

KPI MVP:

- [x] Blueprint disesuaikan dengan Laravel 13, permission custom, dan schema
  legacy sebelum implementasi.
- [x] Dashboard KPI dan daftar assignment.
- [x] Pembuatan periode KPI.
- [x] Master indikator dengan perspektif `FIN`, `CUS`, `IBP`, `LGR`, dan `SHR`.
- [x] Assignment KPI ke employee dengan validasi bobot maksimum 100%.
- [x] Input actual dan score capped 120% untuk indikator maximize.
- [x] Audit log dan permission `performance.*`.
- [x] Seeder idempotent membuat periode, indikator, dan assignment demo.
- [~] Template jabatan, cascading KPI, approval khusus KPI, kalibrasi 9-box,
  evidence upload, dan dashboard analitik lintas periode masih fase lanjutan.

Dummy data Bank Syariah:

- [x] Seeder lokal membuat struktur perusahaan, cabang Jakarta/Bandung/Surabaya,
  departemen, divisi, section, job level, job grade, jabatan, cost center
  terkait, dan lokasi kantor.
- [x] Seeder lokal membuat user demo HR, Finance, Supervisor, Teller, dan
  Compliance dengan role serta permission yang sesuai.
- [x] Akun demo menggunakan password `Demo@12345`; akun administrator tetap
  `admin` dengan password `Admin@123`.
- [x] Seeder bersifat idempotent dan hanya menjalankan dataset dummy ketika
  `APP_ENV=local`. Pada `APP_ENV=production`, hanya fondasi organisasi minimum,
  role/permission, dan user administrator yang dibuat.
- [x] Dokumentasi role dan matriks hak akses tersedia di
  `USER-ACCESS-MATRIX.md`.

Onboarding dan offboarding workflow:

- [x] Onboarding task dapat dibuat.
- [x] Onboarding task dapat ditandai selesai/belum selesai.
- [x] Progress onboarding dihitung otomatis dari task.
- [x] Status onboarding otomatis menjadi `completed` saat progress 100%.
- [x] Exit interview dapat dicatat.
- [x] Clearance item dapat dibuat dan ditandai cleared/uncleared.
- [x] Semua operasi onboarding/offboarding dicatat melalui AuditService.
- [x] Validasi terakhir: 2 test passed, Pint passed, seeder passed, 74 routes,
  cache passed, dan Composer audit bersih.

Payroll parity finalization:

- [x] Salary component fixed dan percentage calculation didukung.
- [x] BPJS employee deduction memakai system settings dan basic/gross base.
- [x] PPh21 bulanan progressive memakai PTKP status employee atau default
  `TK/0`.
- [x] Approved overtime masuk ke gross salary dan payroll detail.
- [x] Pending payroll deductions diproses satu kali ke payroll period.
- [x] Payslip dibuat otomatis ketika payroll period berubah menjadi `paid`.
- [x] Endpoint payslip HTML dan PDF memakai detail payroll serta authorization.
- [x] Smoke test PPh21 menghasilkan nilai positif untuk sample taxable income.
- [x] Absence deduction dihitung dari hari absent, hari kerja periode, dan
  basic salary.
- [x] Test suite, Pint, PHP syntax check, database seeder, cache, route list,
  dan MySQL settings verification berhasil.

Checkpoint berikutnya sebaiknya dibuat setelah P0 browser/UAT selesai, lalu
dilanjutkan ke reports/export, scheduler, dan production hardening.
