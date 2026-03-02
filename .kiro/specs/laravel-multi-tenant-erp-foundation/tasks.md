# Implementation Plan: Laravel Multi-Tenant ERP Foundation

## Overview

This implementation plan breaks down the Laravel multi-tenant ERP system into discrete, implementable tasks. The system uses a two-database architecture with complete tenant isolation, subscription-based access control, and RESTful APIs for mobile consumption. Tasks are organized to build incrementally, starting with core infrastructure, then database layers, middleware stack, services, and finally API endpoints.

## Tasks

- [x] 1. Laravel project setup and configuration
  - Install Laravel latest with required dependencies (JWT, Redis client)
  - Configure dual database connections in config/database.php (control and tenant)
  - Set up environment variables for Control DB and Tenant DB connections
  - Configure Redis for caching and rate limiting
  - Set up API versioning structure with /api/v1 prefix
  - _Requirements: 1.1, 1.5, 1.6, 1.7_

- [x] 2. Control Database migrations and models
  - [x] 2.1 Create Control Database migrations
    - Create organizations table migration with all fields and indexes
    - Create subscription_plans table migration with JSON modules_included field
    - Create org_subscriptions table migration with foreign keys
    - Create active_subscriptions table migration with denormalized fields
    - Create payment_records table migration (immutable, no updated_at)
    - Create feature_controls table migration with unique constraint
    - _Requirements: 2.1-2.26, 15.2_

  - [x] 2.2 Create database triggers for active_subscriptions sync
    - Write sync_active_subscriptions_insert trigger
    - Write sync_active_subscriptions_update trigger
    - Test trigger execution on subscription status changes
    - _Requirements: 6.13, 6.14, 16.1-16.10_

  - [x] 2.3 Create Control Database Eloquent models
    - Create Organization model with relationships and scopes
    - Create SubscriptionPlan model with JSON casting
    - Create OrgSubscription model with status enums
    - Create ActiveSubscription model (no timestamps)
    - Create PaymentRecord model with immutability guards
    - Create FeatureControl model with typed value methods
    - _Requirements: 2.1-2.26_

  - [ ]* 2.4 Write unit tests for Control Database models
    - Test Organization model relationships and scopes
    - Test PaymentRecord immutability (update/delete prevention)
    - Test FeatureControl effective period logic
    - Test SubscriptionPlan JSON casting
    - _Requirements: 2.1-2.26_

- [x] 3. Tenant Database migrations and models
  - [x] 3.1 Create Tenant Database migrations
    - Create department_master table migration with self-referencing FK
    - Create role_master table migration with system role flag
    - Create role_permissions table migration with permission flags
    - Create users table migration with foreign keys to dept and role
    - Add indexes for performance optimization
    - _Requirements: 3.1-3.13, 15.3_

  - [x] 3.2 Create Tenant Database Eloquent models
    - Create Department model with cycle detection logic
    - Create Role model with system role deletion guard
    - Create RolePermission model with permission check methods
    - Create User model with password hashing and authentication
    - _Requirements: 3.1-3.13_

  - [ ]* 3.3 Write unit tests for Tenant Database models
    - Test Department cycle detection prevents circular hierarchy
    - Test Role system role deletion prevention
    - Test User password hashing with bcrypt cost 12
    - Test RolePermission hasPermission method
    - _Requirements: 3.4, 3.8, 12.4, 13.4, 13.5_

  - [ ]* 3.4 Write property test for department hierarchy acyclicity
    - **Property 27: Department Hierarchy Acyclicity**
    - **Validates: Requirements 13.4, 13.5**
    - Generate random department hierarchies and verify no cycles exist
    - _Requirements: 13.4, 13.5_

- [-] 4. Database Connection Router service
  - [x] 4.1 Implement DatabaseConnectionRouter interface and service
    - Create DatabaseConnectionRouter interface with switchToTenant/switchToControl methods
    - Implement service using Laravel Config and DB facade
    - Add connection verification method
    - Add connection state tracking
    - Implement connection switch logging
    - _Requirements: 4.1-4.4, 4.10_

  - [ ]* 4.2 Write unit tests for DatabaseConnectionRouter
    - Test successful tenant connection switch
    - Test connection verification
    - Test error handling for non-existent databases
    - Test connection switch logging
    - _Requirements: 4.1-4.10_

  - [ ]* 4.3 Write property test for database connection routing
    - **Property 4: Database Connection Routing**
    - **Validates: Requirements 4.2, 4.3, 4.4**
    - Generate random org_slugs and verify connection routing works
    - _Requirements: 4.2, 4.3, 4.4_

