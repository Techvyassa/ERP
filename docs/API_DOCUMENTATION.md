# API Documentation

## Overview

This document provides comprehensive documentation for the Laravel Multi-Tenant ERP Foundation API. All endpoints return JSON responses and follow RESTful conventions.

**Base URL**: `https://your-domain.com/api/v1`

**API Version**: v1

## Table of Contents

1. [Authentication](#authentication)
2. [Error Handling](#error-handling)
3. [Rate Limiting](#rate-limiting)
4. [Endpoints](#endpoints)
   - [Health Check](#health-check)
   - [Authentication](#authentication-endpoints)
   - [Organization Registration](#organization-registration)
   - [Subscription Management](#subscription-management)
   - [User Management](#user-management)
   - [Department Management](#department-management)
   - [Role Management](#role-management)
   - [Feature Controls](#feature-controls-admin)
   - [Rate Limit Status](#rate-limit-status)
   - [Payment Webhooks](#payment-webhooks)

## Authentication

The API uses JWT (JSON Web Token) authentication. Include the access token in the `Authorization` header:

```
Authorization: Bearer {access_token}
```

### Token Lifecycle

- **Access Token**: Valid for 24 hours
- **Refresh Token**: Valid for 30 days

### Tenant Context

All authenticated requests must include the organization slug in the `X-Tenant-Slug` header:

```
X-Tenant-Slug: acme
```

## Error Handling

### Response Format

**Success Response**:
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "request_id": "uuid-v4",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Error Response**:
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "details": "Detailed error information"
  },
  "message": "Human-readable error message",
  "request_id": "uuid-v4",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid input or tenant context missing |
| 401 | Unauthorized - Invalid or expired token |
| 402 | Payment Required - Subscription expired or required |
| 403 | Forbidden - Insufficient permissions or suspended tenant |
| 404 | Not Found - Resource or tenant not found |
| 410 | Gone - Tenant terminated |
| 422 | Unprocessable Entity - Validation errors |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |
| 503 | Service Unavailable - Tenant database unavailable |

### Common Error Codes

| Error Code | Description |
|------------|-------------|
| `TENANT_CONTEXT_REQUIRED` | X-Tenant-Slug header missing |
| `TENANT_NOT_FOUND` | Organization does not exist |
| `TENANT_SUSPENDED` | Organization is suspended |
| `TENANT_TERMINATED` | Organization is terminated |
| `SUBSCRIPTION_REQUIRED` | No active subscription |
| `SUBSCRIPTION_EXPIRED` | Subscription has expired |
| `MODULE_NOT_AVAILABLE` | Module not included in plan |
| `INSUFFICIENT_PERMISSION` | User lacks required permission |
| `RATE_LIMIT_EXCEEDED` | API rate limit exceeded |
| `USER_LIMIT_REACHED` | Maximum users for plan reached |
| `VALIDATION_ERROR` | Input validation failed |
| `AUTHENTICATION_FAILED` | Invalid credentials |
| `INVALID_TOKEN` | Token is invalid or expired |

## Rate Limiting

API requests are rate-limited based on your subscription plan. The limit is enforced per organization per day.

**Rate Limit Headers**:
```
X-RateLimit-Limit: 10000
X-RateLimit-Remaining: 9500
X-RateLimit-Reset: 1705363200
```

When rate limit is exceeded, you'll receive a 429 response with a `Retry-After` header indicating seconds until reset.

---

## Endpoints

### Health Check

Check API availability.

**Endpoint**: `GET /health`

**Authentication**: Not required

**Response**:
```json
{
  "success": true,
  "message": "ERP API is running",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

---

## Authentication Endpoints

### Login

Authenticate user and receive access tokens.

**Endpoint**: `POST /auth/login`

**Authentication**: Not required

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123",
  "org_slug": "acme"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "user_id": 1,
      "email": "user@example.com",
      "full_name": "John Doe",
      "employee_code": "EMP001",
      "dept_id": 1,
      "role_id": 1,
      "is_active": true
    }
  },
  "message": "Login successful"
}
```

**Error Responses**:
- 401: Invalid credentials
- 403: Organization suspended
- 410: Organization terminated
- 422: Validation errors

---

### Refresh Token

Obtain a new access token using refresh token.

**Endpoint**: `POST /auth/refresh`

**Authentication**: Not required

**Request Body**:
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 86400
  },
  "message": "Token refreshed successfully"
}
```

**Error Responses**:
- 401: Invalid or expired refresh token

---

### Logout

Revoke refresh token and invalidate session.

**Endpoint**: `POST /auth/logout`

**Authentication**: Required

**Request Body**:
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## Organization Registration

Register a new organization (tenant).

**Endpoint**: `POST /organizations/register`

**Authentication**: Not required

**Request Body**:
```json
{
  "org_slug": "acme",
  "org_name": "Acme Corporation",
  "primary_email": "admin@acme.com",
  "primary_phone": "+1234567890",
  "address_line1": "123 Main Street",
  "address_line2": "Suite 100",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country_code": "US",
  "timezone": "America/New_York",
  "currency_code": "USD"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "org_id": 1,
    "org_slug": "acme",
    "org_name": "Acme Corporation",
    "registration_status": "PENDING",
    "message": "Organization registered. Provisioning in progress."
  },
  "message": "Organization registration initiated"
}
```

**Error Responses**:
- 422: Validation errors (duplicate org_slug, invalid email, etc.)

---

## Subscription Management

### Get Current Subscription

Retrieve current subscription details.

**Endpoint**: `GET /subscriptions/current`

**Authentication**: Required

**Headers**:
```
Authorization: Bearer {access_token}
X-Tenant-Slug: acme
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "subscription_id": 1,
    "plan_code": "PROFESSIONAL",
    "plan_name": "Professional Plan",
    "subscription_status": "ACTIVE",
    "current_period_start": "2024-01-01",
    "current_period_end": "2024-02-01",
    "next_billing_date": "2024-02-01",
    "modules_allowed": ["PR", "PO", "GRN", "QC", "INVOICE", "PAYMENT"],
    "max_users": 50,
    "is_in_trial": false
  },
  "message": "Subscription retrieved successfully"
}
```

**Error Responses**:
- 402: No active subscription

---

### List Available Plans

Get all available subscription plans.

**Endpoint**: `GET /subscriptions/plans`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "plan_id": 1,
      "plan_code": "BASIC",
      "plan_name": "Basic Plan",
      "description": "Perfect for small teams",
      "billing_cycle": "MONTHLY",
      "price_amount": 49.99,
      "currency_code": "USD",
      "max_users": 10,
      "max_warehouses": 2,
      "max_materials": 500,
      "storage_gb": 10,
      "api_rate_limit_day": 5000,
      "modules_included": ["PR", "PO", "GRN"]
    }
  ],
  "message": "Plans retrieved successfully"
}
```

---

### Upgrade Subscription

Upgrade to a different plan.

**Endpoint**: `POST /subscriptions/upgrade`

**Authentication**: Required

**Request Body**:
```json
{
  "plan_id": 2
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "subscription_id": 2,
    "plan_code": "PROFESSIONAL",
    "subscription_status": "ACTIVE",
    "message": "Subscription upgraded successfully"
  },
  "message": "Subscription upgrade initiated"
}
```

**Error Responses**:
- 404: Plan not found
- 422: Invalid plan selection

---

### Cancel Subscription

Cancel current subscription.

**Endpoint**: `POST /subscriptions/cancel`

**Authentication**: Required

**Request Body**:
```json
{
  "reason": "Switching to different solution"
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "subscription_id": 1,
    "subscription_status": "CANCELLED",
    "period_end_date": "2024-02-01",
    "message": "Access will continue until period end"
  },
  "message": "Subscription cancelled successfully"
}
```

---

## User Management

All user endpoints require `USERS` module access and appropriate RBAC permissions.

### List Users

Get paginated list of users.

**Endpoint**: `GET /users`

**Authentication**: Required

**Query Parameters**:
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `dept_id` (optional): Filter by department
- `role_id` (optional): Filter by role
- `is_active` (optional): Filter by active status

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "user_id": 1,
        "email": "john.doe@acme.com",
        "full_name": "John Doe",
        "employee_code": "EMP001",
        "dept_id": 1,
        "department_name": "Engineering",
        "role_id": 1,
        "role_name": "ADMIN",
        "is_active": true,
        "last_login_at": "2024-01-15T10:30:00Z",
        "created_at": "2024-01-01T00:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3
    }
  },
  "message": "Users retrieved successfully"
}
```

**Required Permission**: `can_view` for USERS module

---

### Get User

Retrieve specific user details.

**Endpoint**: `GET /users/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "email": "john.doe@acme.com",
    "full_name": "John Doe",
    "employee_code": "EMP001",
    "dept_id": 1,
    "department": {
      "dept_id": 1,
      "dept_code": "ENG",
      "dept_name": "Engineering"
    },
    "role_id": 1,
    "role": {
      "role_id": 1,
      "role_code": "ADMIN",
      "role_name": "Administrator"
    },
    "is_active": true,
    "last_login_at": "2024-01-15T10:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
  },
  "message": "User retrieved successfully"
}
```

**Required Permission**: `can_view` for USERS module

**Error Responses**:
- 404: User not found

---

### Create User

Create a new user.

**Endpoint**: `POST /users`

**Authentication**: Required

**Request Body**:
```json
{
  "email": "jane.smith@acme.com",
  "full_name": "Jane Smith",
  "employee_code": "EMP002",
  "password": "SecurePassword123",
  "dept_id": 1,
  "role_id": 2,
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user_id": 2,
    "email": "jane.smith@acme.com",
    "full_name": "Jane Smith",
    "employee_code": "EMP002",
    "dept_id": 1,
    "role_id": 2,
    "is_active": true
  },
  "message": "User created successfully"
}
```

**Required Permission**: `can_create` for USERS module

**Error Responses**:
- 403: User limit reached for plan
- 422: Validation errors (duplicate email/employee_code, invalid dept/role)

---

### Update User

Update existing user.

**Endpoint**: `PUT /users/{id}`

**Authentication**: Required

**Request Body**:
```json
{
  "full_name": "Jane Smith Updated",
  "dept_id": 2,
  "role_id": 3,
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user_id": 2,
    "email": "jane.smith@acme.com",
    "full_name": "Jane Smith Updated",
    "dept_id": 2,
    "role_id": 3,
    "is_active": true
  },
  "message": "User updated successfully"
}
```

**Required Permission**: `can_edit` for USERS module

**Error Responses**:
- 404: User not found
- 422: Validation errors

---

### Delete User

Delete (soft delete) a user.

**Endpoint**: `DELETE /users/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

**Required Permission**: `can_delete` for USERS module

**Error Responses**:
- 404: User not found

---

## Department Management

All department endpoints require `SETTINGS` module access and appropriate RBAC permissions.

### List Departments

Get all departments with hierarchy.

**Endpoint**: `GET /departments`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "dept_id": 1,
      "dept_code": "ROOT",
      "dept_name": "Root Department",
      "parent_dept_id": null,
      "is_active": true,
      "children": [
        {
          "dept_id": 2,
          "dept_code": "ENG",
          "dept_name": "Engineering",
          "parent_dept_id": 1,
          "is_active": true
        }
      ]
    }
  ],
  "message": "Departments retrieved successfully"
}
```

**Required Permission**: `can_view` for SETTINGS module

---

### Get Department

Retrieve specific department.

**Endpoint**: `GET /departments/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "dept_id": 2,
    "dept_code": "ENG",
    "dept_name": "Engineering",
    "parent_dept_id": 1,
    "parent_department": {
      "dept_id": 1,
      "dept_code": "ROOT",
      "dept_name": "Root Department"
    },
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  },
  "message": "Department retrieved successfully"
}
```

**Required Permission**: `can_view` for SETTINGS module

---

### Create Department

Create a new department.

**Endpoint**: `POST /departments`

**Authentication**: Required

**Request Body**:
```json
{
  "dept_code": "SALES",
  "dept_name": "Sales Department",
  "parent_dept_id": 1,
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "dept_id": 3,
    "dept_code": "SALES",
    "dept_name": "Sales Department",
    "parent_dept_id": 1,
    "is_active": true
  },
  "message": "Department created successfully"
}
```

**Required Permission**: `can_create` for SETTINGS module

**Error Responses**:
- 400: Circular hierarchy detected
- 422: Validation errors (duplicate dept_code, invalid parent)

---

### Update Department

Update existing department.

**Endpoint**: `PUT /departments/{id}`

**Authentication**: Required

**Request Body**:
```json
{
  "dept_name": "Sales & Marketing",
  "parent_dept_id": 1,
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "dept_id": 3,
    "dept_code": "SALES",
    "dept_name": "Sales & Marketing",
    "parent_dept_id": 1,
    "is_active": true
  },
  "message": "Department updated successfully"
}
```

**Required Permission**: `can_edit` for SETTINGS module

**Error Responses**:
- 400: Circular hierarchy detected
- 404: Department not found

---

### Delete Department

Delete (soft delete) a department.

**Endpoint**: `DELETE /departments/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "message": "Department deleted successfully"
}
```

**Required Permission**: `can_delete` for SETTINGS module

---

## Role Management

All role endpoints require `SETTINGS` module access and appropriate RBAC permissions.

### List Roles

Get all roles.

**Endpoint**: `GET /roles`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "role_id": 1,
      "role_code": "ADMIN",
      "role_name": "Administrator",
      "is_system_role": true,
      "is_active": true
    }
  ],
  "message": "Roles retrieved successfully"
}
```

