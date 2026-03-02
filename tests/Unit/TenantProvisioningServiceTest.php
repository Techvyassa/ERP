<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Contracts\TenantProvisioningService;
use App\Contracts\ProvisioningResult;
use App\Contracts\ProvisioningStatus;

class TenantProvisioningServiceTest extends TestCase
{
    /**
     * Test that TenantProvisioningService can be resolved from container
     */
    public function test_service_can_be_resolved_from_container(): void
    {
        $service = app(TenantProvisioningService::class);
        
        $this->assertInstanceOf(TenantProvisioningService::class, $service);
    }
    
    /**
     * Test ProvisioningResult structure
     */
    public function test_provisioning_result_structure(): void
    {
        $result = new ProvisioningResult(
            success: true,
            tenantDbName: 'erp_test',
            errorMessage: null,
            steps: ['step1', 'step2']
        );
        
        $this->assertTrue($result->success);
        $this->assertEquals('erp_test', $result->tenantDbName);
        $this->assertNull($result->errorMessage);
        $this->assertCount(2, $result->steps);
    }
    
    /**
     * Test ProvisioningStatus structure
     */
    public function test_provisioning_status_structure(): void
    {
        $status = new ProvisioningStatus(
            status: 'ACTIVE',
            tenantDbName: 'erp_test',
            lastError: null,
            completedSteps: ['step1']
        );
        
        $this->assertEquals('ACTIVE', $status->status);
        $this->assertEquals('erp_test', $status->tenantDbName);
        $this->assertNull($status->lastError);
        $this->assertCount(1, $status->completedSteps);
    }
}
