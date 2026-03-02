<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\ProvisionTenantJob;

class ProvisionTenantJobTest extends TestCase
{
    /**
     * Test that ProvisionTenantJob can be instantiated
     */
    public function test_job_can_be_instantiated(): void
    {
        $job = new ProvisionTenantJob(orgId: 1);
        
        $this->assertInstanceOf(ProvisionTenantJob::class, $job);
        $this->assertEquals(1, $job->orgId);
    }
    
    /**
     * Test job configuration
     */
    public function test_job_has_correct_configuration(): void
    {
        $job = new ProvisionTenantJob(orgId: 1);
        
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }
    
    /**
     * Test backoff calculation
     */
    public function test_backoff_calculation(): void
    {
        $job = new ProvisionTenantJob(orgId: 1);
        
        // Mock attempts
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('backoff');
        $method->setAccessible(true);
        
        // First attempt: 30s
        $this->assertEquals(30, $method->invoke($job));
    }
}