**Required Permission**: `can_view` for SETTINGS module

---

### Get Role

Retrieve specific role.

**Endpoint**: `GET /roles/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "role_id": 1,
    "role_code": "ADMIN",
    "role_name": "Administrator",
    "is_system_role": true,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  },
  "message": "Role retrieved successfully"
}
```

**Required Permission**: `can_view` for SETTINGS module

---

### Create Role

Create a new role.

**Endpoint**: `POST /roles`

**Authentication**: Required

**Request Body**:
```json
{
  "role_code": "SUPERVISOR",
  "role_name": "Supervisor",
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "role_id": 5,
    "role_code": "SUPERVISOR",
    "role_name": "Supervisor",
    "is_system_role": false,
    "is_active": true
  },
  "message": "Role created successfully"
}
```

**Required Permission**: `can_create` for SETTINGS module

---

### Update Role

Update existing role.

**Endpoint**: `PUT /roles/{id}`

**Authentication**: Required

**Request Body**:
```json
{
  "role_name": "Senior Supervisor",
  "is_active": true
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "role_id": 5,
    "role_code": "SUPERVISOR",
    "role_name": "Senior Supervisor",
    "is_active": true
  },
  "message": "Role updated successfully"
}
```

**Required Permission**: `can_edit` for SETTINGS module

