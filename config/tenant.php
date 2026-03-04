<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Tenant Mode
    |--------------------------------------------------------------------------
    |
    | Determines the default URL structure for tenant access:
    | - 'subdomain': company1.yoursite.com/dashboard
    | - 'path': yoursite.com/org/company1/dashboard
    |
    */
    'default_mode' => env('TENANT_MODE', 'subdomain'),
    
    /*
    |--------------------------------------------------------------------------
    | Allow Both Modes
    |--------------------------------------------------------------------------
    |
    | If true, both subdomain and path-based URLs will work
    | If false, only the default mode will be accessible
    |
    */
    'allow_both_modes' => env('TENANT_ALLOW_BOTH', true),
    
    /*
    |--------------------------------------------------------------------------
    | Subdomain Configuration
    |--------------------------------------------------------------------------
    */
    'subdomain' => [
        // Reserved subdomains that cannot be used as organization slugs
        'reserved' => [
            'www',
            'api',
            'admin',
            'app',
            'mail',
            'ftp',
            'localhost',
            'staging',
            'dev',
            'test',
        ],
        
        // Automatically redirect path-based URLs to subdomain
        'auto_redirect' => env('TENANT_SUBDOMAIN_AUTO_REDIRECT', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Path-Based Configuration
    |--------------------------------------------------------------------------
    */
    'path' => [
        // URL prefix for path-based tenant access
        'prefix' => 'org',
        
        // Automatically redirect subdomain URLs to path-based
        'auto_redirect' => env('TENANT_PATH_AUTO_REDIRECT', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        // Control database connection name
        'control_connection' => 'control',
        
        // Tenant database connection name
        'tenant_connection' => 'tenant',
        
        // Database name prefix for tenant databases
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Cache tenant data for faster lookups
        'enabled' => env('TENANT_CACHE_ENABLED', true),
        
        // Cache TTL in seconds
        'ttl' => env('TENANT_CACHE_TTL', 3600),
        
        // Cache key prefix
        'prefix' => 'tenant:',
    ],
];
