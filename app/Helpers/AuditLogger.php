<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * AuditLogger - Centralized audit logging for compliance
 * 
 * Provides consistent structured logging for all audit events
 * with standardized format: timestamp, level, context, message
 */
class AuditLogger
{
    /**
     * Log authentication attempt
     */
    public static function logAuthAttempt(
        string $email,
        string $orgSlug,
        bool $success,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?int $userId = null
    ): void {
        $context = [
            'event_type' => 'authentication',
            'email' => $email,
            'org_slug' => $orgSlug,
            'success' => $success,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($userId) {
            $context['user_id'] = $userId;
        }

        if ($reason) {
            $context['reason'] = $reason;
        }

        $message = $success 
            ? "Authentication successful for {$email} in organization {$orgSlug}"
            : "Authentication failed for {$email} in organization {$orgSlug}: {$reason}";

        Log::channel('auth')->info($message, $context);
        Log::channel('audit')->info($message, $context);
    }

    /**
     * Log database connection switch
     */
    public static function logDatabaseSwitch(
        int $orgId,
        string $tenantDbName,
        string $orgSlug
    ): void {
        $context = [
            'event_type' => 'database_connection',
            'org_id' => $orgId,
            'tenant_db_name' => $tenantDbName,
            'org_slug' => $orgSlug,
            'timestamp' => now()->toIso8601String(),
        ];

        $message = "Database connection switched to {$tenantDbName} for organization {$orgSlug} (org_id: {$orgId})";

        Log::channel('database')->info($message, $context);
        Log::channel('audit')->info($message, $context);
    }

    /**
     * Log RBAC permission denial
     */
    public static function logPermissionDenial(
        int $userId,
        string $moduleCode,
        string $action,
        int $orgId,
        ?int $roleId = null
    ): void {
        $context = [
            'event_type' => 'permission_denial',
            'user_id' => $userId,
            'org_id' => $orgId,
            'module_code' => $moduleCode,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($roleId) {
            $context['role_id'] = $roleId;
        }

        $message = "Permission denied for user {$userId} attempting {$action} on module {$moduleCode}";

        Log::channel('audit')->warning($message, $context);
    }

    /**
     * Log subscription status change
     */
    public static function logSubscriptionChange(
        int $orgId,
        int $subscriptionId,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?array $additionalData = []
    ): void {
        $context = array_merge([
            'event_type' => 'subscription_change',
            'org_id' => $orgId,
            'subscription_id' => $subscriptionId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'timestamp' => now()->toIso8601String(),
        ], $additionalData);

        if ($reason) {
            $context['reason'] = $reason;
        }

        $message = "Subscription {$subscriptionId} for organization {$orgId} changed from {$oldStatus} to {$newStatus}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        Log::channel('subscription')->info($message, $context);
        Log::channel('audit')->info($message, $context);
    }

    /**
     * Log payment transaction
     */
    public static function logPaymentTransaction(
        int $paymentId,
        int $orgId,
        string $paymentType,
        string $paymentStatus,
        float $amount,
        ?string $gatewayName = null,
        ?string $gatewayPaymentId = null,
        ?int $subscriptionId = null
    ): void {
        $context = [
            'event_type' => 'payment_transaction',
            'payment_id' => $paymentId,
            'org_id' => $orgId,
            'payment_type' => $paymentType,
            'payment_status' => $paymentStatus,
            'amount' => $amount,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($gatewayName) {
            $context['gateway_name'] = $gatewayName;
        }

        if ($gatewayPaymentId) {
            $context['gateway_payment_id'] = $gatewayPaymentId;
        }

        if ($subscriptionId) {
            $context['subscription_id'] = $subscriptionId;
        }

        $message = "Payment {$paymentId} for organization {$orgId}: {$paymentType} - {$paymentStatus} (Amount: {$amount})";

        Log::channel('payment')->info($message, $context);
        Log::channel('audit')->info($message, $context);
    }

    /**
     * Log tenant provisioning event
     */
    public static function logProvisioningEvent(
        int $orgId,
        string $orgSlug,
        string $status,
        ?string $tenantDbName = null,
        ?string $errorMessage = null,
        ?array $steps = []
    ): void {
        $context = [
            'event_type' => 'tenant_provisioning',
            'org_id' => $orgId,
            'org_slug' => $orgSlug,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($tenantDbName) {
            $context['tenant_db_name'] = $tenantDbName;
        }

        if ($errorMessage) {
            $context['error_message'] = $errorMessage;
        }

        if (!empty($steps)) {
            $context['completed_steps'] = $steps;
        }

        $message = "Tenant provisioning for organization {$orgSlug} (org_id: {$orgId}): {$status}";
        if ($errorMessage) {
            $message .= " - Error: {$errorMessage}";
        }

        $level = $status === 'failed' ? 'error' : 'info';

        Log::channel('provisioning')->{$level}($message, $context);
        Log::channel('audit')->{$level}($message, $context);
    }

    /**
     * Log feature control change
     */
    public static function logFeatureControlChange(
        int $orgId,
        string $featureKey,
        string $action,
        ?int $grantedBy = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        $context = [
            'event_type' => 'feature_control',
            'org_id' => $orgId,
            'feature_key' => $featureKey,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($grantedBy) {
            $context['granted_by'] = $grantedBy;
        }

        if ($oldValue !== null) {
            $context['old_value'] = $oldValue;
        }

        if ($newValue !== null) {
            $context['new_value'] = $newValue;
        }

        $message = "Feature control {$action} for organization {$orgId}: {$featureKey}";
        if ($oldValue !== null && $newValue !== null) {
            $message .= " (changed from {$oldValue} to {$newValue})";
        }

        Log::channel('audit')->info($message, $context);
    }

    /**
     * Log general audit event
     */
    public static function log(
        string $eventType,
        string $message,
        array $context = [],
        string $level = 'info'
    ): void {
        $context['event_type'] = $eventType;
        $context['timestamp'] = $context['timestamp'] ?? now()->toIso8601String();

        Log::channel('audit')->{$level}($message, $context);
    }
}
