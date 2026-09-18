<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'employee_id',
    'username',
    'email',
    'password',
    'status',
    'failed_login_attempts',
    'locked_until',
    'remember_token',
    'password_reset_token',
    'password_reset_expires_at',
    'must_change_password',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
            'password_reset_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->hasRole('super-administrator')
            || $this->roles()
                ->whereHas('permissions', fn ($query) => $query->where('slug', $slug))
                ->exists();
    }
}
