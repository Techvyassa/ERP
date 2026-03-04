# Google Auth Token Missing Fix - Bugfix Design

## Overview

Users authenticating via Google OAuth encounter a "No authentication token found" error when redirected to the dashboard. The bug occurs due to a race condition: the frontend JavaScript sets the `auth_token` cookie via `document.cookie` and immediately redirects to `/dashboard` using `window.location.href`, but the browser doesn't have sufficient time to persist the cookie before the navigation occurs. The `WebJWTAuth` middleware then fails to find the cookie and redirects back to login.

The fix will move cookie setting to the server-side in the `FirebaseAuthController`, ensuring the cookie is properly set in the HTTP response before any redirect occurs. This eliminates the timing issue while maintaining all existing authentication flows.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when Google OAuth authentication completes and JavaScript attempts to set a cookie immediately before redirecting
- **Property (P)**: The desired behavior - the `auth_token` cookie must be available to the `WebJWTAuth` middleware when the dashboard loads
- **Preservation**: Existing email/password authentication flow and all protected route behaviors that must remain unchanged
- **firebaseLogin**: The method in `FirebaseAuthController.php` that handles Firebase authentication and returns JWT tokens
- **WebJWTAuth**: The middleware in `app/Http/Middleware/WebJWTAuth.php` that validates JWT tokens from cookies or headers
- **auth_token**: The cookie name used to store the JWT access token for web route authentication

## Bug Details

### Fault Condition

The bug manifests when a user completes Google OAuth authentication and the frontend receives a successful response from `/api/v1/auth/firebase-login`. The JavaScript code sets the `auth_token` cookie using `document.cookie` and immediately redirects to `/dashboard` using `window.location.href`. The browser's cookie persistence mechanism doesn't complete before the navigation occurs, resulting in the cookie being unavailable when the `WebJWTAuth` middleware checks for it.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type AuthenticationFlow
  OUTPUT: boolean
  
  RETURN input.provider == 'google'
         AND input.authenticationSuccessful == true
         AND input.cookieSetMethod == 'client-side-javascript'
         AND input.redirectMethod == 'immediate-window-location'
         AND input.cookieAvailableOnNextRequest == false
