# Requirements Document

## Introduction

This document specifies the requirements for a multi-tenant Laravel ERP system foundation. The system implements a two-database architecture where a Control Database manages tenant organizations, subscriptions, and billing, while separate Tenant Databases store each organization's ERP data. The system provides RESTful APIs for mobile app consumption and implements role-based access control with module-level permissions.

## Glossary

- **Control_Database**: The ERP_saas_control database that manages all tenant organizations, subscription plans, billing records, and feature controls
- **Tenant_Database**: An organization-specific database (erp_{slug}) containing ERP operational data for a single tenant
- **Organization**: A registered tenant entity with its own isolated database and subscription
- **Tenant_Context**: The runtime identification of which organization is making a request, derived from org_slug
- **Subscription_Plan**: A SaaS pricing tier defining features, limits, and billing terms
- **RBAC_System**: Role-Based Access Control system managing user permissions at module level
- **Module_Code**: A unique identifier for system modules (PR, PO, GRN, QC, INVOICE, PAYMENT, etc.)
- **Tenant_Provisioning**: The process of creating and initializing a new Tenant_Database for an organization
- **Registration_Status**: Organization lifecycle state (PENDING, ACTIVE, SUSPENDED, TERMINATED)
- **Subscription_Status**: Subscription lifecycle state (TRIAL, ACTIVE, PAST_DUE, CANCELLED, EXPIRED)
- **Active_Subscriptions_Table**: Denormalized fast-lookup table maintaining current subscription state per organization
- **Feature_Control**: Per-tenant feature flag or limit override stored in Control_Database
- **Database_Connection_Router**: Component that dynamically switches database connections based on Tenant_Context
- **API_Gateway**: RESTful API layer designed for mobile app consumption
- **Grace_Period**: Time window after subscription expiry where tenant retains limited access
- **Tenant_Isolation**: Architectural guarantee that no cross-tenant data access is possible
- **Master_Data**: Reference data tables (departments, roles, permissions, users) in Tenant_Database
- **Payment_Gateway**: External service integration for processing subscription payments (Razorpay/Stripe)
- **Org_Slug**: URL-safe unique identifier for an organization used in routing and database naming

## Requirements

### Requirement 1: Laravel Project Foundation

**User Story:** As a system administrator, I want a properly configured Laravel project foundation, so that the ERP system has a maintainable and scalable codebase structure.

#### Acceptance Criteria

1. THE System SHALL use Laravel version 10.x or higher
2. THE System SHALL organize code into separate directories for Control_Database models and Tenant_Database models
3. THE System SHALL provide migration files for all database schemas
4. THE System SHALL use Eloquent ORM for all database operations
5. THE System SHALL implement RESTful API routes under /api prefix for mobile consumption
6. THE System SHALL return JSON responses for all API endpoints
7. THE System SHALL implement API versioning using /api/v1 prefix

### Requirement 2: Control Database Schema

**User Story:** As a SaaS platform operator, I want a Control_Database to manage all tenant organizations and subscriptions, so that I can centrally control the multi-tenant system.

#### Acceptance Criteria

