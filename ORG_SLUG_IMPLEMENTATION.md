# Organization Slug (org_slug) Implementation Guide

## Overview
The system uses `org_slug` to identify which organization/tenant a user belongs to. This enables multi-tenancy where each organization has its own database and isolated data.

## How It Works

### 1. Login Flow
When a user logs in, they must provide:
- `org_slug`: Their organization identifier (e.g., "acme-corp")
- `email`: Their email address
- `password`: Their password

The login form (`resources/views/auth/login.blade.php`) collects all three fields and sends them to `/api/v1/auth/login`.

### 2. Authentication Response
Upon successful authentication, the backend returns:
- `access_token`: JWT token for API authentication
- `refresh_token`: Token for refreshing expired access tokens
- `user`: User profile data

The frontend stores these in localStorage along with the `org_slug`:
```javascript
localStorage.setItem('org_slug', orgSlug);
localStorage.setItem('access_token', accessToken);
localStorage.setItem('refresh_token', refreshToken);
localStorage.setItem('user', JSON.stringify(user));
```

### 3. Subsequent API Requests
For all protected API endpoints, the `org_slug` must be included in one of two ways:

#### Option A: X-Org-Slug Header (Recommended)
```javascript
fetch('/api/v1/users', {
    headers: {
        'X-Org-Slug': 'acme-corp',
        'Authorization': 'Bearer <access_token>'
    }
});
```

#### Option B: Route Parameter
```
GET /api/v1/org/acme-corp/users
```

### 4. Tenant Resolution Middleware
The `ResolveTenant` middleware (`app/Http/Middleware/ResolveTenant.php`) automatically:
1. Extracts `org_slug` from the `X-Org-Slug` header or route parameter
2. Queries the control database to find the organization
3. Validates the organization status (ACTIVE, PENDING, SUSPENDED, TERMINATED)
4. Retrieves the tenant database name
5. Attaches tenant context to the request

### 5. Database Switching
Once the tenant is resolved, the system automatically switches to the tenant's database for all subsequent queries. This is handled by the `DatabaseConnectionRouterService`.

## API Client Utility

A JavaScript utility (`public/js/api-client.js`) is provided to automatically attach the `org_slug` header to all API requests:

```javascript
// Use the apiRequest helper instead of fetch
const response = await apiRequest('/api/v1/users', {
    method: 'GET'
});

// The helper automatically adds:
// - X-Org-Slug header from localStorage
// - Authorization header with access token
// - CSRF token for non-GET requests
// - Handles token refresh on 401 errors
```

## Protected Routes

All routes in the following middleware group require `org_slug`:
```php
Route::middleware(['validate.jwt', 'resolve.tenant', 'validate.subscription'])->group(function () {
    // All routes here require org_slug
});
```

## Error Handling

The system returns specific errors for tenant-related issues:

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `TENANT_CONTEXT_REQUIRED` | 400 | org_slug is missing |
| `TENANT_NOT_FOUND` | 404 | Organization doesn't exist |
| `TENANT_SUSPENDED` | 403 | Organization is suspended |
| `TENANT_TERMINATED` | 410 | Organization is terminated |

## Implementation Checklist

### Frontend
- [x] Add org_slug input field to login form
- [x] Store org_slug in localStorage after login
- [x] Create API client utility to attach X-Org-Slug header
- [x] Include API client script in all pages that make API calls
- [ ] Update all fetch calls to use apiRequest helper

### Backend
- [x] Login endpoint accepts org_slug in request body
- [x] ResolveTenant middleware extracts org_slug from header
- [x] Protected routes use resolve.tenant middleware
- [x] Database connection router switches based on tenant context

## Example Usage

### Login
```javascript
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        org_slug: 'acme-corp',
        email: 'user@acme.com',
        password: 'password123'
    })
});
```

### Fetch Users (with API client)
```javascript
const response = await apiRequest('/api/v1/users', {
    method: 'GET'
});
const data = await response.json();
```

### Fetch Users (without API client)
```javascript
const orgSlug = localStorage.getItem('org_slug');
const accessToken = localStorage.getItem('access_token');

const response = await fetch('/api/v1/users', {
    headers: {
        'X-Org-Slug': orgSlug,
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json'
    }
});
```

## Security Considerations

1. The `org_slug` is validated against the control database before any tenant data access
2. Users can only access data from their own organization
3. The JWT token contains the user's organization context
4. Cross-tenant access is prevented by the middleware layer
5. Database connections are isolated per tenant

## Testing

To test the org_slug flow:

1. Register an organization (creates org_slug)
2. Login with org_slug, email, and password
3. Verify localStorage contains org_slug
4. Make API requests and verify X-Org-Slug header is sent
5. Check that tenant database is correctly switched
6. Test error cases (invalid org_slug, suspended org, etc.)
