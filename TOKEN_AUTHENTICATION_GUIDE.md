# Token Authentication System - Complete Guide

## Overview
The authentication system has been restructured to use a centralized `TokenService` with refresh tokens stored in the database instead of cache. This provides better reliability, auditability, and token management.

## Architecture

### Components

1. **TokenService** (`app/Services/TokenService.php`)
   - Centralized token generation and management
   - Handles both access tokens (JWT) and refresh tokens
   - Stores refresh tokens in database

2. **RefreshToken Model** (`app/Models/Control/RefreshToken.php`)
   - Database model for refresh tokens
   - Stored in control database
   - Tracks token usage, expiration, and revocation

3. **Controllers**
   - `AuthController`: Email/password authentication
   - `FirebaseAuthController`: Google OAuth authentication
   - Both use the same TokenService

## Database Schema

### refresh_tokens table (Control DB)
```sql
CREATE TABLE refresh_tokens (
    token_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    org_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    user_agent VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    
    INDEX idx_org_user (org_id, user_id),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_revoked (is_revoked),
    FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE
);
```

## Token Flow

### 1. Login (Email/Password)
```
POST /api/v1/auth/login
{
    "email": "user@example.com",
    "password": "password123",
    "org_slug": "acme-corp"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "refresh_token": "a1b2c3d4e5f6...",
        "expires_in": 86400,
        "token_type": "Bearer",
        "user": { ... }
    }
}
```

**Cookie Set:** `auth_token` (httpOnly, secure, sameSite=lax)

### 2. Login (Google OAuth)
```
POST /api/v1/auth/firebase-login
{
    "firebase_token": "firebase_id_token",
    "email": "user@example.com",
    "provider": "google",
    "org_slug": "acme-corp" (optional)
}
```

**Response:** Same as email/password login

### 3. Token Refresh
```
POST /api/v1/auth/refresh
{
    "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "access_token": "new_jwt_token",
        "refresh_token": "a1b2c3d4e5f6...",
        "expires_in": 86400,
        "token_type": "Bearer"
    }
}
```

### 4. Logout
```
POST /api/v1/auth/logout
{
    "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

## Token Types

### Access Token (JWT)
- **Type:** JSON Web Token (JWT)
- **Lifetime:** 24 hours
- **Storage:** Cookie (`auth_token`) + localStorage
- **Claims:**
  ```json
  {
    "sub": 123,           // user_id
    "org_id": 456,        // organization_id
    "org_slug": "acme",   // organization slug
    "type": "access",     // token type
    "iat": 1234567890,    // issued at
    "exp": 1234654290     // expires at
  }
  ```

### Refresh Token
- **Type:** Random 64-character hex string
- **Lifetime:** 30 days
- **Storage:** Database (control.refresh_tokens) + localStorage
- **Purpose:** Obtain new access tokens without re-authentication

## Frontend Integration

### Login Flow
```javascript
// Email/Password Login
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    credentials: 'include', // Important for cookies
    body: JSON.stringify({
        org_slug: 'acme-corp',
        email: 'user@example.com',
        password: 'password123'
    })
});

const data = await response.json();

if (data.success) {
    // Store tokens
    localStorage.setItem('access_token', data.data.access_token);
    localStorage.setItem('refresh_token', data.data.refresh_token);
    localStorage.setItem('org_slug', 'acme-corp');
    localStorage.setItem('user', JSON.stringify(data.data.user));
    
    // Cookie is automatically set by server
    // Redirect to dashboard
    window.location.href = '/dashboard';
}
```

### API Requests
```javascript
// Use the apiRequest helper (from public/js/api-client.js)
const response = await apiRequest('/api/v1/users', {
    method: 'GET'
});

// The helper automatically:
// - Adds X-Org-Slug header from localStorage
// - Adds Authorization: Bearer <token> header
// - Handles token refresh on 401 errors
// - Includes CSRF token for non-GET requests
```

### Token Refresh (Automatic)
The `apiRequest` helper automatically refreshes tokens when it receives a 401 response:

```javascript
if (response.status === 401) {
    const refreshToken = localStorage.getItem('refresh_token');
    const refreshResponse = await fetch('/api/v1/auth/refresh', {
        method: 'POST',
        body: JSON.stringify({ refresh_token: refreshToken })
    });
    
    if (refreshResponse.ok) {
        const data = await refreshResponse.json();
        localStorage.setItem('access_token', data.data.access_token);
        // Retry original request
    }
}
```

## Middleware

### API Routes (validate.jwt)
```php
Route::middleware(['validate.jwt'])->group(function () {
    // Protected API routes
});
```

**Behavior:**
- Extracts token from `Authorization: Bearer <token>` header
- Validates JWT signature and expiration
- Attaches `auth_user_id` and `auth_org_id` to request
- Returns 401 if token is invalid/expired

### Web Routes (web.jwt)
```php
Route::middleware(['web.jwt'])->group(function () {
    // Protected web routes (dashboard, etc.)
});
```

**Behavior:**
- Checks `Authorization` header first
- Falls back to `auth_token` cookie
- Validates JWT
- Redirects to login if invalid
- Attaches user context to request

## Security Features

1. **HttpOnly Cookies:** Access tokens in cookies cannot be accessed by JavaScript
2. **Secure Flag:** Cookies only sent over HTTPS in production
3. **SameSite=Lax:** Protection against CSRF attacks
4. **Token Revocation:** Refresh tokens can be revoked in database
5. **Expiration Tracking:** Both token types have expiration timestamps
6. **Audit Trail:** Tracks IP address, user agent, and last used time
7. **Organization Isolation:** Tokens are scoped to specific organizations

## Token Management

### Revoke Single Token
```php
$tokenService->revokeRefreshToken($refreshToken);
```

### Revoke All User Tokens
```php
$tokenService->revokeAllUserTokens($orgId, $userId);
```

### Cleanup Expired Tokens
```php
$tokenService->cleanupExpiredTokens();
```

Run this periodically via cron:
```php
// In app/Console/Kernel.php
$schedule->call(function () {
    app(TokenService::class)->cleanupExpiredTokens();
})->daily();
```

## Migration Steps

1. **Run Migration:**
   ```bash
   php artisan migrate --path=database/migrations/control/2024_01_01_000008_create_refresh_tokens_table.php
   ```

2. **Clear Old Cache Tokens (if any):**
   ```bash
   php artisan cache:clear
   ```

3. **Test Authentication:**
   - Test email/password login
   - Test Google OAuth login
   - Test token refresh
   - Test logout

## Troubleshooting

### "No authentication token found"
- Check if cookie is being set (inspect browser dev tools)
- Verify `credentials: 'include'` in fetch requests
- Check cookie domain and secure settings
- Ensure middleware is properly registered

### "Invalid or expired refresh token"
- Check if token exists in database
- Verify token hasn't expired
- Check if token is revoked
- Ensure org_id and user_id are correct

### Token not refreshing automatically
- Verify `apiRequest` helper is being used
- Check localStorage has refresh_token
- Ensure refresh endpoint is accessible
- Check browser console for errors

## Best Practices

1. **Always use `apiRequest` helper** for API calls
2. **Store tokens in localStorage** for easy access
3. **Include org_slug** in all requests via X-Org-Slug header
4. **Handle 401 errors** gracefully with token refresh
5. **Clear tokens on logout** from both localStorage and cookies
6. **Use HTTPS in production** for secure cookie transmission
7. **Implement token rotation** for enhanced security
8. **Monitor token usage** via database audit trail
