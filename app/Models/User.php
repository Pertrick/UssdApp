<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\UserActivity;
use App\Enums\UserRole;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the businesses owned by this user.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Get the primary business for this user.
     */
    public function primaryBusiness()
    {
        return $this->hasOne(Business::class)->where('is_primary', true);
    }

    /**
     * Get the USSDs owned by this user.
     */
    public function ussds(): HasMany
    {
        return $this->hasMany(USSD::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Get the roles that belong to this user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', (array) $roles)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles($roles)
    {
        return $this->roles()->whereIn('name', (array) $roles)->count() === count((array) $roles);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission)
    {
        return $this->roles()->get()->some(function ($role) use ($permission) {
            return $role->hasPermission($permission);
        });
    }

    /**
     * Assign a role to the user
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }
        
        if ($role && !$this->hasRole($role->name)) {
            $this->roles()->attach($role);
        }
        
        return $this;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }
        
        if ($role) {
            $this->roles()->detach($role);
        }
        
        return $this;
    }

    /**
     * Check if user has a specific role using enum
     */
    public function hasRoleEnum(UserRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * Check if user has admin role (admin or moderator)
     */
    public function isAdmin(): bool
    {
        return $this->roles()->whereIn('name', [
            UserRole::ADMIN->value,
            UserRole::MODERATOR->value
        ])->exists();
    }

    /**
     * Check if user is a regular user
     */
    public function isRegularUser(): bool
    {
        return $this->hasRole(UserRole::USER->value);
    }

    /**
     * Assign a role to the user using enum
     */
    public function assignRoleEnum(UserRole $role)
    {
        return $this->assignRole($role->value);
    }

    /**
     * Remove a role from the user using enum
     */
    public function removeRoleEnum(UserRole $role)
    {
        return $this->removeRole($role->value);
    }
}
