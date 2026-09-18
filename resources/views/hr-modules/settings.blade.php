@extends('layouts.app')
@section('title','Settings')
@section('content')
<h1 class="h3 mb-3">System Settings</h1>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card shadow-sm"><div class="card-body">@forelse($settings as $setting)<form method="POST" action="{{ route('settings.update',$setting->id) }}" class="row g-2 align-items-center mb-2">@csrf @method('PUT')<div class="col-md-3"><strong>{{ $setting->setting_key }}</strong><small class="d-block text-muted">{{ $setting->setting_group }}</small></div><div class="col-md-7"><input class="form-control" name="setting_value" value="{{ $setting->setting_value }}"></div><div class="col-md-2"><button class="btn btn-outline-primary">Simpan</button></div></form>@empty<p class="text-muted">Belum ada pengaturan.</p>@endforelse</div></div>
@endsection
