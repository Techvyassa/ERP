<?php

namespace App\Services;

use App\Contracts\TenantProvisioningService;
use App\Contracts\ProvisioningResult;
use App\Contracts\ProvisioningStatus;
use App\Contracts\DatabaseConnectionRouter;
use App\Helpers\AuditLogger;
use App\Mail\CredentialsReadyEmail;
use App\Mail\ProvisioningFailedUserEmail;
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
    private const MODULE_CODES = [
        'SETTINGS', 'USERS', 'PR', 'PO', 'ASN', 'GATE_ENTRY', 'MR_GRN', 'GRN', 'QC', 'STOCK', 'INVOICE', 'PAYMENT', 'INVENTORY', 'WAREHOUSE', 'MATERIAL', 'USER_MGMT', 'ROLE_MGMT', 'DEPT_MGMT', 'BOM', 'ADMINISTRATION'
    ];
    
    private const DEFAULT_ROLES = [
        ['code' => 'ADMIN', 'name' => 'Administrator', 'description' => 'Full system access'],
        ['code' => 'MANAGER', 'name' => 'Manager', 'description' => 'Management level access'],
        ['code' => 'USER', 'name' => 'User', 'description' => 'Standard user access'],
        ['code' => 'VIEWER', 'name' => 'Viewer', 'description' => 'Read-only access'],
    ];

    private const DEFAULT_DEPARTMENTS = [
        'Administration' => ['HR', 'Legal', 'IT Settings'],
        'Finance' => ['Accounts Payable', 'Accounts Receivable', 'Invoicing'],
        'Inventory & Warehouse' => ['GRN', 'Stock Movements'],
        'Procurement' => ['Purchase Requests', 'POs'],
        'Quality Assurance' => ['QC', 'Inspections'],
        'Sales & Distribution' => ['Orders', 'Shipping'],
    ];

    public function __construct(
        private DatabaseConnectionRouter $connectionRouter
    ) {}

    /**
     * Provision a new tenant database
     */
    public function provisionTenant(int $orgId, ?array $userData = null): ProvisioningResult
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
            
            // Step 2: Generate tenant database name (replace hyphens with underscores)
            $tenantDbName = "erp_" . str_replace('-', '_', $organization->org_slug);
            Log::info("Generated tenant database name: {$tenantDbName}");
            $steps[] = "Generated database name: {$tenantDbName}";
            
            // Step 3: Create MySQL database
            $this->createTenantDatabase($tenantDbName);
            $steps[] = 'Created tenant database';
            
            // Step 4: Update organization with tenant_db_name
            $organization->tenant_db_name = $tenantDbName;
            $organization->save();
            $steps[] = 'Updated organization record';
            
            // Step 5: Run tenant migrations
            $this->runTenantMigrations($tenantDbName);
            $steps[] = 'Ran tenant migrations';
            
            // Step 6: Seed default roles
            $roles = $this->seedDefaultRoles($tenantDbName);
            $steps[] = 'Seeded default roles';
            
            // Step 7: Seed role permissions
            $this->seedRolePermissions($tenantDbName, $roles);
            $steps[] = 'Seeded role permissions';
            
            // Step 8: Create default departments hierarchy
            $rootDepartment = $this->seedDefaultDepartments($tenantDbName, $organization->org_name);
            $steps[] = 'Seeded default departments hierarchy';
            
            // Step 9: Create initial admin user
            $tempPassword = $this->createInitialAdminUser(
                $tenantDbName,
                $organization->primary_email,
                $roles['ADMIN'],
                $rootDepartment,
                $userData
            );
            $steps[] = 'Created initial admin user';
            
            // Step 10: Update organization status to ACTIVE
            $organization->registration_status = 'ACTIVE';
            $organization->activated_at = now();
            $organization->save();
            $steps[] = 'Updated organization status to ACTIVE';
            
            // Step 11: Create trial subscription
            $this->createTrialSubscription($orgId);
            $steps[] = 'Created trial subscription';
            
            // Step 12: Send credential email after provisioning is fully complete
            $this->sendCredentialsEmail($organization, $tempPassword, $userData['first_name'] ?? null);
            $steps[] = 'Sent credentials email';
            
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

                $this->sendProvisioningFailedEmail($organization, $e->getMessage(), $userData['first_name'] ?? null);
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
            
            // Reset organization to PENDING
            $organization->registration_status = 'PENDING';
            $organization->tenant_db_name = null;
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
            DB::connection('control')->statement("CREATE DATABASE `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            Log::info("Created tenant database: {$tenantDbName}");
        } catch (\Exception $e) {
            throw new \Exception("Failed to create tenant database: {$e->getMessage()}");
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
                $role = Role::create([
                    'role_code' => $roleData['code'],
                    'role_name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null
                ]);
                
                $roles[$roleData['code']] = $role;
                Log::info("Created role: {$roleData['code']}");
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
                    $permissions = $this->getPermissionsForRole($roleCode, $moduleCode);
                    
                    RolePermission::create([
                        'role_id' => $role->id,
                        'module_code' => $moduleCode,
                        'scope' => $permissions['scope'],
                        'view_cross_department' => $permissions['view_cross_department'],
                        'can_view' => $permissions['can_view'],
                        'can_create' => $permissions['can_create'],
                        'can_edit' => $permissions['can_edit'],
                        'can_approve' => $permissions['can_approve'],
                        'can_delete' => $permissions['can_delete'],
                        'created_by' => null
                    ]);
                }
                
                Log::info("Created permissions for role: {$roleCode}");
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
    private function getPermissionsForRole(string $roleCode, string $moduleCode): array
    {
        $can_view = false;
        $can_create = false;
        $can_edit = false;
        $can_approve = false;
        $can_delete = false;
        $scope = 'department';
        $view_cross = false;

        if (in_array($roleCode, ['ADMIN'])) {
            $scope = 'global';
            $view_cross = true;
        }

        switch ($roleCode) {
            case 'ADMIN':
                $can_view = $can_create = $can_edit = $can_approve = $can_delete = true;
                break;
            case 'MANAGER':
                if ($moduleCode === 'ADMINISTRATION') {
                    $can_view = true;
                } else {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                }
                break;
            case 'USER':
                if ($moduleCode === 'ADMINISTRATION') {
                    $can_view = true;
                } else {
                    $can_view = $can_create = $can_edit = true;
                }
                break;
            case 'PROCUREMENT':
                if (in_array($moduleCode, ['PR', 'PO', 'ASN', 'VENDORS'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'SECURITY':
                if (in_array($moduleCode, ['GATE_ENTRY'])) {
                    $can_view = $can_create = $can_edit = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'WAREHOUSE':
                if (in_array($moduleCode, ['INVENTORY', 'WAREHOUSE', 'MR_GRN', 'GRN', 'STOCK', 'MATERIAL'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'QC':
                if (in_array($moduleCode, ['QC'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
        }

        return [
            'scope' => $scope,
            'view_cross_department' => $view_cross,
            'can_view' => $can_view,
            'can_create' => $can_create,
            'can_edit' => $can_edit,
            'can_approve' => $can_approve,
            'can_delete' => $can_delete,
        ];
    }

    /**
     * Create default departments hierarchy
     */
    private function seedDefaultDepartments(string $tenantDbName, string $orgName): Department
    {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            $rootDepartment = Department::create([
                'dept_code' => 'ROOT',
                'dept_name' => substr($orgName, 0, 100),
                'parent_dept_id' => null,
                'is_active' => true,
                'created_by' => null
            ]);
            
            Log::info("Created root department: {$orgName}");
            
            foreach (self::DEFAULT_DEPARTMENTS as $parentName => $children) {
                $parentDept = Department::create([
                    'dept_code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $parentName), 0, 15)),
                    'dept_name' => $parentName,
                    'parent_dept_id' => $rootDepartment->id,
                    'is_active' => true,
                    'created_by' => null
                ]);
                
                foreach ($children as $childName) {
                    Department::create([
                        'dept_code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $childName), 0, 15)),
                        'dept_name' => $childName,
                        'parent_dept_id' => $parentDept->id,
                        'is_active' => true,
                        'created_by' => null
                    ]);
                }
            }
            
            Log::info("Seeded default departments hierarchy");
            
            return $rootDepartment;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to seed default departments: {$e->getMessage()}");
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
        Department $rootDepartment,
        ?array $userData = null
    ): string {
        try {
            $this->connectionRouter->switchToTenant($tenantDbName);
            
            // Use provided user data or generate defaults
            $firstName = $userData['first_name'] ?? ucfirst(explode('@', $email)[0]);
            $lastName = $userData['last_name'] ?? 'Admin';
            $password = $userData['password'] ?? null;
            $provider = $userData['provider'] ?? 'email';
            
            // Generate random temporary password if not provided
            $tempPassword = $password ?? Str::random(12);
            
            $user = new User([
                'employee_code' => 'ADMIN001',
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => null,
                'dept_id' => $rootDepartment->id,
                'role_id' => $adminRole->id,
                'is_active' => true,
                'created_by' => null
            ]);
            
            // Set password using the mutator
            $user->password_hash = $tempPassword;
            $user->save();
            
            Log::info("Created initial admin user: {$email} (provider: {$provider})");
            
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
            // Get the trial plan (assuming there's a plan with code 'TRIAL')
            $trialPlan = SubscriptionPlan::where('plan_code', 'TRIAL')->first();
            
            if (!$trialPlan) {
                // If no trial plan exists, get the first active plan
                $trialPlan = SubscriptionPlan::active()->first();
            }
            
            if (!$trialPlan) {
                throw new \Exception("No subscription plan available for trial");
            }
            
            $trialStartDate = now();
            $trialEndDate = now()->addDays(14);
            
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
            
            Log::info("Created trial subscription for org_id: {$orgId}");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create trial subscription: {$e->getMessage()}");
        }
    }

    /**
     * Send credentials email after the workspace is fully provisioned.
     */
    private function sendCredentialsEmail(Organization $organization, string $tempPassword, ?string $firstName = null): void
    {
        try {
            $recipientFirstName = $firstName ?: ucfirst(explode('@', $organization->primary_email)[0]);

            Mail::to($organization->primary_email)->send(
                new CredentialsReadyEmail(
                    $organization,
                    $recipientFirstName,
                    $organization->primary_email,
                    $tempPassword
                )
            );
            
            Log::info("Credentials email sent to: {$organization->primary_email}", [
                'org_id' => $organization->org_id,
                'org_name' => $organization->org_name,
            ]);
            
        } catch (\Exception $e) {
            // Don't fail provisioning if email fails
            Log::warning("Failed to send credentials email: {$e->getMessage()}", [
                'org_id' => $organization->org_id,
                'email' => $organization->primary_email,
            ]);
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

    /**
     * Notify the organization contact when workspace setup fails.
     */
    private function sendProvisioningFailedEmail(Organization $organization, string $errorMessage, ?string $firstName = null): void
    {
        try {
            $recipientFirstName = $firstName ?: ucfirst(explode('@', $organization->primary_email)[0]);

            Mail::to($organization->primary_email)->send(
                new ProvisioningFailedUserEmail(
                    organization: $organization,
                    firstName: $recipientFirstName,
                    errorMessage: $errorMessage
                )
            );

            Log::warning("Provisioning failure email sent to organization contact", [
                'org_id' => $organization->org_id,
                'email' => $organization->primary_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send provisioning failure email: {$e->getMessage()}", [
                'org_id' => $organization->org_id,
                'email' => $organization->primary_email,
            ]);
        }
    }
}
