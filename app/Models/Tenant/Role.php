<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'tenant';
    protected $table = 'role_master';
    
    protected $fillable = [
        'role_code',
        'role_name',
        'description',
        'is_active',
        'is_system_role',
        'created_by'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_system_role' => 'boolean',
    ];
    
    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }
    
    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
    
    /**
     * Boot method to add model event listeners
     */
    protected static function boot()
    {
        parent::boot();
        
        // Prevent deletion of system roles
        static::deleting(function ($role) {
            if ($role->is_system_role) {
                throw new \Exception('System roles cannot be deleted');
            }
        });
    }
}
