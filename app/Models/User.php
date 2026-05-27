<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    /**
     * `role` and `permissions` are intentionally NOT mass-assignable. Set them
     * via forceFill()/forceCreate() inside admin code that has already passed a
     * canDo() check, so no future controller that does User::create($request->all())
     * can silently grant elevated access.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'permissions' => 'array',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canAccessAdmin(): bool
    {
        return $this->role?->canAccessAdmin() ?? false;
    }

    /**
     * Permission keys granted directly on the user record, on top of role defaults.
     *
     * @return array<int, string>
     */
    public function extraPermissions(): array
    {
        return Permissions::sanitize($this->permissions ?? []);
    }

    /**
     * Permission keys granted by the user's role.
     *
     * @return array<int, string>
     */
    public function rolePermissions(): array
    {
        return $this->role?->defaultPermissions() ?? [];
    }

    /**
     * All effective permission keys (role defaults + explicit grants).
     *
     * @return array<int, string>
     */
    public function effectivePermissions(): array
    {
        return array_values(array_unique(array_merge(
            $this->rolePermissions(),
            $this->extraPermissions(),
        )));
    }

    public function hasPermission(string $key): bool
    {
        if ($this->role === UserRole::Owner) {
            return true;
        }

        return in_array($key, $this->effectivePermissions(), true);
    }

    public function canDo(string $resource, string $action): bool
    {
        return $this->hasPermission(Permissions::buildKey($resource, $action));
    }
}
