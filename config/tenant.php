<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific database settings and provisioning.
    |
    */

    // Tenant database naming pattern
    'database_prefix' => env('TENANT_DB_PREFIX', 'erp_'),

    // Tenant database connection settings
    'connection' => [
        'host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
        'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => env('TENANT_DB_CHARSET', 'utf8mb4'),
        'collation' => env('TENANT_DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
        
        // Grant host for database permissions (% = all hosts, localhost = local only)
        'grant_host' => env('TENANT_DB_GRANT_HOST', '%'),
    ],

    // Provisioning settings
    'provisioning' => [
        // Queue connection for async provisioning
        'queue_connection' => env('TENANT_PROVISIONING_QUEUE', 'default'),
        
        // Queue name for provisioning jobs
        'queue_name' => env('TENANT_PROVISIONING_QUEUE_NAME', 'tenant-provisioning'),
        
        // Timeout for provisioning job (seconds)
        'timeout' => env('TENANT_PROVISIONING_TIMEOUT', 300),
        
        // Maximum provisioning attempts
        'max_attempts' => env('TENANT_PROVISIONING_MAX_ATTEMPTS', 3),
        
        // Send welcome email after provisioning
        'send_welcome_email' => env('TENANT_SEND_WELCOME_EMAIL', true),
        
        // Admin notification email for provisioning failures
        'admin_notification_email' => env('TENANT_ADMIN_NOTIFICATION_EMAIL', 'admin@example.com'),
    ],

    // Default tenant settings
    'defaults' => [
        // Default timezone for new tenants
        'timezone' => env('TENANT_DEFAULT_TIMEZONE', 'UTC'),
        
        // Default currency for new tenants
        'currency_code' => env('TENANT_DEFAULT_CURRENCY', 'USD'),
        
        // Default country code for new tenants
        'country_code' => env('TENANT_DEFAULT_COUNTRY', 'US'),
        
        // Default max users for new tenants
        'max_users' => env('TENANT_DEFAULT_MAX_USERS', 10),
        
        // Initial admin user settings
        'admin_role_code' => 'ADMIN',
        'root_department_code' => 'ROOT',
        'root_department_name' => 'Root Department',
        
        // Temporary password settings
        'temp_password_length' => 12,
        'temp_password_expires_hours' => 24,
    ],

    // Tenant isolation settings
    'isolation' => [
        // Log all database connection switches
        'log_connection_switches' => env('TENANT_LOG_CONNECTION_SWITCHES', true),
        
        // Verify tenant database exists before switching
        'verify_database_exists' => env('TENANT_VERIFY_DATABASE_EXISTS', true),
        
        // Cache tenant resolution for performance
        'cache_tenant_resolution' => env('TENANT_CACHE_RESOLUTION', true),
        'cache_ttl_seconds' => env('TENANT_CACHE_TTL', 300), // 5 minutes
    ],

    // Validation rules
    'validation' => [
        // Allowed characters in org_slug (alphanumeric and hyphens)
        'slug_pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        
        // Minimum and maximum slug length
        'slug_min_length' => 3,
        'slug_max_length' => 50,
        
        // Reserved slugs that cannot be used
        'reserved_slugs' => [
            'admin', 'api', 'app', 'control', 'system', 'test', 'demo',
            'www', 'mail', 'ftp', 'localhost', 'staging', 'production',
        ],
    ],
];