- [x] 5. Checkpoint - Ensure database layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Middleware stack implementation
  - [x] 6.1 Create Authentication middleware (ValidateJWT)
    - Install and configure JWT package (tymon/jwt-auth)
    - Create middleware to validate JWT signature and expiration
    - Extract user_id and org_id from token claims
    - Return 401 for invalid/expired tokens
    - _Requirements: 10.1, 10.2, 10.6_

  - [x] 6.2 Create Tenant Resolution middleware (ResolveTenant)
    - Extract org_slug from request header or route parameter
    - Query Control DB organizations table
    - Validate registration_status (return 400/403/404/410 as needed)
    - Resolve tenant_db_name and store in request context
    - _Requirements: 4.2, 4.3, 4.5, 4.6, 4.7, 4.8_

  - [x] 6.3 Create Subscription Gate middleware (ValidateSubscription)
    - Query active_subscriptions table by org_id
    - Validate subscription_status (handle EXPIRED, CANCELLED, PAST_DUE)
    - Check grace period for PAST_DUE status
    - Verify module_code in modules_allowed array
    - Cache subscription data for 5 minutes
    - Return 402/403 errors as appropriate
    - _Requirements: 11.1-11.11_

  - [x] 6.4 Create RBAC middleware (CheckModulePermission)
    - Switch to Tenant DB connection
    - Load user's role_permissions by role_id
    - Check can_view/can_create/can_edit/can_approve/can_delete flags
    - Cache permissions for 15 minutes in Redis
    - Log permission denials
    - Return 403 for insufficient permissions
    - _Requirements: 9.1-9.10_

  - [x] 6.5 Create Rate Limit middleware (ThrottleRequests)
    - Track request count per org_id per day in Redis
    - Compare against api_rate_limit_day from subscription
    - Check for feature_control override
    - Return 429 with Retry-After header when exceeded
    - Reset counters at midnight UTC
    - _Requirements: 10.9, 10.10, 20.1-20.10_

  - [ ]* 6.6 Write unit tests for middleware stack
    - Test JWT validation with valid/invalid/expired tokens
    - Test tenant resolution with various org statuses
    - Test subscription gate with different subscription states
    - Test RBAC permission checks
    - Test rate limiting enforcement
    - _Requirements: 4.1-4.10, 9.1-9.10, 10.1-10.10, 11.1-11.11, 20.1-20.10_

- [x] 7. Tenant Provisioning service
  - [x] 7.1 Implement TenantProvisioningService interface and service
    - Create interface with provisionTenant, rollbackProvisioning methods
    - Implement database creation using raw SQL CREATE DATABASE
    - Run tenant migrations programmatically
    - Seed default roles (ADMIN, MANAGER, USER, VIEWER)
    - Seed role_permissions with appropriate flags
    - Create root department
    - Create initial admin user with temporary password
    - Update organization status to ACTIVE
    - Create trial subscription
    - Implement error handling and rollback logic
    - _Requirements: 5.1-5.11, 17.1-17.10_

  - [x] 7.2 Create queue job for async tenant provisioning
    - Create ProvisionTenantJob for Laravel queue
    - Dispatch job after email verification
    - Send welcome email with credentials on success
    - Send admin notification on failure
    - _Requirements: 5.2, 5.9, 5.10_

  - [ ]* 7.3 Write unit tests for TenantProvisioningService
    - Test successful provisioning workflow
    - Test rollback on failure
    - Test idempotency (safe to retry)
    - _Requirements: 5.1-5.11_

  - [ ]* 7.4 Write property test for tenant database naming convention
    - **Property 1: Tenant Database Naming Convention**
    - **Validates: Requirements 3.1, 6.6**
    - Generate random org_slugs and verify tenant_db_name = "erp_" + org_slug
    - _Requirements: 3.1, 6.6_

