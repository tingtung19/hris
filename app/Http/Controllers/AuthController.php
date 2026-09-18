<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditService $audit)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users')
            ->where('username', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->first();

        if ($user && $user->locked_until && now()->lessThan($user->locked_until)) {
            return back()->withErrors(['login' => 'Akun terkunci sementara. Silakan coba lagi nanti.'])->onlyInput('login');
        }

        if (! $user || $user->status !== 'active' || ! Hash::check($credentials['password'], $user->password)) {
            $audit->record('login_failed', 'auth', $user->id ?? null, 'Percobaan login gagal.', null, ['login' => $credentials['login']], $request);
            if ($user) {
                $attempts = (int) $user->failed_login_attempts + 1;
                DB::table('users')->where('id', $user->id)->update([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= 5 ? now()->addMinutes(15) : null,
                    'updated_at' => now(),
                ]);
            }

            return back()->withErrors(['login' => 'Username/email atau password salah.'])->onlyInput('login');
        }

        Auth::loginUsingId($user->id, $request->boolean('remember'));
        $request->session()->regenerate();

        DB::table('users')->where('id', $user->id)->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'updated_at' => now(),
        ]);
        $audit->record('login', 'auth', $user->id, 'Login berhasil.', null, ['username' => $user->username], $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
