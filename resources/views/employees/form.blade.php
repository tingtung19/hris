@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan')

@section('content')
@php
    $families = old('families', $employee->families?->toArray() ?? []);
    $educations = old('educations', $employee->educations?->toArray() ?? []);
    $experiences = old('experiences', $employee->experiences?->toArray() ?? []);
    $tabErrorKeys = [
        'personal' => ['photo', 'first_name', 'last_name', 'nik', 'birth_place', 'birth_date', 'gender', 'marital_status', 'religion', 'phone', 'personal_email', 'nip', 'company_id', 'department_id', 'position_id', 'join_date', 'employment_status', 'employment_type'],
        'family' => ['families'],
        'education' => ['educations'],
        'experience' => ['experiences'],
        'documents' => ['documents'],
    ];
    $hasTabErrors = fn (array $keys): bool => collect($errors->keys())->contains(
        fn (string $errorKey): bool => collect($keys)->contains(
            fn (string $key): bool => $errorKey === $key || str_starts_with($errorKey, $key.'.')
        )
    );
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">{{ $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h1>
    <a href="{{ route('employees.index') }}" class="btn btn-light">Kembali</a>
</div>
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Data belum dapat disimpan.</strong> Periksa tab yang ditandai merah.
        <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<div id="client-validation-alert" class="alert alert-warning d-none" role="alert">
    <strong>Data belum lengkap.</strong> Lengkapi field wajib pada tab yang ditandai merah sebelum menyimpan.
</div>
<form method="POST" enctype="multipart/form-data" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
@csrf @if($employee->exists) @method('PUT') @endif
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal" type="button">Informasi Pribadi <span class="badge text-bg-danger {{ $hasTabErrors($tabErrorKeys['personal']) ? '' : 'd-none' }}" data-error-badge="personal">!</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#family" type="button">Data Keluarga <span class="badge text-bg-danger {{ $hasTabErrors($tabErrorKeys['family']) ? '' : 'd-none' }}" data-error-badge="family">!</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education" type="button">Riwayat Pendidikan <span class="badge text-bg-danger {{ $hasTabErrors($tabErrorKeys['education']) ? '' : 'd-none' }}" data-error-badge="education">!</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#experience" type="button">Riwayat Pekerjaan <span class="badge text-bg-danger {{ $hasTabErrors($tabErrorKeys['experience']) ? '' : 'd-none' }}" data-error-badge="experience">!</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents" type="button">File Pendukung <span class="badge text-bg-danger {{ $hasTabErrors($tabErrorKeys['documents']) ? '' : 'd-none' }}" data-error-badge="documents">!</span></button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="personal">
<div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h5 mb-3">Informasi pribadi</h2><div class="row g-3">
<div class="col-md-3 text-center">
    @if($employee->photo)<img src="{{ asset('storage/'.$employee->photo) }}" class="rounded-circle mb-2" style="width:130px;height:130px;object-fit:cover" alt="Foto karyawan">@else<div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-2" style="width:130px;height:130px"><i class="bi bi-person fs-1"></i></div>@endif
    <label class="form-label">Foto karyawan</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp"><small class="text-muted">JPG, PNG, WEBP, maksimal 2 MB.</small>
