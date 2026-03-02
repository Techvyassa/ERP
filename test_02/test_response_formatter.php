<?php

/**
 * Test script for ResponseFormatter
 * Run: php test_response_formatter.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing ResponseFormatter...\n\n";

// Test 1: Success response
echo "Test 1: Success Response\n";
$response = \App\Helpers\ResponseFormatter::success(
    ['user_id' => 123, 'name' => 'John Doe'],
    'User retrieved successfully'
);
echo "Status: " . $response->getStatusCode() . "\n";
$content = json_decode($response->getContent(), true);
echo "Success: " . ($content['success'] ? 'true' : 'false') . "\n";
echo "Message: " . $content['message'] . "\n";
echo "Has request_id: " . (isset($content['request_id']) ? 'Yes' : 'No') . "\n";
echo "Has timestamp: " . (isset($content['timestamp']) ? 'Yes' : 'No') . "\n";
echo "Data keys: " . implode(', ', array_keys($content['data'])) . "\n";
echo "\n";

// Test 2: Error response
echo "Test 2: Error Response\n";
$response = \App\Helpers\ResponseFormatter::error(
    'TENANT_NOT_FOUND',
    'The specified tenant does not exist',
    ['org_slug' => 'invalid-org'],
    404
);
echo "Status: " . $response->getStatusCode() . "\n";
$content = json_decode($response->getContent(), true);
echo "Success: " . ($content['success'] ? 'true' : 'false') . "\n";
echo "Error code: " . $content['error']['code'] . "\n";
echo "Message: " . $content['message'] . "\n";
echo "Has details: " . (isset($content['error']['details']) ? 'Yes' : 'No') . "\n";
echo "\n";

// Test 3: Validation error
echo "Test 3: Validation Error Response\n";
$response = \App\Helpers\ResponseFormatter::validationError([
    'email' => ['The email field is required.'],
    'password' => ['The password must be at least 8 characters.']
]);
echo "Status: " . $response->getStatusCode() . "\n";
$content = json_decode($response->getContent(), true);
echo "Success: " . ($content['success'] ? 'true' : 'false') . "\n";
echo "Error code: " . $content['error']['code'] . "\n";
echo "Validation errors count: " . count($content['error']['details']) . "\n";
echo "\n";

// Test 4: Rate limit exceeded
echo "Test 4: Rate Limit Exceeded Response\n";
$response = \App\Helpers\ResponseFormatter::rateLimitExceeded(3600);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Has Retry-After header: " . ($response->headers->has('Retry-After') ? 'Yes' : 'No') . "\n";
$content = json_decode($response->getContent(), true);
echo "Error code: " . $content['error']['code'] . "\n";
echo "Retry after: " . $content['error']['details']['retry_after'] . " seconds\n";
echo "\n";

// Test 5: ApiException
echo "Test 5: ApiException Rendering\n";
try {
    $exception = new \App\Exceptions\TenantNotFoundException(
        'Tenant with slug "test-org" not found'
    );
    $response = $exception->render();
    echo "Status: " . $response->getStatusCode() . "\n";
    $content = json_decode($response->getContent(), true);
    echo "Error code: " . $content['error']['code'] . "\n";
    echo "Message: " . $content['message'] . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 6: JSON validity
echo "Test 6: JSON Validity Check\n";
$response = \App\Helpers\ResponseFormatter::success(['test' => 'data']);
$content = $response->getContent();
$decoded = json_decode($content);
echo "Valid JSON: " . (json_last_error() === JSON_ERROR_NONE ? 'Yes' : 'No') . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "\n";

echo "All tests completed successfully!\n";
