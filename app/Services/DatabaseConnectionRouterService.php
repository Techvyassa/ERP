<?php

namespace App\Services;

use App\Contracts\DatabaseConnectionRouter;
use App\Exceptions\TenantDatabaseConnectionException;
use App\Exceptions\TenantDatabaseNotFoundException;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class DatabaseConnectionRouterService implements DatabaseConnectionRouter
{
    private string $currentConnection = 'control';
    
    /**
     * Switch to tenant database connection
     * 
     * @param string $tenantDbName Database name (e.g., "erp_acme")
     * @throws TenantDatabaseNotFoundException
     * @throws TenantDatabaseConnectionException
     */
    public function switchToTenant(string $tenantDbName): void
    {
        try {
            // Verify tenant database exists before switching
            if (!$this->verifyTenantDatabase($tenantDbName)) {
                throw new TenantDatabaseNotFoundException($tenantDbName);
            }
            
            // Configure tenant connection dynamically
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
                'database' => $tenantDbName,
                'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'forge')),
                'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);
            
            // Purge existing connection and reconnect
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Test the connection
            DB::connection('tenant')->getPdo();
            
            // Update current connection state
            $this->currentConnection = $tenantDbName;
            
            // Log the connection switch
            Log::info('Database connection switched to tenant', [
                'tenant_db_name' => $tenantDbName,
                'timestamp' => now()->toIso8601String(),
            ]);
            
        } catch (TenantDatabaseNotFoundException $e) {
            throw $e;
        } catch (PDOException $e) {
            Log::error('Failed to connect to tenant database', [
                'tenant_db_name' => $tenantDbName,
                'error' => $e->getMessage(),
            ]);
            throw new TenantDatabaseConnectionException($tenantDbName, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error during tenant connection switch', [
                'tenant_db_name' => $tenantDbName,
                'error' => $e->getMessage(),
            ]);
            throw new TenantDatabaseConnectionException($tenantDbName, $e->getMessage());
        }
    }
    
    /**
     * Switch to control database connection
     */
    public function switchToControl(): void
    {
        $this->currentConnection = 'control';
        
        Log::info('Database connection switched to control', [
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Get current active connection name
     * 
     * @return string Connection name ("control" or tenant db name)
     */
    public function getCurrentConnection(): string
    {
        return $this->currentConnection;
    }
    
    /**
     * Verify tenant database exists and is accessible
     * 
     * @param string $tenantDbName
     * @return bool
     */
    public function verifyTenantDatabase(string $tenantDbName): bool
    {
        try {
            // Query control database to check if tenant database exists
            $result = DB::connection('control')
                ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenantDbName]);
            
            return !empty($result);
        } catch (\Exception $e) {
            Log::error('Failed to verify tenant database existence', [
                'tenant_db_name' => $tenantDbName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
