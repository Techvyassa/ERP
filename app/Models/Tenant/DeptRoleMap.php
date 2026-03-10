<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeptRoleMap extends Model
{
    protected $connection = 'tenant';
    protected $table = 'dept_role_map';

    public $timestamps = false; // Only has created_at, no updated_at

    protected $fillable = [
        'dept_id',
        'role_id',
        'created_by',
    ];

    protected $casts = [
        'dept_id'    => 'integer',
        'role_id'    => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * The department this mapping belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    /**
     * The role this mapping refers to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if a role is valid for a given department.
     */
    public static function isValidForDepartment(int $deptId, int $roleId): bool
    {
        return static::where('dept_id', $deptId)
            ->where('role_id', $roleId)
            ->exists();
    }
}
