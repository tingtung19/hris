# Blueprint & Prompt Desain — Fitur KPI pada HRIS Laravel
**Klien:** Perusahaan Perbankan Syariah
**Modul:** Performance Management / KPI (Key Performance Indicator)
**Basis:** Laravel 13 + MySQL legacy (menempel pada HRIS existing)
**Versi dokumen:** 1.0 — siap dipakai sebagai spesifikasi internal maupun sebagai prompt untuk AI coding assistant.

---

## 0. Cara Memakai Dokumen Ini

### Adaptasi terhadap aplikasi HRIS saat ini

Blueprint awal mengasumsikan Laravel 10/11, Spatie Permission, dan tabel KPI
baru. Aplikasi aktual memakai Laravel 13, Blade + Bootstrap/PolluxUI, Query
Builder pada schema legacy, serta permission custom melalui tabel `roles`,
`permissions`, `user_roles`, dan `role_permissions`. Karena itu implementasi
MVP menggunakan tabel performance legacy yang sudah tersedia:

| Kebutuhan blueprint | Tabel aktual |
|---|---|
| KPI period | `performance_periods` |
| KPI perspective/indicator | `kpis` (perspective disimpan pada `description` untuk MVP) |
| KPI assignment | `employee_kpis` |
| Review | `performance_reviews` dan `performance_details` |

Tabel `kpi_*` baru, Livewire, Tailwind, Spatie Permission, dan migration baru
tidak digunakan pada fase MVP agar tidak membuat schema paralel. Struktur
`kpi_*` pada bagian berikut adalah target fase lanjutan, bukan instruksi untuk
mengubah database legacy saat ini.

Dokumen ini punya tiga fungsi sekaligus:

1. **Blueprint teknis** — dibaca developer untuk membangun modul.
2. **Bahan presentasi** — bagian 1–3 dan 12 bisa langsung dijadikan slide untuk klien.
3. **Prompt AI** — bagian 13 berisi prompt siap tempel untuk Claude Code / Cursor / Copilot agar kode digenerate konsisten dengan blueprint ini.

**Asumsi terhadap HRIS existing** (sesuaikan bila nama tabel berbeda):

| Yang diasumsikan sudah ada | Nama tabel asumsi |
|---|---|
| Data karyawan | `employees` (kolom legacy: `id`, `employee_number`, `first_name`, `last_name`, `position_id`, `department_id`, `join_date`, `employment_status`) |
| Struktur organisasi | `departments`, `positions` |
| User & autentikasi | `users` (relasi ke `employees`) |
| Role/permission | Custom legacy `roles`, `permissions`, `user_roles`, `role_permissions` |
| Kehadiran | `attendances` (opsional, untuk auto-fill KPI kedisiplinan) |

> Bila salah satu tidak ada, modul KPI tetap jalan — cukup tunjuk foreign key ke tabel padanannya.

---

## 1. Tujuan Bisnis

| Tujuan | Ukuran keberhasilan |
|---|---|
| Menggantikan penilaian kinerja manual (Excel) | 100% unit kerja input via sistem pada periode berjalan |
| Menyelaraskan sasaran individu dengan RKAP bank | Setiap KPI individu punya parent KPI departemen |
| Menyediakan dasar objektif untuk remunerasi & promosi | Skor akhir terverifikasi dan ter-audit trail |
| Memenuhi aspek kepatuhan syariah dalam penilaian kinerja | Perspektif "Kepatuhan & Nilai Syariah" wajib ada di setiap jabatan |
| Mempercepat siklus penilaian | Dari ±3 minggu menjadi ≤ 5 hari kerja |

---

## 2. Kerangka Metodologi

Menggunakan **Balanced Scorecard (BSC) versi perbankan syariah**, dengan 5 perspektif:

| Kode | Perspektif | Bobot default | Contoh isi |
|---|---|---|---|
| `FIN` | Keuangan / Financial | 35% | Pertumbuhan DPK, realisasi pembiayaan, fee based income, efisiensi biaya |
| `CUS` | Nasabah / Customer | 20% | Jumlah rekening baru, CSI, SLA layanan, retensi nasabah |
| `IBP` | Proses Bisnis Internal | 20% | NPF, akurasi transaksi, TAT pembiayaan, penyelesaian temuan audit |
| `LGR` | Pembelajaran & Pertumbuhan | 10% | Jam pelatihan, sertifikasi (BSMR/Sertifikasi Syariah), knowledge sharing |
| `SHR` | **Kepatuhan & Nilai Syariah** | 15% | Nihil temuan DPS, akad sesuai fatwa DSN-MUI, partisipasi ZIS, literasi syariah nasabah |

Total bobot perspektif per jabatan **wajib = 100%**. Bobot bisa di-override per jabatan (mis. Compliance Officer: `SHR` 30%, `FIN` 15%).

### Struktur cascading

```
Sasaran Bank (RKAP)
  └── KPI Divisi / Departemen
        └── KPI Unit / Cabang
              └── KPI Individu (karyawan)
```

Setiap `kpi_assignment` individu boleh menunjuk `parent_assignment_id` ke KPI atasan → tergambar sebagai *strategy map* di dashboard.

---

## 3. Alur Proses (Business Flow)

```
[1] HR buka Periode  →  [2] Atasan susun KPI bawahan  →  [3] Karyawan setujui kontrak KPI
        ↓                                                            ↓
[6] Finalisasi & publish  ←  [5] Kalibrasi HR/Komite  ←  [4] Input realisasi + review atasan
```

**Status periode:** `draft → planning → running → assessment → calibration → finalized → closed`

**Status assignment karyawan:** `draft → submitted → agreed → in_progress → self_assessed → reviewed → calibrated → final`

**Aturan kunci:**
- Nilai tidak dapat diubah setelah status `final` — hanya bisa dibuka lewat *reopen request* yang tercatat di audit log.
- Karyawan yang masuk < 3 bulan sebelum akhir periode otomatis `excluded` (bisa diatur di setting).
- Self-assessment bersifat opsional per konfigurasi periode (`require_self_assessment`).

---

## 4. Skema Database

### 4.1 Daftar tabel target fase lanjutan