- [x] 8. Subscription Management service
  - [x] 8.1 Implement SubscriptionManagementService interface and service
    - Create interface with subscription lifecycle methods
    - Implement createTrialSubscription (14-day trial)
    - Implement upgradeToPaid with plan selection
    - Implement processRenewal with billing cycle logic
    - Implement cancelSubscription with reason tracking
    - Implement hasActiveSubscription check
    - Implement getAllowedModules method
    - _Requirements: 6.1-6.14_

  - [x] 8.2 Create scheduled job for subscription lifecycle management
    - Create CheckTrialExpiration job (daily)
    - Create ProcessSubscriptionRenewal job (daily)
    - Create EnforceGracePeriod job (daily)
    - Schedule jobs in app/Console/Kernel.php
    - _Requirements: 6.3, 6.6, 6.10_

  - [ ]* 8.3 Write unit tests for SubscriptionManagementService
    - Test trial subscription creation with 14-day period
    - Test subscription upgrade flow
    - Test renewal processing
    - Test cancellation with period_end_date access
    - _Requirements: 6.1-6.14_

  - [ ]* 8.4 Write property test for subscription status transitions
    - **Property 8: Subscription Status Transitions**
    - **Validates: Requirements 6.3, 6.4, 6.7, 6.8, 6.9, 6.10**
    - Verify only valid state transitions are allowed
    - _Requirements: 6.3, 6.4, 6.7, 6.8, 6.9, 6.10_

  - [ ]* 8.5 Write property test for active subscriptions synchronization
    - **Property 9: Active Subscriptions Synchronization**
    - **Validates: Requirements 6.13, 6.14, 16.1-16.10**
    - Verify active_subscriptions stays in sync with org_subscriptions
    - _Requirements: 6.13, 6.14, 16.1-16.10_

- [x] 9. Checkpoint - Ensure core services tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Payment Processing service
  - [x] 10.1 Implement PaymentProcessingService interface and service
    - Create interface with payment lifecycle methods
    - Implement createPaymentIntent method
    - Implement processCallback for Razorpay and Stripe
    - Implement recordPayment with immutable ledger pattern
    - Implement processRefund (creates new record)
    - Implement calculateTax based on country_code (CGST/SGST/IGST)
    - Generate unique payment_reference for each transaction
    - _Requirements: 7.1-7.11_

  - [x] 10.2 Create payment gateway integrations
    - Create RazorpayGateway adapter class
    - Create StripeGateway adapter class
    - Implement webhook signature verification
    - Create payment gateway factory
    - _Requirements: 7.8_

  - [x] 10.3 Create webhook controllers for payment callbacks
    - Create RazorpayWebhookController
    - Create StripeWebhookController
    - Verify webhook signatures
    - Process payment status updates
    - Update subscription on successful payment
    - _Requirements: 7.3, 7.4, 7.5_

  - [ ]* 10.4 Write unit tests for PaymentProcessingService
    - Test payment record creation
    - Test tax calculation for different countries
    - Test refund record creation
    - Test payment reference uniqueness
    - _Requirements: 7.1-7.11_

  - [ ]* 10.5 Write property test for payment ledger immutability
    - **Property 13: Payment Ledger Immutability**
    - **Validates: Requirements 7.9**
    - Verify payment records cannot be modified after creation
    - _Requirements: 7.9_

  - [ ]* 10.6 Write property test for payment reference uniqueness
    - **Property 12: Payment Reference Uniqueness**
    - **Validates: Requirements 7.2**
    - Generate multiple payments and verify all references are unique
    - _Requirements: 7.2_

- [x] 11. Feature Control service
  - [x] 11.1 Implement FeatureControlService
    - Create service to query feature_controls table
    - Implement getFeatureValue with type casting
    - Check effective_from and effective_to dates
    - Implement fallback to plan defaults
    - Cache feature controls per organization
    - _Requirements: 8.1-8.10_

  - [ ]* 11.2 Write unit tests for FeatureControlService
    - Test feature override precedence
    - Test effective period enforcement
    - Test type casting (BOOLEAN, NUMERIC, TEXT, JSON)
    - Test fallback to plan defaults
    - _Requirements: 8.1-8.10_

  - [ ]* 11.3 Write property test for feature control override precedence
    - **Property 15: Feature Control Override Precedence**
    - **Validates: Requirements 8.1, 8.2**
    - Verify feature controls override plan defaults when effective
    - _Requirements: 8.1, 8.2_

