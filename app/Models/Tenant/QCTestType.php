<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class QCTestType extends Model
{
    protected $connection = 'tenant';
    protected $table = 'qc_test_types';

    protected $fillable = [
        'type_code',
        'type_name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this test type
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get QC parameters that use this test type
     */
    public function qcParameters()
    {
        return $this->hasMany(QCParameter::class, 'test_type_id');
    }

    /**
     * Scope: Active test types only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
