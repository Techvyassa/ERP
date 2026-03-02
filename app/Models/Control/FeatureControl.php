<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;

class FeatureControl extends Model
{
    protected $connection = 'control';
    protected $table = 'feature_controls';
    protected $primaryKey = 'control_id';
    
    protected $fillable = [
        'org_id',
        'feature_key',
        'feature_type',
        'feature_value',
        'effective_from',
        'effective_to',
        'granted_by',
        'notes',
    ];
    
    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the organization that owns this feature control
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'org_id');
    }
    
    /**
     * Get the typed value based on feature_type
     */
    public function getTypedValue()
    {
        return match($this->feature_type) {
            'BOOLEAN' => filter_var($this->feature_value, FILTER_VALIDATE_BOOLEAN),
            'NUMERIC' => (int) $this->feature_value,
            'TEXT' => $this->feature_value,
            'JSON' => json_decode($this->feature_value, true),
            default => $this->feature_value,
        };
    }
    
    /**
     * Set the typed value based on feature_type
     */
    public function setTypedValue($value): void
    {
        $this->feature_value = match($this->feature_type) {
            'BOOLEAN' => $value ? '1' : '0',
            'NUMERIC' => (string) $value,
            'TEXT' => (string) $value,
            'JSON' => json_encode($value),
            default => (string) $value,
        };
    }
    
    /**
     * Check if the feature control is currently effective
     */
    public function isEffective(): bool
    {
        $now = now()->toDateString();
        
        // Check if effective_from is set and we're before that date
        if ($this->effective_from && $now < $this->effective_from->toDateString()) {
            return false;
        }
        
        // Check if effective_to is set and we're after that date
        if ($this->effective_to && $now > $this->effective_to->toDateString()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Scope to filter only effective feature controls
     */
    public function scopeEffective($query)
    {
        $now = now()->toDateString();
        
        return $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $now);
        });
    }
    
    /**
     * Scope to filter by feature key
     */
    public function scopeForFeature($query, string $featureKey)
    {
        return $query->where('feature_key', $featureKey);
    }
    
    /**
     * Scope to filter by organization
     */
    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where('org_id', $orgId);
    }
    
    /**
     * Get boolean value (for BOOLEAN type)
     */
    public function getBooleanValue(): bool
    {
        if ($this->feature_type !== 'BOOLEAN') {
            throw new \Exception("Feature type is not BOOLEAN");
        }
        return $this->getTypedValue();
    }
    
    /**
     * Get numeric value (for NUMERIC type)
     */
    public function getNumericValue(): int
    {
        if ($this->feature_type !== 'NUMERIC') {
            throw new \Exception("Feature type is not NUMERIC");
        }
        return $this->getTypedValue();
    }
    
    /**
     * Get text value (for TEXT type)
     */
    public function getTextValue(): string
    {
        if ($this->feature_type !== 'TEXT') {
            throw new \Exception("Feature type is not TEXT");
        }
        return $this->getTypedValue();
    }
    
    /**
     * Get JSON value (for JSON type)
     */
    public function getJsonValue(): array
    {
        if ($this->feature_type !== 'JSON') {
            throw new \Exception("Feature type is not JSON");
        }
        return $this->getTypedValue();
    }
}
