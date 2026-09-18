<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('ui-assets/css/app.css') }}" rel="stylesheet">
</head>
<body>
    @auth
        @php($user = auth()->user())
        <div class="app-shell">
            <aside class="sidebar" id="appSidebar">
                <div class="sidebar-brand">
                    <div class="logo-badge"><i class="bi bi-buildings"></i></div>
                    <span class="brand-text">HRIS Portal</span>
                </div>
                <nav class="sidebar-nav">
                    <div class="nav-section-title">Menu utama</div>
                    <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i><span class="nav-label">Dashboard</span></a>
                    @if($user->hasPermission('employee.view'))
                        <a class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i><span class="nav-label">Karyawan</span></a>
                    @endif
                    @if($user->hasPermission('organization.view'))
                        <a class="nav-item {{ request()->routeIs('organization.*') ? 'active' : '' }}" href="{{ route('organization.chart') }}"><i class="bi bi-diagram-3"></i><span class="nav-label">Organisasi</span></a>
                    @endif
                    @if($user->hasPermission('attendance.view'))
                        <a class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.today') }}"><i class="bi bi-calendar-check"></i><span class="nav-label">Attendance</span></a>
                    @endif
                    @if($user->hasPermission('leave.view'))
                        <a class="nav-item {{ request()->routeIs('leave.*') ? 'active' : '' }}" href="{{ route('leave.index') }}"><i class="bi bi-calendar2-heart"></i><span class="nav-label">Cuti</span></a>
                    @endif
                    @if($user->hasPermission('payroll.view'))
                        <a class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.index') }}"><i class="bi bi-wallet2"></i><span class="nav-label">Payroll</span></a>
                    @endif
                    @if($user->hasPermission('recruitment.view'))
                        <a class="nav-item {{ request()->routeIs('recruitment.*') ? 'active' : '' }}" href="{{ route('recruitment.vacancies') }}"><i class="bi bi-person-badge"></i><span class="nav-label">Recruitment</span></a>
                    @endif
                    <div class="nav-section-title">Manajemen</div>
                    @foreach([
                        ['onboarding', 'onboarding.view', 'onboarding.index', 'Onboarding', 'bi-person-plus'],
                        ['offboarding', 'offboarding.view', 'offboarding.index', 'Offboarding', 'bi-person-dash'],
                        ['kpi', 'performance.view', 'kpi.dashboard', 'KPI & Performance', 'bi-graph-up-arrow'],
                        ['training', 'training.view', 'training.index', 'Training', 'bi-mortarboard'],
                        ['assets', 'asset.view', 'assets.index', 'Asset Management', 'bi-box-seam'],
                        ['business-trips', 'business_trip.view', 'business-trips.index', 'Business Trip', 'bi-airplane'],
                        ['reimbursements', 'reimbursement.view', 'reimbursements.index', 'Reimbursement', 'bi-receipt'],
                        ['announcements', 'announcement.view', 'announcements.index', 'Announcements', 'bi-megaphone'],
                    ] as [$module, $permission, $route, $label, $icon])
                        @if($user->hasPermission($permission))
                            <a class="nav-item {{ request()->routeIs($module.'.*') ? 'active' : '' }}" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i><span class="nav-label">{{ $label }}</span></a>
                        @endif
                    @endforeach
                    @if($user->hasPermission('report.view') || $user->hasPermission('audit.view') || $user->hasPermission('system.manage'))
                        <div class="nav-section-title">Administrasi</div>
                    @endif
                    @if($user->hasPermission('report.view'))
                        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart"></i><span class="nav-label">Reports</span></a>
                    @endif
                    @if($user->hasPermission('audit.view'))
                        <a class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><i class="bi bi-shield-check"></i><span class="nav-label">Audit Log</span></a>
                    @endif
                    @if($user->hasPermission('system.manage'))
                        <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="bi bi-gear"></i><span class="nav-label">Settings</span></a>
                    @endif
                </nav>
            </aside>
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
            <div class="main-wrap" id="appMainWrap">
                <header class="topbar">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light d-lg-none" id="mobileSidebarToggle" type="button" aria-label="Buka menu"><i class="bi bi-list fs-5"></i></button>
                        <button class="btn btn-light d-none d-lg-inline-flex" id="sidebarToggle" type="button" aria-label="Ciutkan menu"><i class="bi bi-layout-sidebar"></i></button>
                        <div><div class="fw-semibold">@yield('title', 'Dashboard')</div><small class="text-secondary-soft">Sistem Informasi Kepegawaian</small></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        @if($user->hasPermission('announcement.view'))
                            <a class="text-secondary-soft" href="{{ route('notifications.index') }}" title="Notifikasi"><i class="bi bi-bell fs-5"></i></a>
                        @endif
                        <div class="avatar-circle">{{ strtoupper(substr($user->username, 0, 1)) }}</div>
                        <div class="d-none d-md-block"><div class="fw-semibold small">{{ $user->username }}</div><div class="text-secondary-soft" style="font-size:11px;">{{ $user->email }}</div></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm" title="Keluar"><i class="bi bi-box-arrow-right"></i></button></form>
                    </div>
                </header>
                <main class="content-area">
                    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        @yield('content')
    @endauth
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('ui-assets/js/app.js') }}"></script>
</body>
</html>