- [x] 12. RBAC Permission service
  - [x] 12.1 Implement RBACPermissionService interface and service
    - Create interface with permission check methods
    - Implement hasPermission for user/module/action
    - Implement getUserPermissions with caching
    - Implement updateRolePermissions
    - Implement invalidateCache for permission updates
    - Implement getAccessibleModules
    - Use Redis for 15-minute permission cache
    - _Requirements: 9.1-9.10_

  - [ ]* 12.2 Write unit tests for RBACPermissionService
    - Test permission checking logic
    - Test permission caching
    - Test cache invalidation
    - Test accessible modules retrieval
    - _Requirements: 9.1-9.10_

  - [ ]* 12.3 Write property test for RBAC permission enforcement
    - **Property 17: RBAC Permission Enforcement**
    - **Validates: Requirements 9.2, 9.3**
    - Verify users without can_view are denied access
    - _Requirements: 9.2, 9.3_

  - [ ]* 12.4 Write property test for RBAC action permission enforcement
    - **Property 18: RBAC Action Permission Enforcement**
    - **Validates: Requirements 9.4, 9.5, 9.6, 9.7**
    - Verify action-specific permissions are enforced
    - _Requirements: 9.4, 9.5, 9.6, 9.7_

- [x] 13. Authentication service
  - [x] 13.1 Implement AuthenticationService interface and service
    - Create interface with login, refreshToken, logout methods
    - Implement login with email/password/org_slug
    - Validate organization is ACTIVE
    - Switch to Tenant DB for user lookup
    - Verify password with Hash::check
    - Generate JWT access token (24h) and refresh token (30d)
    - Store refresh tokens in Redis
    - Update last_login_at timestamp
    - _Requirements: 10.1-10.6_

  - [x] 13.2 Implement token refresh and logout
    - Implement refreshToken method
    - Implement logout with token revocation
    - Clean up expired refresh tokens
    - _Requirements: 10.7_

  - [ ]* 13.3 Write unit tests for AuthenticationService
    - Test successful login flow
    - Test invalid credentials
    - Test suspended organization login denial
    - Test token refresh
    - Test logout
    - _Requirements: 10.1-10.7_

  - [ ]* 13.4 Write property test for authentication token validity
    - **Property 20: Authentication Token Validity**
    - **Validates: Requirements 10.2, 10.6**
    - Verify expired/invalid tokens are rejected
    - _Requirements: 10.2, 10.6_

  - [ ]* 13.5 Write property test for user-organization association
    - **Property 21: User-Organization Association Validation**
    - **Validates: Requirements 10.4, 10.5**
    - Verify users can only access their own organization
    - _Requirements: 10.4, 10.5_

