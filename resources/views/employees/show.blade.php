@extends('layouts.app')

@section('title', 'Profil Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">{{ trim($employee->first_name.' '.$employee->last_name) }}</h1><p class="text-muted mb-0">{{ $employee->employee_number }}</p></div><div><a href="{{ route('employees.edit',$employee) }}" class="btn btn-outline-secondary">Edit</a><form class="d-inline" method="POST" action="{{ route('employees.destroy',$employee) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger" onclick="return confirm('Hapus data karyawan?')">Hapus</button></form></div></div>
<div class="card shadow-sm"><div class="card-body"><div class="row g-3">
@foreach(['employment_status'=>'Status','employment_type'=>'Tipe','phone'=>'Telepon','personal_email'=>'Email','join_date'=>'Tanggal bergabung','gender'=>'Jenis kelamin','marital_status'=>'Status pernikahan'] as $field=>$label)<div class="col-md-4"><div class="text-muted small">{{ $label }}</div><div class="fw-semibold">{{ $field === 'join_date' ? optional($employee->$field)->format('d M Y') : ucfirst($employee->$field ?? '-') }}</div></div>@endforeach
</div></div></div>
@endsection
