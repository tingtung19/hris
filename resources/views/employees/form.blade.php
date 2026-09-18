@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">{{ $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h1><a href="{{ route('employees.index') }}" class="btn btn-light">Kembali</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
@csrf @if($employee->exists) @method('PUT') @endif
<div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h5 mb-3">Informasi pribadi</h2><div class="row g-3">
@foreach([['first_name','Nama depan','required'],['last_name','Nama belakang',''],['nik','NIK',''],['birth_place','Tempat lahir',''],['phone','No. telepon',''],['personal_email','Email pribadi','']] as [$name,$label,$required])
<div class="col-md-4"><label class="form-label">{{ $label }}</label><input name="{{ $name }}" value="{{ old($name, $employee->$name) }}" class="form-control" {{ $required }}></div>
@endforeach
<div class="col-md-4"><label class="form-label">Tanggal lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Jenis kelamin</label><select name="gender" class="form-select">@foreach(['male'=>'Laki-laki','female'=>'Perempuan'] as $value=>$label)<option value="{{ $value }}" @selected(old('gender',$employee->gender)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Status pernikahan</label><select name="marital_status" class="form-select">@foreach(['single'=>'Belum menikah','married'=>'Menikah','divorced'=>'Cerai','widowed'=>'Janda/Duda'] as $value=>$label)<option value="{{ $value }}" @selected(old('marital_status',$employee->marital_status)===$value)>{{ $label }}</option>@endforeach</select></div>
</div></div></div>
<div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h5 mb-3">Informasi kepegawaian</h2><div class="row g-3">
<div class="col-md-4"><label class="form-label">Company ID</label><input type="number" name="company_id" value="{{ old('company_id',$employee->company_id) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Departemen</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $item)<option value="{{ $item->id }}" @selected(old('department_id',$employee->department_id)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Jabatan</label><select name="position_id" class="form-select"><option value="">-</option>@foreach($positions as $item)<option value="{{ $item->id }}" @selected(old('position_id',$employee->position_id)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Tanggal bergabung</label><input type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date)->format('Y-m-d')) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="employment_status" class="form-select">@foreach(['active'=>'Aktif','probation'=>'Probation','resigned'=>'Resign','terminated'=>'Terminated'] as $value=>$label)<option value="{{ $value }}" @selected(old('employment_status',$employee->employment_status)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Tipe</label><select name="employment_type" class="form-select">@foreach(['permanent'=>'Tetap','contract'=>'Kontrak','intern'=>'Magang','daily'=>'Harian','freelance'=>'Freelance'] as $value=>$label)<option value="{{ $value }}" @selected(old('employment_type',$employee->employment_type)===$value)>{{ $label }}</option>@endforeach</select></div>
</div></div></div>
<button class="btn btn-primary">Simpan</button>
</form>
@endsection
