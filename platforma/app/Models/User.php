<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** Role => list of permissions. "*" grants everything. */
    public const ROLES = [
        'admin' => ['*'],
        'editor' => ['dashboard', 'content', 'media', 'seo', 'ai.knowledge', 'quotes.view'],
        'sales' => ['dashboard', 'quotes.view', 'quotes.manage', 'quotes.export', 'quotes.files'],
        'viewer' => ['dashboard', 'quotes.view'],
    ];

    public const ROLE_LABELS = [
        'admin' => 'Administrator',
        'editor' => 'Editor conținut',
        'sales' => 'Vânzări / ofertare',
        'viewer' => 'Doar vizualizare',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

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

    public function can_($permission): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $perms = self::ROLES[$this->role] ?? [];

        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }
}
