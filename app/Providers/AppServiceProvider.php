<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \App\Contracts\DatabaseConnectionRouter::class,
            \App\Services\DatabaseConnectionRouterService::class
        );
        
        $this->app->singleton(
            \App\Contracts\TenantProvisioningService::class,
            \App\Services\TenantProvisioningServiceImpl::class
        );
        
        $this->app->singleton(
            \App\Contracts\SubscriptionManagementService::class,
            \App\Services\SubscriptionManagementServiceImpl::class
        );
        
        $this->app->singleton(
            \App\Contracts\PaymentProcessingService::class,
            \App\Services\PaymentProcessingServiceImpl::class
        );
        
        $this->app->singleton(
            \App\Contracts\FeatureControlService::class,
            \App\Services\FeatureControlServiceImpl::class
        );
        
        $this->app->singleton(
            \App\Contracts\RBACPermissionService::class,
            \App\Services\RBACPermissionServiceImpl::class
        );
        
        $this->app->singleton(
            \App\Contracts\AuthenticationService::class,
            \App\Services\AuthenticationServiceImpl::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validate configuration on startup (only in non-testing environments)
        if (!app()->environment('testing')) {
            $this->validateConfiguration();
        }
    }

    /**
     * Validate system configuration
     * 
     * @throws \RuntimeException if configuration is invalid
     */
    private function validateConfiguration(): void
    {
        try {
            $validator = new \App\Services\ConfigValidator();
            $validator->validateAll();
        } catch (\RuntimeException $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::critical('Configuration validation failed', [
                'error' => $e->getMessage(),
            ]);
            
            // In production, fail fast with descriptive error
            if (app()->environment('production')) {
                throw $e;
            }
            
            // In development, show warning but allow app to continue
            if (app()->environment('local', 'development')) {
                \Illuminate\Support\Facades\Log::warning('Configuration validation failed (continuing in development mode)');
            }
        }
    }
}
