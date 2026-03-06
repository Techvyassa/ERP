<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $connection = 'tenant';
    protected $table = 'role_permissions';
    public $timestamps = false;
    
    protected $fillable = [
        'role_id',
        'module_code',
        'can_view',
        'can_create',
        'can_edit',
        'can_approve',
        'can_delete',
        'created_by'
    ];
    
    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_approve' => 'boolean',
        'can_delete' => 'boolean',
    ];
    
    /**
     * Get the role that owns this permission
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    /**
     * Check if a specific permission action is granted
     * 
     * @param string $action The action to check (view, create, edit, approve, delete)
     * @return bool True if the action is permitted
     */
    public function hasPermission(string $action): bool
    {
        return match($action) {
            'view' => $this->can_view,
            'create' => $this->can_create,
            'edit' => $this->can_edit,
            'approve' => $this->can_approve,
            'delete' => $this->can_delete,
            default => false,
        };
    }
}
