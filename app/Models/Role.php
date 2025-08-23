<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the users that belong to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission($permission)
    {
        // For now, we'll use role-based permissions
        // You can extend this to use a permissions table later
        $permissions = [
            'admin' => ['*'],
            'moderator' => ['view_businesses', 'approve_businesses', 'view_users'],
            'user' => ['view_own_business', 'manage_own_ussd'],
        ];

        return in_array('*', $permissions[$this->name] ?? []) || 
               in_array($permission, $permissions[$this->name] ?? []);
    }
}
