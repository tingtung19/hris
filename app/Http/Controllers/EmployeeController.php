<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private const FIELDS = [
        'nik', 'first_name', 'last_name', 'birth_place', 'birth_date', 'gender',
        'religion', 'marital_status', 'phone', 'personal_email', 'company_id',
        'branch_id', 'department_id', 'division_id', 'section_id', 'position_id',
        'job_level_id', 'job_grade_id', 'work_location_id', 'cost_center_id',
        'supervisor_id', 'manager_id', 'join_date', 'appointment_date', 'resign_date',
        'employment_status', 'employment_type', 'bank_name', 'bank_account_number',
        'bank_account_holder', 'npwp', 'ptkp_status', 'bpjs_health_number',
        'bpjs_employment_number',
    ];

    public function index(Request $request)
    {
        $query = Employee::query()
            ->whereNull('deleted_at')
            ->with(['department', 'position'])
            ->when($request->string('q')->trim()->value(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->when($request->input('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->when($request->input('employment_status'), fn ($query, $status) => $query->where('employment_status', $status))
            ->when($request->input('employment_type'), fn ($query, $type) => $query->where('employment_type', $type));

        return view('employees.index', [
            'employees' => $query->orderBy('first_name')->paginate(15)->withQueryString(),
            'departments' => Department::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(['gender' => 'male', 'marital_status' => 'single', 'employment_status' => 'probation', 'employment_type' => 'contract']),
            'departments' => Department::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'positions' => Position::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);
        $data['employee_number'] = $this->nextEmployeeNumber();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $employee = Employee::create($data);
        $audit->record('created', 'employee', $employee->id, 'Karyawan dibuat.', null, $employee->fresh()->toArray(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Karyawan berhasil ditambahkan.');
    }

    public function show(Employee $employee)
    {
        abort_if($employee->deleted_at, 404);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        abort_if($employee->deleted_at, 404);

        return view('employees.form', [
            'employee' => $employee,
            'departments' => Department::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'positions' => Position::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Employee $employee, AuditService $audit)
    {
        abort_if($employee->deleted_at, 404);
        $before = $employee->toArray();
        $employee->update(array_merge($this->validated($request), ['updated_at' => now()]));
        $audit->record('updated', 'employee', $employee->id, 'Data karyawan diperbarui.', $before, $employee->fresh()->toArray(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee, AuditService $audit)
    {
        $before = $employee->toArray();
        $employee->update(['deleted_at' => now(), 'updated_at' => now()]);
        $audit->record('deleted', 'employee', $employee->id, 'Karyawan dihapus secara soft delete.', $before, $employee->fresh()->toArray());

        return redirect()->route('employees.index')->with('status', 'Karyawan berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'nik' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'personal_email' => ['nullable', 'email', 'max:100'],
            'company_id' => ['required', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'join_date' => ['required', 'date'],
            'appointment_date' => ['nullable', 'date'],
            'resign_date' => ['nullable', 'date'],
            'employment_status' => ['required', 'in:active,probation,resigned,terminated'],
            'employment_type' => ['required', 'in:permanent,contract,intern,daily,freelance'],
        ] + collect(self::FIELDS)->reject(fn ($field) => in_array($field, [
            'first_name', 'last_name', 'nik', 'gender', 'marital_status', 'birth_place',
            'birth_date', 'religion', 'phone', 'personal_email', 'company_id',
            'department_id', 'position_id', 'join_date', 'appointment_date', 'resign_date',
            'employment_status', 'employment_type',
        ], true))->mapWithKeys(fn ($field) => [$field => ['nullable']])->all());
    }

    private function nextEmployeeNumber(): string
    {
        $last = Employee::whereNotNull('employee_number')->orderByDesc('id')->value('employee_number');
        $number = $last ? ((int) str_replace('EMP-', '', $last)) + 1 : 1;

        return 'EMP-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
