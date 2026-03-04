# Authentication & Registration Flow

## Overview
This document describes the complete authentication and registration flow for both email/password and Google OAuth authentication.

## Registration Flow

### 1. Organization Registration (Both Methods)

**Endpoint:** `POST /api/v1/organizations/register`

#### For Email/Password Registration:
```json
{
  "org_name": "Acme Corporation",
  "org_slug": "acme-corp",
  "primary_email": "admin@acme.com",
  "primary_phone": "+1234567890",
  "first_name": "John",
  "last_name": "Doe",
  "password": "SecurePassword123!",
  "country_code": "US",
  "timezone": "America/New_York",
  "currency_code": "USD",
  "address_line1": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001"
}
```

#### For Google OAuth Registration:
```json
{
  "org_name": "Acme Corporation",
  "org_slug": "acme-corp",
  "primary_email": "admin@acme.com",
  "primary_phone": "+1234567890",
  "first_name": "John",
  "last_name": "Doe",
  "firebase_uid": "google_uid_123",
  "firebase_token": "firebase_token_here",
  "provider": "google",
  "photo_url": "https://example.com/photo.jpg",
  "country_code": "US",
  "timezone": "America/New_York",
  "currency_code": "USD"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "org_id": 1,
    "org_slug": "acme-corp",
    "org_name": "Acme Corporation",
    "registration_status": "PENDING",
    "tenant_db_name": "erp_acme-corp",
    "primary_email": "admin@acme.com",
    "organization_url": "https://yourdomain.com/acme-corp"
  },
  "message": "Organization registered successfully. Provisioning in progress. You can now login."
}
```

### 2. Slug Utilities

#### Check Slug Availability
**Endpoint:** `GET /api/v1/organizations/check-slug/{slug}`

**Response:**
```json
{
  "success": true,
  "data": {
    "available": true,
    "slug": "acme-corp",
    "message": "This slug is available"
  }
}
```

#### Suggest Slug from Organization Name
**Endpoint:** `POST /api/v1/organizations/suggest-slug`

**Request:**
```json
{
  "org_name": "Acme Corporation"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "suggested_slug": "acme-corporation",
    "base_slug": "acme-corporation"
  }
}
```

## Login Flow

### 1. Email/Password Login

**Endpoint:** `POST /api/v1/auth/login`

**Request:**
```json
{
  "email": "admin@acme.com",
  "password": "SecurePassword123!",
  "org_slug": "acme-corp"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 86400,
    "token_type": "Bearer",
    "user": {
      "user_id": 1,
      "email": "admin@acme.com",
      "employee_code": "ADMIN001",
      "first_name": "John",
      "last_name": "Doe",
      "role_id": 1,
      "dept_id": 1
    }
  },
  "message": "Login successful"
}
```

### 2. Google OAuth Login

**Endpoint:** `POST /api/v1/auth/firebase-login`

**Request:**
```json
{
  "firebase_token": "firebase_id_token_here",
  "email": "admin@acme.com",
  "org_slug": "acme-corp",
  "provider": "google",
  "display_name": "John Doe",
  "photo_url": "https://example.com/photo.jpg",
  "firebase_uid": "google_uid_123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 86400,
    "token_type": "Bearer",
    "user": {
      "user_id": 1,
      "email": "admin@acme.com",
      "employee_code": "ADMIN001",
      "first_name": "John",
      "last_name": "Doe",
      "full_name": "John Doe",
      "role_id": 1,
      "dept_id": 1,
      "photo_url": "https://example.com/photo.jpg",
      "provider": "google"
    },
    "organization": {
      "org_id": 1,
      "org_slug": "acme-corp",
      "org_name": "Acme Corporation",
      "organization_url": "https://yourdomain.com/acme-corp"
    }
  },
  "message": "Authentication successful"
}
```

## Key Features

### 1. Organization Slug
- Each organization gets a unique slug (e.g., `acme-corp`)
- Slug is used in the organization URL: `https://yourdomain.com/acme-corp`
- Slug must be lowercase, alphanumeric with hyphens only
- Slug is validated for uniqueness during registration

### 2. Tenant Database
- Each organization gets its own database: `erp_{org_slug}`
- Database is automatically created during provisioning
- Initial admin user is created in the tenant database

### 3. User Creation
- For email/password: User is created with provided password
- For Google OAuth: User is created with random password (not used)
- First user is always assigned ADMIN role
- User is assigned to root department

### 4. Authentication Methods
- Email/Password: Requires `org_slug` to identify organization
- Google OAuth: Requires `org_slug` to identify organization
- Both methods return JWT tokens (access + refresh)

## Frontend Integration

### Registration Page Flow

1. User enters organization details
2. Frontend calls `/suggest-slug` to get available slug
3. User can modify suggested slug
4. Frontend calls `/check-slug/{slug}` to verify availability
5. User completes registration form
6. Frontend calls `/organizations/register`
7. User is redirected to login page with org_slug

### Login Page Flow

1. User enters email and selects authentication method
2. For email/password: User enters password
3. For Google OAuth: User clicks "Sign in with Google"
4. Frontend includes `org_slug` in login request
5. On success, store tokens and redirect to dashboard

### Organization URL Structure

```
https://yourdomain.com/{org_slug}/login
https://yourdomain.com/{org_slug}/dashboard
https://yourdomain.com/{org_slug}/users
```

## Error Handling

### Common Error Codes

- `VALIDATION_ERROR`: Invalid input data
- `ORGANIZATION_NOT_FOUND`: Organization doesn't exist
- `ORGANIZATION_NOT_ACTIVE`: Organization is suspended/terminated
- `USER_NOT_FOUND`: User doesn't exist in organization
- `USER_INACTIVE`: User account is disabled
- `AUTHENTICATION_FAILED`: Invalid credentials

### Example Error Response

```json
{
  "success": false,
  "error": {
    "code": "ORGANIZATION_NOT_FOUND",
    "details": []
  },
  "message": "Organization not found. Please check your organization URL.",
  "request_id": "uuid-here",
  "timestamp": "2024-03-04T10:30:00Z"
}
```

## Security Considerations

1. **Slug Validation**: Only lowercase alphanumeric and hyphens allowed
2. **Email Uniqueness**: Primary email must be unique across organizations
3. **Password Requirements**: Minimum 8 characters (enforced in validation)
4. **JWT Tokens**: 
   - Access token: 24 hours expiry
   - Refresh token: 30 days expiry
5. **Organization Isolation**: Each tenant has separate database
6. **User Verification**: Users must exist in organization's tenant database

## Testing

### Test Registration (Email/Password)
```bash
curl -X POST http://localhost:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_name": "Test Corp",
    "org_slug": "test-corp",
    "primary_email": "admin@test.com",
    "first_name": "Test",
    "last_name": "Admin",
    "password": "TestPass123!",
    "country_code": "US"
  }'
```

### Test Login (Email/Password)
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "TestPass123!",
    "org_slug": "test-corp"
  }'
```

### Test Slug Check
```bash
curl http://localhost:8000/api/v1/organizations/check-slug/test-corp
```