1. THE Control_Database SHALL be named ERP_saas_control
2. THE Control_Database SHALL contain an organizations table with org_id as primary key
3. THE organizations table SHALL store org_slug as a unique URL-safe identifier
4. THE organizations table SHALL store tenant_db_name as the target Tenant_Database name
5. THE organizations table SHALL store registration_status with values (PENDING, ACTIVE, SUSPENDED, TERMINATED)
6. THE organizations table SHALL store organization contact information (primary_email, primary_phone, address fields)
7. THE organizations table SHALL store localization settings (country_code, timezone, currency_code)
8. THE organizations table SHALL store capacity limits (max_users)
9. THE organizations table SHALL enforce unique constraints on org_slug, primary_email, and tenant_db_name
10. THE Control_Database SHALL contain a subscription_plans table defining SaaS pricing tiers
11. THE subscription_plans table SHALL store billing_cycle with values (MONTHLY, QUARTERLY, ANNUAL)
12. THE subscription_plans table SHALL store capacity limits (max_users, max_warehouses, max_materials, storage_gb, api_rate_limit_day)
13. THE subscription_plans table SHALL store modules_included as an array of Module_Code values
14. THE Control_Database SHALL contain an org_subscriptions table tracking subscription lifecycle per organization
15. THE org_subscriptions table SHALL store subscription_status with values (TRIAL, ACTIVE, PAST_DUE, CANCELLED, EXPIRED)
16. THE org_subscriptions table SHALL store billing period dates (current_period_start, current_period_end, next_billing_date)
17. THE Control_Database SHALL contain an active_subscriptions table for fast subscription lookups
18. THE active_subscriptions table SHALL enforce a unique constraint on org_id
19. THE active_subscriptions table SHALL store denormalized subscription data (plan_code, modules_allowed, max_users)
20. THE Control_Database SHALL contain a payment_records table as an immutable transaction ledger
21. THE payment_records table SHALL store payment_type with values (INVOICE, ADVANCE, REFUND, CREDIT_NOTE, ADJUSTMENT)
22. THE payment_records table SHALL store payment_status with values (PENDING, SUCCESS, FAILED, REFUNDED, PARTIALLY_REFUNDED)
23. THE payment_records table SHALL store tax breakdown fields (cgst_amount, sgst_amount, igst_amount)
24. THE Control_Database SHALL contain a feature_controls table for per-tenant feature overrides
25. THE feature_controls table SHALL store feature_type with values (BOOLEAN, NUMERIC, TEXT, JSON)
26. THE feature_controls table SHALL enforce unique constraint on (org_id, feature_key)

### Requirement 3: Tenant Database Schema

**User Story:** As a tenant organization, I want an isolated database for my ERP data, so that my operational data is secure and separated from other tenants.

#### Acceptance Criteria

1. THE System SHALL create Tenant_Database with naming pattern erp_{org_slug}
2. THE Tenant_Database SHALL contain a department_master table with dept_id as primary key
3. THE department_master table SHALL support hierarchical structure via parent_dept_id self-referencing foreign key
4. THE department_master table SHALL enforce unique constraint on dept_code
5. THE Tenant_Database SHALL contain a role_master table defining system roles
6. THE role_master table SHALL enforce unique constraint on role_code
7. THE Tenant_Database SHALL contain a role_permissions table for module-level access control
8. THE role_permissions table SHALL store permission flags (can_view, can_create, can_edit, can_approve, can_delete) for each Module_Code
9. THE role_permissions table SHALL enforce unique constraint on (role_id, module_code)
10. THE Tenant_Database SHALL contain a users table with user_id as primary key
11. THE users table SHALL enforce unique constraints on employee_code and email
12. THE users table SHALL store foreign keys to department_master (dept_id) and role_master (role_id)
13. THE users table SHALL store password_hash for authentication
14. THE users table SHALL store is_active flag and last_login_at timestamp

### Requirement 4: Multi-Tenant Database Architecture

**User Story:** As a system architect, I want complete database isolation between tenants, so that no cross-tenant data access is possible and each organization's data is secure.

#### Acceptance Criteria

1. THE Database_Connection_Router SHALL maintain separate database connections for Control_Database and each Tenant_Database
2. WHEN an API request is received, THE Database_Connection_Router SHALL extract org_slug from the request
3. WHEN org_slug is extracted, THE Database_Connection_Router SHALL resolve the tenant_db_name from Control_Database
4. WHEN tenant_db_name is resolved, THE Database_Connection_Router SHALL switch the active database connection to the Tenant_Database
5. IF org_slug is missing from request, THEN THE System SHALL return HTTP 400 error with message "Tenant context required"
6. IF tenant_db_name does not exist, THEN THE System SHALL return HTTP 404 error with message "Tenant not found"
7. IF organization registration_status is SUSPENDED, THEN THE System SHALL return HTTP 403 error with message "Tenant suspended"
8. IF organization registration_status is TERMINATED, THEN THE System SHALL return HTTP 410 error with message "Tenant terminated"
9. THE System SHALL prevent any query from accessing data across different Tenant_Database instances
10. THE System SHALL log all database connection switches with org_id and timestamp