| Tabel | Fungsi |
|---|---|
| `kpi_periods` | Periode penilaian (semester/tahunan/kuartalan) |
| `kpi_perspectives` | Master perspektif BSC |
| `kpi_indicators` | Master/library indikator KPI |
| `kpi_position_templates` | Template paket KPI per jabatan |
| `kpi_position_template_items` | Detail indikator + bobot dalam template |
| `kpi_assignments` | Kontrak KPI seorang karyawan pada satu periode |
| `kpi_assignment_items` | Baris indikator beserta target & realisasi |
| `kpi_progress_logs` | Riwayat update realisasi (bulanan/insidentil) |
| `kpi_reviews` | Catatan review atasan / kalibrasi |
| `kpi_scores` | Hasil skor akhir per perspektif & total |
| `kpi_grades` | Master konversi skor → predikat |
| `kpi_approvals` | Jejak persetujuan berjenjang |

### 4.2 Migration inti

```php
// database/migrations/2025_01_01_000001_create_kpi_periods_table.php
Schema::create('kpi_periods', function (Blueprint $table) {
    $table->id();
    $table->string('code', 30)->unique();            // 2025-S1
    $table->string('name');                          // Semester I 2025
    $table->enum('type', ['monthly','quarterly','semester','annual'])->default('semester');
    $table->date('start_date');
    $table->date('end_date');
    $table->date('assessment_open_at')->nullable();
    $table->date('assessment_close_at')->nullable();
    $table->enum('status', ['draft','planning','running','assessment','calibration','finalized','closed'])
          ->default('draft');
    $table->boolean('require_self_assessment')->default(true);
    $table->unsignedTinyInteger('min_months_service')->default(3);
    $table->decimal('max_achievement_cap', 5, 2)->default(120.00); // capping %
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

```php
// create_kpi_perspectives_table
Schema::create('kpi_perspectives', function (Blueprint $table) {
    $table->id();
    $table->string('code', 10)->unique();      // FIN, CUS, IBP, LGR, SHR
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('default_weight', 5, 2);   // 35.00
    $table->string('color', 20)->default('#0ea5e9');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

```php
// create_kpi_indicators_table  (library indikator)
Schema::create('kpi_indicators', function (Blueprint $table) {
    $table->id();
    $table->string('code', 30)->unique();               // FIN-001
    $table->foreignId('kpi_perspective_id')->constrained();
    $table->string('name');                             // Pertumbuhan Dana Pihak Ketiga
    $table->text('definition')->nullable();             // definisi operasional
    $table->text('formula')->nullable();                // rumus perhitungan
    $table->string('unit', 30);                         // Rp Juta, %, Nasabah, Hari, Skor
    $table->enum('polarity', ['maximize','minimize','stabilize'])->default('maximize');
    $table->enum('data_source', ['manual','core_banking','attendance','ticketing','survey'])
          ->default('manual');
    $table->enum('frequency', ['monthly','quarterly','semester','annual'])->default('monthly');
    $table->boolean('is_mandatory')->default(false);    // wajib untuk semua jabatan
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

```php
// create_kpi_position_templates_table
Schema::create('kpi_position_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('position_id')->constrained();
    $table->string('name');
    $table->year('effective_year');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('kpi_position_template_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_position_template_id')->constrained()->cascadeOnDelete();
    $table->foreignId('kpi_indicator_id')->constrained();
    $table->decimal('weight', 5, 2);            // bobot dalam %
    $table->string('default_target')->nullable();
    $table->timestamps();
});
```

```php
// create_kpi_assignments_table
Schema::create('kpi_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_period_id')->constrained()->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained();
    $table->foreignId('reviewer_id')->nullable()->constrained('employees'); // atasan langsung
    $table->foreignId('parent_assignment_id')->nullable()
          ->constrained('kpi_assignments')->nullOnDelete();                 // cascading
    $table->enum('status', [
        'draft','submitted','agreed','in_progress',
        'self_assessed','reviewed','calibrated','final','excluded'
    ])->default('draft');
    $table->decimal('final_score', 6, 2)->nullable();
    $table->foreignId('kpi_grade_id')->nullable()->constrained();
    $table->text('employee_note')->nullable();
    $table->text('reviewer_note')->nullable();
    $table->timestamp('agreed_at')->nullable();
    $table->timestamp('finalized_at')->nullable();
    $table->timestamps();

    $table->unique(['kpi_period_id','employee_id']);
});
```

```php
// create_kpi_assignment_items_table
Schema::create('kpi_assignment_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_assignment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('kpi_indicator_id')->constrained();
    $table->decimal('weight', 5, 2);                      // bobot indikator (%)
    $table->decimal('target_value', 18, 2);
    $table->decimal('threshold_value', 18, 2)->nullable(); // batas minimal dianggap tercapai
    $table->decimal('stretch_value', 18, 2)->nullable();   // target ambisius
    $table->decimal('actual_value', 18, 2)->nullable();
    $table->decimal('achievement', 6, 2)->nullable();      // % capaian (sudah di-cap)
    $table->decimal('score', 6, 2)->nullable();            // achievement x weight / 100
    $table->text('evidence_note')->nullable();
    $table->string('evidence_path')->nullable();           // lampiran bukti
    $table->timestamps();
});
```

```php
// tabel pendukung
Schema::create('kpi_progress_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_assignment_item_id')->constrained()->cascadeOnDelete();
    $table->date('log_date');
    $table->decimal('value', 18, 2);
    $table->text('note')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

Schema::create('kpi_scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_assignment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('kpi_perspective_id')->constrained();
    $table->decimal('weight', 5, 2);
    $table->decimal('achievement', 6, 2);
    $table->decimal('weighted_score', 6, 2);
    $table->timestamps();
});

Schema::create('kpi_grades', function (Blueprint $table) {
    $table->id();
    $table->string('code', 5)->unique();      // A, B, C, D, E
    $table->string('name');                   // Istimewa
    $table->decimal('min_score', 6, 2);
    $table->decimal('max_score', 6, 2);
    $table->decimal('bonus_multiplier', 5, 2)->default(1.00);
    $table->string('color', 20)->default('#22c55e');
    $table->timestamps();
});

