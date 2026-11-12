<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'locale', 'phone', 'job_title'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /** @return array<string,string> role key → label */
    public static function roleLabels(): array
    {
        return array_map(fn ($r) => $r['label'], config('permissions.roles'));
    }

    public function permissions(): array
    {
        return config('permissions.roles.'.$this->role.'.permissions', []);
    }

    /** Server-side permission check used by routes, controllers and views. */
    public function can_($permission): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $perms = $this->permissions();

        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /** Administrators (both levels) – used by the inherited "last administrator" safeguards. */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin'], true);
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function roleLabel(): string
    {
        return config('permissions.roles.'.$this->role.'.label', $this->role);
    }
}
