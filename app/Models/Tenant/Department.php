<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $connection = 'tenant';
    protected $table = 'department_master';
    public $timestamps = false;
    
    protected $fillable = [
        'dept_code',
        'dept_name',
        'parent_dept_id',
        'cost_center_code',
        'is_active',
        'created_by'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the parent department
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_dept_id');
    }
    
    /**
     * Get child departments
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_dept_id');
    }
    
    /**
     * Get users in this department
     */
    public function users()
    {
        return $this->hasMany(User::class, 'dept_id');
    }
    
    /**
     * Boot method to add model event listeners
     */
    protected static function boot()
    {
        parent::boot();
        
        // Prevent circular hierarchy before saving
        static::saving(function ($department) {
            if ($department->parent_dept_id) {
                if ($department->wouldCreateCycle($department->parent_dept_id)) {
                    throw new \Exception('Circular department hierarchy detected');
                }
            }
        });
        
        // Re-assign orphaned users to ROOT department when their department is deleted
        static::deleting(function ($department) {
            $rootDept = self::whereNull('parent_dept_id')->first();
            if ($rootDept && $department->id !== $rootDept->id) {
                $department->users()->update(['dept_id' => $rootDept->id]);
            }
        });
    }
    
    /**
     * Check if setting this parent would create a cycle in the hierarchy
     * 
     * @param int $parentId The proposed parent department ID
     * @param array $visited Array of already visited department IDs
     * @return bool True if a cycle would be created
     */
    protected function wouldCreateCycle($parentId, $visited = []): bool
    {
        // If parent is self, that's a cycle
        if ($parentId == $this->id) {
            return true;
        }
        
        // If we've already visited this parent, that's a cycle
        if (in_array($parentId, $visited)) {
            return true;
        }
        
        // Add current parent to visited list
        $visited[] = $parentId;
        
        // Get the parent department
        $parent = self::find($parentId);
        
        // If parent has a parent, recursively check
        if ($parent && $parent->parent_dept_id) {
            return $this->wouldCreateCycle($parent->parent_dept_id, $visited);
        }
        
        // No cycle detected
        return false;
    }
}
