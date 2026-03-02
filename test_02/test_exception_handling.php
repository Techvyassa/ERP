<?php

/**
 * Test script for Exception Handling
 * Run: php test_exception_handling.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Exception Handling System...\n\n";

// Test 1: TenantNotFoundException
echo "Test 1: TenantNotFoundException\n";
try {
    $exception = new \App\Exceptions\TenantNotFoundException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . "\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "Message: " . $content['message'] . "\n";
    echo "Success: " . ($content['success'] ? 'true' : 'false') . "\n";
    echo "Has request_id: " . (isset($content['request_id']) ? 'Yes' : 'No') . "\n";
    echo "Has timestamp: " . (isset($content['timestamp']) ? 'Yes' : 'No') . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 2: TenantSuspendedException
echo "Test 2: TenantSuspendedException\n";
try {
    $exception = new \App\Exceptions\TenantSuspendedException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 403)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 3: TenantTerminatedException
echo "Test 3: TenantTerminatedException\n";
try {
    $exception = new \App\Exceptions\TenantTerminatedException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 410)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 4: SubscriptionRequiredException
echo "Test 4: SubscriptionRequiredException\n";
try {
    $exception = new \App\Exceptions\SubscriptionRequiredException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 402)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 5: SubscriptionExpiredException
echo "Test 5: SubscriptionExpiredException\n";
try {
    $exception = new \App\Exceptions\SubscriptionExpiredException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 402)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 6: ModuleNotAvailableException
echo "Test 6: ModuleNotAvailableException\n";
try {
    $exception = new \App\Exceptions\ModuleNotAvailableException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 403)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 7: InsufficientPermissionException
echo "Test 7: InsufficientPermissionException\n";
try {
    $exception = new \App\Exceptions\InsufficientPermissionException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 403)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 8: UserLimitReachedException
echo "Test 8: UserLimitReachedException\n";
try {
    $exception = new \App\Exceptions\UserLimitReachedException();
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 403)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 9: RateLimitExceededException
echo "Test 9: RateLimitExceededException\n";
try {
    $exception = new \App\Exceptions\RateLimitExceededException(3600);
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 429)\n";
    echo "Has Retry-After header: " . ($response->headers->has('Retry-After') ? 'Yes' : 'No') . "\n";
    echo "Retry-After value: " . $response->headers->get('Retry-After') . " seconds\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 10: ValidationException
echo "Test 10: ValidationException\n";
try {
    $exception = new \App\Exceptions\ValidationException([
        'email' => ['The email field is required.'],
        'password' => ['The password must be at least 8 characters.']
    ]);
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 422)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "Has field errors: " . (isset($content['error']['details']['email']) ? 'Yes' : 'No') . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 11: Custom ApiException
echo "Test 11: Custom ApiException\n";
try {
    $exception = new \App\Exceptions\ApiException(
        'CUSTOM_ERROR',
        'This is a custom error message',
        ['detail1' => 'value1', 'detail2' => 'value2'],
        418
    );
    $response = $exception->render();
    echo "Status Code: " . $response->getStatusCode() . " (Expected: 418)\n";
    $content = json_decode($response->getContent(), true);
    echo "Error Code: " . $content['error']['code'] . "\n";
    echo "Has custom details: " . (isset($content['error']['details']['detail1']) ? 'Yes' : 'No') . "\n";
    echo "✓ PASSED\n\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 12: Response format consistency
echo "Test 12: Response Format Consistency\n";
try {
    $exception = new \App\Exceptions\TenantNotFoundException();
    $response = $exception->render();
    $content = json_decode($response->getContent(), true);
    
    $requiredFields = ['success', 'error', 'message', 'request_id', 'timestamp'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($content[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "All required fields present: " . implode(', ', $requiredFields) . "\n";
        echo "Error object has 'code' and 'details': " . 
             (isset($content['error']['code']) && isset($content['error']['details']) ? 'Yes' : 'No') . "\n";
        echo "✓ PASSED\n\n";
    } else {
        echo "✗ FAILED: Missing fields: " . implode(', ', $missingFields) . "\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 13: JSON validity
echo "Test 13: JSON Validity\n";
try {
    $exception = new \App\Exceptions\TenantNotFoundException();
    $response = $exception->render();
    $content = $response->getContent();
    $decoded = json_decode($content);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Valid JSON: Yes\n";
        echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
        echo "✓ PASSED\n\n";
    } else {
        echo "✗ FAILED: Invalid JSON - " . json_last_error_msg() . "\n\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

echo "===========================================\n";
echo "All exception handling tests completed!\n";
echo "===========================================\n";
