<?php

namespace App\Models\Control;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $connection = 'control';
    protected $table = 'refresh_tokens';
    protected $primaryKey = 'token_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'org_id',
        'user_id',
        'token',
        'expires_at',
        'last_used_at',
        'is_revoked',
        'user_agent',
        'ip_address',
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];
    
    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        return !$this->is_revoked && $this->expires_at->isFuture();
    }
    
    /**
     * Revoke the token
     */
    public function revoke(): void
    {
        $this->update(['is_revoked' => true]);
    }
    
    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
    
    /**
     * Relationship to organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'org_id');
    }
}