</div>
<div class="col-md-9"><div class="row g-3">
@foreach([['first_name','Nama depan','required'],['last_name','Nama belakang',''],['nik','NIK',''],['birth_place','Tempat lahir',''],['phone','No. telepon',''],['personal_email','Email pribadi','']] as [$name,$label,$required])
<div class="col-md-4"><label class="form-label">{{ $label }}</label><input name="{{ $name }}" value="{{ old($name, $employee->$name) }}" class="form-control" {{ $required }}></div>
@endforeach
<div class="col-md-4"><label class="form-label">Tanggal lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Jenis kelamin</label><select name="gender" class="form-select">@foreach(['male'=>'Laki-laki','female'=>'Perempuan'] as $value=>$label)<option value="{{ $value }}" @selected(old('gender',$employee->gender)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Status pernikahan</label><select name="marital_status" class="form-select">@foreach(['single'=>'Belum menikah','married'=>'Menikah','divorced'=>'Cerai','widowed'=>'Janda/Duda'] as $value=>$label)<option value="{{ $value }}" @selected(old('marital_status',$employee->marital_status)===$value)>{{ $label }}</option>@endforeach</select></div>
</div></div></div></div></div>
<div class="card shadow-sm mb-3" id="employment-section"><div class="card-body"><h2 class="h5 mb-3">Informasi kepegawaian</h2><div class="row g-3">
<div class="col-md-4"><label class="form-label">NIP</label><input name="nip" value="{{ old('nip',$employee->nip) }}" class="form-control" maxlength="50" placeholder="Nomor Induk Pegawai"><small class="text-muted">Diisi manual dan harus unik.</small></div>
<div class="col-md-4"><label class="form-label">Company ID</label><input type="number" name="company_id" value="{{ old('company_id',$employee->company_id) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Departemen</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $item)<option value="{{ $item->id }}" @selected(old('department_id',$employee->department_id)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Jabatan</label><select name="position_id" class="form-select"><option value="">-</option>@foreach($positions as $item)<option value="{{ $item->id }}" @selected(old('position_id',$employee->position_id)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Tanggal bergabung</label><input type="date" name="join_date" value="{{ old('join_date', optional($employee->join_date)->format('Y-m-d')) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="employment_status" class="form-select">@foreach(['active'=>'Aktif','probation'=>'Probation','resigned'=>'Resign','terminated'=>'Terminated'] as $value=>$label)<option value="{{ $value }}" @selected(old('employment_status',$employee->employment_status)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Tipe</label><select name="employment_type" class="form-select">@foreach(['permanent'=>'Tetap','contract'=>'Kontrak','intern'=>'Magang','daily'=>'Harian','freelance'=>'Freelance'] as $value=>$label)<option value="{{ $value }}" @selected(old('employment_type',$employee->employment_type)===$value)>{{ $label }}</option>@endforeach</select></div>
</div></div></div>
</div>
<div class="tab-pane fade" id="family"><div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h2 class="h5">Data keluarga</h2><button type="button" class="btn btn-sm btn-outline-primary" data-add-row="family">Tambah anggota</button></div><div id="family-rows">
@foreach($families as $index=>$family)<div class="row g-2 mb-2 repeater-row"><div class="col-md-2"><input name="families[{{ $index }}][name]" value="{{ $family['name'] ?? '' }}" class="form-control" placeholder="Nama" required></div><div class="col-md-2"><input name="families[{{ $index }}][nik]" value="{{ $family['nik'] ?? '' }}" class="form-control" placeholder="NIK"></div><div class="col-md-2"><select name="families[{{ $index }}][relationship]" class="form-select"><option value="spouse" @selected(($family['relationship'] ?? '')==='spouse')>Istri/Suami</option><option value="child" @selected(($family['relationship'] ?? 'child')==='child')>Anak</option><option value="other" @selected(($family['relationship'] ?? '')==='other')>Lainnya</option></select></div><div class="col-md-2"><input type="date" name="families[{{ $index }}][birth_date]" value="{{ $family['birth_date'] ?? '' }}" class="form-control"></div><div class="col-md-2"><input name="families[{{ $index }}][phone]" value="{{ $family['phone'] ?? '' }}" class="form-control" placeholder="No. telepon"></div><div class="col-md-2"><input name="families[{{ $index }}][occupation]" value="{{ $family['occupation'] ?? '' }}" class="form-control" placeholder="Pekerjaan"></div><div class="col-md-10"><textarea name="families[{{ $index }}][address]" class="form-control" placeholder="Alamat">{{ $family['address'] ?? '' }}</textarea></div><div class="col-md-1 form-check pt-2"><input type="hidden" name="families[{{ $index }}][is_dependent]" value="0"><input type="checkbox" name="families[{{ $index }}][is_dependent]" value="1" class="form-check-input" @checked(!empty($family['is_dependent']))> Tanggungan</div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>@endforeach
</div><p class="text-muted small mb-0">Tambahkan istri/suami, anak, atau anggota keluarga lainnya.</p></div></div></div>
<div class="tab-pane fade" id="education"><div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h2 class="h5">Riwayat pendidikan</h2><button type="button" class="btn btn-sm btn-outline-primary" data-add-row="education">Tambah pendidikan</button></div><div id="education-rows">@foreach($educations as $index=>$education)<div class="row g-2 mb-2 repeater-row"><div class="col-md-2"><select name="educations[{{ $index }}][level]" class="form-select">@foreach(['sd'=>'SD','smp'=>'SMP','sma'=>'SMA/SMK','d3'=>'D3','s1'=>'S1','s2'=>'S2','s3'=>'S3'] as $value=>$label)<option value="{{ $value }}" @selected(($education['level'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-3"><input name="educations[{{ $index }}][school_name]" value="{{ $education['school_name'] ?? '' }}" class="form-control" placeholder="Nama sekolah/universitas" required></div><div class="col-md-3"><input name="educations[{{ $index }}][major]" value="{{ $education['major'] ?? '' }}" class="form-control" placeholder="Jurusan"></div><div class="col-md-2"><input type="number" name="educations[{{ $index }}][graduation_year]" value="{{ $education['graduation_year'] ?? '' }}" class="form-control" placeholder="Tahun lulus"></div><div class="col-md-1"><input type="number" step="0.01" name="educations[{{ $index }}][gpa]" value="{{ $education['gpa'] ?? '' }}" class="form-control" placeholder="IPK"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>@endforeach</div></div></div></div>
<div class="tab-pane fade" id="experience"><div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h2 class="h5">Riwayat pekerjaan</h2><button type="button" class="btn btn-sm btn-outline-primary" data-add-row="experience">Tambah pengalaman</button></div><div id="experience-rows">@foreach($experiences as $index=>$experience)<div class="row g-2 mb-2 repeater-row"><div class="col-md-3"><input name="experiences[{{ $index }}][company_name]" value="{{ $experience['company_name'] ?? '' }}" class="form-control" placeholder="Nama perusahaan" required></div><div class="col-md-2"><input name="experiences[{{ $index }}][position]" value="{{ $experience['position'] ?? '' }}" class="form-control" placeholder="Jabatan"></div><div class="col-md-2"><input type="date" name="experiences[{{ $index }}][start_date]" value="{{ $experience['start_date'] ?? '' }}" class="form-control"></div><div class="col-md-2"><input type="date" name="experiences[{{ $index }}][end_date]" value="{{ $experience['end_date'] ?? '' }}" class="form-control"></div><div class="col-md-2"><input name="experiences[{{ $index }}][description]" value="{{ $experience['description'] ?? '' }}" class="form-control" placeholder="Keterangan"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>@endforeach</div></div></div></div>
<div class="tab-pane fade" id="documents"><div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h2 class="h5">File pendukung</h2><button type="button" class="btn btn-sm btn-outline-primary" data-add-row="document">Tambah file</button></div><div id="document-rows"></div>@if($employee->exists && $employee->documents->isNotEmpty())<h3 class="h6 mt-4">File tersimpan</h3><ul class="list-group">@foreach($employee->documents as $document)<li class="list-group-item d-flex justify-content-between"><span>{{ $document->name }} <small class="text-muted">({{ strtoupper($document->category) }})</small></span><a href="{{ route('employees.documents.download', [$employee, $document]) }}" class="btn btn-sm btn-outline-secondary">Unduh</a></li>@endforeach</ul>@endif</div></div></div>
</div>
<button class="btn btn-primary mt-3">Simpan</button>
</form>
@endsection

@push('scripts')
<script>
    const form = document.querySelector('form[action*="/employees"]');
    const tabForPane = {
        personal: 'personal',
        'employment-section': 'personal',
        family: 'family',
        education: 'education',
        experience: 'experience',
        documents: 'documents'
    };
    const openErrorTab = (pane) => {
        const tabName = tabForPane[pane] || pane;
        const tabButton = document.querySelector(`[data-bs-target="#${tabName}"]`);
        const badge = document.querySelector(`[data-error-badge="${tabName}"]`);

        if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
        if (badge) badge.classList.remove('d-none');
        document.getElementById('client-validation-alert')?.classList.remove('d-none');
    };

    form.addEventListener('invalid', (event) => {
        const pane = event.target.closest('.tab-pane');

        if (!pane) return;

        event.preventDefault();
        openErrorTab(pane.id);
        event.target.focus({ preventScroll: true });
        event.target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, true);

    form.addEventListener('submit', (event) => {
        const invalidField = form.querySelector(':invalid');

        if (!invalidField) return;

        event.preventDefault();
        const pane = invalidField.closest('.tab-pane');

        if (pane) {
            openErrorTab(pane.id);
            invalidField.focus({ preventScroll: true });
            invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    const rowTemplates = {
        family: (i) => `<div class="row g-2 mb-2 repeater-row"><div class="col-md-2"><input name="families[${i}][name]" class="form-control" placeholder="Nama" required></div><div class="col-md-2"><input name="families[${i}][nik]" class="form-control" placeholder="NIK"></div><div class="col-md-2"><select name="families[${i}][relationship]" class="form-select"><option value="spouse">Istri/Suami</option><option value="child">Anak</option><option value="other">Lainnya</option></select></div><div class="col-md-2"><input type="date" name="families[${i}][birth_date]" class="form-control"></div><div class="col-md-2"><input name="families[${i}][phone]" class="form-control" placeholder="No. telepon"></div><div class="col-md-2"><input name="families[${i}][occupation]" class="form-control" placeholder="Pekerjaan"></div><div class="col-md-10"><textarea name="families[${i}][address]" class="form-control" placeholder="Alamat"></textarea></div><div class="col-md-1 form-check pt-2"><input type="hidden" name="families[${i}][is_dependent]" value="0"><input type="checkbox" name="families[${i}][is_dependent]" value="1" class="form-check-input"> Tanggungan</div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>`,
        education: (i) => `<div class="row g-2 mb-2 repeater-row"><div class="col-md-2"><select name="educations[${i}][level]" class="form-select"><option value="sd">SD</option><option value="smp">SMP</option><option value="sma">SMA/SMK</option><option value="d3">D3</option><option value="s1">S1</option><option value="s2">S2</option><option value="s3">S3</option></select></div><div class="col-md-3"><input name="educations[${i}][school_name]" class="form-control" placeholder="Nama sekolah/universitas" required></div><div class="col-md-3"><input name="educations[${i}][major]" class="form-control" placeholder="Jurusan"></div><div class="col-md-2"><input type="number" name="educations[${i}][graduation_year]" class="form-control" placeholder="Tahun lulus"></div><div class="col-md-1"><input type="number" step="0.01" name="educations[${i}][gpa]" class="form-control" placeholder="IPK"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>`,
        experience: (i) => `<div class="row g-2 mb-2 repeater-row"><div class="col-md-3"><input name="experiences[${i}][company_name]" class="form-control" placeholder="Nama perusahaan" required></div><div class="col-md-2"><input name="experiences[${i}][position]" class="form-control" placeholder="Jabatan"></div><div class="col-md-2"><input type="date" name="experiences[${i}][start_date]" class="form-control"></div><div class="col-md-2"><input type="date" name="experiences[${i}][end_date]" class="form-control"></div><div class="col-md-2"><input name="experiences[${i}][description]" class="form-control" placeholder="Keterangan"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>`,
        document: (i) => `<div class="row g-2 mb-2 repeater-row"><div class="col-md-3"><select name="documents[${i}][category]" class="form-select"><option value="ktp">KTP</option><option value="kk">KK</option><option value="npwp">NPWP</option><option value="bpjs">BPJS</option><option value="ijazah">Ijazah</option><option value="certificate">Sertifikat</option><option value="cv">CV</option><option value="contract">Kontrak</option><option value="appointment_letter">Surat pengangkatan</option><option value="promotion_letter">Surat promosi</option><option value="mutation_letter">Surat mutasi</option><option value="other">Lainnya</option></select></div><div class="col-md-3"><input name="documents[${i}][name]" class="form-control" placeholder="Nama file" required></div><div class="col-md-3"><input type="file" name="documents[${i}][file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required></div><div class="col-md-2"><input type="date" name="documents[${i}][expiry_date]" class="form-control"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row">×</button></div></div>`
    };
    const counters = { family: {{ count($families) }}, education: {{ count($educations) }}, experience: {{ count($experiences) }}, document: 0 };
    document.querySelectorAll('[data-add-row]').forEach((button) => button.addEventListener('click', () => {
        const type = button.dataset.addRow;
        const container = document.getElementById(`${type}-rows`);
        container.insertAdjacentHTML('beforeend', rowTemplates[type](counters[type]++));
    }));
    document.addEventListener('click', (event) => {
        if (event.target.closest('.remove-row')) event.target.closest('.repeater-row').remove();
    });
</script>
@endpush