END FUNCTION
```

### Examples

- **Google OAuth Flow**: User clicks "Continue with Google" → Firebase authenticates → Backend returns JWT → JavaScript sets cookie via `document.cookie` → Immediate redirect to `/dashboard` → `WebJWTAuth` middleware checks for cookie → Cookie not found → Error: "No authentication token found" → Redirect to login
- **Email/Password Flow**: User submits email/password → Firebase authenticates → Backend returns JWT → JavaScript sets cookie via `document.cookie` → Immediate redirect to `/dashboard` → Cookie is available (timing works differently) → Dashboard loads successfully
- **API Authentication**: Frontend makes API call with Bearer token in Authorization header → Works correctly (doesn't rely on cookies)
- **Subsequent Navigation**: After successful login (if cookie eventually persists), navigating between protected routes works correctly

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Email/password authentication must continue to work and successfully redirect to dashboard
- API authentication using Bearer tokens in Authorization headers must continue to work
- The `WebJWTAuth` middleware must continue to validate JWT tokens from both cookies and headers
- Protected routes must continue to redirect to login when no valid token is present
- JWT token generation and validation logic must remain unchanged
- User and organization data returned in authentication responses must remain unchanged
- Session persistence across page navigations must continue to work

**Scope:**
All authentication flows that do NOT involve the specific timing issue of client-side cookie setting followed by immediate redirect should be completely unaffected by this fix. This includes:
- API calls with Authorization headers
- Subsequent page navigations after successful authentication
- Token refresh flows
- Logout functionality
- Protected route access with existing valid cookies

## Hypothesized Root Cause

Based on the bug description and code analysis, the root cause is:

1. **Client-Side Cookie Timing Issue**: The JavaScript code uses `document.cookie` to set the cookie and immediately calls `window.location.href` for redirection. Browsers may not guarantee that the cookie is fully persisted to disk or available for the next HTTP request when navigation happens synchronously.

2. **No Server-Side Cookie Setting**: The `FirebaseAuthController::firebaseLogin` method returns a JSON response with the access token but doesn't set the cookie server-side. This forces the client to handle cookie management, introducing the race condition.

3. **Synchronous Redirect**: The `window.location.href = '/dashboard'` executes immediately after setting the cookie, not allowing any time for the browser to complete cookie operations.

4. **Provider-Specific Timing**: Google OAuth may have slightly different timing characteristics than email/password authentication (possibly due to popup window handling or additional Firebase operations), making the race condition more likely to manifest.

## Correctness Properties

Property 1: Fault Condition - Cookie Available After Google OAuth

_For any_ authentication flow where Google OAuth completes successfully and the backend returns a JWT access token, the fixed implementation SHALL ensure the `auth_token` cookie is set server-side in the HTTP response and is available to the `WebJWTAuth` middleware when the user is redirected to `/dashboard`.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Preservation - Existing Authentication Flows

_For any_ authentication flow that does NOT involve the Google OAuth cookie timing issue (email/password login, API authentication, subsequent navigations), the fixed implementation SHALL produce exactly the same behavior as the original implementation, preserving all existing authentication mechanisms and session management.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

## Fix Implementation

### Changes Required

The fix will move cookie setting from client-side JavaScript to server-side Laravel response handling.

**File**: `app/Http/Controllers/FirebaseAuthController.php`

**Function**: `firebaseLogin`

**Specific Changes**:
1. **Add Cookie to Response**: Modify the successful authentication response to include a `withCookie()` call that sets the `auth_token` cookie server-side
   - Use Laravel's `cookie()` helper to create a secure cookie
   - Set the same attributes: `path=/`, `max-age=86400` (24 hours), `SameSite=Lax`
   - Consider adding `Secure` flag for HTTPS environments
   - Consider adding `HttpOnly` flag to prevent JavaScript access (improves security)

2. **Maintain JSON Response**: Keep the existing JSON response structure unchanged so the frontend can still access the token for localStorage and API calls

3. **Cookie Configuration**: Ensure cookie settings match the client-side implementation:
   - Name: `auth_token`
   - Value: JWT access token
   - Max-Age: 86400 seconds (24 hours)
   - Path: `/`
   - SameSite: `Lax`

**File**: `resources/views/auth/login.blade.php`

**Section**: Google OAuth and Email/Password authentication handlers

**Specific Changes**:
1. **Remove Client-Side Cookie Setting**: Remove or comment out the `document.cookie` line since the server now handles it
   - Keep localStorage operations for API calls
   - Keep the redirect logic unchanged

2. **Optional Enhancement**: Add a small delay before redirect to ensure cookie is fully received (though server-side setting should eliminate the need)

3. **Maintain Backward Compatibility**: Keep the client-side cookie setting as a fallback if needed, but the server-side cookie should take precedence

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code, then verify the fix works correctly and preserves existing behavior.

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm that the cookie timing issue causes authentication failures for Google OAuth.

**Test Plan**: Write automated tests that simulate the Google OAuth flow and verify that the cookie is NOT available on the unfixed code. Use browser automation (Selenium/Playwright) or Laravel Dusk to test the actual timing behavior.

**Test Cases**:
1. **Google OAuth Cookie Timing Test**: Simulate Google OAuth login → Verify cookie is NOT set before redirect on unfixed code → Verify middleware fails to find cookie (will fail on unfixed code)
2. **Immediate Redirect Test**: Set cookie via JavaScript → Immediately redirect → Check if cookie is available in next request (will fail on unfixed code)
3. **Cookie Persistence Test**: Verify that `document.cookie` doesn't guarantee immediate availability for subsequent HTTP requests (will fail on unfixed code)
4. **Middleware Cookie Check Test**: Simulate dashboard access without cookie → Verify middleware redirects to login with error message (should pass on unfixed code - this is correct behavior)

**Expected Counterexamples**:
- Cookie is not available to `WebJWTAuth` middleware after Google OAuth redirect
- Possible causes: browser cookie timing, synchronous redirect, client-side cookie setting limitations

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds (Google OAuth authentication), the fixed function ensures the cookie is available.

**Pseudocode:**
```
FOR ALL authFlow WHERE isBugCondition(authFlow) DO
  response := firebaseLogin_fixed(authFlow.request)
  ASSERT response.hasCookie('auth_token')
  ASSERT response.cookie('auth_token').value == authFlow.expectedToken
  ASSERT cookieAvailableInNextRequest(response)
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold (email/password, API calls, subsequent navigations), the fixed function produces the same result as the original function.

**Pseudocode:**
```
FOR ALL authFlow WHERE NOT isBugCondition(authFlow) DO
  ASSERT firebaseLogin_original(authFlow.request) == firebaseLogin_fixed(authFlow.request)
  ASSERT existingBehaviorPreserved(authFlow)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across different authentication scenarios
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs

**Test Plan**: Observe behavior on UNFIXED code first for email/password authentication and API calls, then write property-based tests capturing that behavior.

**Test Cases**:
1. **Email/Password Preservation**: Observe that email/password login works correctly on unfixed code, then write test to verify this continues after fix
2. **API Authentication Preservation**: Observe that API calls with Bearer tokens work correctly on unfixed code, then write test to verify this continues after fix
3. **Protected Route Access Preservation**: Observe that accessing protected routes with valid cookies works correctly on unfixed code, then write test to verify this continues after fix
4. **Token Validation Preservation**: Observe that JWT validation logic works correctly on unfixed code, then write test to verify this continues after fix

### Unit Tests

- Test `FirebaseAuthController::firebaseLogin` returns response with `auth_token` cookie
- Test cookie attributes (name, value, max-age, path, SameSite)
- Test that JSON response structure remains unchanged
- Test that email/password authentication continues to work
- Test that `WebJWTAuth` middleware successfully finds cookie after server-side setting
- Test that middleware still accepts Bearer tokens in Authorization header

### Property-Based Tests

- Generate random authentication scenarios (Google OAuth, email/password, API calls) and verify correct cookie handling
- Generate random JWT tokens and verify cookie setting works for all valid tokens
- Generate random user/organization data and verify authentication flow works correctly
- Test that all non-Google-OAuth flows produce identical results before and after fix

### Integration Tests

- Test full Google OAuth flow: click button → authenticate → verify cookie is set → verify dashboard loads successfully
- Test full email/password flow: submit form → authenticate → verify cookie is set → verify dashboard loads successfully
- Test switching between protected routes after successful authentication
- Test that logout properly clears cookies
- Test that expired tokens are handled correctly
- Test concurrent authentication attempts