Schema::create('kpi_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('kpi_assignment_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('level');                 // 1 = atasan, 2 = kadiv, 3 = HR
    $table->foreignId('approver_id')->constrained('employees');
    $table->enum('action', ['pending','approved','rejected','revised'])->default('pending');
    $table->text('comment')->nullable();
    $table->timestamp('acted_at')->nullable();
    $table->timestamps();
});
```

---

## 5. Formula Perhitungan Skor

### 5.1 Capaian per indikator (`achievement`)

| Polaritas | Rumus | Contoh |
|---|---|---|
| `maximize` (makin besar makin baik) | `actual / target × 100` | DPK, jumlah nasabah |
| `minimize` (makin kecil makin baik) | `(2 − actual / target) × 100` | NPF, komplain, TAT |
| `stabilize` (mendekati target) | `(1 − \|actual − target\| / target) × 100` | FDR di rentang 80–90% |

Hasil dibatasi `0% ≤ achievement ≤ max_achievement_cap` (default 120%).

### 5.2 Skor indikator & total

```
score_indikator   = achievement × (weight / 100)
skor_perspektif   = Σ score_indikator dalam perspektif tersebut
skor_akhir        = Σ skor_perspektif
```

Karena `Σ weight` seluruh indikator = 100, maka skor akhir berada di rentang 0–120.

### 5.3 Implementasi service

```php
<?php
// app/Services/Kpi/KpiScoreCalculator.php
namespace App\Services\Kpi;

use App\Models\KpiAssignment;
use App\Models\KpiAssignmentItem;
use Illuminate\Support\Facades\DB;

class KpiScoreCalculator
{
    public function calculateItem(KpiAssignmentItem $item, float $cap = 120.00): float
    {
        $target = (float) $item->target_value;
        $actual = (float) ($item->actual_value ?? 0);

        if ($target == 0.0) {
            return 0.00;
        }

        $achievement = match ($item->indicator->polarity) {
            'maximize'  => ($actual / $target) * 100,
            'minimize'  => (2 - ($actual / $target)) * 100,
            'stabilize' => (1 - abs($actual - $target) / $target) * 100,
        };

        return round(max(0, min($achievement, $cap)), 2);
    }

    public function recalculate(KpiAssignment $assignment): KpiAssignment
    {
        $cap = (float) $assignment->period->max_achievement_cap;

        DB::transaction(function () use ($assignment, $cap) {
            $assignment->loadMissing('items.indicator.perspective');

            foreach ($assignment->items as $item) {
                $achievement = $this->calculateItem($item, $cap);
                $item->forceFill([
                    'achievement' => $achievement,
                    'score'       => round($achievement * $item->weight / 100, 2),
                ])->save();
            }

            $assignment->scores()->delete();

            $assignment->items
                ->groupBy(fn ($i) => $i->indicator->kpi_perspective_id)
                ->each(function ($items, $perspectiveId) use ($assignment) {
                    $weight = $items->sum('weight');
                    $score  = $items->sum('score');

                    $assignment->scores()->create([
                        'kpi_perspective_id' => $perspectiveId,
                        'weight'             => $weight,
                        'achievement'        => $weight > 0 ? round($score / $weight * 100, 2) : 0,
                        'weighted_score'     => round($score, 2),
                    ]);
                });

            $final = round($assignment->items->sum('score'), 2);

            $assignment->forceFill([
                'final_score'  => $final,
                'kpi_grade_id' => \App\Models\KpiGrade::whereRaw('? BETWEEN min_score AND max_score', [$final])
                                    ->value('id'),
            ])->save();
        });

        return $assignment->refresh();
    }
}
```

---

## 6. Model & Relasi

```php
// app/Models/KpiAssignment.php  (ringkas)
class KpiAssignment extends Model
{
    protected $guarded = [];
    protected $casts = ['agreed_at' => 'datetime', 'finalized_at' => 'datetime'];

