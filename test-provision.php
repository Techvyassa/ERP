<?php
use App\Models\Control\Organization;
use App\Jobs\ProvisionTenantJob;
use App\Services\TenantProvisioningServiceImpl;

$org = Organization::create([
    'org_slug' => 'test-tenant-db-'.time(),
    'org_name' => 'Test Tenant '.time(),
    'tenant_db_name' => 'erp_test_tenant_' . time(),
    'registration_status' => 'PENDING',
    'primary_email' => 'test'.time().'@example.com',
    'country_code' => 'US',
    'max_users' => 10,
]);

echo "Created Org ID: {$org->org_id}\n";
try {
    $job = new ProvisionTenantJob($org->org_id, ['first_name' => 'Test', 'password' => 'Pass123!']);
    $service = app(App\Contracts\TenantProvisioningService::class);
    $result = $service->provisionTenant($org->org_id, ['first_name' => 'Test', 'password' => 'Pass123!']);
    
    if ($result->success) {
        echo "Provisioning SUCCESSFUL!\n";
    } else {
        echo "Provisioning FAILED: {$result->errorMessage}\n";
        print_r($result->steps);
    }
} catch (\Exception $e) {
    echo "Exception: {$e->getMessage()}\n";
}
