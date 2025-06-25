<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\UserActivity;
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
}