- [x] 14. Checkpoint - Ensure all services tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. API Controllers and routes
  - [x] 15.1 Create authentication API endpoints
    - Create AuthController with login, refresh, logout methods
    - Define routes: POST /api/v1/auth/login, POST /api/v1/auth/refresh, POST /api/v1/auth/logout
    - Return consistent JSON response format
    - _Requirements: 1.5, 1.6, 1.7, 10.1-10.7, 14.1-14.10_

  - [x] 15.2 Create organization registration API endpoints
    - Create OrganizationController with register method
    - Define route: POST /api/v1/organizations/register
    - Validate input data
    - Create organization with PENDING status
    - Queue tenant provisioning job
    - _Requirements: 5.1, 5.2_

  - [x] 15.3 Create user management API endpoints
    - Create UserController with CRUD methods
    - Define routes: GET/POST/PUT/DELETE /api/v1/users
    - Apply middleware stack (auth, tenant, subscription, RBAC)
    - Validate user capacity limits
    - Enforce unique email and employee_code
    - _Requirements: 12.1-12.11_

  - [x] 15.4 Create department management API endpoints
    - Create DepartmentController with CRUD methods
    - Define routes: GET/POST/PUT/DELETE /api/v1/departments
    - Apply middleware stack
    - Validate parent_dept_id references
    - Prevent circular hierarchy
    - _Requirements: 13.1-13.9_

  - [x] 15.5 Create role and permission management API endpoints
    - Create RoleController with CRUD methods
    - Create RolePermissionController for permission updates
    - Define routes: GET/POST/PUT/DELETE /api/v1/roles, PUT /api/v1/roles/{id}/permissions
    - Apply middleware stack
    - Prevent system role deletion
    - Invalidate permission cache on updates
    - _Requirements: 9.1-9.10_

  - [x] 15.6 Create subscription management API endpoints
    - Create SubscriptionController with upgrade, cancel methods
    - Define routes: POST /api/v1/subscriptions/upgrade, POST /api/v1/subscriptions/cancel
    - Apply middleware stack
    - Return current subscription details
    - _Requirements: 6.1-6.14_

  - [x] 15.7 Create feature control API endpoints (admin only)
    - Create FeatureControlController with CRUD methods
    - Define routes: GET/POST/PUT/DELETE /api/v1/admin/feature-controls
    - Require admin authentication
    - Log all feature control changes
    - _Requirements: 8.1-8.10_

  - [x] 15.8 Create rate limit status API endpoint
    - Create RateLimitController with status method
    - Define route: GET /api/v1/rate-limit/status
    - Return current usage and limit
    - Exclude from rate limiting
    - _Requirements: 20.10_

  - [ ]* 15.9 Write API integration tests
    - Test authentication endpoints
    - Test user management endpoints with RBAC
    - Test department management endpoints
    - Test subscription endpoints
    - Test error responses and status codes
    - _Requirements: 1.5-1.7, 10.1-10.10, 12.1-12.11, 13.1-13.9, 14.1-14.10_

- [x] 16. Response formatting and error handling
  - [x] 16.1 Create API response formatter
    - Create ResponseFormatter helper class
    - Implement success response format with data, message, request_id, timestamp
    - Implement error response format with error code, details, message
    - Ensure all responses are valid JSON
    - _Requirements: 14.1-14.10_

  - [x] 16.2 Create custom exception classes
    - Create ApiException base class with render method
    - Create tenant-related exceptions (TenantNotFoundException, etc.)
    - Create subscription-related exceptions
    - Create permission-related exceptions
    - Create validation exception handler
    - Map exceptions to HTTP status codes
    - _Requirements: 14.3-14.10_

  - [x] 16.3 Configure global exception handler
    - Update app/Exceptions/Handler.php
    - Catch all exceptions and format as JSON
    - Hide stack traces in production
    - Log all errors with context
    - _Requirements: 14.10_

  - [ ]* 16.4 Write property test for API response format consistency
    - **Property 28: API Response Format Consistency**
    - **Validates: Requirements 14.1-14.4**
    - Verify all responses follow consistent format
    - _Requirements: 14.1-14.4_

  - [ ]* 16.5 Write property test for JSON response validity
    - **Property 31: JSON Response Validity**
    - **Validates: Requirements 1.6**
    - Verify all API responses are valid JSON
    - _Requirements: 1.6_

- [x] 17. Database seeders
  - [x] 17.1 Create Control Database seeders
    - Create SubscriptionPlanSeeder with default plans (BASIC, PROFESSIONAL, ENTERPRISE)
    - Define modules_included arrays for each plan
    - Set capacity limits and pricing
    - _Requirements: 2.10-2.13_

  - [x] 17.2 Create Tenant Database seeders
    - Create DefaultRoleSeeder (ADMIN, MANAGER, USER, VIEWER)
    - Create DefaultRolePermissionSeeder with appropriate flags
    - ADMIN: all permissions true for all modules
    - VIEWER: only can_view true for all modules
    - _Requirements: 17.1-17.5_

  - [ ]* 17.3 Write tests for seeders
    - Test subscription plan seeding
    - Test default role seeding
    - Test role permission seeding
    - Verify ADMIN has all permissions
    - _Requirements: 17.1-17.5_