### Requirement 5: Tenant Provisioning Workflow

**User Story:** As a new customer, I want an automated tenant provisioning process, so that my organization database is created and initialized without manual intervention.

#### Acceptance Criteria

1. WHEN a new organization registers, THE System SHALL create a record in organizations table with registration_status PENDING
2. WHEN organization email is verified, THE System SHALL queue a tenant provisioning job
3. WHEN provisioning job executes, THE System SHALL create a new Tenant_Database with name erp_{org_slug}
4. WHEN Tenant_Database is created, THE System SHALL run all tenant schema migrations
5. WHEN migrations complete, THE System SHALL seed initial Master_Data (default roles and permissions)
6. WHEN seeding completes, THE System SHALL update organizations.registration_status to ACTIVE
7. WHEN seeding completes, THE System SHALL update organizations.activated_at to current timestamp
8. WHEN seeding completes, THE System SHALL create a trial subscription record in org_subscriptions
9. IF provisioning fails at any step, THEN THE System SHALL log the error and set registration_status to PENDING
10. IF provisioning fails, THEN THE System SHALL send notification to system administrators
11. THE System SHALL complete tenant provisioning within 60 seconds of email verification

### Requirement 6: Subscription Management

**User Story:** As a SaaS platform operator, I want automated subscription lifecycle management, so that billing, renewals, and access control are handled correctly.

#### Acceptance Criteria

1. WHEN a new organization is activated, THE System SHALL create a subscription record with subscription_status TRIAL
2. WHEN trial subscription is created, THE System SHALL set trial_end_date to 14 days from trial_start_date
3. WHEN trial_end_date is reached, THE System SHALL change subscription_status to EXPIRED
4. WHEN payment is successfully processed, THE System SHALL create a new subscription with subscription_status ACTIVE
5. WHEN subscription becomes ACTIVE, THE System SHALL set next_billing_date based on billing_cycle
6. WHEN next_billing_date is reached, THE System SHALL generate an invoice and attempt payment
7. IF payment succeeds, THEN THE System SHALL extend current_period_end and update next_billing_date
8. IF payment fails, THEN THE System SHALL change subscription_status to PAST_DUE
9. WHEN subscription_status changes to PAST_DUE, THE System SHALL set grace_period_until to 7 days from current date
10. IF payment is not received before grace_period_until, THEN THE System SHALL change registration_status to SUSPENDED
11. WHEN subscription is cancelled, THE System SHALL set subscription_status to CANCELLED and record cancellation_reason
12. THE System SHALL allow access until current_period_end even after cancellation
13. WHEN org_subscriptions record is inserted or updated, THE System SHALL automatically refresh active_subscriptions table
14. THE active_subscriptions refresh SHALL copy current subscription data including plan_code, modules_allowed, and max_users

### Requirement 7: Payment Processing

**User Story:** As a billing administrator, I want complete payment transaction tracking, so that all financial records are auditable and reconcilable.

#### Acceptance Criteria

1. WHEN an invoice is generated, THE System SHALL create a payment_records entry with payment_status PENDING
2. THE System SHALL generate a unique payment_reference for each payment record
3. WHEN payment gateway callback is received, THE System SHALL update payment_status based on gateway response
4. WHEN payment_status changes to SUCCESS, THE System SHALL record payment_date and gateway_payment_id
5. WHEN payment_status changes to SUCCESS, THE System SHALL update the associated subscription record
6. THE System SHALL calculate tax amounts (cgst_amount, sgst_amount, igst_amount) based on organization country_code
7. THE System SHALL store total_amount as sum of taxable_amount plus all tax amounts
8. THE System SHALL support Payment_Gateway integration for Razorpay and Stripe
9. THE System SHALL never modify existing payment_records entries after creation (immutable ledger)
10. IF refund is processed, THEN THE System SHALL create a new payment_records entry with payment_type REFUND
11. THE System SHALL link refund records to original payment via gateway_payment_id

### Requirement 8: Feature Controls

