<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Common test helper functions
 */
class TestHelpers
{
    /**
     * Clear all Redis data
     */
    public static function clearRedis(): void
    {
        try {
            Redis::flushall();
        } catch (\Exception $e) {
            // Redis might not be available in test environment
        }
    }

    /**
     * Clear all cache data
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Clear permission cache for a user
     * 
     * @param int $userId The user ID
     */
    public static function clearPermissionCache(int $userId): void
    {
        $cacheKey = "rbac:user:{$userId}:permissions";
        Cache::forget($cacheKey);
    }

    /**
     * Clear rate limit counter for an organization
     * 
     * @param int $orgId The organization ID
     */
    public static function clearRateLimitCounter(int $orgId): void
    {
        $date = now()->format('Y-m-d');
        $key = "rate_limit:org:{$orgId}:{$date}";
        
        try {
            Redis::del($key);
        } catch (\Exception $e) {
            // Redis might not be available
        }
    }

    /**
     * Set rate limit counter for an organization
     * 
     * @param int $orgId The organization ID
     * @param int $count The request count
     */
    public static function setRateLimitCounter(int $orgId, int $count): void
    {
        $date = now()->format('Y-m-d');
        $key = "rate_limit:org:{$orgId}:{$date}";
        
        try {
            Redis::set($key, $count);
            Redis::expire($key, 86400); // 24 hours
        } catch (\Exception $e) {
            // Redis might not be available
        }
    }

    /**
     * Get rate limit counter for an organization
     * 
     * @param int $orgId The organization ID
     * @return int The current request count
     */
    public static function getRateLimitCounter(int $orgId): int
    {
        $date = now()->format('Y-m-d');
        $key = "rate_limit:org:{$orgId}:{$date}";
        
        try {
            return (int) Redis::get($key);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Truncate all tables in a database connection
     * 
     * @param string $connection The database connection name
     */
    public static function truncateAllTables(string $connection = 'control'): void
    {
        DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = DB::connection($connection)
            ->select('SHOW TABLES');
        
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            DB::connection($connection)->table($tableName)->truncate();
        }
        
        DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Generate a unique organization slug
     * 
     * @return string The unique slug
     */
    public static function generateUniqueOrgSlug(): string
    {
        return 'test-org-' . uniqid() . '-' . rand(1000, 9999);
    }

    /**
     * Generate a unique employee code
     * 
     * @return string The unique employee code
     */
    public static function generateUniqueEmployeeCode(): string
    {
        return 'EMP' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique payment reference
     * 
     * @return string The unique payment reference
     */
    public static function generateUniquePaymentReference(): string
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }

    /**
     * Assert JSON response structure
     * 
     * @param array $response The response data
     * @param bool $success Expected success value
     */
    public static function assertJsonResponseStructure(array $response, bool $success = true): void
    {
        \PHPUnit\Framework\Assert::assertArrayHasKey('success', $response);
        \PHPUnit\Framework\Assert::assertEquals($success, $response['success']);
        \PHPUnit\Framework\Assert::assertArrayHasKey('message', $response);
        \PHPUnit\Framework\Assert::assertArrayHasKey('request_id', $response);
        \PHPUnit\Framework\Assert::assertArrayHasKey('timestamp', $response);
        
        if ($success) {
            \PHPUnit\Framework\Assert::assertArrayHasKey('data', $response);
        } else {
            \PHPUnit\Framework\Assert::assertArrayHasKey('error', $response);
        }
    }

    /**
     * Assert error response structure
     * 
     * @param array $response The response data
     * @param string $expectedCode Expected error code
     */
    public static function assertErrorResponse(array $response, string $expectedCode): void
    {
        self::assertJsonResponseStructure($response, false);
        \PHPUnit\Framework\Assert::assertArrayHasKey('code', $response['error']);
        \PHPUnit\Framework\Assert::assertEquals($expectedCode, $response['error']['code']);
    }

    /**
     * Create a mock JWT payload
     * 
     * @param int $userId The user ID
     * @param int $orgId The organization ID
     * @param string $orgSlug The organization slug
     * @return array The JWT payload
     */
    public static function createMockJwtPayload(
        int $userId,
        int $orgId,
        string $orgSlug
    ): array {
        return [
            'sub' => $userId,
            'org_id' => $orgId,
            'org_slug' => $orgSlug,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
            'type' => 'access',
        ];
    }

    /**
     * Wait for a condition to be true (polling)
     * 
     * @param callable $condition The condition to check
     * @param int $timeout Timeout in seconds
     * @param int $interval Polling interval in milliseconds
     * @return bool True if condition met, false if timeout
     */
    public static function waitFor(
        callable $condition,
        int $timeout = 5,
        int $interval = 100
    ): bool {
        $start = microtime(true);
        
        while (microtime(true) - $start < $timeout) {
            if ($condition()) {
                return true;
            }
            usleep($interval * 1000);
        }
        
        return false;
    }

    /**
     * Get all module codes used in the system
     * 
     * @return array Array of module codes
     */
    public static function getAllModuleCodes(): array
    {
        return [
            'PR',
            'PO',
            'GRN',
            'QC',
            'INVOICE',
            'PAYMENT',
            'INVENTORY',
            'REPORTS',
        ];
    }

    /**
     * Get all subscription statuses
     * 
     * @return array Array of subscription statuses
     */
    public static function getAllSubscriptionStatuses(): array
    {
        return [
            'TRIAL',
            'ACTIVE',
            'PAST_DUE',
            'CANCELLED',
            'EXPIRED',
        ];
    }

    /**
     * Get all organization registration statuses
     * 
     * @return array Array of registration statuses
     */
    public static function getAllRegistrationStatuses(): array
    {
        return [
            'PENDING',
            'ACTIVE',
            'SUSPENDED',
            'TERMINATED',
        ];
    }

    /**
     * Get all payment statuses
     * 
     * @return array Array of payment statuses
     */
    public static function getAllPaymentStatuses(): array
    {
        return [
            'PENDING',
            'SUCCESS',
            'FAILED',
            'REFUNDED',
            'PARTIALLY_REFUNDED',
        ];
    }

    /**
     * Get all payment types
     * 
     * @return array Array of payment types
     */
    public static function getAllPaymentTypes(): array
    {
        return [
            'INVOICE',
            'ADVANCE',
            'REFUND',
            'CREDIT_NOTE',
            'ADJUSTMENT',
        ];
    }
}
