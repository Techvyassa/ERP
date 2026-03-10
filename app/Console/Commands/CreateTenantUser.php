<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Control\Organization;
use App\Models\Tenant\User;
use App\Models\Tenant\Role;
use App\Models\Tenant\Department;
use App\Contracts\DatabaseConnectionRouter;
use Illuminate\Support\Facades\Hash;

class CreateTenantUser extends Command
{
    protected $signature = 'tenant:create-user 
                            {org_slug : Organization slug}
                            {email : User email}
                            {password : User password}
                            {--first-name= : First name}
                            {--last-name= : Last name}
                            {--employee-code= : Employee code}';

    protected $description = 'Create a user in a tenant database';

    public function __construct(
        private DatabaseConnectionRouter $dbRouter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = $this->argument('org_slug');
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        // Switch to control database
        $this->dbRouter->switchToControl();
        
        // Find organization
        $organization = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$organization) {
            $this->error("Organization not found: {$orgSlug}");
            return 1;
        }
        
        $this->info("Found organization: {$organization->org_name}");
        
        // Switch to tenant database
        try {
            $this->dbRouter->switchToTenant($organization->tenant_db_name);
            $this->info("Connected to tenant database: {$organization->tenant_db_name}");
        } catch (\Exception $e) {
            $this->error("Failed to connect to tenant database: {$e->getMessage()}");
            return 1;
        }
        
        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User already exists with email: {$email}");
            return 1;
        }
        
        // Get or create default role (Admin)
        $role = Role::where('role_code', 'ADMIN')->first();
        if (!$role) {
            $this->warn("Admin role not found, creating it...");
            $role = Role::create([
                'role_code' => 'ADMIN',
                'role_name' => 'Administrator',
                'description' => 'System Administrator with full access',
                'is_active' => true
            ]);
        }
        
        // Get or create default department
        $department = Department::first();
        if (!$department) {
            $this->warn("No department found, creating default...");
            $department = Department::create([
                'dept_code' => 'ADMIN',
                'dept_name' => 'Administration',
                'is_active' => true
            ]);
        }
        
        // Create user - use 'password' to trigger the setPasswordHashAttribute mutator
        $user = User::create([
            'employee_code' => $this->option('employee-code') ?? 'EMP001',
            'email' => $email,
            'password' => $password,
            'first_name' => $this->option('first-name') ?? 'Admin',
            'last_name' => $this->option('last-name') ?? 'User',
            'phone' => null,
            'dept_id' => $department->id,
            'role_id' => $role->id,
            'is_active' => true,
            'created_by' => null
        ]);
        
        $this->info("User created successfully!");
        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', $user->id],
                ['Email', $user->email],
                ['Employee Code', $user->employee_code],
                ['Name', $user->first_name . ' ' . $user->last_name],
                ['Role', $role->role_name],
                ['Department', $department->dept_name],
            ]
        );
        
        return 0;
    }
}
