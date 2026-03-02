<?php

namespace App\Exceptions;

use Exception;

class TenantDatabaseNotFoundException extends Exception
{
    public function __construct(string $tenantDbName)
    {
        parent::__construct("Tenant database not found: {$tenantDbName}", 404);
    }
}