- [x] 18. Configuration and environment setup
  - [x] 18.1 Create configuration files
    - Create config/tenant.php for tenant-specific settings
    - Create config/subscription.php for subscription settings
    - Create config/payment.php for payment gateway credentials
    - Document all environment variables in .env.example
    - _Requirements: 19.1-19.9_

  - [x] 18.2 Implement configuration validation
    - Create ConfigValidator service
    - Validate Control DB connection parameters on startup
    - Validate tenant DB connection parameters
    - Validate timezone and currency_code values
    - Fail fast with descriptive errors if invalid
    - _Requirements: 19.1-19.9_

  - [ ]* 18.3 Write property test for configuration round-trip
    - **Property 30: Configuration Round-Trip**
    - **Validates: Requirements 19.10**
    - Verify parse(serialize(config)) equals config
    - _Requirements: 19.10_

- [x] 19. Logging and audit trail
  - [x] 19.1 Configure structured logging
    - Configure Laravel logging channels
    - Create audit log channel for compliance
    - Set up log rotation and retention (90 days minimum)
    - _Requirements: 18.1-18.10_

  - [x] 19.2 Implement audit logging
    - Log authentication attempts (success and failure)
    - Log database connection switches
    - Log RBAC permission denials
    - Log subscription status changes
    - Log payment transactions
    - Log tenant provisioning events
    - Log feature control changes
    - Use consistent log format with timestamp, level, context, message
    - _Requirements: 18.1-18.10_

  - [ ]* 19.3 Write tests for audit logging
    - Test authentication logging
    - Test permission denial logging
    - Test subscription change logging
    - Verify log format consistency
    - _Requirements: 18.1-18.10_

- [x] 20. Artisan commands for administration
  - [x] 20.1 Create tenant management commands
    - Create tenant:provision command for manual provisioning
    - Create tenant:migrate command to run migrations on specific tenant
    - Create tenant:migrate-all command to run migrations on all tenants
    - Create tenant:seed command to seed specific tenant
    - _Requirements: 15.6, 15.7, 15.8_

  - [x] 20.2 Create subscription management commands
    - Create subscription:check-trials command to check expired trials
    - Create subscription:process-renewals command for billing
    - Create subscription:enforce-grace command for grace period enforcement
    - _Requirements: 6.3, 6.6, 6.10_

  - [x] 20.3 Create maintenance commands
    - Create cache:clear-permissions command to clear RBAC cache
    - Create logs:cleanup command to remove old logs
    - Create tokens:cleanup command to remove expired refresh tokens
    - _Requirements: 9.9_

- [x] 21. Testing infrastructure setup
  - [x] 21.1 Configure PHPUnit and Pest
    - Install Pest testing framework
    - Configure test database connections
    - Set up test suites (Unit, Feature, Property)
    - Configure code coverage reporting
    - _Requirements: All_

  - [x] 21.2 Create test factories
    - Create OrganizationFactory
    - Create SubscriptionPlanFactory
    - Create OrgSubscriptionFactory
    - Create PaymentRecordFactory
    - Create DepartmentFactory
    - Create RoleFactory
    - Create RolePermissionFactory
    - Create UserFactory
    - _Requirements: All_

  - [x] 21.3 Create test helpers and traits
    - Create TenantTestTrait for tenant database setup
    - Create AuthenticationTestTrait for JWT token generation
    - Create SubscriptionTestTrait for subscription setup
    - Create helper methods for common test scenarios
    - _Requirements: All_