    public function period()   { return $this->belongsTo(KpiPeriod::class, 'kpi_period_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function reviewer() { return $this->belongsTo(Employee::class, 'reviewer_id'); }
    public function parent()   { return $this->belongsTo(self::class, 'parent_assignment_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_assignment_id'); }
    public function items()    { return $this->hasMany(KpiAssignmentItem::class); }
    public function scores()   { return $this->hasMany(KpiScore::class); }
    public function grade()    { return $this->belongsTo(KpiGrade::class, 'kpi_grade_id'); }
    public function approvals(){ return $this->hasMany(KpiApproval::class); }

    public function scopeForPeriod($q, $periodId) { return $q->where('kpi_period_id', $periodId); }
    public function getTotalWeightAttribute() { return $this->items->sum('weight'); }
}
```

**Validasi wajib:** `FormRequest` pada penyimpanan kontrak KPI harus menolak bila `Σ weight != 100`.

```php
// app/Http/Requests/StoreKpiAssignmentRequest.php
public function withValidator($validator)
{
    $validator->after(function ($v) {
        $total = collect($this->input('items', []))->sum('weight');
        if (round($total, 2) != 100.00) {
            $v->errors()->add('items', "Total bobot harus 100%. Saat ini: {$total}%.");
        }
    });
}
```

---

## 7. Role & Hak Akses

### Hak akses pada aplikasi aktual (MVP)

Gunakan permission custom berikut:

- `performance.view`: dashboard, daftar periode, KPI, dan assignment yang
  boleh dilihat.
- `performance.create`: membuat periode, indikator, dan assignment.
- `performance.update`: input actual dan perubahan assignment yang belum final.
- `performance.review`: review bawahan dan finalisasi review.
- `audit.view`: melihat audit log.

Mapping role aplikasi aktual: `hr-administrator` sebagai HR, `manager` atau
`supervisor` sebagai reviewer, `employee` sebagai pemilik KPI, dan
`super-administrator` sebagai akses penuh. Pembatasan baris pada MVP
menggunakan `employee_id` dan `reviewer_id` yang tersedia pada schema legacy.

| Role | Kewenangan |
|---|---|
| `hr-admin` | Kelola periode, master indikator, template jabatan, kalibrasi, finalisasi, semua laporan |
| `atasan` (manager/kepala unit) | Susun & setujui KPI bawahan, input review, lihat dashboard timnya |
| `karyawan` | Lihat kontrak KPI sendiri, setujui, input realisasi + bukti, self-assessment |
| `direksi` | Read-only seluruh organisasi + dashboard eksekutif |
| `auditor-dps` | Read-only indikator berperspektif `SHR` dan audit log |

Gunakan **Policy** untuk pembatasan baris:

```php
// app/Policies/KpiAssignmentPolicy.php
public function view(User $user, KpiAssignment $a): bool
{
    return $user->hasAnyRole(['hr-admin','direksi'])
        || $a->employee_id === $user->employee_id
        || $a->reviewer_id === $user->employee_id
        || $user->employee?->isAncestorOf($a->employee);
}
```

---

## 8. Route & Endpoint

```php
// routes/web.php
Route::prefix('kpi')->name('kpi.')->middleware(['auth'])->group(function () {
    Route::resource('periods', KpiPeriodController::class)->middleware('role:hr-admin');
    Route::resource('indicators', KpiIndicatorController::class)->middleware('role:hr-admin');
    Route::resource('templates', KpiPositionTemplateController::class)->middleware('role:hr-admin');

    Route::get('my-kpi', [MyKpiController::class, 'index'])->name('my.index');
    Route::post('assignments/{assignment}/agree',   [KpiAssignmentController::class, 'agree']);
    Route::post('assignments/{assignment}/submit',  [KpiAssignmentController::class, 'submitSelfAssessment']);
    Route::post('items/{item}/progress',            [KpiProgressController::class, 'store']);

    Route::get('team',  [KpiTeamController::class, 'index'])->name('team.index');
    Route::post('assignments/{assignment}/review',  [KpiReviewController::class, 'store']);

    Route::get('calibration', [KpiCalibrationController::class, 'index'])->middleware('role:hr-admin');
    Route::post('periods/{period}/generate',  [KpiPeriodController::class, 'generateAssignments']);
    Route::post('periods/{period}/finalize',  [KpiPeriodController::class, 'finalize']);

    Route::get('dashboard', [KpiDashboardController::class, 'index'])->name('dashboard');
    Route::get('reports/export/{period}', [KpiReportController::class, 'export']);
});
```

Route pada MVP mengikuti struktur tersebut secara konseptual tetapi memakai
`KpiController` tunggal dan route permission custom Laravel HRIS.

**API (opsional, untuk mobile HRIS):** mirror endpoint di atas di `routes/api.php` dengan Sanctum + API Resource.

---

## 9. Halaman UI (minimal untuk demo)

| # | Halaman | Isi utama |
|---|---|---|
| 1 | **Dashboard KPI** | Kartu: rata-rata skor bank, distribusi grade (donut), top/bottom 5 unit, tren per periode (line), radar 5 perspektif |
| 2 | **Master Indikator** | Tabel + filter perspektif, tombol import Excel |
| 3 | **Template per Jabatan** | Drag indikator, atur bobot, indikator total bobot real-time (merah bila ≠100%) |
| 4 | **Periode** | Wizard: buat periode → generate assignment massal → monitor progress pengisian (progress bar per departemen) |
| 5 | **Kontrak KPI Saya** | Tabel indikator, target, realisasi (input), % capaian berwarna, tombol unggah bukti |
| 6 | **Penilaian Tim** | List bawahan + status, tombol review, komparasi skor tim |
| 7 | **Kalibrasi** | Matriks 9-box (kinerja × potensi), penyesuaian skor dengan alasan wajib |
| 8 | **Cetak/Export** | PDF form penilaian bertanda tangan + Excel rekap |

**Saran stack UI untuk demo cepat:** Blade + Livewire 3 + Tailwind + Alpine, grafik memakai ApexCharts. Bila HRIS existing sudah pakai Vue/Inertia, ikuti stack tersebut.

---

## 10. Seeder untuk Demo

Struktur file:

```
database/seeders/
├── KpiDemoSeeder.php          ← pemanggil utama
├── KpiPerspectiveSeeder.php
├── KpiGradeSeeder.php
├── KpiIndicatorSeeder.php
├── KpiPeriodSeeder.php
├── KpiPositionTemplateSeeder.php
└── KpiAssignmentDemoSeeder.php
```

### 10.1 KpiPerspectiveSeeder

```php
<?php
namespace Database\Seeders;

use App\Models\KpiPerspective;
use Illuminate\Database\Seeder;

class KpiPerspectiveSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code'=>'FIN','name'=>'Keuangan','default_weight'=>35,'color'=>'#0ea5e9','sort_order'=>1,
             'description'=>'Pencapaian target keuangan: DPK, pembiayaan, fee based income, efisiensi biaya.'],
            ['code'=>'CUS','name'=>'Nasabah','default_weight'=>20,'color'=>'#22c55e','sort_order'=>2,
             'description'=>'Pertumbuhan, kepuasan, dan retensi nasabah.'],
            ['code'=>'IBP','name'=>'Proses Bisnis Internal','default_weight'=>20,'color'=>'#f59e0b','sort_order'=>3,
             'description'=>'Kualitas proses, kecepatan layanan, pengendalian risiko dan kualitas pembiayaan.'],
            ['code'=>'LGR','name'=>'Pembelajaran & Pertumbuhan','default_weight'=>10,'color'=>'#8b5cf6','sort_order'=>4,
             'description'=>'Pengembangan kompetensi, sertifikasi, dan budaya berbagi pengetahuan.'],
            ['code'=>'SHR','name'=>'Kepatuhan & Nilai Syariah','default_weight'=>15,'color'=>'#14b8a6','sort_order'=>5,
             'description'=>'Kepatuhan terhadap fatwa DSN-MUI, ketentuan DPS/OJK, serta internalisasi nilai syariah.'],
        ];

        foreach ($rows as $row) {
            KpiPerspective::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
```

### 10.2 KpiGradeSeeder

```php
<?php
namespace Database\Seeders;

use App\Models\KpiGrade;
use Illuminate\Database\Seeder;

class KpiGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['code'=>'A','name'=>'Istimewa',        'min_score'=>110.01,'max_score'=>120.00,'bonus_multiplier'=>2.00,'color'=>'#15803d'],
            ['code'=>'B','name'=>'Sangat Baik',     'min_score'=>100.01,'max_score'=>110.00,'bonus_multiplier'=>1.50,'color'=>'#22c55e'],
            ['code'=>'C','name'=>'Baik',            'min_score'=> 90.01,'max_score'=>100.00,'bonus_multiplier'=>1.00,'color'=>'#eab308'],
            ['code'=>'D','name'=>'Cukup',           'min_score'=> 75.01,'max_score'=> 90.00,'bonus_multiplier'=>0.50,'color'=>'#f97316'],
            ['code'=>'E','name'=>'Perlu Perbaikan', 'min_score'=>  0.00,'max_score'=> 75.00,'bonus_multiplier'=>0.00,'color'=>'#dc2626'],
        ];

        foreach ($grades as $g) {
            KpiGrade::updateOrCreate(['code' => $g['code']], $g);
        }
    }
}
```

### 10.3 KpiIndicatorSeeder — library indikator bank syariah

```php
<?php
namespace Database\Seeders;

