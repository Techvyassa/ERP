<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateway integrations (Razorpay, Stripe).
    |
    */

    // Default payment gateway
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'razorpay'),

    // Available payment gateways
    'gateways' => [
        'razorpay' => [
            'enabled' => env('PAYMENT_RAZORPAY_ENABLED', true),
            'key_id' => env('RAZORPAY_KEY_ID', ''),
            'key_secret' => env('RAZORPAY_KEY_SECRET', ''),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
            'currency' => env('RAZORPAY_CURRENCY', 'INR'),
            'test_mode' => env('RAZORPAY_TEST_MODE', true),
            
            // API endpoints
            'api_base_url' => env('RAZORPAY_API_URL', 'https://api.razorpay.com/v1'),
            
            // Webhook configuration
            'webhook_events' => [
                'payment.authorized',
                'payment.captured',
                'payment.failed',
                'refund.created',
                'refund.processed',
            ],
        ],

        'stripe' => [
            'enabled' => env('PAYMENT_STRIPE_ENABLED', false),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
            'currency' => env('STRIPE_CURRENCY', 'USD'),
            'test_mode' => env('STRIPE_TEST_MODE', true),
            
            // API version
            'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),
            
            // Webhook configuration
            'webhook_events' => [
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
                'charge.refunded',
                'invoice.payment_succeeded',
                'invoice.payment_failed',
            ],
        ],
    ],

    // Payment processing settings
    'processing' => [
        // Timeout for payment gateway API calls (seconds)
        'api_timeout' => env('PAYMENT_API_TIMEOUT', 30),
        
        // Retry failed payments
        'retry_failed_payments' => env('PAYMENT_RETRY_FAILED', true),
        'max_retry_attempts' => env('PAYMENT_MAX_RETRY_ATTEMPTS', 3),
        'retry_delay_hours' => env('PAYMENT_RETRY_DELAY_HOURS', 24),
        
        // Payment reference generation
        'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'PAY'),
        'reference_length' => 16,
        
        // Queue settings for async payment processing
        'queue_connection' => env('PAYMENT_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('PAYMENT_QUEUE_NAME', 'payments'),
    ],

    // Tax calculation settings
    'tax' => [
        // Tax calculation method
        'calculation_method' => env('PAYMENT_TAX_METHOD', 'country_based'),
        
        // India GST rates
        'india_gst' => [
            'cgst_rate' => env('PAYMENT_INDIA_CGST_RATE', 9.0), // 9%
            'sgst_rate' => env('PAYMENT_INDIA_SGST_RATE', 9.0), // 9%
            'igst_rate' => env('PAYMENT_INDIA_IGST_RATE', 18.0), // 18%
        ],
        
        // Country-specific tax rates (ISO 3166-1 alpha-2 codes)
        'country_rates' => [
            'IN' => 18.0, // India GST
            'US' => 0.0,  // US sales tax varies by state
            'GB' => 20.0, // UK VAT
            'EU' => 20.0, // EU VAT (varies by country)
            'AU' => 10.0, // Australia GST
            'CA' => 13.0, // Canada HST (varies by province)
        ],
        
        // Tax exemption settings
        'allow_exemptions' => env('PAYMENT_ALLOW_TAX_EXEMPTIONS', false),
    ],

    // Refund settings
    'refunds' => [
        // Allow refunds
        'enabled' => env('PAYMENT_REFUNDS_ENABLED', true),
        
        // Refund processing method
        'processing_method' => env('PAYMENT_REFUND_METHOD', 'gateway'), // gateway or manual
        
        // Maximum refund period (days after payment)
        'max_refund_period_days' => env('PAYMENT_MAX_REFUND_PERIOD', 90),
        
        // Allow partial refunds
        'allow_partial' => env('PAYMENT_ALLOW_PARTIAL_REFUNDS', true),
        
        // Require admin approval for refunds
        'require_approval' => env('PAYMENT_REFUND_REQUIRE_APPROVAL', true),
        
        // Send refund confirmation email
        'send_confirmation' => env('PAYMENT_REFUND_SEND_CONFIRMATION', true),
    ],

    // Payment record settings (immutable ledger)
    'ledger' => [
        // Prevent updates to payment records
        'immutable' => env('PAYMENT_LEDGER_IMMUTABLE', true),
        
        // Prevent deletion of payment records
        'prevent_deletion' => env('PAYMENT_LEDGER_PREVENT_DELETION', true),
        
        // Audit log all payment operations
        'audit_logging' => env('PAYMENT_AUDIT_LOGGING', true),
        
        // Retention period for payment records (days, 0 = forever)
        'retention_period_days' => env('PAYMENT_RETENTION_PERIOD', 0),
    ],

    // Webhook security
    'webhook' => [
        // Verify webhook signatures
        'verify_signatures' => env('PAYMENT_WEBHOOK_VERIFY_SIGNATURES', true),
        
        // Webhook timeout (seconds)
        'timeout' => env('PAYMENT_WEBHOOK_TIMEOUT', 10),
        
        // Log all webhook requests
        'log_requests' => env('PAYMENT_WEBHOOK_LOG_REQUESTS', true),
        
        // Allowed IP addresses for webhooks (empty = allow all)
        'allowed_ips' => env('PAYMENT_WEBHOOK_ALLOWED_IPS', ''),
        
        // Rate limiting for webhook endpoints
        'rate_limit' => env('PAYMENT_WEBHOOK_RATE_LIMIT', 100), // requests per minute
    ],

    // Notification settings
    'notifications' => [
        // Send email on successful payment
        'email_on_success' => env('PAYMENT_EMAIL_ON_SUCCESS', true),
        
        // Send email on failed payment
        'email_on_failure' => env('PAYMENT_EMAIL_ON_FAILURE', true),
        
        // Send email on refund
        'email_on_refund' => env('PAYMENT_EMAIL_ON_REFUND', true),
        
        // Admin notification email
        'admin_email' => env('PAYMENT_ADMIN_EMAIL', 'admin@example.com'),
        
        // Send admin notification on payment failures
        'notify_admin_on_failure' => env('PAYMENT_NOTIFY_ADMIN_ON_FAILURE', true),
    ],

    // Currency settings
    'currencies' => [
        // Supported currencies (ISO 4217 codes)
        'supported' => ['USD', 'INR', 'EUR', 'GBP', 'AUD', 'CAD'],
        
        // Default currency
        'default' => env('PAYMENT_DEFAULT_CURRENCY', 'USD'),
        
        // Currency conversion settings
        'conversion' => [
            'enabled' => env('PAYMENT_CURRENCY_CONVERSION', false),
            'api_provider' => env('PAYMENT_CONVERSION_API', 'exchangerate-api'),
            'api_key' => env('PAYMENT_CONVERSION_API_KEY', ''),
            'cache_rates_hours' => env('PAYMENT_CONVERSION_CACHE_HOURS', 24),
        ],
    ],

    // Testing and development
    'testing' => [
        // Use test mode in development
        'force_test_mode' => env('PAYMENT_FORCE_TEST_MODE', env('APP_ENV') !== 'production'),
        
        // Test card numbers (for development)
        'test_cards' => [
            'razorpay' => [
                'success' => '4111111111111111',
                'failure' => '4000000000000002',
            ],
            'stripe' => [
                'success' => '4242424242424242',
                'failure' => '4000000000000002',
            ],
        ],
        
        // Mock payment gateway in testing
        'mock_gateway' => env('PAYMENT_MOCK_GATEWAY', env('APP_ENV') === 'testing'),
    ],
];
