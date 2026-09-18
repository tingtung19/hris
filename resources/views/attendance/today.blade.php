@extends('layouts.app')
@section('title','Absensi Hari Ini')
@section('content')
<h1 class="h3 mb-3">Absensi Hari Ini</h1>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card shadow-sm"><div class="card-body"><p>Status: <strong>{{ $attendance?->status ?? 'Belum absensi' }}</strong></p><p>Clock in: {{ $attendance?->clock_in ?? '-' }} | Clock out: {{ $attendance?->clock_out ?? '-' }}</p><div class="d-flex gap-2">@if(!$attendance)<form method="POST" action="{{ route('attendance.clock-in') }}">@csrf<button class="btn btn-primary">Clock in</button></form>@elseif(!$attendance->clock_out)<form method="POST" action="{{ route('attendance.clock-out') }}">@csrf<button class="btn btn-primary">Clock out</button></form>@endif</div></div></div>
@endsection