- [ ] 22. Property-based tests for remaining properties
  - [ ]* 22.1 Write property test for tenant isolation invariant
    - **Property 2: Tenant Isolation Invariant**
    - **Validates: Requirements 4.9**
    - Verify requests from different orgs cannot access each other's data
    - _Requirements: 4.9_

  - [ ]* 22.2 Write property test for organization status access control
    - **Property 3: Organization Status Access Control**
    - **Validates: Requirements 4.7, 4.8**
    - Verify SUSPENDED returns 403, TERMINATED returns 410
    - _Requirements: 4.7, 4.8_

  - [ ]* 22.3 Write property test for subscription-access consistency
    - **Property 10: Subscription-Access Consistency**
    - **Validates: Requirements 11.3, 11.4, 11.6**
    - Verify expired subscriptions block API access
    - _Requirements: 11.3, 11.4, 11.6_

  - [ ]* 22.4 Write property test for module access enforcement
    - **Property 11: Module Access Enforcement**
    - **Validates: Requirements 11.9, 11.10**
    - Verify modules not in plan are blocked
    - _Requirements: 11.9, 11.10_

  - [ ]* 22.5 Write property test for rate limit enforcement
    - **Property 22: Rate Limit Enforcement**
    - **Validates: Requirements 10.9, 10.10, 20.1-20.4**
    - Verify rate limits are enforced correctly
    - _Requirements: 10.9, 10.10, 20.1-20.4_

  - [ ]* 22.6 Write property test for rate limit monotonicity
    - **Property 23: Rate Limit Counter Monotonicity**
    - **Validates: Requirements 20.2, 20.5**
    - Verify request counters increase monotonically
    - _Requirements: 20.2, 20.5_

  - [ ]* 22.7 Write property test for user capacity limit
    - **Property 24: User Capacity Limit Enforcement**
    - **Validates: Requirements 12.7, 12.8**
    - Verify user creation blocked when limit reached
    - _Requirements: 12.7, 12.8_

  - [ ]* 22.8 Write property test for user uniqueness
    - **Property 25: User Email and Employee Code Uniqueness**
    - **Validates: Requirements 12.2, 12.3**
    - Verify email and employee_code are unique per tenant
    - _Requirements: 12.2, 12.3_

  - [ ]* 22.9 Write property test for password hash security
    - **Property 26: Password Hash Security**
    - **Validates: Requirements 12.4**
    - Verify bcrypt hashing with cost 12 and irreversibility
    - _Requirements: 12.4_

- [ ] 23. Integration tests for critical workflows
  - [ ]* 23.1 Write integration test for complete tenant provisioning
    - Test end-to-end provisioning workflow
    - Verify database creation, migrations, seeding, user creation
    - Verify trial subscription creation
    - _Requirements: 5.1-5.11, 17.1-17.10_

  - [ ]* 23.2 Write integration test for subscription lifecycle
    - Test trial creation, upgrade, renewal, cancellation
    - Verify active_subscriptions sync
    - Verify access control at each stage
    - _Requirements: 6.1-6.14_

  - [ ]* 23.3 Write integration test for payment processing
    - Test payment intent creation, webhook processing
    - Verify subscription update on successful payment
    - Verify immutable ledger
    - _Requirements: 7.1-7.11_

  - [ ]* 23.4 Write integration test for complete API request flow
    - Test request through full middleware stack
    - Verify authentication, tenant resolution, subscription gate, RBAC
    - Test successful request and various error scenarios
    - _Requirements: 4.1-4.10, 9.1-9.10, 10.1-10.10, 11.1-11.11_

- [x] 24. Documentation and deployment preparation
  - [x] 24.1 Create API documentation
    - Document all API endpoints with request/response examples
    - Document authentication flow
    - Document error codes and responses
    - Create Postman collection or OpenAPI spec
    - _Requirements: 1.5-1.7, 14.1-14.10_

  - [x] 24.2 Create deployment documentation
    - Document environment setup requirements
    - Document database setup and migration process
    - Document queue worker setup
    - Document scheduled job configuration
    - Document Redis setup
    - Document payment gateway configuration
    - _Requirements: All_

  - [x] 24.3 Create developer onboarding guide
    - Document project structure
    - Document multi-tenant architecture
    - Document middleware stack flow
    - Document testing strategy
    - Document common development tasks
    - _Requirements: All_

- [ ] 25. Final checkpoint - Complete system verification
  - Run all unit tests and verify 80%+ coverage
  - Run all property tests and verify all 31 properties pass
  - Run all integration tests
  - Verify all migrations work on fresh databases
  - Verify seeders populate correct data
  - Test complete tenant provisioning workflow
  - Test complete subscription lifecycle
  - Test payment processing with test gateways
  - Verify API documentation is accurate
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical breaks
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end workflows
- The implementation follows a bottom-up approach: database layer → services → middleware → controllers → API
- All code should follow Laravel best practices and PSR-12 coding standards
- Use dependency injection for all services
- Use Laravel's built-in features (validation, authentication, queues, scheduling) where possible
- Maintain strict separation between Control DB and Tenant DB concerns