---

### Delete Role

Delete a role (system roles cannot be deleted).

**Endpoint**: `DELETE /roles/{id}`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "message": "Role deleted successfully"
}
```

**Required Permission**: `can_delete` for SETTINGS module

**Error Responses**:
- 403: Cannot delete system role

---

### Get Role Permissions

Retrieve permissions for a role.

**Endpoint**: `GET /roles/{id}/permissions`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "role_id": 1,
    "role_code": "ADMIN",
    "permissions": [
      {
        "module_code": "PR",
        "can_view": true,
        "can_create": true,
        "can_edit": true,
        "can_approve": true,
        "can_delete": true
      },
      {
        "module_code": "PO",
        "can_view": true,
        "can_create": true,
        "can_edit": true,
        "can_approve": true,
        "can_delete": true
      }
    ]
  },
  "message": "Role permissions retrieved successfully"
}
```

**Required Permission**: `can_view` for SETTINGS module

---

### Update Role Permissions

Update permissions for a role.

**Endpoint**: `PUT /roles/{id}/permissions`

**Authentication**: Required

**Request Body**:
```json
{
  "permissions": [
    {
      "module_code": "PR",
      "can_view": true,
      "can_create": true,
      "can_edit": false,
      "can_approve": false,
      "can_delete": false
    }
  ]
}
```

