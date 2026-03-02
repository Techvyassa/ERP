<?php

namespace App\Contracts;

interface TenantProvisioningService
{
    /**
     * Provision a new tenant database
     * 
     * @param int $orgId Organization ID from Control DB
     * @return ProvisioningResult
     * @throws \Exception
     */
    public function provisionTenant(int $orgId): ProvisioningResult;
    
    /**
     * Rollback failed provisioning
     * 
     * @param int $orgId
     * @return void
     */
    public function rollbackProvisioning(int $orgId): void;
    
    /**
     * Check provisioning status
     * 
     * @param int $orgId
     * @return ProvisioningStatus
     */
    public function getProvisioningStatus(int $orgId): ProvisioningStatus;
}

class ProvisioningResult
{
    public function __construct(
        public bool $success,
        public string $tenantDbName,
        public ?string $errorMessage = null,
        public array $steps = []
    ) {}
}

class ProvisioningStatus
{
    public function __construct(
        public string $status,
        public ?string $tenantDbName = null,
        public ?string $lastError = null,
        public array $completedSteps = []
    ) {}
}
