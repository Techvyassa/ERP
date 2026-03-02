<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Configuration Validator Service
 * 
 * Validates system configuration on startup to ensure all required
 * settings are present and valid. Implements fail-fast pattern.
 */
class ConfigValidator
{
    /**
     * Valid IANA timezone identifiers (subset for validation)
     */
    private const VALID_TIMEZONES = [
        'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Kolkata', 'Asia/Dubai',
        'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney', 'Pacific/Auckland',
    ];

    /**
     * Valid ISO 4217 currency codes
     */
    private const VALID_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD', 'SGD', 'JPY', 'CNY', 'AED',
    ];

    /**
     * Valid ISO 3166-1 alpha-2 country codes (subset)
     */
    private const VALID_COUNTRIES = [
        'US', 'GB', 'IN', 'AU', 'CA', 'SG', 'JP', 'CN', 'AE', 'DE', 'FR',
    ];

    /**
     * Validation errors collected during validation
     */
    private array $errors = [];

    /**
     * Validate all system configuration
     * 
     * @throws RuntimeException if validation fails
     */
    public function validateAll(): void
    {
        $this->errors = [];

        // Validate database configurations
        $this->validateControlDatabaseConfig();
        $this->validateTenantDatabaseConfig();

        // Validate tenant configuration
        $this->validateTenantConfig();

        // Validate subscription configuration
        $this->validateSubscriptionConfig();

        // Validate payment configuration
        $this->validatePaymentConfig();

        // If any errors, throw exception with all errors
        if (!empty($this->errors)) {
            $errorMessage = "Configuration validation failed:\n" . implode("\n", $this->errors);
            throw new RuntimeException($errorMessage);
        }
    }

    /**
     * Validate Control Database configuration
     */
    private function validateControlDatabaseConfig(): void
    {
        $connection = Config::get('database.connections.control');

        if (!$connection) {
            $this->addError('Control database connection not configured');
            return;
        }

        // Validate required connection parameters
        $this->validateRequired('database.connections.control.host', 'Control DB host');
        $this->validateRequired('database.connections.control.database', 'Control DB database name');
        $this->validateRequired('database.connections.control.username', 'Control DB username');

        // Validate database name matches expected pattern
        $dbName = Config::get('database.connections.control.database');
        if ($dbName && $dbName !== 'ERP_saas_control') {
            $this->addError("Control database name should be 'ERP_saas_control', got '{$dbName}'");
        }

        // Test connection
        try {
            DB::connection('control')->getPdo();
        } catch (\Exception $e) {
            $this->addError("Cannot connect to Control database: " . $e->getMessage());
        }
    }

    /**
     * Validate Tenant Database configuration
     */
    private function validateTenantDatabaseConfig(): void
    {
        // Validate tenant connection template exists
        $this->validateRequired('database.connections.tenant.host', 'Tenant DB host');
        $this->validateRequired('database.connections.tenant.username', 'Tenant DB username');

        // Validate tenant database prefix
        $prefix = Config::get('tenant.database_prefix');
        if (!$prefix) {
            $this->addError('Tenant database prefix not configured');
        } elseif (!preg_match('/^[a-z0-9_]+$/', $prefix)) {
            $this->addError("Invalid tenant database prefix '{$prefix}'. Must contain only lowercase letters, numbers, and underscores.");
        }
    }

    /**
     * Validate tenant configuration
     */
    private function validateTenantConfig(): void
    {
        // Validate default timezone
        $timezone = Config::get('tenant.defaults.timezone', 'UTC');
        if (!$this->isValidTimezone($timezone)) {
            $this->addError("Invalid default timezone '{$timezone}'");
        }

        // Validate default currency
        $currency = Config::get('tenant.defaults.currency_code', 'USD');
        if (!$this->isValidCurrency($currency)) {
            $this->addError("Invalid default currency code '{$currency}'");
        }

        // Validate default country
        $country = Config::get('tenant.defaults.country_code', 'US');
        if (!$this->isValidCountry($country)) {
            $this->addError("Invalid default country code '{$country}'");
        }

        // Validate max users is positive integer
        $maxUsers = Config::get('tenant.defaults.max_users', 10);
        if (!is_int($maxUsers) || $maxUsers <= 0) {
            $this->addError("Invalid default max_users '{$maxUsers}'. Must be a positive integer.");
        }

        // Validate slug pattern
        $slugPattern = Config::get('tenant.validation.slug_pattern');
        if ($slugPattern && @preg_match($slugPattern, '') === false) {
            $this->addError("Invalid slug pattern regex: " . error_get_last()['message']);
        }
    }

    /**
     * Validate subscription configuration
     */
    private function validateSubscriptionConfig(): void
    {
        // Validate trial duration
        $trialDays = Config::get('subscription.trial.duration_days', 14);
        if (!is_int($trialDays) || $trialDays <= 0) {
            $this->addError("Invalid trial duration '{$trialDays}'. Must be a positive integer.");
        }

        // Validate grace period
        $graceDays = Config::get('subscription.grace_period.duration_days', 7);
        if (!is_int($graceDays) || $graceDays <= 0) {
            $this->addError("Invalid grace period '{$graceDays}'. Must be a positive integer.");
        }

        // Validate billing cycles
        $billingCycles = Config::get('subscription.billing_cycles', []);
        $requiredCycles = ['MONTHLY', 'QUARTERLY', 'ANNUAL'];
        foreach ($requiredCycles as $cycle) {
            if (!isset($billingCycles[$cycle])) {
                $this->addError("Missing billing cycle configuration for '{$cycle}'");
            }
        }

        // Validate cache TTL
        $cacheTtl = Config::get('subscription.cache.ttl_seconds', 300);
        if (!is_int($cacheTtl) || $cacheTtl < 0) {
            $this->addError("Invalid subscription cache TTL '{$cacheTtl}'. Must be a non-negative integer.");
        }
    }

    /**
     * Validate payment configuration
     */
    private function validatePaymentConfig(): void
    {
        // Validate default gateway
        $defaultGateway = Config::get('payment.default_gateway');
        $validGateways = ['razorpay', 'stripe'];
        if (!in_array($defaultGateway, $validGateways)) {
            $this->addError("Invalid default payment gateway '{$defaultGateway}'. Must be one of: " . implode(', ', $validGateways));
        }

        // Validate Razorpay configuration if enabled
        if (Config::get('payment.gateways.razorpay.enabled', false)) {
            $this->validatePaymentGateway('razorpay', [
                'key_id' => 'Razorpay Key ID',
                'key_secret' => 'Razorpay Key Secret',
            ]);
        }

        // Validate Stripe configuration if enabled
        if (Config::get('payment.gateways.stripe.enabled', false)) {
            $this->validatePaymentGateway('stripe', [
                'secret_key' => 'Stripe Secret Key',
            ]);
        }

        // Validate tax rates
        $cgstRate = Config::get('payment.tax.india_gst.cgst_rate', 9.0);
        $sgstRate = Config::get('payment.tax.india_gst.sgst_rate', 9.0);
        $igstRate = Config::get('payment.tax.india_gst.igst_rate', 18.0);

        if (!is_numeric($cgstRate) || $cgstRate < 0 || $cgstRate > 100) {
            $this->addError("Invalid CGST rate '{$cgstRate}'. Must be between 0 and 100.");
        }
        if (!is_numeric($sgstRate) || $sgstRate < 0 || $sgstRate > 100) {
            $this->addError("Invalid SGST rate '{$sgstRate}'. Must be between 0 and 100.");
        }
        if (!is_numeric($igstRate) || $igstRate < 0 || $igstRate > 100) {
            $this->addError("Invalid IGST rate '{$igstRate}'. Must be between 0 and 100.");
        }

        // Validate supported currencies
        $supportedCurrencies = Config::get('payment.currencies.supported', []);
        foreach ($supportedCurrencies as $currency) {
            if (!$this->isValidCurrency($currency)) {
                $this->addError("Invalid currency code '{$currency}' in supported currencies list");
            }
        }

        // Validate default currency is in supported list
        $defaultCurrency = Config::get('payment.currencies.default', 'USD');
        if (!in_array($defaultCurrency, $supportedCurrencies)) {
            $this->addError("Default currency '{$defaultCurrency}' is not in supported currencies list");
        }
    }

    /**
     * Validate payment gateway configuration
     */
    private function validatePaymentGateway(string $gateway, array $requiredFields): void
    {
        foreach ($requiredFields as $field => $label) {
            $value = Config::get("payment.gateways.{$gateway}.{$field}");
            if (empty($value)) {
                $this->addError("{$label} is required when {$gateway} is enabled");
            }
        }
    }

    /**
     * Validate that a configuration value is present
     */
    private function validateRequired(string $key, string $label): void
    {
        $value = Config::get($key);
        if (empty($value)) {
            $this->addError("{$label} is required (config key: {$key})");
        }
    }

    /**
     * Check if timezone is valid
     */
    private function isValidTimezone(string $timezone): bool
    {
        // Check against common timezones or use PHP's timezone_identifiers_list
        return in_array($timezone, timezone_identifiers_list()) || in_array($timezone, self::VALID_TIMEZONES);
    }

    /**
     * Check if currency code is valid (ISO 4217)
     */
    private function isValidCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), self::VALID_CURRENCIES);
    }

    /**
     * Check if country code is valid (ISO 3166-1 alpha-2)
     */
    private function isValidCountry(string $country): bool
    {
        return in_array(strtoupper($country), self::VALID_COUNTRIES);
    }

    /**
     * Add validation error
     */
    private function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Get all validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if configuration is valid
     */
    public function isValid(): bool
    {
        try {
            $this->validateAll();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * Validate timezone value
     * 
     * @param string $timezone
     * @throws InvalidArgumentException
     */
    public function validateTimezone(string $timezone): void
    {
        if (!$this->isValidTimezone($timezone)) {
            throw new InvalidArgumentException("Invalid timezone '{$timezone}'. Must be a valid IANA timezone identifier.");
        }
    }

    /**
     * Validate currency code
     * 
     * @param string $currency
     * @throws InvalidArgumentException
     */
    public function validateCurrency(string $currency): void
    {
        if (!$this->isValidCurrency($currency)) {
            throw new InvalidArgumentException("Invalid currency code '{$currency}'. Must be a valid ISO 4217 currency code.");
        }
    }

    /**
     * Validate country code
     * 
     * @param string $country
     * @throws InvalidArgumentException
     */
    public function validateCountry(string $country): void
    {
        if (!$this->isValidCountry($country)) {
            throw new InvalidArgumentException("Invalid country code '{$country}'. Must be a valid ISO 3166-1 alpha-2 country code.");
        }
    }
}