**Success Response** (200):
```json
{
  "success": true,
  "message": "Role permissions updated successfully"
}
```

**Required Permission**: `can_edit` for SETTINGS module

---

## Feature Controls (Admin)

Admin-only endpoints for managing feature controls.

### List Feature Controls

Get all feature controls for an organization.

**Endpoint**: `GET /admin/feature-controls`

**Authentication**: Required (Admin only)

**Query Parameters**:
- `org_id` (required): Organization ID

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "feature_control_id": 1,
      "org_id": 1,
      "feature_key": "max_users",
      "feature_type": "NUMERIC",
      "feature_value": "100",
      "effective_from": "2024-01-01",
      "effective_to": null,
      "granted_by": 1
    }
  ],
  "message": "Feature controls retrieved successfully"
}
```

---

### Create Feature Control

Create a new feature control override.

**Endpoint**: `POST /admin/feature-controls`

**Authentication**: Required (Admin only)

**Request Body**:
```json
{
  "org_id": 1,
  "feature_key": "max_users",
  "feature_type": "NUMERIC",
  "feature_value": "100",
  "effective_from": "2024-01-01",
  "effective_to": null
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "feature_control_id": 1,
    "org_id": 1,
    "feature_key": "max_users",
    "feature_type": "NUMERIC",
    "feature_value": "100"
  },
  "message": "Feature control created successfully"
}
```

---

## Rate Limit Status

Check current rate limit usage.

**Endpoint**: `GET /rate-limit/status`

**Authentication**: Required

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "limit": 10000,
    "remaining": 9500,
    "reset_at": "2024-01-16T00:00:00Z",
    "current_usage": 500
  },
  "message": "Rate limit status retrieved successfully"
}
```

