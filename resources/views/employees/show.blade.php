@extends('layouts.app')

@section('title', 'Profil Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        @if($employee->photo)<img src="{{ asset('storage/'.$employee->photo) }}" class="rounded-circle" style="width:72px;height:72px;object-fit:cover" alt="Foto karyawan">@else<div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center" style="width:72px;height:72px"><i class="bi bi-person fs-2"></i></div>@endif
        <div><h1 class="h3 mb-1">{{ trim($employee->first_name.' '.$employee->last_name) }}</h1><p class="text-muted mb-0">{{ $employee->employee_number }}</p></div>
    </div>
    <div><a href="{{ route('employees.edit',$employee) }}" class="btn btn-outline-secondary">Edit</a><form class="d-inline" method="POST" action="{{ route('employees.destroy',$employee) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger" onclick="return confirm('Hapus data karyawan?')">Hapus</button></form></div>
</div>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal" type="button">Informasi Pribadi & Kepegawaian</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#family" type="button">Data Keluarga</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education" type="button">Riwayat Pendidikan</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#experience" type="button">Riwayat Pekerjaan</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents" type="button">File Pendukung</button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="personal"><div class="card shadow-sm"><div class="card-body"><div class="row g-3">
@foreach(['nik'=>'NIK','birth_place'=>'Tempat lahir','birth_date'=>'Tanggal lahir','religion'=>'Agama','phone'=>'Telepon','personal_email'=>'Email','nip'=>'NIP','employment_status'=>'Status','employment_type'=>'Tipe','join_date'=>'Tanggal bergabung','gender'=>'Jenis kelamin','marital_status'=>'Status pernikahan'] as $field=>$label)
<div class="col-md-4"><div class="text-muted small">{{ $label }}</div><div class="fw-semibold">{{ in_array($field, ['birth_date','join_date'], true) ? optional($employee->$field)->format('d M Y') : ucfirst($employee->$field ?? '-') }}</div></div>
@endforeach
</div></div></div></div>
<div class="tab-pane fade" id="family"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Data keluarga</h2><div class="table-responsive"><table class="table"><thead><tr><th>Nama</th><th>NIK</th><th>Status</th><th>Tanggal lahir</th><th>No. telepon</th><th>Alamat</th><th>Pekerjaan</th><th>Tanggungan</th></tr></thead><tbody>@forelse($employee->families as $family)<tr><td>{{ $family->name }}</td><td>{{ $family->nik ?: '-' }}</td><td>{{ match($family->relationship) { 'spouse' => 'Istri/Suami', 'child' => 'Anak', default => 'Lainnya' } }}</td><td>{{ optional($family->birth_date)->format('d M Y') ?? '-' }}</td><td>{{ $family->phone ?: '-' }}</td><td>{{ $family->address ?: '-' }}</td><td>{{ $family->occupation ?: '-' }}</td><td>{{ $family->is_dependent ? 'Ya' : 'Tidak' }}</td></tr>@empty<tr><td colspan="8" class="text-muted">Belum ada data keluarga.</td></tr>@endforelse</tbody></table></div></div></div></div>
<div class="tab-pane fade" id="education"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Riwayat pendidikan</h2><div class="table-responsive"><table class="table"><thead><tr><th>Tingkat</th><th>Sekolah/Universitas</th><th>Jurusan</th><th>Tahun lulus</th><th>IPK</th></tr></thead><tbody>@forelse($employee->educations as $education)<tr><td>{{ strtoupper($education->level) }}</td><td>{{ $education->school_name }}</td><td>{{ $education->major ?: '-' }}</td><td>{{ $education->graduation_year ?: '-' }}</td><td>{{ $education->gpa ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="text-muted">Belum ada riwayat pendidikan.</td></tr>@endforelse</tbody></table></div></div></div></div>
<div class="tab-pane fade" id="experience"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Riwayat pekerjaan</h2><div class="table-responsive"><table class="table"><thead><tr><th>Perusahaan</th><th>Jabatan</th><th>Periode</th><th>Keterangan</th></tr></thead><tbody>@forelse($employee->experiences as $experience)<tr><td>{{ $experience->company_name }}</td><td>{{ $experience->position ?: '-' }}</td><td>{{ optional($experience->start_date)->format('d M Y') ?? '-' }} - {{ optional($experience->end_date)->format('d M Y') ?? 'Sekarang' }}</td><td>{{ $experience->description ?: '-' }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Belum ada riwayat pekerjaan.</td></tr>@endforelse</tbody></table></div></div></div></div>
<div class="tab-pane fade" id="documents"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">File pendukung</h2><div class="list-group">@forelse($employee->documents as $document)<a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="list-group-item list-group-item-action d-flex justify-content-between"><span>{{ $document->name }} <small class="text-muted">({{ strtoupper($document->category) }})</small></span><span>Unduh <i class="bi bi-download"></i></span></a>@empty<div class="text-muted">Belum ada file pendukung.</div>@endforelse</div></div></div></div>
</div>
@endsection
