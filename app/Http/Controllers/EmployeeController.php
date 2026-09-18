<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    private const FIELDS = [
        'nip', 'nik', 'first_name', 'last_name', 'birth_place', 'birth_date', 'gender',
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
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
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
        $details = $this->extractDetails($data);
        $data['employee_number'] = $this->nextEmployeeNumber();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $employee = Employee::create($data);
        $this->persistDetails($employee, $request, $details);
        $audit->record('created', 'employee', $employee->id, 'Karyawan dibuat.', null, $employee->fresh()->toArray(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Karyawan berhasil ditambahkan.');
    }

    public function show(Employee $employee)
    {
        abort_if($employee->deleted_at, 404);
        $employee->load(['families', 'educations', 'experiences', 'documents']);

        return view('employees.show', compact('employee'));
    }

    public function downloadDocument(Employee $employee, int $document)
    {
        abort_if($employee->deleted_at, 404);

        $file = $employee->documents()->whereKey($document)->firstOrFail();

        return Storage::disk('private')->download($file->file_path, $file->name);
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
        $data = $this->validated($request, $employee);
        $details = $this->extractDetails($data);
        $employee->update(array_merge($data, ['updated_at' => now()]));
        $this->persistDetails($employee, $request, $details, true);
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

    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'nip')->ignore($employee?->id),
            ],
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
            'families' => ['nullable', 'array', 'max:20'],
            'families.*.name' => ['required', 'string', 'max:150'],
            'families.*.nik' => ['nullable', 'string', 'max:20'],
            'families.*.relationship' => ['required', 'in:spouse,child,other'],
            'families.*.birth_date' => ['nullable', 'date'],
            'families.*.occupation' => ['nullable', 'string', 'max:100'],
            'families.*.phone' => ['nullable', 'string', 'max:30'],
            'families.*.address' => ['nullable', 'string', 'max:1000'],
            'families.*.is_dependent' => ['nullable', 'boolean'],
            'educations' => ['nullable', 'array', 'max:10'],
            'educations.*.level' => ['required', 'in:sd,smp,sma,d3,s1,s2,s3'],
            'educations.*.school_name' => ['required', 'string', 'max:150'],
            'educations.*.major' => ['nullable', 'string', 'max:100'],
            'educations.*.graduation_year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 10)],
            'educations.*.gpa' => ['nullable', 'numeric', 'between:0,4'],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.company_name' => ['required', 'string', 'max:150'],
            'experiences.*.position' => ['nullable', 'string', 'max:150'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array', 'max:20'],
            'documents.*.category' => ['required', 'in:ktp,kk,npwp,bpjs,ijazah,certificate,cv,contract,appointment_letter,promotion_letter,mutation_letter,other'],
            'documents.*.name' => ['required', 'string', 'max:150'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'documents.*.expiry_date' => ['nullable', 'date'],
        ] + collect(self::FIELDS)->reject(fn ($field) => in_array($field, [
            'first_name', 'last_name', 'nip', 'nik', 'gender', 'marital_status', 'birth_place',
            'birth_date', 'religion', 'phone', 'personal_email', 'company_id',
            'department_id', 'position_id', 'join_date', 'appointment_date', 'resign_date',
            'employment_status', 'employment_type',
        ], true))->mapWithKeys(fn ($field) => [$field => ['nullable']])->all());
    }

    private function extractDetails(array &$data): array
    {
        $details = [
            'families' => $data['families'] ?? [],
            'educations' => $data['educations'] ?? [],
            'experiences' => $data['experiences'] ?? [],
            'documents' => $data['documents'] ?? [],
        ];

        unset($data['families'], $data['educations'], $data['experiences'], $data['documents']);
        unset($data['photo']);

        return $details;
    }

    private function persistDetails(Employee $employee, Request $request, array $details, bool $replace = false): void
    {
        if ($replace) {
            $employee->families()->delete();
            $employee->educations()->delete();
            $employee->experiences()->delete();
        }

        foreach ($details['families'] as $family) {
            $employee->families()->create([
                'name' => $family['name'],
                'nik' => $family['nik'] ?? null,
                'relationship' => $family['relationship'],
                'birth_date' => $family['birth_date'] ?? null,
                'occupation' => $family['occupation'] ?? null,
                'phone' => $family['phone'] ?? null,
                'address' => $family['address'] ?? null,
                'is_dependent' => ! empty($family['is_dependent']),
            ]);
        }

        foreach ($details['educations'] as $education) {
            $employee->educations()->create($education);
        }

        foreach ($details['experiences'] as $experience) {
            $employee->experiences()->create($experience);
        }

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $employee->update(['photo' => $request->file('photo')->store('employees/photos', 'public')]);
        }

        foreach ($details['documents'] as $index => $document) {
            $file = $request->file("documents.{$index}.file");

            if (! $file) {
                continue;
            }

            $employee->documents()->create([
                'category' => $document['category'],
                'name' => $document['name'],
                'file_path' => $file->store('employees/documents', 'private'),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'expiry_date' => $document['expiry_date'] ?? null,
                'uploaded_by' => $request->user()?->id,
            ]);
        }
    }

    private function nextEmployeeNumber(): string
    {
        $last = Employee::query()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderByRaw('CAST(SUBSTRING(employee_number, 5) AS UNSIGNED) DESC')
            ->value('employee_number');
        $number = $last ? ((int) str_replace('EMP-', '', $last)) + 1 : 1;

        while (Employee::where('employee_number', 'EMP-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT))->exists()) {
            $number++;
        }

        return 'EMP-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
