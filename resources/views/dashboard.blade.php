@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Selamat datang kembali, {{ auth()->user()->username }}</h1>
            <p class="text-secondary-soft mb-0">Berikut ringkasan kondisi HRIS hari ini.</p>
        </div>
        <span class="badge-soft bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Sistem aktif</span>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="kpi-card h-100"><div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div><div><div class="kpi-label">Karyawan aktif</div><div class="kpi-value">{{ number_format($employeeCount) }}</div><span class="small text-success"><i class="bi bi-activity"></i> Data terkini</span></div></div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card h-100"><div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-calendar2-heart"></i></div><div><div class="kpi-label">Pengajuan cuti pending</div><div class="kpi-value">{{ number_format($pendingLeaves) }}</div><span class="small text-secondary-soft">Menunggu proses approval</span></div></div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card h-100"><div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-clock-history"></i></div><div><div class="kpi-label">Lembur pending</div><div class="kpi-value">{{ number_format($pendingOvertime) }}</div><span class="small text-secondary-soft">Menunggu verifikasi</span></div></div>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-lg-8"><div class="card-soft h-100"><div class="card-header d-flex justify-content-between align-items-center"><span>Aktivitas HRIS</span><span class="badge-soft bg-primary-subtle text-primary">Ringkasan</span></div><div class="card-body"><div class="d-flex align-items-center gap-3 py-2 border-bottom"><div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-shield-check"></i></div><div><div class="fw-semibold">Data terlindungi</div><div class="text-secondary-soft small">Akses modul mengikuti role dan permission akun Anda.</div></div></div><div class="d-flex align-items-center gap-3 py-3"><div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-database-check"></i></div><div><div class="fw-semibold">Database terhubung</div><div class="text-secondary-soft small">Sistem siap digunakan untuk operasional HR.</div></div></div></div></div></div>
        <div class="col-lg-4"><div class="card-soft h-100"><div class="card-header">Akses cepat</div><div class="card-body d-grid gap-2">@if(auth()->user()->hasPermission('employee.view'))<a href="{{ route('employees.index') }}" class="btn btn-light text-start"><i class="bi bi-people me-2 text-primary"></i>Kelola karyawan</a>@endif @if(auth()->user()->hasPermission('attendance.view'))<a href="{{ route('attendance.today') }}" class="btn btn-light text-start"><i class="bi bi-calendar-check me-2 text-success"></i>Lihat attendance</a>@endif @if(auth()->user()->hasPermission('report.view'))<a href="{{ route('reports.index') }}" class="btn btn-light text-start"><i class="bi bi-bar-chart me-2 text-info"></i>Buka reports</a>@endif</div></div></div>
    </div>
@endsection