---

## Payment Webhooks

### Razorpay Webhook

Handle Razorpay payment callbacks.

**Endpoint**: `POST /webhooks/razorpay`

**Authentication**: Webhook signature verification

**Request Body**: Razorpay webhook payload

**Success Response** (200):
```json
{
  "success": true,
  "message": "Webhook processed successfully"
}
```

---

### Stripe Webhook

Handle Stripe payment callbacks.

**Endpoint**: `POST /webhooks/stripe`

**Authentication**: Webhook signature verification

**Request Body**: Stripe webhook payload

**Success Response** (200):
```json
{
  "success": true,
  "message": "Webhook processed successfully"
}
```

---

## Postman Collection

A Postman collection is available at `docs/postman/ERP_API_Collection.json` for easy API testing.

### Environment Variables

Set these variables in your Postman environment:

- `base_url`: API base URL (e.g., `https://your-domain.com/api/v1`)
- `access_token`: JWT access token (auto-populated after login)
- `refresh_token`: JWT refresh token (auto-populated after login)
- `tenant_slug`: Organization slug (e.g., `acme`)

---

## OpenAPI Specification

An OpenAPI 3.0 specification is available at `docs/openapi/api-spec.yaml` for integration with API tools and code generators.

---

## Support

For API support, contact: api-support@your-domain.com