use App\Models\KpiIndicator;
use App\Models\KpiPerspective;
use Illuminate\Database\Seeder;

class KpiIndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $p = KpiPerspective::pluck('id', 'code');

        $indicators = [
            // ---------- FINANSIAL ----------
            ['FIN-001','FIN','Pertumbuhan Dana Pihak Ketiga (DPK)','Rp Juta','maximize','monthly',
             'Kenaikan saldo DPK (tabungan, giro, deposito) dibanding awal periode.',
             '(Saldo DPK akhir - Saldo DPK awal)'],
            ['FIN-002','FIN','Realisasi Pembiayaan','Rp Juta','maximize','monthly',
             'Total pencairan pembiayaan (murabahah, musyarakah, mudharabah, ijarah) pada periode berjalan.',
             'Σ nominal akad cair'],
            ['FIN-003','FIN','Pertumbuhan CASA','%','maximize','quarterly',
             'Rasio pertumbuhan dana murah (giro + tabungan) terhadap total DPK.',
             '(CASA akhir / DPK akhir) x 100'],
            ['FIN-004','FIN','Fee Based Income','Rp Juta','maximize','monthly',
             'Pendapatan berbasis ujrah/jasa: transfer, payroll, bancassurance syariah, remitansi.',
             'Σ pendapatan ujrah & jasa'],
            ['FIN-005','FIN','Efisiensi Biaya Operasional (BOPO Unit)','%','minimize','quarterly',
             'Rasio biaya operasional terhadap pendapatan operasional unit.',
             '(Biaya Ops / Pendapatan Ops) x 100'],

            // ---------- NASABAH ----------
            ['CUS-001','CUS','Jumlah Rekening Baru','Rekening','maximize','monthly',
             'Rekening baru aktif yang dibuka dan tidak dormant dalam 30 hari.', 'Σ rekening baru aktif'],
            ['CUS-002','CUS','Customer Satisfaction Index','Skor','maximize','semester',
             'Hasil survei kepuasan nasabah skala 1-5.', 'Rata-rata skor survei'],
            ['CUS-003','CUS','Penyelesaian Komplain Nasabah ≤ 3 Hari','%','maximize','monthly',
             'Persentase komplain tuntas dalam SLA 3 hari kerja.',
             '(Komplain selesai ≤3 hari / total komplain) x 100'],
            ['CUS-004','CUS','Retensi Nasabah Prioritas','%','maximize','semester',
             'Persentase nasabah prioritas yang tetap aktif pada akhir periode.',
             '(Nasabah prioritas aktif akhir / awal) x 100'],

            // ---------- PROSES BISNIS INTERNAL ----------
            ['IBP-001','IBP','Non Performing Financing (NPF) Gross','%','minimize','monthly',
             'Rasio pembiayaan bermasalah (kol 3-5) terhadap total pembiayaan yang dikelola.',
             '(Pembiayaan kol 3-5 / total pembiayaan) x 100'],
            ['IBP-002','IBP','Turn Around Time Proses Pembiayaan','Hari','minimize','monthly',
             'Rata-rata hari kerja dari berkas lengkap sampai keputusan komite.',
             'Rata-rata (tgl keputusan - tgl berkas lengkap)'],
            ['IBP-003','IBP','Akurasi Transaksi Teller','%','maximize','monthly',
             'Persentase transaksi tanpa selisih/koreksi.',
             '((Total transaksi - transaksi selisih) / total transaksi) x 100'],
            ['IBP-004','IBP','Penyelesaian Temuan Audit Internal','%','maximize','quarterly',
             'Persentase temuan audit yang ditindaklanjuti tepat waktu.',
             '(Temuan selesai tepat waktu / total temuan) x 100'],
            ['IBP-005','IBP','Ketepatan Pelaporan ke Regulator','%','maximize','monthly',
             'Persentase laporan OJK/BI yang dikirim sebelum batas waktu.',
             '(Laporan tepat waktu / total laporan) x 100'],

            // ---------- PEMBELAJARAN & PERTUMBUHAN ----------
            ['LGR-001','LGR','Jam Pelatihan per Karyawan','Jam','maximize','semester',
             'Akumulasi jam pelatihan internal maupun eksternal.', 'Σ jam pelatihan'],
            ['LGR-002','LGR','Sertifikasi Profesi Perbankan Syariah','Sertifikat','maximize','annual',
             'Perolehan/perpanjangan sertifikasi (BSMR, Sertifikasi Syariah, AAOIFI, dsb).',
             'Σ sertifikat valid'],
            ['LGR-003','LGR','Knowledge Sharing Session','Sesi','maximize','semester',
             'Jumlah sesi berbagi pengetahuan yang dibawakan.', 'Σ sesi sebagai pemateri'],
            ['LGR-004','LGR','Tingkat Kehadiran','%','maximize','monthly',
             'Persentase kehadiran tepat waktu (otomatis dari modul absensi).',
             '(Hari hadir tepat waktu / hari kerja) x 100'],

            // ---------- KEPATUHAN & NILAI SYARIAH ----------
            ['SHR-001','SHR','Temuan Dewan Pengawas Syariah','Temuan','minimize','semester',
             'Jumlah temuan DPS atas kesesuaian akad & operasional. Target = 0 (dipakai skor threshold).',
             'Σ temuan DPS'],
            ['SHR-002','SHR','Kesesuaian Akad dengan Fatwa DSN-MUI','%','maximize','quarterly',
             'Persentase akad hasil uji petik yang sesuai fatwa dan SOP syariah.',
             '(Akad sesuai / akad diuji) x 100'],
            ['SHR-003','SHR','Penyaluran & Sosialisasi ZISWAF','Rp Juta','maximize','semester',
             'Nilai zakat, infak, sedekah, wakaf yang dihimpun/disalurkan melalui unit.',
             'Σ dana ZISWAF'],
            ['SHR-004','SHR','Literasi & Edukasi Keuangan Syariah','Kegiatan','maximize','semester',
             'Jumlah kegiatan edukasi keuangan syariah kepada masyarakat/nasabah.',
             'Σ kegiatan edukasi'],
            ['SHR-005','SHR','Kepatuhan APU-PPT & Anti Fraud','%','maximize','quarterly',
             'Persentase pemenuhan checklist APU-PPT, termasuk pelaporan transaksi mencurigakan.',
             '(Item checklist terpenuhi / total item) x 100'],
        ];

        foreach ($indicators as [$code,$persp,$name,$unit,$polarity,$freq,$definition,$formula]) {
            KpiIndicator::updateOrCreate(['code' => $code], [
                'kpi_perspective_id' => $p[$persp],
                'name'        => $name,
                'unit'        => $unit,
                'polarity'    => $polarity,
                'frequency'   => $freq,
                'definition'  => $definition,
                'formula'     => $formula,
                'data_source' => $code === 'LGR-004' ? 'attendance' : 'manual',
                'is_mandatory'=> in_array($code, ['SHR-001','LGR-004']),
                'is_active'   => true,
            ]);
        }
    }
}
```

### 10.4 KpiPeriodSeeder

```php
<?php
namespace Database\Seeders;