**User Story:** As a SaaS platform operator, I want per-tenant feature flag overrides, so that I can enable custom features or adjust limits for specific organizations.

#### Acceptance Criteria

1. THE System SHALL check feature_controls table before enforcing plan-based limits
2. WHEN a feature_control exists for an organization, THE System SHALL use the override value instead of plan default
3. THE System SHALL support BOOLEAN feature flags for enabling/disabling features
4. THE System SHALL support NUMERIC overrides for capacity limits (max_users, storage_gb, etc.)
5. THE System SHALL support TEXT overrides for configuration values
6. THE System SHALL support JSON overrides for complex feature configurations
7. WHEN effective_from is set, THE System SHALL only apply feature_control after that date
8. WHEN effective_to is set, THE System SHALL stop applying feature_control after that date
9. THE System SHALL log all feature_control changes with granted_by user reference
10. THE System SHALL allow multiple feature_controls per organization with different feature_key values

### Requirement 9: Role-Based Access Control

**User Story:** As a system administrator, I want module-level permission control, so that users only access features appropriate to their role.

#### Acceptance Criteria

1. THE RBAC_System SHALL load user permissions from role_permissions table based on user's role_id
2. WHEN a user attempts to access a module, THE RBAC_System SHALL check if can_view permission is granted
3. IF can_view is false for the Module_Code, THEN THE System SHALL return HTTP 403 error
4. WHEN a user attempts to create a record, THE RBAC_System SHALL check if can_create permission is granted
5. WHEN a user attempts to edit a record, THE RBAC_System SHALL check if can_edit permission is granted
6. WHEN a user attempts to approve a record, THE RBAC_System SHALL check if can_approve permission is granted
7. WHEN a user attempts to delete a record, THE RBAC_System SHALL check if can_delete permission is granted
8. THE RBAC_System SHALL cache user permissions for 15 minutes to reduce database queries
9. WHEN role_permissions are updated, THE System SHALL invalidate permission cache for affected users
10. THE System SHALL log all permission denial events with user_id, Module_Code, and attempted action

### Requirement 10: API Authentication and Authorization

**User Story:** As a mobile app developer, I want secure API authentication, so that only authorized users can access tenant data.

#### Acceptance Criteria

1. THE API_Gateway SHALL require authentication token for all protected endpoints
2. WHEN authentication token is received, THE System SHALL validate token signature and expiration
3. WHEN token is valid, THE System SHALL extract user_id and org_id from token claims
4. THE System SHALL verify that user belongs to the organization specified in org_slug
5. IF user does not belong to organization, THEN THE System SHALL return HTTP 403 error
6. THE System SHALL issue JWT tokens with 24-hour expiration
7. THE System SHALL support token refresh mechanism with refresh tokens valid for 30 days
8. WHEN user logs in, THE System SHALL update last_login_at timestamp in users table
9. THE System SHALL enforce rate limiting based on subscription plan's api_rate_limit_day
10. IF rate limit is exceeded, THEN THE System SHALL return HTTP 429 error with Retry-After header

### Requirement 11: Subscription Gate Middleware

**User Story:** As a SaaS platform operator, I want API requests to be validated against active subscriptions, so that only paying customers with valid subscriptions can access the system.

#### Acceptance Criteria

1. THE System SHALL implement subscription gate middleware for all tenant API endpoints
2. WHEN API request is received, THE middleware SHALL query active_subscriptions table by org_id
3. IF no active subscription exists, THEN THE System SHALL return HTTP 402 error with message "Subscription required"
4. IF subscription_status is EXPIRED, THEN THE System SHALL return HTTP 402 error with message "Subscription expired"
5. IF subscription_status is CANCELLED, THEN THE System SHALL check if current date is before period_end_date
6. IF current date is after period_end_date for cancelled subscription, THEN THE System SHALL return HTTP 402 error
7. WHEN subscription_status is PAST_DUE, THE System SHALL allow read-only access until grace_period_until
8. IF current date is after grace_period_until, THEN THE System SHALL return HTTP 402 error
9. THE middleware SHALL verify that requested Module_Code is included in modules_allowed array
10. IF Module_Code is not in modules_allowed, THEN THE System SHALL return HTTP 403 error with message "Module not available in your plan"
11. THE middleware SHALL cache active_subscriptions data for 5 minutes per organization

