<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('ui-assets/css/app.css') }}" rel="stylesheet">
</head>
<body>
    <main class="auth-shell px-3">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="sidebar-brand justify-content-center border-0 p-0 mb-3"><div class="logo-badge"><i class="bi bi-buildings"></i></div></div>
                <h1 class="h4 mb-1 fw-bold">{{ config('app.name') }}</h1>
                <p class="text-secondary-soft mb-0">Masuk ke sistem informasi kepegawaian</p>
            </div>
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="login" class="form-label">Username atau email</label>
                        <input id="login" name="login" type="text" class="form-control" value="{{ old('login') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" name="password" type="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input id="remember" name="remember" type="checkbox" class="form-check-input">
                        <label for="remember" class="form-check-label">Ingat saya</label>
                    </div>
                    <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke dashboard</button>
                </form>
        </div>
    </main>
</body>
</html>
