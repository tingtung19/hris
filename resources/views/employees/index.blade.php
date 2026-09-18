@extends('layouts.app')

@section('title', 'Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-1">Karyawan</h1><p class="text-muted mb-0">Daftar dan pengelolaan data karyawan.</p></div>
    <a href="{{ route('employees.create') }}" class="btn btn-primary">Tambah Karyawan</a>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3">
            <div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari nama, NIK, atau ID karyawan"></div>
            <div class="col-md-3"><select class="form-select" name="department_id"><option value="">Semua departemen</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="employment_status"><option value="">Semua status</option>@foreach(['active'=>'Aktif','probation'=>'Probation','resigned'=>'Resign','terminated'=>'Terminated'] as $value => $label)<option value="{{ $value }}" @selected(request('employment_status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>ID</th><th>Nama</th><th>Departemen</th><th>Jabatan</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($employees as $employee)
                <tr><td>{{ $employee->employee_number }}</td><td>{{ trim($employee->first_name.' '.$employee->last_name) }}</td><td>{{ $employee->department?->name ?? '-' }}</td><td>{{ $employee->position?->name ?? '-' }}</td><td><span class="badge text-bg-{{ $employee->employment_status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->employment_status) }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('employees.show', $employee) }}">Lihat</a> <a class="btn btn-sm btn-outline-secondary" href="{{ route('employees.edit', $employee) }}">Edit</a></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada karyawan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $employees->links() }}
    </div>
</div>
@endsection
