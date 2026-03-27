<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for subscription lifecycle, billing, and access control.
    |
    */

    // Trial subscription settings
    'trial' => [
        // Trial period duration in days
        'duration_days' => env('SUBSCRIPTION_TRIAL_DAYS', 14),
        
        // Automatically create trial on tenant activation
        'auto_create' => env('SUBSCRIPTION_AUTO_CREATE_TRIAL', true),
        
        // Default trial plan code
        'default_plan_code' => env('SUBSCRIPTION_TRIAL_PLAN_CODE', 'TRIAL'),
    ],

    // Billing cycle settings
    'billing_cycles' => [
        'MONTHLY' => [
            'days' => 30,
            'label' => 'Monthly',
        ],
        'QUARTERLY' => [
            'days' => 90,
            'label' => 'Quarterly',
        ],
        'ANNUAL' => [
            'days' => 365,
            'label' => 'Annual',
        ],
    ],

    // Grace period settings
    'grace_period' => [
        // Grace period duration for PAST_DUE subscriptions (days)
        'duration_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),
        
        // Allow read-only access during grace period
        'allow_read_only' => env('SUBSCRIPTION_GRACE_ALLOW_READ_ONLY', true),
        
        // Send reminder emails during grace period
        'send_reminders' => env('SUBSCRIPTION_GRACE_SEND_REMINDERS', true),
        
        // Reminder email schedule (days before grace period ends)
        'reminder_schedule' => [7, 3, 1],
    ],

    // Subscription status transitions
    'status_transitions' => [
        'TRIAL' => ['ACTIVE', 'EXPIRED'],
        'ACTIVE' => ['PAST_DUE', 'CANCELLED', 'EXPIRED'],
        'PAST_DUE' => ['ACTIVE', 'EXPIRED'],
        'CANCELLED' => ['EXPIRED'],
        'EXPIRED' => [], // Terminal state
    ],

    // Active subscriptions cache settings
    'cache' => [
        // Cache active subscriptions for performance
        'enabled' => env('SUBSCRIPTION_CACHE_ENABLED', true),
        
        // Cache TTL in seconds (5 minutes)
        'ttl_seconds' => env('SUBSCRIPTION_CACHE_TTL', 300),
        
        // Cache key prefix
        'key_prefix' => 'subscription:active:',
    ],

    // Scheduled jobs configuration
    'jobs' => [
        // Check trial expirations (daily at 00:00 UTC)
        'check_trial_expiration' => [
            'enabled' => env('SUBSCRIPTION_JOB_CHECK_TRIALS', true),
            'schedule' => env('SUBSCRIPTION_JOB_CHECK_TRIALS_SCHEDULE', '0 0 * * *'),
        ],
        
        // Process subscription renewals (daily at 01:00 UTC)
        'process_renewals' => [
            'enabled' => env('SUBSCRIPTION_JOB_PROCESS_RENEWALS', true),
            'schedule' => env('SUBSCRIPTION_JOB_PROCESS_RENEWALS_SCHEDULE', '0 1 * * *'),
        ],
        
        // Enforce grace period (daily at 02:00 UTC)
        'enforce_grace_period' => [
            'enabled' => env('SUBSCRIPTION_JOB_ENFORCE_GRACE', true),
            'schedule' => env('SUBSCRIPTION_JOB_ENFORCE_GRACE_SCHEDULE', '0 2 * * *'),
        ],
    ],

    // Module access control
    'modules' => [
        // Available module codes
        'available_modules' => [
            'PR' => 'Purchase Requisition',
            'PO' => 'Purchase Order',
            'GRN' => 'Goods Receipt Note',
            'QC' => 'Quality Control',
            'INVOICE' => 'Invoice Management',
            'PAYMENT' => 'Payment Processing',
            'INVENTORY' => 'Inventory Management',
            'WAREHOUSE' => 'Warehouse Management',
            'REPORTS' => 'Reports & Analytics',
            'PRODUCTION' => 'Production Management',
            'SETTINGS' => 'System Settings',
        ],
        
        // Modules always included (regardless of plan)
        'core_modules' => ['SETTINGS'],
    ],

    // Cancellation settings
    'cancellation' => [
        // Allow access until period end after cancellation
        'allow_until_period_end' => env('SUBSCRIPTION_CANCEL_ALLOW_UNTIL_END', true),
        
        // Require cancellation reason
        'require_reason' => env('SUBSCRIPTION_CANCEL_REQUIRE_REASON', true),
        
        // Send cancellation confirmation email
        'send_confirmation' => env('SUBSCRIPTION_CANCEL_SEND_CONFIRMATION', true),
        
        // Offer retention incentives
        'offer_retention' => env('SUBSCRIPTION_CANCEL_OFFER_RETENTION', false),
    ],

    // Upgrade/downgrade settings
    'plan_changes' => [
        // Allow mid-cycle plan changes
        'allow_mid_cycle' => env('SUBSCRIPTION_ALLOW_MID_CYCLE_CHANGES', true),
        
        // Prorate charges for mid-cycle upgrades
        'prorate_upgrades' => env('SUBSCRIPTION_PRORATE_UPGRADES', true),
        
        // Prorate credits for mid-cycle downgrades
        'prorate_downgrades' => env('SUBSCRIPTION_PRORATE_DOWNGRADES', true),
        
        // Apply plan changes immediately or at next billing cycle
        'apply_immediately' => env('SUBSCRIPTION_APPLY_CHANGES_IMMEDIATELY', true),
    ],

    // Notification settings
    'notifications' => [
        // Send email on subscription status changes
        'email_on_status_change' => env('SUBSCRIPTION_EMAIL_STATUS_CHANGE', true),
        
        // Send email before renewal
        'email_before_renewal_days' => env('SUBSCRIPTION_EMAIL_BEFORE_RENEWAL', 7),
        
        // Send email on payment failure
        'email_on_payment_failure' => env('SUBSCRIPTION_EMAIL_PAYMENT_FAILURE', true),
        
        // Admin notification email
        'admin_email' => env('SUBSCRIPTION_ADMIN_EMAIL', 'admin@example.com'),
    ],
];
