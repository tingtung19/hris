@extends('layouts.app')

@section('title', 'Organization Chart')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">Organization Chart</h1><a href="{{ route('organization.index','companies') }}" class="btn btn-outline-secondary">Master organisasi</a></div>
@forelse($companies as $company)<div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h5">{{ $company->name }} <small class="text-muted">({{ $company->code }})</small></h2>@forelse($company->branches as $branch)<div class="border-start ps-3 ms-2 mb-2"><strong>{{ $branch->name }}</strong>@forelse($branch->departments as $department)<div class="ms-3 text-muted">- {{ $department->name }}</div>@empty<div class="ms-3 small text-muted">Belum ada departemen.</div>@endforelse</div>@empty<p class="text-muted mb-0">Belum ada cabang.</p>@endforelse</div></div>@empty<div class="alert alert-info">Belum ada data perusahaan.</div>@endforelse
@endsection
