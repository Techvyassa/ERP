<?php

namespace App\Contracts;

use App\Exceptions\TenantDatabaseConnectionException;
use App\Exceptions\TenantDatabaseNotFoundException;

interface DatabaseConnectionRouter
{
    /**
     * Switch to tenant database connection
     * 
     * @param string $tenantDbName Database name (e.g., "erp_acme")
     * @throws TenantDatabaseNotFoundException
     * @throws TenantDatabaseConnectionException
     */
    public function switchToTenant(string $tenantDbName): void;
    
    /**
     * Switch to control database connection
     */
    public function switchToControl(): void;
    
    /**
     * Get current active connection name
     * 
     * @return string Connection name ("control" or tenant db name)
     */
    public function getCurrentConnection(): string;
    
    /**
     * Verify tenant database exists and is accessible
     * 
     * @param string $tenantDbName
     * @return bool
     */
    public function verifyTenantDatabase(string $tenantDbName): bool;
}
