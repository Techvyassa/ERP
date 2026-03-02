# Tenant Provisioning Service

This document describes how to use the Tenant Provisioning Service to create and initialize new tenant databases.

## Overview

The Tenant Provisioning Service automates the complete workflow of setting up a new tenant organization:

1. Creates a dedicated MySQL database for the tenant
2. Runs all tenant-specific migrations
3. Seeds default roles (ADMIN, MANAGER, USER, VIEWER)
4. Seeds role permissions for all modules
5. Creates a root department
6. Creates an initial admin user with temporary password
7. Activates the organization
8. Creates a trial subscription
9. Sends welcome email with credentials

## Usage

### Synchronous Provisioning

```php
use App\Contracts\TenantProvisioningService;

$provisioningService = app(TenantProvisioningService::class);
$result = $provisioningService->provisionTenant($orgId);

if ($result->success) {
    echo "Tenant provisioned successfully: {$result->tenantDbName}";
} else {
    echo "Provisioning failed: {$result->errorMessage}";
}
```

### Asynchronous Provisioning (Recommended)

```php
use App\Jobs\ProvisionTenantJob;

// Dispatch the job after email verification
ProvisionTenantJob::dispatch($orgId);
```

### Check Provisioning Status

```php
$status = $provisioningService->getProvisioningStatus($orgId);
echo "Status: {$status->status}";
echo "Database: {$status->tenantDbName}";
```

### Rollback Failed Provisioning

```php
$provisioningService->rollbackProvisioning($orgId);
```

## Default Roles and Permissions

### ADMIN Role
- All permissions enabled (view, create, edit, approve, delete) for all modules

### MANAGER Role
- Can view, create, edit, and approve
- Cannot delete

### USER Role
- Can view, create, and edit
- Cannot approve or delete

### VIEWER Role
- Can only view
- No create, edit, approve, or delete permissions

## Modules

The following modules have permissions seeded:
- PR (Purchase Requisition)
- PO (Purchase Order)
- GRN (Goods Receipt Note)
- QC (Quality Control)
- INVOICE
- PAYMENT
- INVENTORY
- REPORTS
- USERS
- SETTINGS

## Error Handling

The service implements comprehensive error handling:

- **Database Creation Failure**: Rolls back and keeps organization in PENDING status
- **Migration Failure**: Drops database and resets organization
- **Seeding Failure**: Drops database and resets organization
- **Email Failure**: Continues provisioning (email is non-critical)

All errors are logged and admin notifications are sent for failures.

## Queue Configuration

The `ProvisionTenantJob` is configured with:
- **Tries**: 3 attempts
- **Timeout**: 120 seconds
- **Backoff**: Exponential (30s, 60s, 120s)

## Example: Complete Registration Flow

```php
// 1. Create organization record
$organization = Organization::create([
    'org_slug' => 'acme-corp',
    'org_name' => 'Acme Corporation',
    'primary_email' => 'admin@acme.com',
    'registration_status' => 'PENDING',
    // ... other fields
]);

// 2. Send email verification
// ... email verification logic ...

// 3. After email verification, dispatch provisioning job
ProvisionTenantJob::dispatch($organization->org_id);

// 4. Job will automatically:
//    - Create tenant database
//    - Run migrations
//    - Seed data
//    - Activate organization
//    - Create trial subscription
//    - Send welcome email
```

## Logging

All provisioning activities are logged with context:

```
[INFO] Starting tenant provisioning for org_id: 123
[INFO] Generated tenant database name: erp_acme_corp
[INFO] Created tenant database: erp_acme_corp
[INFO] Ran tenant migrations for: erp_acme_corp
[INFO] Created role: ADMIN
[INFO] Created permissions for role: ADMIN
[INFO] Created root department
[INFO] Created initial admin user: admin@acme.com
[INFO] Created trial subscription for org_id: 123
[INFO] Tenant provisioning completed successfully for org_id: 123
```

## Requirements Satisfied

This implementation satisfies the following requirements:

- **Requirement 5.1-5.11**: Complete tenant provisioning workflow
- **Requirement 17.1-17.10**: Tenant data seeding with default roles and permissions
