<?php

namespace Tests\Traits;

use App\Models\Control\Organization;
use App\Models\Tenant\User;
use App\Models\Tenant\Role;
use App\Models\Tenant\Department;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Config;

/**
 * Trait for JWT token generation in tests
 */
trait AuthenticationTestTrait
{
    /**
     * Generate a JWT token for a user
     * 
     * @param User $user The user to generate token for
     * @param Organization $organization The organization the user belongs to
     * @return string The JWT token
     */
    protected function generateToken(User $user, Organization $organization): string
    {
        $payload = [
            'sub' => $user->user_id,
            'org_id' => $organization->org_id,
            'org_slug' => $organization->org_slug,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'type' => 'access',
        ];

        return JWTAuth::claims($payload)->fromUser($user);
    }

    /**
     * Generate a refresh token for a user
     * 
     * @param User $user The user to generate token for
     * @param Organization $organization The organization the user belongs to
     * @return string The refresh token
     */
    protected function generateRefreshToken(User $user, Organization $organization): string
    {
        $payload = [
            'sub' => $user->user_id,
            'org_id' => $organization->org_id,
            'org_slug' => $organization->org_slug,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60), // 30 days
            'type' => 'refresh',
        ];

        return JWTAuth::claims($payload)->fromUser($user);
    }

    /**
     * Create a test user with authentication
     * 
     * @param Organization $organization The organization
     * @param array $userAttributes Optional user attributes
     * @param array $roleAttributes Optional role attributes
     * @return array ['user' => User, 'token' => string]
     */
    protected function createAuthenticatedUser(
        Organization $organization,
        array $userAttributes = [],
        array $roleAttributes = []
    ): array {
        // Create role if not provided
        if (!isset($userAttributes['role_id'])) {
            $role = Role::factory()->create($roleAttributes);
            $userAttributes['role_id'] = $role->role_id;
        }

        // Create department if not provided
        if (!isset($userAttributes['dept_id'])) {
            $department = Department::factory()->create();
            $userAttributes['dept_id'] = $department->dept_id;
        }

        // Create user
        $user = User::factory()->create($userAttributes);

        // Generate token
        $token = $this->generateToken($user, $organization);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Create an admin user with authentication
     * 
     * @param Organization $organization The organization
     * @return array ['user' => User, 'token' => string]
     */
    protected function createAuthenticatedAdmin(Organization $organization): array
    {
        $role = Role::factory()->admin()->create();
        $department = Department::factory()->root()->create();

        $user = User::factory()->create([
            'role_id' => $role->role_id,
            'dept_id' => $department->dept_id,
        ]);

        $token = $this->generateToken($user, $organization);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Act as an authenticated user for API requests
     * 
     * @param User $user The user to act as
     * @param Organization $organization The organization
     * @return $this
     */
    protected function actingAsUser(User $user, Organization $organization): static
    {
        $token = $this->generateToken($user, $organization);

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Org-Slug' => $organization->org_slug,
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Act as an authenticated admin for API requests
     * 
     * @param Organization $organization The organization
     * @return $this
     */
    protected function actingAsAdmin(Organization $organization): static
    {
        $auth = $this->createAuthenticatedAdmin($organization);

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
            'X-Org-Slug' => $organization->org_slug,
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Get authentication headers for API requests
     * 
     * @param string $token The JWT token
     * @param string $orgSlug The organization slug
     * @return array The headers array
     */
    protected function getAuthHeaders(string $token, string $orgSlug): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'X-Org-Slug' => $orgSlug,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse JWT token and return payload
     * 
     * @param string $token The JWT token
     * @return array The token payload
     */
    protected function parseToken(string $token): array
    {
        try {
            return (array) JWTAuth::setToken($token)->getPayload();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Assert that a token is valid
     * 
     * @param string $token The JWT token
     */
    protected function assertValidToken(string $token): void
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $this->assertNotNull($payload);
            $this->assertArrayHasKey('sub', $payload);
            $this->assertArrayHasKey('org_id', $payload);
            $this->assertArrayHasKey('exp', $payload);
        } catch (\Exception $e) {
            $this->fail('Token is invalid: ' . $e->getMessage());
        }
    }

    /**
     * Assert that a token is expired
     * 
     * @param string $token The JWT token
     */
    protected function assertExpiredToken(string $token): void
    {
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenExpiredException::class);
        JWTAuth::setToken($token)->getPayload();
    }
}