use App\Models\KpiPeriod;
use Illuminate\Database\Seeder;

class KpiPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            ['code'=>'2024-S2','name'=>'Semester II 2024','start_date'=>'2024-07-01','end_date'=>'2024-12-31','status'=>'closed'],
            ['code'=>'2025-S1','name'=>'Semester I 2025', 'start_date'=>'2025-01-01','end_date'=>'2025-06-30','status'=>'finalized'],
            ['code'=>'2025-S2','name'=>'Semester II 2025','start_date'=>'2025-07-01','end_date'=>'2025-12-31','status'=>'assessment'],
        ];

        foreach ($periods as $p) {
            KpiPeriod::updateOrCreate(['code' => $p['code']], array_merge($p, [
                'type' => 'semester',
                'assessment_open_at'  => date('Y-m-d', strtotime($p['end_date'].' -14 days')),
                'assessment_close_at' => date('Y-m-d', strtotime($p['end_date'].' +10 days')),
                'require_self_assessment' => true,
                'min_months_service' => 3,
                'max_achievement_cap' => 120.00,
            ]));
        }
    }
}
```

### 10.5 KpiPositionTemplateSeeder — paket KPI per jabatan

```php
<?php
namespace Database\Seeders;

use App\Models\{KpiIndicator, KpiPositionTemplate, Position};
use Illuminate\Database\Seeder;

class KpiPositionTemplateSeeder extends Seeder
{
    /**
     * Format: 'Nama Jabatan' => ['KODE-INDIKATOR' => [bobot, target default]]
     * Total bobot tiap jabatan = 100.
     */
    private array $map = [
        'Funding Officer' => [
            'FIN-001' => [30, 2500], 'FIN-003' => [10, 45], 'CUS-001' => [20, 120],
            'CUS-004' => [10, 90],   'LGR-004' => [5, 98],  'LGR-001' => [5, 20],
            'SHR-001' => [10, 0],    'SHR-004' => [10, 4],
        ],
        'Account Officer Pembiayaan' => [
            'FIN-002' => [30, 8000], 'FIN-004' => [10, 150], 'IBP-001' => [20, 2],
            'IBP-002' => [10, 7],    'CUS-001' => [5, 25],   'LGR-002' => [5, 1],
            'SHR-002' => [15, 100],
        ],
        'Teller' => [
            'IBP-003' => [30, 99.5], 'CUS-003' => [15, 95], 'CUS-002' => [10, 4.5],
            'FIN-001' => [10, 500],  'LGR-004' => [15, 98], 'LGR-001' => [5, 16],
            'SHR-001' => [5, 0],     'SHR-005' => [10, 100],
        ],
        'Customer Service' => [
            'CUS-001' => [25, 150],  'CUS-002' => [15, 4.5], 'CUS-003' => [20, 95],
            'IBP-003' => [10, 99],   'LGR-004' => [10, 98],  'SHR-004' => [10, 3],
            'SHR-005' => [10, 100],
        ],
        'Compliance Officer' => [
            'SHR-002' => [20, 100],  'SHR-005' => [20, 100], 'SHR-001' => [10, 0],
            'IBP-004' => [20, 100],  'IBP-005' => [15, 100], 'LGR-002' => [10, 1],
            'LGR-004' => [5, 98],
        ],
        'Kepala Cabang' => [
            'FIN-001' => [20, 25000],'FIN-002' => [20, 40000],'FIN-005' => [10, 78],
            'IBP-001' => [15, 2.5],  'CUS-002' => [10, 4.5],  'LGR-003' => [5, 4],
            'SHR-001' => [10, 0],    'SHR-003' => [10, 250],
        ],
        'Staff IT' => [
            'IBP-005' => [20, 100],  'IBP-004' => [15, 100], 'CUS-003' => [20, 95],
            'IBP-003' => [15, 99.9], 'LGR-001' => [10, 24],  'LGR-003' => [5, 2],
            'SHR-005' => [15, 100],
        ],
        'Staff HRD' => [
            'LGR-001' => [25, 24],   'LGR-002' => [15, 2],   'LGR-003' => [10, 4],
            'IBP-004' => [15, 100],  'LGR-004' => [15, 99],  'CUS-002' => [10, 4.3],
            'SHR-004' => [10, 2],
        ],
    ];

