<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $put = function (string $table, array $where, array $values) use ($now): int {
            $query = DB::table($table);
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }

            $existing = $query->value('id');
            if ($existing) {
                return (int) $existing;
            }

            return (int) DB::table($table)->insertGetId(array_merge($where, $values, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        };

        $company = $put('companies', ['code' => 'HQ'], [
            'name' => 'PT Bank Syariah Amanah Nusantara',
            'legal_name' => 'PT Bank Syariah Amanah Nusantara Tbk',
            'address' => 'Jl. Prof. Dr. Satrio No. 99, Jakarta Selatan',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'phone' => '+62-21-5551234',
            'email' => 'info@amanahsyariah.test',
        ]);
        DB::table('companies')->where('id', $company)->update([
            'name' => 'PT Bank Syariah Amanah Nusantara',
            'legal_name' => 'PT Bank Syariah Amanah Nusantara Tbk',
            'email' => 'info@amanahsyariah.test',
            'updated_at' => $now,
        ]);
        $branch = $put('branches', ['company_id' => $company, 'code' => 'HO'], [
            'name' => 'Head Office Jakarta',
            'address' => 'Jl. Prof. Dr. Satrio No. 99, Jakarta Selatan',
            'city' => 'Jakarta Selatan',
            'phone' => '+62-21-5551234',
            'is_head_office' => 1,
        ]);
        $departments = [];
        foreach ([['HRD', 'Human Resources'], ['IT', 'Information Technology'], ['FIN', 'Finance & Accounting']] as [$code, $name]) {
            $departments[$code] = $put('departments', ['company_id' => $company, 'code' => $code], [
                'branch_id' => $branch,
                'name' => $name,
            ]);
        }

        $levels = [];
        foreach ([['STAFF', 'Staff', 1], ['SPV', 'Supervisor', 2], ['MGR', 'Manager', 3], ['DIR', 'Director', 4]] as [$code, $name, $order]) {
            $levels[$code] = $put('job_levels', ['code' => $code], ['name' => $name, 'level_order' => $order]);
        }
        $positions = [
            'HRM' => $put('positions', ['code' => 'HRM'], ['name' => 'HR Manager', 'department_id' => $departments['HRD'], 'job_level_id' => $levels['MGR']]),
            'HRS' => $put('positions', ['code' => 'HRS'], ['name' => 'HR Staff', 'department_id' => $departments['HRD'], 'job_level_id' => $levels['STAFF']]),
            'ITS' => $put('positions', ['code' => 'ITS'], ['name' => 'IT Staff', 'department_id' => $departments['IT'], 'job_level_id' => $levels['STAFF']]),
        ];
        $location = $put('work_locations', ['name' => 'Head Office Jakarta'], [
            'address' => 'Jl. Jendral Sudirman No. 1, Jakarta Selatan',
            'latitude' => -6.224,
            'longitude' => 106.809,
            'radius_meter' => 200,
        ]);

        $roles = [];
        foreach ([
            ['super-administrator', 'Super Administrator', 1],
            ['hr-administrator', 'HR Administrator', 0],
            ['hr-manager', 'HR Manager', 0],
            ['finance', 'Finance', 0],
            ['manager', 'Manager', 0],
            ['supervisor', 'Supervisor', 0],
            ['employee', 'Employee', 0],
            ['auditor', 'Auditor', 0],
        ] as [$slug, $name, $system]) {
            $roles[$slug] = $put('roles', ['slug' => $slug], [
                'name' => $name,
                'description' => $name.' role',
                'is_system' => $system,
            ]);
        }

        $permissions = [];
        $permissionMap = [
            'employee' => ['view', 'create', 'update', 'delete', 'export'],
            'organization' => ['view', 'create', 'update', 'delete'],
            'attendance' => ['view', 'create', 'update', 'delete', 'correct', 'approve'],
            'leave' => ['view', 'create', 'update', 'delete', 'approve'],
            'payroll' => ['view', 'create', 'update', 'generate', 'approve', 'delete'],
            'recruitment' => ['view', 'create', 'update', 'delete'],
            'onboarding' => ['view', 'create', 'update'],
            'offboarding' => ['view', 'create', 'update', 'approve'],
            'performance' => ['view', 'create', 'update', 'delete', 'review'],
            'training' => ['view', 'create', 'update', 'delete'],
            'asset' => ['view', 'create', 'update', 'delete'],
            'business_trip' => ['view', 'create', 'update', 'approve'],
            'reimbursement' => ['view', 'create', 'update', 'approve'],
            'announcement' => ['view', 'create', 'update', 'delete'],
            'report' => ['view', 'export'],
            'system' => ['manage'],
            'audit' => ['view'],
        ];
        foreach ($permissionMap as $module => $actions) {
            foreach ($actions as $action) {
                $slug = $module.'.'.$action;
                $permissions[$slug] = $put('permissions', ['slug' => $slug], [
                    'name' => ucfirst($action).' '.ucwords(str_replace('_', ' ', $module)),
                    'module' => $module,
                ]);
            }
        }
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roles['super-administrator'],
                'permission_id' => $permission,
                'created_at' => $now,
            ]);
        }
        $rolePermissionMap = [
            'auditor' => ['report.view', 'report.export', 'employee.view', 'audit.view'],
            'hr-administrator' => [
                'employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.export',
                'organization.view', 'organization.create', 'organization.update', 'organization.delete',
                'attendance.view', 'attendance.correct', 'attendance.approve',
                'leave.view', 'leave.create', 'leave.update', 'leave.approve',
                'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.delete',
                'onboarding.view', 'onboarding.create', 'onboarding.update',
                'offboarding.view', 'offboarding.create', 'offboarding.update', 'offboarding.approve',
                'performance.view', 'performance.create', 'performance.update', 'performance.delete', 'performance.review',
                'training.view', 'training.create', 'training.update', 'training.delete',
                'asset.view', 'asset.create', 'asset.update', 'asset.delete',
                'announcement.view', 'announcement.create', 'announcement.update', 'announcement.delete',
                'report.view', 'report.export', 'audit.view',
            ],
            'hr-manager' => ['employee.view', 'leave.view', 'leave.approve', 'attendance.view', 'attendance.approve', 'performance.view', 'performance.create', 'performance.update', 'performance.review', 'report.view', 'report.export'],
            'finance' => ['employee.view', 'payroll.view', 'payroll.create', 'payroll.update', 'payroll.generate', 'payroll.approve', 'reimbursement.view', 'reimbursement.approve', 'report.view', 'report.export'],
            'manager' => [
                'employee.view',
                'attendance.view', 'attendance.approve',
                'leave.view', 'leave.approve',
                'performance.view', 'performance.update', 'performance.review',
                'business_trip.view', 'business_trip.approve',
                'reimbursement.view', 'reimbursement.approve',
            ],
            'supervisor' => ['employee.view', 'attendance.view', 'attendance.approve', 'leave.view', 'leave.approve', 'performance.view', 'performance.update'],
            'employee' => ['employee.view', 'attendance.view', 'attendance.create', 'leave.view', 'leave.create', 'performance.view', 'performance.update', 'reimbursement.view', 'reimbursement.create'],
        ];
        foreach ($rolePermissionMap as $roleSlug => $slugs) {
            $permissionIds = array_map(fn (string $slug): int => $permissions[$slug], $slugs);
            DB::table('role_permissions')
                ->where('role_id', $roles[$roleSlug])
                ->whereNotIn('permission_id', $permissionIds)
                ->delete();
            foreach ($slugs as $slug) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roles[$roleSlug],
                    'permission_id' => $permissions[$slug],
                    'created_at' => $now,
                ]);
            }
        }

        $employee = $put('employees', ['employee_number' => 'EMP-0001'], [
            'nik' => '3171000000000001',
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'marital_status' => 'single',
            'phone' => '081200000001',
            'personal_email' => 'admin.personal@example.com',
            'company_id' => $company,
            'branch_id' => $branch,
            'department_id' => $departments['HRD'],
            'position_id' => $positions['HRM'],
            'job_level_id' => $levels['MGR'],
            'work_location_id' => $location,
            'join_date' => today(),
            'employment_status' => 'active',
            'employment_type' => 'permanent',
        ]);
        $user = $put('users', ['username' => 'admin'], [
            'employee_id' => $employee,
            'email' => 'admin@hris.local',
            'password' => Hash::make('Admin@123'),
            'status' => 'active',
            'must_change_password' => 1,
        ]);
        DB::table('user_roles')->insertOrIgnore([
            'user_id' => $user,
            'role_id' => $roles['super-administrator'],
            'created_at' => $now,
        ]);

        // Production receives only the minimum organization and administrator.
        // The records below are deterministic demo data for local development.
        if (! app()->environment('local')) {
            return;
        }

        $demoBranches = [];
        foreach ([
            ['JKT', 'Cabang Jakarta Selatan', 'Jakarta Selatan'],
            ['BDG', 'Cabang Bandung', 'Bandung'],
            ['SBY', 'Cabang Surabaya', 'Surabaya'],
        ] as [$code, $name, $city]) {
            $demoBranches[$code] = $put('branches', ['company_id' => $company, 'code' => $code], [
                'name' => $name,
                'address' => 'Jl. Pusat Kota '.$city,
                'city' => $city,
                'phone' => '+62-21-555'.substr($code, 0, 3),
                'is_head_office' => 0,
            ]);
        }

        foreach ([
            ['RISK', 'Manajemen Risiko dan Kepatuhan'],
            ['OPS', 'Operasional dan Layanan'],
            ['BUS', 'Bisnis dan Pembiayaan'],
            ['DIG', 'Digital Banking dan Teknologi'],
        ] as [$code, $name]) {
            $departments[$code] = $put('departments', ['company_id' => $company, 'code' => $code], [
                'branch_id' => $branch,
                'name' => $name,
            ]);
        }

        $divisions = [];
        foreach ([
            ['HRD', 'HC', 'Human Capital'],
            ['FIN', 'ACC', 'Finance, Accounting and Tax'],
            ['RISK', 'COM', 'Compliance and Sharia Governance'],
            ['RISK', 'RM', 'Enterprise Risk Management'],
            ['OPS', 'OPS-BR', 'Branch Operations'],
            ['BUS', 'MKT', 'Retail and SME Financing'],
            ['BUS', 'FND', 'Funding and Transaction Banking'],
            ['DIG', 'ENG', 'Digital Product Engineering'],
        ] as [$department, $code, $name]) {
            $divisions[$code] = $put('divisions', [
                'department_id' => $departments[$department],
                'code' => $code,
            ], ['name' => $name]);
        }

        $sections = [];
        foreach ([
            ['HC', 'HRIS', 'HRIS and People Analytics'],
            ['HC', 'REC', 'Recruitment and Talent'],
            ['ACC', 'GL', 'General Ledger'],
            ['COM', 'SYC', 'Sharia Compliance'],
            ['OPS-BR', 'TELLER', 'Teller and Customer Service'],
            ['MKT', 'MICRO', 'Micro and SME Financing'],
            ['ENG', 'APP', 'Mobile and Internet Banking'],
        ] as [$division, $code, $name]) {
            $sections[$code] = $put('sections', [
                'division_id' => $divisions[$division],
                'code' => $code,
            ], ['name' => $name]);
        }

        $grades = [];
        foreach ([
            ['G1', 'Officer', 1], ['G2', 'Senior Officer', 2],
            ['G3', 'Assistant Manager', 3], ['G4', 'Manager', 4],
            ['G5', 'Senior Manager', 5], ['G6', 'Vice President', 6],
        ] as [$code, $name, $order]) {
            $grades[$code] = $put('job_grades', ['code' => $code], [
                'name' => $name,
                'grade_order' => $order,
            ]);
        }

        foreach ([
            ['HCS', 'Human Capital Specialist', 'HRD', 'STAFF'],
            ['HCM', 'Human Capital Manager', 'HRD', 'MGR'],
            ['CFO', 'Head of Finance', 'FIN', 'DIR'],
            ['CCO', 'Head of Compliance', 'RISK', 'DIR'],
            ['RMO', 'Risk Management Officer', 'RISK', 'MGR'],
            ['BOA', 'Branch Operations Area Manager', 'OPS', 'MGR'],
            ['TLO', 'Teller and Customer Service Officer', 'OPS', 'STAFF'],
            ['FMO', 'Financing Marketing Officer', 'BUS', 'STAFF'],
            ['PTE', 'Product Technology Engineer', 'DIG', 'STAFF'],
        ] as [$code, $name, $department, $level]) {
            $positions[$code] = $put('positions', ['code' => $code], [
                'name' => $name,
                'department_id' => $departments[$department],
                'job_level_id' => $levels[$level],
            ]);
        }
        foreach ([
            ['CC-HC', 'Human Capital Cost Center', 'HRD'],
            ['CC-FIN', 'Finance Cost Center', 'FIN'],
            ['CC-RISK', 'Risk and Compliance Cost Center', 'RISK'],
            ['CC-OPS', 'Branch Operations Cost Center', 'OPS'],
            ['CC-BUS', 'Business Banking Cost Center', 'BUS'],
            ['CC-DIG', 'Digital Banking Cost Center', 'DIG'],
        ] as [$code, $name, $department]) {
            $put('cost_centers', ['code' => $code], [
                'name' => $name,
                'department_id' => $departments[$department],
            ]);
        }

        $demoLocations = [];
        foreach ([
            ['KANTOR-PUSAT', 'Kantor Pusat Jakarta', -6.2297, 106.8223],
            ['CABANG-BDG', 'Kantor Cabang Bandung', -6.9175, 107.6191],
            ['CABANG-SBY', 'Kantor Cabang Surabaya', -7.2575, 112.7521],
        ] as [$name, $label, $latitude, $longitude]) {
            $demoLocations[$name] = $put('work_locations', ['name' => $label], [
                'address' => $label,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_meter' => 150,
            ]);
        }

        $createDemoEmployee = function (array $data) use ($put, $company, $branch, $departments, $positions, $levels, $grades, $demoLocations, $roles, $now): int {
            $employee = $put('employees', ['employee_number' => $data['number']], [
                'nik' => $data['nik'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'],
                'birth_date' => $data['birth_date'],
                'religion' => 'Islam',
                'marital_status' => $data['marital_status'],
                'phone' => $data['phone'],
                'personal_email' => $data['username'].'@amanahsyariah.test',
                'company_id' => $company,
                'branch_id' => $data['branch'] === 'HO' ? $branch : $data['branch'],
                'department_id' => $departments[$data['department']],
                'division_id' => $data['division'] ? DB::table('divisions')->where('code', $data['division'])->value('id') : null,
                'section_id' => $data['section'] ? DB::table('sections')->where('code', $data['section'])->value('id') : null,
                'position_id' => $positions[$data['position']],
                'job_level_id' => $levels[$data['level']],
                'job_grade_id' => $grades[$data['grade']],
                'work_location_id' => $demoLocations[$data['location']],
                'join_date' => $data['join_date'],
                'employment_status' => 'active',
                'employment_type' => 'permanent',
            ]);
            $demoUser = $put('users', ['username' => $data['username']], [
                'employee_id' => $employee,
                'email' => $data['username'].'@amanahsyariah.test',
                'password' => Hash::make('Demo@12345'),
                'status' => 'active',
                'must_change_password' => 1,
            ]);
            DB::table('user_roles')->insertOrIgnore([
                'user_id' => $demoUser,
                'role_id' => $roles[$data['role']],
                'created_at' => $now,
            ]);

            return $employee;
        };

        $demoEmployees = [
            ['number' => 'BSAN-0002', 'nik' => '3171000000000002', 'first_name' => 'Aisyah', 'last_name' => 'Rahmawati', 'gender' => 'female', 'birth_place' => 'Jakarta', 'birth_date' => '1987-04-12', 'marital_status' => 'married', 'phone' => '081200000002', 'username' => 'hr.manager', 'department' => 'HRD', 'division' => 'HC', 'section' => 'HRIS', 'position' => 'HCM', 'level' => 'MGR', 'grade' => 'G4', 'location' => 'KANTOR-PUSAT', 'branch' => 'HO', 'join_date' => '2015-01-05', 'role' => 'hr-manager'],
            ['number' => 'BSAN-0003', 'nik' => '3171000000000003', 'first_name' => 'Fajar', 'last_name' => 'Hidayat', 'gender' => 'male', 'birth_place' => 'Bandung', 'birth_date' => '1984-09-23', 'marital_status' => 'married', 'phone' => '081200000003', 'username' => 'finance.manager', 'department' => 'FIN', 'division' => 'ACC', 'section' => 'GL', 'position' => 'CFO', 'level' => 'DIR', 'grade' => 'G6', 'location' => 'KANTOR-PUSAT', 'branch' => 'HO', 'join_date' => '2012-07-02', 'role' => 'finance'],
            ['number' => 'BSAN-0004', 'nik' => '3171000000000004', 'first_name' => 'Nadia', 'last_name' => 'Putri', 'gender' => 'female', 'birth_place' => 'Depok', 'birth_date' => '1992-11-08', 'marital_status' => 'single', 'phone' => '081200000004', 'username' => 'supervisor.ops', 'department' => 'OPS', 'division' => 'OPS-BR', 'section' => 'TELLER', 'position' => 'BOA', 'level' => 'MGR', 'grade' => 'G4', 'location' => 'CABANG-BDG', 'branch' => $demoBranches['BDG'], 'join_date' => '2018-03-12', 'role' => 'supervisor'],
            ['number' => 'BSAN-0005', 'nik' => '3171000000000005', 'first_name' => 'Rizky', 'last_name' => 'Maulana', 'gender' => 'male', 'birth_place' => 'Surabaya', 'birth_date' => '1996-02-17', 'marital_status' => 'single', 'phone' => '081200000005', 'username' => 'employee.teller', 'department' => 'OPS', 'division' => 'OPS-BR', 'section' => 'TELLER', 'position' => 'TLO', 'level' => 'STAFF', 'grade' => 'G1', 'location' => 'CABANG-SBY', 'branch' => $demoBranches['SBY'], 'join_date' => '2022-06-20', 'role' => 'employee'],
            ['number' => 'BSAN-0006', 'nik' => '3171000000000006', 'first_name' => 'Siti', 'last_name' => 'Mardiyah', 'gender' => 'female', 'birth_place' => 'Bogor', 'birth_date' => '1994-06-30', 'marital_status' => 'married', 'phone' => '081200000006', 'username' => 'employee.compliance', 'department' => 'RISK', 'division' => 'COM', 'section' => 'SYC', 'position' => 'RMO', 'level' => 'MGR', 'grade' => 'G3', 'location' => 'KANTOR-PUSAT', 'branch' => 'HO', 'join_date' => '2020-01-13', 'role' => 'auditor'],
        ];
        foreach ($demoEmployees as $demoEmployee) {
            $createDemoEmployee($demoEmployee);
        }

        $kpiPeriod = $put('performance_periods', ['name' => 'KPI '.today()->year], [
            'type' => 'annual',
            'start_date' => today()->startOfYear()->toDateString(),
            'end_date' => today()->endOfYear()->toDateString(),
            'status' => 'open',
        ]);
        $kpiIndicators = [];
        foreach ([
            ['FIN-001', 'Pertumbuhan pendapatan', '[FIN] Pencapaian target pendapatan unit.'],
            ['CUS-001', 'Kepuasan nasabah', '[CUS] Skor kepuasan nasabah.'],
            ['IBP-001', 'Ketepatan proses kerja', '[IBP] Penyelesaian proses sesuai SLA.'],
            ['LGR-001', 'Pengembangan kompetensi', '[LGR] Pelatihan dan pengembangan kompetensi.'],
            ['SHR-001', 'Kepatuhan nilai syariah', '[SHR] Kepatuhan terhadap kebijakan dan nilai syariah.'],
        ] as [$code, $name, $description]) {
            $kpiIndicators[$code] = $put('kpis', ['name' => $name], [
                'description' => $description,
                'department_id' => null,
            ]);
        }
        $existingKpiAssignment = DB::table('employee_kpis')
            ->where('performance_period_id', $kpiPeriod)
            ->where('employee_id', $employee)
            ->exists();
        if (! $existingKpiAssignment) {
            foreach ([
                ['FIN-001', 30, 100], ['CUS-001', 20, 100], ['IBP-001', 20, 100],
                ['LGR-001', 10, 100], ['SHR-001', 20, 100],
            ] as [$code, $weight, $target]) {
                DB::table('employee_kpis')->insert([
                    'performance_period_id' => $kpiPeriod,
                    'employee_id' => $employee,
                    'kpi_id' => $kpiIndicators[$code],
                    'target' => $target,
                    'weight' => $weight,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['AL', 'Annual Leave', 12, 1], ['SL', 'Sick Leave', 12, 1],
            ['ML', 'Marriage Leave', 3, 1], ['MTL', 'Maternity Leave', 90, 1],
            ['BL', 'Bereavement Leave', 2, 1], ['SPL', 'Special Leave', 3, 1],
            ['UL', 'Unpaid Leave', 0, 0], ['CL', 'Company Leave', 0, 1],
        ] as [$code, $name, $days, $paid]) {
            $put('leave_types', ['code' => $code], [
                'name' => $name, 'default_days_per_year' => $days, 'is_paid' => $paid,
            ]);
        }
        foreach ([['LATE', 'Terlambat'], ['EARLY', 'Pulang Cepat'], ['PERSONAL', 'Keperluan Pribadi'], ['SICK', 'Sakit'], ['WFH', 'Work From Home'], ['BUSINESS', 'Urusan Dinas'], ['LEAVING', 'Keluar Kantor']] as [$code, $name]) {
            $put('permission_types', ['code' => $code], ['name' => $name]);
        }
        foreach ([
            ['LAPTOP', 'Laptop'], ['MOBILE', 'Handphone'], ['FURNITURE', 'Furniture'], ['VEHICLE', 'Kendaraan'],
        ] as [$code, $name]) {
            $put('asset_categories', ['code' => $code], ['name' => $name]);
        }
        foreach ([
            ['TRANSPORT', 'Transportasi', 1000000], ['MEDICAL', 'Kesehatan', 2000000],
            ['MEAL', 'Makan', 500000], ['OTHER', 'Lainnya', null],
        ] as [$code, $name, $max]) {
            $put('reimbursement_categories', ['code' => $code], ['name' => $name, 'max_amount' => $max]);
        }
        foreach ([
            'company_name' => 'PT Bank Syariah Amanah Nusantara',
            'company_email' => 'info@amanahsyariah.test',
            'company_phone' => '+62-21-5551234',
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'attendance_grace_minutes' => '15',
            'annual_leave_default_days' => '12',
            'bpjs_calculation_base' => 'basic',
            'ptkp_default_status' => 'TK/0',
        ] as $key => $value) {
            $put('system_settings', ['setting_key' => $key], [
                'setting_value' => $value, 'setting_group' => 'general',
            ]);
        }

        foreach ([
            ['BASIC', 'Gaji Pokok', 'income', 'fixed', 1],
            ['POS_ALLOW', 'Tunjangan Jabatan', 'income', 'fixed', 1],
            ['TRANSPORT', 'Tunjangan Transport', 'income', 'fixed', 0],
            ['MEAL', 'Tunjangan Makan', 'income', 'fixed', 0],
            ['OVERTIME', 'Lembur', 'income', 'formula', 1],
            ['BONUS', 'Bonus', 'income', 'fixed', 1],
            ['BPJS_HEALTH', 'BPJS Kesehatan', 'deduction', 'percentage', 0],
            ['BPJS_EMP', 'BPJS Ketenagakerjaan', 'deduction', 'percentage', 0],
            ['PPH21', 'PPh 21', 'deduction', 'formula', 0],
            ['LOAN', 'Cicilan Pinjaman', 'deduction', 'fixed', 0],
            ['ABSENCE', 'Potongan Absensi', 'deduction', 'formula', 0],
        ] as [$code, $name, $type, $calculation, $taxable]) {
            $put('salary_components', ['code' => $code], [
                'name' => $name, 'type' => $type, 'calculation_type' => $calculation, 'is_taxable' => $taxable,
            ]);
        }
        $regularShift = $put('shifts', ['name' => 'Regular'], [
            'start_time' => '08:00:00', 'end_time' => '17:00:00',
            'break_start' => '12:00:00', 'break_end' => '13:00:00',
            'grace_period_minutes' => 15, 'is_overnight' => 0,
        ]);
        $schedule = $put('work_schedules', ['name' => 'Senin - Jumat'], [
            'description' => 'Jadwal kerja reguler Senin sampai Jumat',
        ]);
        for ($day = 0; $day <= 6; $day++) {
            $put('work_schedule_days', [
                'work_schedule_id' => $schedule,
                'day_of_week' => $day,
            ], [
                'shift_id' => $day >= 1 && $day <= 5 ? $regularShift : null,
                'is_working_day' => $day >= 1 && $day <= 5 ? 1 : 0,
            ]);
        }
        if (! DB::table('shift_assignments')->where('employee_id', $employee)->where('work_schedule_id', $schedule)->exists()) {
            DB::table('shift_assignments')->insert([
                'employee_id' => $employee, 'work_schedule_id' => $schedule,
                'start_date' => today()->startOfMonth(), 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach ([
            ['Tahun Baru Masehi', date('Y').'-01-01'],
            ['Hari Buruh', date('Y').'-05-01'],
            ['Hari Kemerdekaan RI', date('Y').'-08-17'],
            ['Hari Raya Natal', date('Y').'-12-25'],
        ] as [$name, $date]) {
            $put('holidays', ['name' => $name, 'date' => $date], ['type' => 'national']);
        }
        foreach ([
            'leave' => ['Alur Persetujuan Cuti', ['supervisor', 'manager', 'hr-administrator']],
            'reimbursement' => ['Alur Persetujuan Reimbursement', ['manager', 'finance']],
            'business_trip' => ['Alur Persetujuan Perjalanan Dinas', ['supervisor', 'manager']],
            'offboarding' => ['Alur Persetujuan Resign', ['supervisor', 'hr-administrator', 'finance']],
        ] as $module => [$name, $steps]) {
            $workflow = $put('approval_workflows', ['module' => $module], ['name' => $name, 'is_active' => 1]);
            foreach ($steps as $order => $approver) {
                $step = ['approval_workflow_id' => $workflow, 'step_order' => $order + 1];
                if (isset($roles[$approver])) {
                    $step['approver_type'] = 'role';
                    $step['role_id'] = $roles[$approver];
                } else {
                    $step['approver_type'] = $approver;
                }
                $put('approval_steps', [
                    'approval_workflow_id' => $workflow,
                    'step_order' => $order + 1,
                ], array_merge($step, ['role_id' => $step['role_id'] ?? null]));
            }
        }
    }
}
