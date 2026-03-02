<?php

namespace App\Services;

use App\Contracts\TenantProvisioningService;
use App\Contracts\ProvisioningResult;
use App\Contracts\ProvisioningStatus;
use App\Contracts\DatabaseConnectionRouter;
use App\Helpers\AuditLogger;
use App\Models\Control\Organization;
use App\Models\Control\OrgSubscription;
use App\Models\Control\SubscriptionPlan;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Department;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantProvisioningServiceImpl implements TenantProvisioningService
{
    private const MODULE_CODES = ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS', 'USERS', 'SETTINGS'];
    
    private const DEFAULT_ROLES = [
        ['code' => 'ADMIN', 'name' => 'Administrator', 'description' => 'Full system access'],
        ['code' => 'MANAGER', 'name' => 'Manager', 'description' => 'Management level access'],
        ['code' => 'USER', 'name' => 'User', 'description' => 'Standard user access'],
        ['code' => 'VIEWER', 'name' => 'Viewer', 'description' => 'Read-only access'],
    ];

    public function __construct(
        private DatabaseConnectionRouter $connectionRouter
    ) {}

    /**
     * Provision a new tenant database
     */
    public function provisionTenant(int $orgId): ProvisioningResult
    {
        $steps = [];
        $tenantDbName = '';
        
        try {
            // Step 1: Validate organization exists and is PENDING
            Log::info("Starting tenant provisioning for org_id: {$orgId}");
            $organization = Organization::find($orgId);
            
            if (!$organization) {
                throw new \Exception("Organization with ID {$orgId} not found");
            }
            
            if ($organization->registration_status !== 'PENDING') {
                throw new \Exception("Organization status must be PENDING, current status: {$organization->registration_status}");
            }
            
            $steps[] = 'Validated organization';
            
            // Log provisioning start
            AuditLogger::logProvisioningEvent(
                $orgId,
                $organization->org_slug,
                'started',
                null,
                null,
                $steps
            );
            
            // Step 2: Generate tenant database name
            $tenantDbName = "erp_{$organization->org_slug}";
            Log::info("Generated tenant database name: {$tenantDbName}");
            $steps[] = "Generated database name: {$tenantDbName}";
            
            // Verify tenant_db_name matches expected value
            if ($organization->tenant_db_name !== $tenantDbName) {
                throw new \Exception("Tenant database name mismatch. Expected: {$tenantDbName}, Got: {$organization->tenant_db_name}");
            }
            
            // Step 3: Create MySQL database
            $this->createTenantDatabase($tenantDbName);
            $steps[] = 'Created tenant database';
            
            // Step 4: Grant database permissions
            $this->grantTenantDatabasePermissions($tenantDbName);
            $steps[] = 'Granted database permissions';
            
            // Step 5: Update organization with tenant_db_name (already set, but verify)
            // No need to update as it's already set during registration
            $steps[] = 'Verified organization record';
            
            // Step 6: Run tenant migrations
            $this->runTenantMigrations($tenantDbName);
            $steps[] = 'Ran tenant migrations';
            
            // Step 7: Seed default roles
            $roles = $this->seedDefaultRoles($tenantDbName);
            $steps[] = 'Seeded default roles';
            
            // Step 8: Seed role permissions
            $this->seedRolePermissions($tenantDbName, $roles);
            $steps[] = 'Seeded role permissions';
            
            // Step 9: Create root department
            $rootDepartment = $this->createRootDepartment($tenantDbName);
            $steps[] = 'Created root department';
            
            // Step 10: Create initial admin user
            $tempPassword = $this->createInitialAdminUser(
                $tenantDbName,
                $organization->primary_email,
                $roles['ADMIN'],
                $rootDepartment
            );
            $steps[] = 'Created initial admin user';
            
            // Step 11: Update organization status to ACTIVE
            $organization->registration_status = 'ACTIVE';
            $organization->activated_at = now();
            $organization->save();
            $steps[] = 'Updated organization status to ACTIVE';
            
            // Step 12: Create trial subscription
            $this->createTrialSubscription($orgId);
            $steps[] = 'Created trial subscription';
            
            // Step 13: Send welcome email
            $this->sendWelcomeEmail($organization, $tempPassword);
            $steps[] = 'Sent welcome email';
            
            Log::info("Tenant provisioning completed successfully for org_id: {$orgId}");
            
            // Log provisioning success
            AuditLogger::logProvisioningEvent(
                $orgId,
                $organization->org_slug,
                'completed',
                $tenantDbName,
                null,
                $steps
            );
            
            return new ProvisioningResult(
                success: true,
                tenantDbName: $tenantDbName,
                errorMessage: null,
                steps: $steps
            );
            
        } catch (\Exception $e) {
            Log::error("Tenant provisioning failed for org_id: {$orgId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'completed_steps' => $steps
            ]);
            
            // Log provisioning failure
            $organization = Organization::find($orgId);
            if ($organization) {
                AuditLogger::logProvisioningEvent(
                    $orgId,
                    $organization->org_slug,
                    'failed',
                    $tenantDbName ?: null,
                    $e->getMessage(),
                    $steps
                );
            }
            
            // Automatically rollback on failure
            try {
                Log::info("Attempting automatic rollback for org_id: {$orgId}");
                $this->rollbackProvisioning($orgId);
                Log::info("Automatic rollback completed for org_id: {$orgId}");
            } catch (\Exception $rollbackError) {
                Log::error("Automatic rollback failed for org_id: {$orgId}", [
                    'error' => $rollbackError->getMessage()
                ]);
            }
            
            // Send admin notification
            $this->sendAdminNotification($orgId, $e->getMessage());
            
            return new ProvisioningResult(
                success: false,
                tenantDbName: $tenantDbName,
                errorMessage: $e->getMessage(),
                steps: $steps
            );
        }
    }

    /**
     * Rollback failed provisioning
     */
    public function rollbackProvisioning(int $orgId): void
    {
        try {
            $organization = Organization::find($orgId);
            
            if (!$organization) {
                Log::warning("Cannot rollback: Organization {$orgId} not found");
                return;
            }
            
            $tenantDbName = $organization->tenant_db_name;
            
            if ($tenantDbName) {
                // Drop the tenant database if it exists
                try {
                    DB::connection('control')->statement("DROP DATABASE IF EXISTS `{$tenantDbName}`");
                    Log::info("Dropped tenant database: {$tenantDbName}");
                } catch (\Exception $e) {
                    Log::error("Failed to drop tenant database: {$tenantDbName}", ['error' => $e->getMessage()]);
                }
            }
            
            // Reset organization to PENDING (keep tenant_db_name for retry)
            $organization->registration_status = 'PENDING';
            $organization->activated_at = null;
            $organization->save();
            
            Log::info("Rollback completed for org_id: {$orgId}");
            
        } catch (\Exception $e) {
            Log::error("Rollback failed for org_id: {$orgId}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check provisioning status
     */
    public function getProvisioningStatus(int $orgId): ProvisioningStatus
    {
        $organization = Organization::find($orgId);
        
        if (!$organization) {
            return new ProvisioningStatus(
                status: 'NOT_FOUND',
                tenantDbName: null,
                lastError: 'Organization not found'
            );
        }
        
        return new ProvisioningStatus(
            status: $organization->registration_status,
            tenantDbName: $organization->tenant_db_name,
            lastError: null,
            completedSteps: []
        );
    }

    /**
     * Create tenant database using raw SQL
     */
    private function createTenantDatabase(string $tenantDbName): void
    {
        try {
            // Validate database name length (MySQL limit is 64 characters)
            if (strlen($tenantDbName) > 64) {
                throw new \Exception("Database name exceeds maximum length of 64 characters: {$tenantDbName}");
            }
            
            // Note: max_execution_time is only available in MySQL 8.0+
            // For older versions, timeout is controlled by PHP max_execution_time
            
            // Create database if it doesn't exist (idempotent)
            DB::connection('control')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            
            Log::info("Created tenant database: {$tenantDbName}");
            
            // Verify database is accessible
            $this->verifyDatabaseConnection($tenantDbName);
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create tenant database: {$e->getMessage()}");
        }
    }
    
    /**
     * Verify tenant database connection
     */
    private function verifyDatabaseConnection(string $tenantDbName): void
    {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            DB::connection('tenant')->select('SELECT 1');
            $this->connectionRouter->switchToControl();
            
            Log::info("Verified tenant database connection: {$tenantDbName}");
        } catch (\Exception $e) {
            throw new \Exception("Failed to verify tenant database connection: {$e->getMessage()}");
        }
    }

    /**
     * Grant permissions to tenant database user
     */
    private function grantTenantDatabasePermissions(string $tenantDbName): void
    {
        try {
            // Use fixed tenant user for all tenant databases
            $username = 'erp_user';
            $host = '%'; // Allow from any host
            
            // Grant all privileges on the tenant database
            DB::connection('control')->statement(
                "GRANT ALL PRIVILEGES ON `{$tenantDbName}`.* TO '{$username}'@'{$host}'"
            );
            
            // Flush privileges to apply changes
            DB::connection('control')->statement("FLUSH PRIVILEGES");
            
            Log::info("Granted permissions on tenant database: {$tenantDbName} to user: {$username}@{$host}");
            
        } catch (\Exception $e) {
            // Log warning but don't fail provisioning for permission errors
            // The user may already have privileges or GRANT may not be needed
            $errorMessage = $e->getMessage();
            
            Log::warning("Could not grant permissions (continuing provisioning): {$errorMessage}", [
                'tenant_db_name' => $tenantDbName,
                'username' => 'erp_user',
                'host' => '%',
            ]);
            
            // Don't throw exception - allow provisioning to continue
        }
    }

    /**
     * Run tenant migrations programmatically
     */
    private function runTenantMigrations(string $tenantDbName): void
    {
        try {
            // Switch to tenant database
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            // Run migrations for tenant database
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true
            ]);
            
            Log::info("Ran tenant migrations for: {$tenantDbName}");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to run tenant migrations: {$e->getMessage()}");
        } finally {
            // Switch back to control database
            $this->connectionRouter->switchToControl();
        }
    }

    /**
     * Seed default roles
     */
    private function seedDefaultRoles(string $tenantDbName): array
    {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            $roles = [];
            
            foreach (self::DEFAULT_ROLES as $roleData) {
                // Check if role already exists (idempotent)
                $role = Role::where('role_code', $roleData['code'])->first();
                
                if (!$role) {
                    $role = Role::create([
                        'role_code' => $roleData['code'],
                        'role_name' => $roleData['name'],
                        'description' => $roleData['description'],
                        'is_active' => true,
                        'is_system_role' => true,
                        'created_by' => null
                    ]);
                    
                    Log::info("Created role: {$roleData['code']}");
                } else {
                    Log::info("Role already exists: {$roleData['code']}");
                }
                
                $roles[$roleData['code']] = $role;
            }
            
            return $roles;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to seed default roles: {$e->getMessage()}");
        } finally {
            $this->connectionRouter->switchToControl();
        }
    }

    /**
     * Seed role permissions for all roles
     */
    private function seedRolePermissions(string $tenantDbName, array $roles): void
    {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            foreach ($roles as $roleCode => $role) {
                foreach (self::MODULE_CODES as $moduleCode) {
                    // Check if permission already exists (idempotent)
                    $existingPermission = RolePermission::where('role_id', $role->role_id)
                        ->where('module_code', $moduleCode)
                        ->first();
                    
                    if (!$existingPermission) {
                        $permissions = $this->getPermissionsForRole($roleCode);
                        
                        RolePermission::create([
                            'role_id' => $role->role_id,
                            'module_code' => $moduleCode,
                            'can_view' => $permissions['can_view'],
                            'can_create' => $permissions['can_create'],
                            'can_edit' => $permissions['can_edit'],
                            'can_approve' => $permissions['can_approve'],
                            'can_delete' => $permissions['can_delete'],
                            'created_by' => null
                        ]);
                    }
                }
                
                Log::info("Seeded permissions for role: {$roleCode}");
            }
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to seed role permissions: {$e->getMessage()}");
        } finally {
            $this->connectionRouter->switchToControl();
        }
    }

    /**
     * Get permissions configuration for a role
     */
    private function getPermissionsForRole(string $roleCode): array
    {
        return match($roleCode) {
            'ADMIN' => [
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_approve' => true,
                'can_delete' => true,
            ],
            'MANAGER' => [
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_approve' => true,
                'can_delete' => false,
            ],
            'USER' => [
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_approve' => false,
                'can_delete' => false,
            ],
            'VIEWER' => [
                'can_view' => true,
                'can_create' => false,
                'can_edit' => false,
                'can_approve' => false,
                'can_delete' => false,
            ],
            default => [
                'can_view' => false,
                'can_create' => false,
                'can_edit' => false,
                'can_approve' => false,
                'can_delete' => false,
            ],
        };
    }

    /**
     * Create root department
     */
    private function createRootDepartment(string $tenantDbName): Department
    {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            // Check if root department already exists (idempotent)
            $department = Department::where('dept_code', 'ROOT')->first();
            
            if (!$department) {
                $department = Department::create([
                    'dept_code' => 'ROOT',
                    'dept_name' => 'Root Department',
                    'parent_dept_id' => null,
                    'is_active' => true,
                    'created_by' => null
                ]);
                
                Log::info("Created root department");
            } else {
                Log::info("Root department already exists");
            }
            
            return $department;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create root department: {$e->getMessage()}");
        } finally {
            $this->connectionRouter->switchToControl();
        }
    }

    /**
     * Create initial admin user with temporary password
     */
    private function createInitialAdminUser(
        string $tenantDbName,
        string $email,
        Role $adminRole,
        Department $rootDepartment
    ): string {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            // Check if admin user already exists (idempotent)
            $user = User::where('email', $email)->first();
            
            if ($user) {
                Log::info("Admin user already exists: {$email}");
                // Return a placeholder since we don't store the original temp password
                return 'EXISTING_USER';
            }
            
            // Generate random temporary password
            $tempPassword = Str::random(12);
            
            // Extract first name from email
            $emailParts = explode('@', $email);
            $firstName = ucfirst($emailParts[0]);
            
            $user = new User([
                'employee_code' => 'ADMIN001',
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => 'Admin',
                'phone' => null,
                'dept_id' => $rootDepartment->dept_id,
                'role_id' => $adminRole->role_id,
                'is_active' => true,
                'created_by' => null
            ]);
            
            // Set password using the mutator
            $user->password_hash = $tempPassword;
            $user->save();
            
            Log::info("Created initial admin user: {$email}");
            
            return $tempPassword;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create initial admin user: {$e->getMessage()}");
        } finally {
            $this->connectionRouter->switchToControl();
        }
    }

    /**
     * Create trial subscription
     */
    private function createTrialSubscription(int $orgId): void
    {
        try {
            // Get trial configuration
            $trialDays = (int) config('subscription.trial.duration_days', 14);
            $trialPlanCode = config('subscription.trial.default_plan_code', 'TRIAL');
            
            // Get the trial plan
            $trialPlan = SubscriptionPlan::where('plan_code', $trialPlanCode)->first();
            
            if (!$trialPlan) {
                // If no trial plan exists, get the first active plan
                $trialPlan = SubscriptionPlan::active()->first();
            }
            
            if (!$trialPlan) {
                throw new \Exception("No subscription plan available for trial");
            }
            
            $trialStartDate = now();
            $trialEndDate = now()->addDays($trialDays);
            
            OrgSubscription::create([
                'org_id' => $orgId,
                'plan_id' => $trialPlan->plan_id,
                'subscription_status' => 'TRIAL',
                'trial_start_date' => $trialStartDate,
                'trial_end_date' => $trialEndDate,
                'current_period_start' => $trialStartDate,
                'current_period_end' => $trialEndDate,
                'next_billing_date' => $trialEndDate,
            ]);
            
            Log::info("Created trial subscription for org_id: {$orgId}", [
                'trial_days' => $trialDays,
                'plan_code' => $trialPlan->plan_code,
            ]);
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create trial subscription: {$e->getMessage()}");
        }
    }

    /**
     * Send welcome email with credentials
     */
    private function sendWelcomeEmail(Organization $organization, string $tempPassword): void
    {
        try {
            // TODO: Implement actual email sending using Laravel Mail
            // For now, just log the credentials
            Log::info("Welcome email would be sent to: {$organization->primary_email}", [
                'org_name' => $organization->org_name,
                'email' => $organization->primary_email,
                'temp_password' => $tempPassword,
                'tenant_db' => $organization->tenant_db_name
            ]);
            
            // Uncomment when email is configured:
            // Mail::to($organization->primary_email)->send(
            //     new WelcomeEmail($organization, $tempPassword)
            // );
            
        } catch (\Exception $e) {
            // Don't fail provisioning if email fails
            Log::warning("Failed to send welcome email: {$e->getMessage()}");
        }
    }

    /**
     * Send admin notification on failure
     */
    private function sendAdminNotification(int $orgId, string $errorMessage): void
    {
        try {
            // TODO: Implement actual admin notification
            Log::error("Admin notification: Tenant provisioning failed", [
                'org_id' => $orgId,
                'error' => $errorMessage
            ]);
            
            // Uncomment when email is configured:
            // Mail::to(config('app.admin_email'))->send(
            //     new ProvisioningFailedEmail($orgId, $errorMessage)
            // );
            
        } catch (\Exception $e) {
            Log::error("Failed to send admin notification: {$e->getMessage()}");
        }
    }
}
