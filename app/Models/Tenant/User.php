<?php

namespace App\Models\Tenant;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $connection = 'tenant';
    protected $table = 'users';
    
    protected $fillable = [
        'employee_code',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'dept_id',
        'role_id',
        'is_active',
        'created_by'
    ];
    
    protected $hidden = [
        'password_hash',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];
    
    /**
     * Get the department this user belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }
    
    /**
     * Get the role assigned to this user
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    /**
     * Set the password hash attribute with bcrypt cost 12
     * 
     * @param string $value The plain text password
     */
    public function setPasswordHashAttribute($value)
    {
        // Hash password with bcrypt cost factor 12
        $this->attributes['password_hash'] = Hash::make($value, ['rounds' => 12]);
        $this->attributes['password_changed_at'] = now();
    }
    
    /**
     * Verify a password against the stored hash
     * 
     * @param string $password The plain text password to verify
     * @return bool True if password matches
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password_hash);
    }
    
    /**
     * Get the user's full name
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    /**
     * Update the last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
