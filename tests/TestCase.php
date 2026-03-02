<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\TenantTestTrait;
use Tests\Traits\AuthenticationTestTrait;
use Tests\Traits\SubscriptionTestTrait;
use Tests\Helpers\TestHelpers;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use TenantTestTrait;
    use AuthenticationTestTrait;
    use SubscriptionTestTrait;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache and Redis before each test
        TestHelpers::clearCache();
        TestHelpers::clearRedis();

        // Use control_test connection for testing
        config(['database.default' => 'control_test']);
        config(['database.connections.control' => config('database.connections.control_test')]);
        config(['database.connections.tenant' => config('database.connections.tenant_test')]);
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        // Clean up tenant database if it was set up
        if ($this->testTenantDb) {
            $this->tearDownTenantDatabase();
        }

        parent::tearDown();
    }

    /**
     * Assert that the response has a successful JSON structure
     */
    protected function assertSuccessResponse(): void
    {
        $this->assertJsonStructure([
            'success',
            'data',
            'message',
            'request_id',
            'timestamp',
        ]);

        $this->assertJson(['success' => true]);
    }

    /**
     * Assert that the response has an error JSON structure
     */
    protected function assertErrorResponse(string $errorCode = null): void
    {
        $this->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'details',
            ],
            'message',
            'request_id',
            'timestamp',
        ]);

        $this->assertJson(['success' => false]);

        if ($errorCode) {
            $this->assertJson([
                'error' => [
                    'code' => $errorCode,
                ],
            ]);
        }
    }
}