    public function run(): void
    {
        $indicators = KpiIndicator::pluck('id', 'code');

        foreach ($this->map as $positionName => $items) {
            $position = Position::firstOrCreate(['name' => $positionName]);

            $template = KpiPositionTemplate::updateOrCreate(
                ['position_id' => $position->id, 'effective_year' => 2025],
                ['name' => "Template KPI {$positionName} 2025", 'is_active' => true]
            );

            $template->items()->delete();

            foreach ($items as $code => [$weight, $target]) {
                $template->items()->create([
                    'kpi_indicator_id' => $indicators[$code],
                    'weight'           => $weight,
                    'default_target'   => $target,
                ]);
            }
        }
    }
}
```

### 10.6 KpiAssignmentDemoSeeder — data siap demo

```php
<?php
namespace Database\Seeders;

use App\Models\{Employee, KpiAssignment, KpiPeriod, KpiPositionTemplate};
use App\Services\Kpi\KpiScoreCalculator;
use Illuminate\Database\Seeder;

class KpiAssignmentDemoSeeder extends Seeder
{
    public function __construct(private KpiScoreCalculator $calculator) {}

    public function run(): void
    {
        $periods = KpiPeriod::whereIn('code', ['2025-S1','2025-S2'])->get()->keyBy('code');

        Employee::with('position')->where('status', 'active')->chunk(50, function ($employees) use ($periods) {
            foreach ($employees as $employee) {
                $template = KpiPositionTemplate::with('items.indicator')
                    ->where('position_id', $employee->position_id)
                    ->where('is_active', true)
                    ->first();

                if (! $template) {
                    continue; // jabatan belum punya template
                }

                foreach ($periods as $code => $period) {
                    $isClosed = $code === '2025-S1';

                    $assignment = KpiAssignment::updateOrCreate(
                        ['kpi_period_id' => $period->id, 'employee_id' => $employee->id],
                        [
                            'reviewer_id'   => $employee->manager_id,
                            'status'        => $isClosed ? 'final' : 'self_assessed',
                            'employee_note' => $isClosed ? 'Realisasi sesuai laporan unit.' : null,
                            'reviewer_note' => $isClosed ? 'Kinerja konsisten, pertahankan kualitas akad.' : null,
                            'agreed_at'     => $period->start_date,
                            'finalized_at'  => $isClosed ? $period->end_date : null,
                        ]
                    );

                    $assignment->items()->delete();

                    foreach ($template->items as $ti) {
                        $target = (float) $ti->default_target;

                        // simulasi realisasi 80%-115% dari target (arah menyesuaikan polaritas)
                        $factor = mt_rand(80, 115) / 100;
                        $actual = $ti->indicator->polarity === 'minimize'
                            ? round($target * (2 - $factor), 2)
                            : round($target * $factor, 2);

                        // indikator "temuan DPS" bertarget 0 → realisasi 0 atau 1
                        if ($ti->indicator->code === 'SHR-001') {
                            $target = 0; $actual = mt_rand(0, 10) > 8 ? 1 : 0;
                        }

                        $assignment->items()->create([
                            'kpi_indicator_id' => $ti->kpi_indicator_id,
                            'weight'           => $ti->weight,
                            'target_value'     => $target,
                            'threshold_value'  => round($target * 0.8, 2),
                            'stretch_value'    => round($target * 1.2, 2),
                            'actual_value'     => $actual,
                            'evidence_note'    => 'Data demo hasil generate seeder.',
                        ]);
                    }

                    $this->calculator->recalculate($assignment->load('items.indicator', 'period'));
                }
            }
        });
    }
}
```

> **Catatan indikator bertarget nol** (`SHR-001`): rumus pembagian tidak berlaku. Tangani di `KpiScoreCalculator` dengan aturan khusus — `actual = 0` → achievement 100%, setiap temuan mengurangi 25% (mis. `max(0, 100 - 25 × actual)`).

### 10.7 KpiDemoSeeder (pemanggil)

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KpiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KpiPerspectiveSeeder::class,
            KpiGradeSeeder::class,
            KpiIndicatorSeeder::class,
            KpiPeriodSeeder::class,
            KpiPositionTemplateSeeder::class,
            KpiAssignmentDemoSeeder::class,
        ]);

        $this->command->info('✅ Data demo KPI siap. Login sebagai hr-admin lalu buka /kpi/dashboard');
    }
}
```

**Perintah demo:**

```bash
php artisan migrate
php artisan db:seed --class=KpiDemoSeeder
# reset ulang cepat sebelum presentasi:
php artisan migrate:fresh --seed --seeder=DatabaseSeeder
```

---

## 11. Pengujian & Kriteria Terima

### Status implementasi pada HRIS saat ini

Fase MVP awal sudah diimplementasikan pada route `/kpi` dengan tabel legacy:

- Dashboard KPI dan daftar assignment.
- Pembuatan periode KPI.
- Master indikator dengan lima perspektif BSC (`FIN`, `CUS`, `IBP`, `LGR`,
  `SHR`) yang disimpan pada prefix `description` karena tabel legacy `kpis`
  belum memiliki kolom perspektif.
- Assignment KPI ke employee dan validasi bobot maksimal 100%.
- Input actual dan perhitungan score capped 120% untuk indikator maximize.
- Akses mengikuti permission `performance.*` dan mutation dicatat pada audit.
- Seed demo idempotent membuat satu periode, lima indikator, dan assignment
  admin pada `DatabaseSeeder`.

Fitur yang masih merupakan fase lanjutan dan belum diklaim selesai:
`kpi_*` schema terpisah, template jabatan, cascading parent assignment,
approval berjenjang khusus KPI, kalibrasi 9-box, evidence upload, dan dashboard
analitik lintas periode. Fitur tersebut memerlukan perluasan schema legacy atau
migration terkontrol agar database HRIS tidak rusak.

### Test yang wajib ada

