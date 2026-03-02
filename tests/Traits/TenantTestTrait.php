<?php

namespace Tests\Traits;

use App\Models\Control\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

/**
 * Trait for setting up tenant database connections in tests
 */
trait TenantTestTrait
{
    /**
     * The current test organization
     */
    protected ?Organization $testOrganization = null;

    /**
     * The current tenant database name
     */
    protected ?string $testTenantDb = null;

    /**
     * Set up tenant database for testing
     * 
     * @param Organization|null $organization Optional organization to use
     * @return Organization The test organization
     */
    protected function setupTenantDatabase(?Organization $organization = null): Organization
    {
        // Use provided organization or create a new one
        $this->testOrganization = $organization ?? Organization::factory()->active()->create();
        $this->testTenantDb = $this->testOrganization->tenant_db_name;

        // Configure tenant database connection
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Reconnect to tenant database
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Run tenant migrations
        $this->runTenantMigrations();

        return $this->testOrganization;
    }

    /**
     * Switch to tenant database connection
     * 
     * @param Organization $organization The organization to switch to
     */
    protected function switchToTenant(Organization $organization): void
    {
        $this->testOrganization = $organization;
        $this->testTenantDb = $organization->tenant_db_name;

        // Set the default database connection to tenant
        Config::set('database.default', 'tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Switch to control database connection
     */
    protected function switchToControl(): void
    {
        Config::set('database.default', 'control');
        DB::setDefaultConnection('control');
    }

    /**
     * Run tenant database migrations
     */
    protected function runTenantMigrations(): void
    {
        // Run tenant migrations on the tenant connection
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    /**
     * Seed tenant database with default data
     * 
     * @param array $seeders Optional array of seeder class names
     */
    protected function seedTenantDatabase(array $seeders = []): void
    {
        $this->switchToTenant($this->testOrganization);

        if (empty($seeders)) {
            // Default seeders
            $seeders = [
                \Database\Seeders\Tenant\DefaultRoleSeeder::class,
                \Database\Seeders\Tenant\DefaultRolePermissionSeeder::class,
            ];
        }

        foreach ($seeders as $seeder) {
            $this->seed($seeder);
        }
    }

    /**
     * Clean up tenant database after test
     */
    protected function tearDownTenantDatabase(): void
    {
        if ($this->testTenantDb) {
            // Rollback tenant migrations
            try {
                Artisan::call('migrate:rollback', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }

        // Reset to control database
        $this->switchToControl();
    }

    /**
     * Create a test tenant with full setup
     * 
     * @param array $organizationAttributes Optional organization attributes
     * @return Organization The created organization
     */
    protected function createTestTenant(array $organizationAttributes = []): Organization
    {
        $organization = Organization::factory()->active()->create($organizationAttributes);
        $this->setupTenantDatabase($organization);
        $this->seedTenantDatabase();
        
        return $organization;
    }

    /**
     * Assert that a record exists in the tenant database
     * 
     * @param string $table The table name
     * @param array $data The data to check
     */
    protected function assertTenantDatabaseHas(string $table, array $data): void
    {
        $this->assertDatabaseHas($table, $data, 'tenant');
    }

    /**
     * Assert that a record does not exist in the tenant database
     * 
     * @param string $table The table name
     * @param array $data The data to check
     */
    protected function assertTenantDatabaseMissing(string $table, array $data): void
    {
        $this->assertDatabaseMissing($table, $data, 'tenant');
    }
}
