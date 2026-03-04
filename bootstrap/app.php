<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            
            // Tenant routes - Subdomain-based
            Route::domain('{tenant}.' . config('app.domain'))
                ->middleware('web')
                ->group(base_path('routes/tenant.php'));
            
            // Tenant routes - Path-based
            Route::prefix('org/{tenant}')
                ->middleware('web')
                ->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'validate.jwt' => \App\Http\Middleware\ValidateJWT::class,
            'web.jwt' => \App\Http\Middleware\WebJWTAuth::class,
            'resolve.tenant' => \App\Http\Middleware\ResolveTenant::class,
            'validate.subscription' => \App\Http\Middleware\ValidateSubscription::class,
            'check.module.permission' => \App\Http\Middleware\CheckModulePermission::class,
            'detect.tenant' => \App\Http\Middleware\DetectTenantContext::class,
        ]);
        
        // Exclude auth_token from cookie encryption
        $middleware->encryptCookies(except: [
            'auth_token',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle ApiException and its subclasses
        $exceptions->renderable(function (\App\Exceptions\ApiException $e) {
            return $e->render();
        });

        // Handle Laravel's validation exception
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ResponseFormatter::validationError(
                $e->errors(),
                $e->getMessage()
            );
        });

        // Handle authentication exceptions
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e) {
            return \App\Helpers\ResponseFormatter::unauthorized(
                $e->getMessage() ?: 'Unauthenticated'
            );
        });

        // Handle authorization exceptions
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return \App\Helpers\ResponseFormatter::forbidden(
                $e->getMessage() ?: 'This action is unauthorized'
            );
        });

        // Handle model not found exceptions
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \App\Helpers\ResponseFormatter::notFound(
                'Resource not found'
            );
        });

        // Handle method not allowed exceptions
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return \App\Helpers\ResponseFormatter::error(
                'METHOD_NOT_ALLOWED',
                'The specified method for the request is invalid',
                [],
                405
            );
        });

        // Handle not found exceptions (404)
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return \App\Helpers\ResponseFormatter::notFound(
                'The requested resource was not found'
            );
        });

        // Handle throttle exceptions (rate limiting)
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            return \App\Helpers\ResponseFormatter::rateLimitExceeded(
                (int) $retryAfter,
                'Too many requests. Please try again later.'
            );
        });

        // Handle all other exceptions
        $exceptions->renderable(function (\Throwable $e, $request) {
            // Only return detailed error in non-production environments
            if (config('app.debug')) {
                $message = $e->getMessage();
                $details = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ];
            } else {
                // Hide stack traces in production
                $message = 'An error occurred while processing your request';
                $details = [];
            }

            // Log the error with full context
            \Illuminate\Support\Facades\Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            return \App\Helpers\ResponseFormatter::error(
                'INTERNAL_SERVER_ERROR',
                $message,
                $details,
                500
            );
        });
    })->create();
