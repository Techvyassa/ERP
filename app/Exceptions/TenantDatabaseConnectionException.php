<?php

namespace App\Exceptions;

use Exception;

class TenantDatabaseConnectionException extends Exception
{
    public function __construct(string $tenantDbName, string $reason = '')
    {
        $message = "Failed to connect to tenant database: {$tenantDbName}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        parent::__construct($message, 503);
    }
}
