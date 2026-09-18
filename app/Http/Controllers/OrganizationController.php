<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    private const ENTITIES = [
        'companies' => ['title' => 'Perusahaan', 'table' => 'companies', 'fields' => ['code', 'name', 'legal_name', 'address', 'city', 'province', 'postal_code', 'phone', 'email', 'npwp', 'established_date']],
        'branches' => ['title' => 'Cabang', 'table' => 'branches', 'fields' => ['company_id', 'code', 'name', 'address', 'city', 'phone', 'is_head_office']],
        'departments' => ['title' => 'Departemen', 'table' => 'departments', 'fields' => ['company_id', 'branch_id', 'code', 'name', 'head_employee_id']],
        'divisions' => ['title' => 'Divisi', 'table' => 'divisions', 'fields' => ['department_id', 'code', 'name', 'head_employee_id']],
        'sections' => ['title' => 'Seksi', 'table' => 'sections', 'fields' => ['division_id', 'code', 'name', 'head_employee_id']],
        'positions' => ['title' => 'Jabatan', 'table' => 'positions', 'fields' => ['code', 'name', 'department_id', 'job_level_id']],
        'job-levels' => ['title' => 'Job Level', 'table' => 'job_levels', 'fields' => ['code', 'name', 'level_order']],
        'job-grades' => ['title' => 'Job Grade', 'table' => 'job_grades', 'fields' => ['code', 'name', 'grade_order']],
        'work-locations' => ['title' => 'Lokasi Kerja', 'table' => 'work_locations', 'fields' => ['name', 'address', 'latitude', 'longitude', 'radius_meter']],
        'cost-centers' => ['title' => 'Cost Center', 'table' => 'cost_centers', 'fields' => ['code', 'name', 'department_id']],
    ];

    public function index(string $slug, Request $request)
    {
        $config = $this->config($slug);
        $query = DB::table($config['table'])->whereNull('deleted_at');
        if ($request->filled('q')) {
            $query->where(function ($query) use ($request, $config) {
                $query->where('name', 'like', '%'.$request->string('q')->trim().'%');
                if (in_array('code', $config['fields'], true)) {
                    $query->orWhere('code', 'like', '%'.$request->string('q')->trim().'%');
                }
            });
        }

        return view('organization.index', [
            'slug' => $slug,
            'config' => $config,
            'entities' => self::ENTITIES,
            'rows' => $query->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function chart()
    {
        $companies = DB::table('companies')->whereNull('deleted_at')->orderBy('name')->get();
        foreach ($companies as $company) {
            $company->branches = DB::table('branches')->where('company_id', $company->id)->whereNull('deleted_at')->orderBy('name')->get();
            foreach ($company->branches as $branch) {
                $branch->departments = DB::table('departments')->where('branch_id', $branch->id)->whereNull('deleted_at')->orderBy('name')->get();
            }
        }

        return view('organization.chart', compact('companies'));
    }

    public function store(string $slug, Request $request, AuditService $audit)
    {
        $config = $this->config($slug);
        $data = $this->validated($config, $request);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table($config['table'])->insertGetId($data);
        $audit->record('created', 'organization', $id, $config['title'].' dibuat.', null, $data, $request);

        return back()->with('status', $config['title'].' berhasil ditambahkan.');
    }

    public function update(string $slug, int $id, Request $request, AuditService $audit)
    {
        $config = $this->config($slug);
        $data = $this->validated($config, $request);
        $data['updated_at'] = now();
        $before = DB::table($config['table'])->where('id', $id)->first();
        abort_unless($before, 404);
        DB::table($config['table'])->where('id', $id)->update($data);
        $audit->record('updated', 'organization', $id, $config['title'].' diperbarui.', $before, $data, $request);

        return back()->with('status', $config['title'].' berhasil diperbarui.');
    }

    public function destroy(string $slug, int $id, AuditService $audit)
    {
        $config = $this->config($slug);
        $before = DB::table($config['table'])->where('id', $id)->first();
        abort_unless($before, 404);
        DB::table($config['table'])->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        $audit->record('deleted', 'organization', $id, $config['title'].' dihapus.', $before, ['deleted_at' => now()]);

        return back()->with('status', $config['title'].' berhasil dihapus.');
    }

    private function config(string $slug): array
    {
        abort_unless(isset(self::ENTITIES[$slug]), 404);

        return self::ENTITIES[$slug];
    }

    private function validated(array $config, Request $request): array
    {
        $rules = [];
        foreach ($config['fields'] as $field) {
            $rules[$field] = in_array($field, ['name', 'code'], true) ? ['required', 'string', 'max:150'] : ['nullable'];
        }
        if (in_array('email', $config['fields'], true)) {
            $rules['email'] = ['nullable', 'email'];
        }

        return $request->validate($rules);
    }
}