```php
// tests/Feature/KpiScoreCalculatorTest.php
it('menghitung capaian maximize dengan benar', function () {
    // target 100, actual 110 → 110%
});

it('membalik capaian untuk indikator minimize', function () {
    // NPF target 2%, actual 1.5% → (2 - 0.75) x 100 = 125% → dicap 120%
});

it('menolak kontrak KPI dengan total bobot bukan 100%', function () { /* ... */ });

it('mencegah karyawan mengubah realisasi setelah status final', function () { /* ... */ });
```

### Acceptance criteria untuk UAT klien

- [ ] HR dapat membuat periode dan generate kontrak KPI seluruh karyawan dalam satu aksi.
- [ ] Sistem menolak kontrak KPI bila total bobot ≠ 100%.
- [ ] Karyawan hanya melihat kontrak KPI miliknya sendiri.
- [ ] Atasan melihat seluruh bawahan langsung maupun tidak langsung.
- [ ] Skor akhir dan predikat terhitung otomatis sesuai formula di bagian 5.
- [ ] Setiap perubahan nilai setelah `reviewed` tercatat di audit log lengkap dengan alasan.
- [ ] Perspektif Kepatuhan & Nilai Syariah muncul di semua template jabatan.
- [ ] Ekspor PDF form penilaian dan Excel rekap berfungsi.
- [ ] Dashboard menampilkan distribusi grade dan tren antar periode.

---

## 12. Roadmap Implementasi

| Fase | Durasi | Lingkup |
|---|---|---|
| **Fase 1 — MVP (demo)** | 2–3 minggu | Migration, model, master indikator, template jabatan, kontrak KPI, perhitungan skor, dashboard sederhana, seeder demo |
| **Fase 2 — Workflow** | 2 minggu | Approval berjenjang, self-assessment, notifikasi email/WA, unggah bukti, audit log |
| **Fase 3 — Analitik** | 2 minggu | 9-box kalibrasi, strategy map cascading, export PDF/Excel, dashboard eksekutif |
| **Fase 4 — Integrasi** | 3–4 minggu | Tarik realisasi otomatis dari core banking & modul absensi, integrasi payroll untuk bonus berbasis grade, API mobile |

**Risiko yang perlu disampaikan ke klien:**
- Ketersediaan API core banking menentukan apakah realisasi bisa otomatis atau tetap input manual.
- Bobot dan target indikator perlu disepakati komite SDM lebih dulu; blueprint ini menyediakan angka awal sebagai bahan diskusi, bukan ketetapan.
- Data kinerja tergolong sensitif — perlu enkripsi at-rest dan pembatasan akses berbasis Policy sejak Fase 1.

---

## 13. Prompt Siap Pakai untuk AI Coding Assistant

Salin blok berikut ke Claude Code / Cursor bersama dokumen ini:

```
Konteks: Saya punya aplikasi HRIS berbasis Laravel 11 + MySQL untuk sebuah bank syariah.
Tabel existing: employees, departments, positions, users, attendances. Autentikasi memakai
Laravel Breeze, permission memakai Spatie Laravel Permission. Stack UI: Blade + Livewire 3 +
Tailwind CSS.

Tugas: Bangun modul KPI (Performance Management) sesuai blueprint terlampir
(blueprint-kpi-hris-bank-syariah.md). Kerjakan bertahap, mulai dari Fase 1 (MVP).

Langkah 1 — Database
Buat seluruh migration pada bagian 4 blueprint. Gunakan foreign key constraint, soft delete
pada kpi_periods dan kpi_assignments, serta index pada kolom yang sering difilter
(kpi_period_id, employee_id, status).

Langkah 2 — Model & relasi
Buat model Eloquent beserta relasi dan scope sesuai bagian 6. Tambahkan accessor
total_weight dan is_editable.

Langkah 3 — Service perhitungan
Implementasikan App\Services\Kpi\KpiScoreCalculator persis seperti bagian 5, termasuk
penanganan khusus indikator bertarget nol (rumus: max(0, 100 - 25 * actual)).

Langkah 4 — Seeder
Buat semua seeder pada bagian 10 dengan data demo yang realistis untuk bank syariah.
Pastikan `php artisan db:seed --class=KpiDemoSeeder` menghasilkan minimal 3 periode,
25 indikator, 8 template jabatan, dan kontrak KPI terisi lengkap dengan skor untuk
seluruh karyawan aktif.

Langkah 5 — Controller, Request, Policy, Route
Ikuti bagian 7 dan 8. Validasi total bobot 100% wajib ada di FormRequest.

Langkah 6 — UI Livewire
Bangun halaman 1, 3, 4, 5, dan 6 pada bagian 9. Gunakan komponen tabel yang bisa
difilter per periode dan departemen. Grafik memakai ApexCharts.

Langkah 7 — Test
Tulis Pest test untuk skenario pada bagian 11.

Aturan pengerjaan:
- Ikuti konvensi kode HRIS existing; jangan ubah tabel yang sudah ada kecuali menambah kolom.
- Semua label UI dalam Bahasa Indonesia.
- Format mata uang Rupiah dan tanggal Indonesia (d F Y).
- Setiap perubahan nilai setelah status 'reviewed' harus tercatat di audit log.
- Kerjakan satu langkah, tunjukkan hasilnya, tunggu konfirmasi sebelum lanjut ke langkah berikutnya.
```

---

## 14. Checklist Persiapan Demo ke Klien

1. `php artisan migrate:fresh --seed` — pastikan data bersih.
2. Siapkan 3 akun login: `hr@bank.co.id` (hr-admin), `kacab@bank.co.id` (atasan), `teller@bank.co.id` (karyawan).
3. Alur demo yang disarankan (±15 menit):
   - Dashboard eksekutif → tunjukkan distribusi grade & tren 2 periode.
   - Master indikator → sorot perspektif Kepatuhan & Nilai Syariah sebagai pembeda.
   - Template jabatan → demo drag bobot dengan validasi total 100%.
   - Login karyawan → input realisasi + unggah bukti → skor berubah realtime.
   - Login atasan → review & beri catatan.
   - Login HR → kalibrasi 9-box → finalisasi → export PDF.
4. Siapkan jawaban untuk pertanyaan yang hampir pasti muncul: integrasi core banking, keamanan data, siapa yang berhak mengubah bobot, dan bagaimana kaitannya dengan remunerasi.
