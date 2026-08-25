<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'users_code',
        'name',
        'email',
        'password',
        'signature_path',
        'signature_name',
    ];

    public function roleAssignments()
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    public function hasRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $this->roleAssignments()->where('role', $value)->exists();
    }

    /** Sinkronkan role user (hapus semua role lama, ganti dengan yang baru). */
    public function syncRoles(array $roles): void
    {
        $this->roleAssignments()->delete();
        foreach (array_unique($roles) as $role) {
            $this->roleAssignments()->create(['role' => $role]);
        }
    }

    /** Array string value role, mis. ['asesor', 'manager_sertifikasi']. */
    public function roleValues(): array
    {
        return $this->roleAssignments()->pluck('role')->map(fn ($r) => $r->value)->all();
    }

    public function isAsesor(): bool
    {
        return $this->hasRole(UserRole::Asesor);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isManagerSertifikasi(): bool
    {
        return $this->hasRole(UserRole::ManagerSertifikasi);
    }

    public function assignments()
    {
        return $this->hasMany(\App\Models\AsesorAssignment::class, 'user_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'users_code',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'assessed_at'       => 'datetime',
        ];
    }
}