### Requirement 12: User Management

**User Story:** As an organization administrator, I want to manage user accounts within my tenant, so that I can control who has access to the ERP system.

#### Acceptance Criteria

1. THE System SHALL allow creating users within the Tenant_Database
2. WHEN a user is created, THE System SHALL validate that email is unique within the tenant
3. WHEN a user is created, THE System SHALL validate that employee_code is unique within the tenant
4. THE System SHALL hash passwords using bcrypt with cost factor 12 before storing in password_hash
5. THE System SHALL validate that dept_id references an existing department in department_master
6. THE System SHALL validate that role_id references an existing role in role_master
7. WHEN user count reaches subscription max_users limit, THE System SHALL prevent creating additional users
8. IF max_users limit is reached, THEN THE System SHALL return HTTP 403 error with message "User limit reached for your plan"
9. THE System SHALL allow deactivating users by setting is_active to false
10. WHEN user is deactivated, THE System SHALL prevent login but preserve user data
11. THE System SHALL support user search and filtering by department, role, and active status

### Requirement 13: Department Hierarchy Management

**User Story:** As an organization administrator, I want to organize departments hierarchically, so that I can model my organization structure accurately.

#### Acceptance Criteria

1. THE System SHALL allow creating departments with optional parent_dept_id
2. WHEN parent_dept_id is null, THE System SHALL treat department as root-level
3. WHEN parent_dept_id is set, THE System SHALL validate that it references an existing department
4. THE System SHALL prevent circular references in department hierarchy
5. IF setting parent_dept_id would create a cycle, THEN THE System SHALL return HTTP 400 error with message "Circular department hierarchy detected"
6. THE System SHALL allow querying department hierarchy with parent-child relationships
7. THE System SHALL support soft deletion of departments by setting is_active to false
8. WHEN department is deactivated, THE System SHALL prevent assigning new users to that department
9. THE System SHALL maintain audit trail with created_at and created_by for all departments

### Requirement 14: API Response Format

**User Story:** As a mobile app developer, I want consistent API response formats, so that I can reliably parse responses in the mobile application.

#### Acceptance Criteria

1. THE API_Gateway SHALL return all successful responses with HTTP 200 status and JSON body
2. THE successful response JSON SHALL contain "success": true, "data": {}, and "message" fields
3. THE API_Gateway SHALL return all error responses with appropriate HTTP status codes and JSON body
4. THE error response JSON SHALL contain "success": false, "error": {}, and "message" fields
5. THE error object SHALL include "code" and "details" fields
6. THE API_Gateway SHALL return validation errors with HTTP 422 status
7. THE validation error response SHALL include field-level error messages in "details" object
8. THE API_Gateway SHALL include request_id in all responses for tracing
9. THE API_Gateway SHALL include timestamp in ISO 8601 format in all responses
10. THE API_Gateway SHALL handle exceptions and return HTTP 500 with generic error message without exposing stack traces

### Requirement 15: Database Migration Management

**User Story:** As a DevOps engineer, I want version-controlled database migrations, so that schema changes are tracked and deployable across environments.

#### Acceptance Criteria

1. THE System SHALL provide separate migration files for Control_Database and Tenant_Database schemas
2. THE Control_Database migrations SHALL create all six tables (organizations, subscription_plans, org_subscriptions, active_subscriptions, payment_records, feature_controls)
3. THE Tenant_Database migrations SHALL create all four Master_Data tables (department_master, role_master, role_permissions, users)
4. THE migrations SHALL define all foreign key constraints with appropriate ON DELETE behaviors
5. THE migrations SHALL define all unique constraints and indexes
6. THE System SHALL provide a command to run Control_Database migrations
7. THE System SHALL provide a command to run Tenant_Database migrations for a specific tenant
8. THE System SHALL provide a command to run Tenant_Database migrations for all tenants
9. THE migrations SHALL be reversible with down() methods for rollback capability
10. THE System SHALL track migration execution in migrations table per database

### Requirement 16: Database Trigger for Active Subscriptions

**User Story:** As a system architect, I want automatic synchronization of active subscriptions, so that the fast-lookup table is always current without manual intervention.

#### Acceptance Criteria

1. THE Control_Database SHALL implement a database trigger on org_subscriptions table
2. WHEN a subscription record is inserted with subscription_status ACTIVE or TRIAL, THE trigger SHALL upsert active_subscriptions
3. WHEN a subscription record is updated to subscription_status ACTIVE or TRIAL, THE trigger SHALL upsert active_subscriptions
4. WHEN a subscription record is updated to subscription_status EXPIRED or CANCELLED, THE trigger SHALL check if it is the current active subscription
5. IF expired subscription is the current active subscription, THEN THE trigger SHALL delete the active_subscriptions record
6. THE trigger SHALL copy plan_id, plan_code, subscription_status, and period_end_date to active_subscriptions
7. THE trigger SHALL join to subscription_plans to copy max_users and modules_included to active_subscriptions
8. THE trigger SHALL copy tenant_db_name and tenant_db_status from organizations to active_subscriptions
9. THE trigger SHALL set refreshed_at to current timestamp
10. THE trigger SHALL set is_in_trial based on whether subscription_status is TRIAL

### Requirement 17: Tenant Data Seeding

**User Story:** As a new tenant, I want my database pre-populated with essential master data, so that I can start using the system immediately after provisioning.

#### Acceptance Criteria

1. WHEN Tenant_Database is provisioned, THE System SHALL seed default roles in role_master
2. THE default roles SHALL include at minimum: ADMIN, MANAGER, USER, VIEWER
3. WHEN default roles are seeded, THE System SHALL seed role_permissions for each role
4. THE ADMIN role SHALL have all permissions (can_view, can_create, can_edit, can_approve, can_delete) set to true for all Module_Code values
5. THE VIEWER role SHALL have only can_view set to true for all Module_Code values
6. THE System SHALL create a root department in department_master with dept_code "ROOT"
7. THE System SHALL create an initial admin user with email from organizations.primary_email
8. THE initial admin user SHALL be assigned to ADMIN role and ROOT department
9. THE System SHALL generate a random temporary password for initial admin user
10. THE System SHALL send the temporary password to primary_email via email

### Requirement 18: Logging and Audit Trail

**User Story:** As a compliance officer, I want comprehensive audit logging, so that all system actions are traceable for security and compliance purposes.

#### Acceptance Criteria

1. THE System SHALL log all authentication attempts with user_id, org_id, IP address, and timestamp
2. THE System SHALL log all failed authentication attempts with reason
3. THE System SHALL log all database connection switches with org_id and tenant_db_name
4. THE System SHALL log all RBAC permission denials with user_id, Module_Code, and action
5. THE System SHALL log all subscription status changes with old status, new status, and reason
6. THE System SHALL log all payment transactions with payment_id and payment_status
7. THE System SHALL log all tenant provisioning events with org_id and status
8. THE System SHALL log all feature_control changes with org_id, feature_key, and granted_by
9. THE System SHALL store logs in structured format with consistent fields (timestamp, level, context, message)
10. THE System SHALL retain logs for minimum 90 days

### Requirement 19: Configuration Parser and Validator

**User Story:** As a DevOps engineer, I want validated configuration management, so that database connections and tenant settings are correctly loaded at runtime.

#### Acceptance Criteria

1. THE System SHALL parse database configuration from .env file
2. THE System SHALL validate that Control_Database connection parameters are present (host, database, username, password)
3. IF Control_Database connection parameters are missing, THEN THE System SHALL fail to start with descriptive error
4. THE System SHALL support dynamic Tenant_Database connection configuration
5. THE System SHALL validate tenant_db_host and tenant_db_name before attempting connection
6. IF Tenant_Database connection fails, THEN THE System SHALL return HTTP 503 error with message "Tenant database unavailable"
7. THE Configuration_Parser SHALL support environment-specific overrides (local, staging, production)
8. THE System SHALL validate that timezone values in organizations table are valid IANA timezone identifiers
9. THE System SHALL validate that currency_code values are valid ISO 4217 currency codes
10. FOR ALL valid configuration objects, parsing then serializing then parsing SHALL produce an equivalent object (round-trip property)

### Requirement 20: API Rate Limiting

**User Story:** As a SaaS platform operator, I want per-tenant API rate limiting, so that system resources are fairly distributed and abuse is prevented.

#### Acceptance Criteria

1. THE System SHALL enforce rate limits based on subscription plan's api_rate_limit_day value
2. THE System SHALL track API request count per organization per day
3. WHEN request count exceeds api_rate_limit_day, THE System SHALL return HTTP 429 error
4. THE HTTP 429 response SHALL include Retry-After header indicating seconds until limit resets
5. THE System SHALL reset rate limit counters at midnight UTC
6. THE System SHALL use Redis or cache for rate limit counter storage
7. THE System SHALL exclude health check and status endpoints from rate limiting
8. WHERE feature_controls contains a rate limit override, THE System SHALL use the override value
9. THE System SHALL log rate limit violations with org_id and request count
10. THE System SHALL provide API endpoint for organizations to check their current rate limit usage

## Correctness Properties

### Property 1: Tenant Isolation Invariant
FOR ALL API requests r1 and r2, IF r1.org_id ≠ r2.org_id, THEN r1 SHALL NOT access data from r2's Tenant_Database

### Property 2: Subscription-Access Consistency
FOR ALL organizations o, IF active_subscriptions.subscription_status IN (EXPIRED, CANCELLED with period_end_date < current_date), THEN API requests for o SHALL be rejected with HTTP 402

### Property 3: RBAC Permission Enforcement
FOR ALL users u and Module_Code m, IF role_permissions.can_view = false FOR u.role_id AND m, THEN requests to access m SHALL return HTTP 403

### Property 4: Active Subscriptions Synchronization
FOR ALL org_subscriptions records s, IF s.subscription_status IN (ACTIVE, TRIAL), THEN active_subscriptions SHALL contain exactly one record for s.org_id with matching subscription data

### Property 5: Payment Ledger Immutability
FOR ALL payment_records p, AFTER p is created, p SHALL NOT be modified (only new records may be inserted)

### Property 6: Tenant Database Naming Convention
FOR ALL organizations o, o.tenant_db_name SHALL equal "erp_" + o.org_slug

### Property 7: User Capacity Limit
FOR ALL organizations o, COUNT(users WHERE is_active = true) SHALL be less than or equal to active_subscriptions.max_users FOR o.org_id

### Property 8: Department Hierarchy Acyclicity
FOR ALL departments d, following parent_dept_id references SHALL NOT form a cycle

### Property 9: Configuration Round-Trip
FOR ALL valid configuration objects c, parse(serialize(c)) SHALL equal c

### Property 10: Module Access Consistency
FOR ALL organizations o and Module_Code m, IF m NOT IN active_subscriptions.modules_allowed FOR o, THEN API requests to m SHALL return HTTP 403

### Property 11: Authentication Token Validity
FOR ALL JWT tokens t, IF t.expiration < current_time OR t.signature is invalid, THEN requests with t SHALL return HTTP 401

### Property 12: Rate Limit Monotonicity
FOR ALL organizations o, request_count(o, day) SHALL be monotonically increasing until reset at midnight UTC

### Property 13: Subscription Status Transitions
FOR ALL subscriptions s, status transitions SHALL follow valid lifecycle: TRIAL → (ACTIVE | EXPIRED), ACTIVE → (PAST_DUE | CANCELLED), PAST_DUE → (ACTIVE | SUSPENDED)

### Property 14: Foreign Key Referential Integrity
FOR ALL foreign key relationships, referenced records SHALL exist in parent tables OR operations SHALL fail with constraint violation

### Property 15: Password Hash Irreversibility
FOR ALL passwords p, bcrypt_hash(p) SHALL NOT be reversible to recover p
