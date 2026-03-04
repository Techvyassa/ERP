# Authentication Token Cookie Fix

## Problem: "No authentication token found"

### What Was Happening

After successful Google OAuth login, users were getting redirected to the dashboard but immediately seeing "No authentication token found" error and being redirected back to login.

### Root Cause: Race Condition

The issue was a **timing/race condition** in how authentication tokens were being set:

```
1. User logs in with Google OAuth
2. Backend returns JWT token in JSON response
3. Frontend JavaScript receives token
4. Frontend sets cookie: document.cookie = 'auth_token=...'
5. Frontend IMMEDIATELY redirects: window.location.href = '/dashboard'
6. Browser navigates to /dashboard
7. WebJWTAuth middleware checks for auth_token cookie
8. ❌ Cookie not found! (Browser hasn't finished setting it yet)
9. Error: "No authentication token found"
10. Redirect back to login
```

### Why It's a Race Condition

When JavaScript sets a cookie and immediately redirects:
- The cookie setting is **asynchronous**
- The redirect happens **synchronously**
- The browser may not have persisted the cookie before navigation
- The new page request doesn't include the cookie
- Middleware can't find the token

### Why Email/Password Sometimes Worked

Email/password authentication might have worked because:
- Different timing characteristics
- Additional processing time before redirect
- Browser had more time to persist the cookie
- But it was still unreliable and could fail

## Solution: Server-Side Cookie Setting

### What We Changed

Instead of relying on JavaScript to set the cookie, we now **set it on the server-side** in the HTTP response.

### Files Modified

#### 1. FirebaseAuthController.php
```php
// Before
return response()->json([...], 200);

// After
return response()->json([...], 200)
    ->cookie('auth_token', $accessToken, 60 * 24, '/', null, true, true);
```

#### 2. AuthController.php
```php
// Before
return response()->json([...], 200);

// After
return response()->json([...], 200)
    ->cookie('auth_token', $result->accessToken, 60 * 24, '/', null, true, true);
```

### Cookie Parameters Explained

```php
->cookie(
    'auth_token',        // Cookie name
    $accessToken,        // Cookie value (JWT token)
    60 * 24,            // Expiry: 24 hours (in minutes)
    '/',                // Path: available on all routes
    null,               // Domain: current domain
    true,               // Secure: only over HTTPS (true in production)
    true                // HttpOnly: not accessible via JavaScript (security)
)
```

## How It Works Now

### New Flow (Fixed)

```
1. User logs in with Google OAuth
2. Backend generates JWT token
3. Backend sets auth_token cookie in HTTP response
4. Backend returns JSON with token data
5. Browser receives response with Set-Cookie header
6. Browser AUTOMATICALLY sets the cookie
7. Frontend JavaScript redirects to /dashboard
8. Browser navigates with cookie already set
9. WebJWTAuth middleware finds auth_token cookie
10. ✅ Authentication successful!
11. Dashboard loads
```

### Key Improvements

1. **Server-Side Cookie Setting**: Cookie is set by Laravel in HTTP response
2. **Automatic**: Browser handles cookie setting (no JavaScript needed)
3. **Reliable**: Cookie is guaranteed to be set before redirect
4. **Secure**: HttpOnly flag prevents JavaScript access (XSS protection)
5. **No Race Condition**: Cookie is available immediately on next request

## Frontend Changes (Optional)

### Before (Old Way - Don't Use)
```javascript
// ❌ Old way - causes race condition
const response = await fetch('/api/v1/auth/firebase-login', {...});
const data = await response.json();

if (data.success) {
    // Setting cookie manually (unreliable)
    document.cookie = `auth_token=${data.data.access_token}; path=/; max-age=86400`;
    
    // Immediate redirect (cookie might not be set yet)
    window.location.href = '/dashboard';
}
```

### After (New Way - Recommended)
```javascript
// ✅ New way - cookie is set automatically by server
const response = await fetch('/api/v1/auth/firebase-login', {...});
const data = await response.json();

if (data.success) {
    // Cookie is ALREADY set by server in response
    // Just store additional data if needed
    localStorage.setItem('user', JSON.stringify(data.data.user));
    
    // Safe to redirect - cookie is already available
    window.location.href = `/${data.data.organization.org_slug}/dashboard`;
}
```

### Even Better (Store Tokens in localStorage Too)
```javascript
const response = await fetch('/api/v1/auth/firebase-login', {...});
const data = await response.json();

if (data.success) {
    // Cookie is set by server (for web routes)
    // Also store in localStorage (for API calls)
    localStorage.setItem('access_token', data.data.access_token);
    localStorage.setItem('refresh_token', data.data.refresh_token);
    localStorage.setItem('user', JSON.stringify(data.data.user));
    
    // Redirect to dashboard
    window.location.href = `/${data.data.organization.org_slug}/dashboard`;
}
```

## Security Benefits

### HttpOnly Cookie

The cookie is set with `httpOnly: true`, which means:
- ✅ JavaScript **cannot** read the cookie
- ✅ Protects against XSS (Cross-Site Scripting) attacks
- ✅ Token is only sent in HTTP requests
- ✅ More secure than localStorage

### Secure Flag

In production (HTTPS), the cookie has `secure: true`:
- ✅ Cookie only sent over HTTPS
- ✅ Prevents man-in-the-middle attacks
- ✅ Token cannot be intercepted on insecure connections

## Testing

### Test 1: Google OAuth Login
```javascript
// 1. Login with Google
const response = await fetch('/api/v1/auth/firebase-login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        firebase_token: idToken,
        email: user.email,
        provider: 'google',
        display_name: user.displayName
    })
});

// 2. Check response includes Set-Cookie header
console.log(response.headers.get('set-cookie')); // Should include auth_token

// 3. Redirect to dashboard
const data = await response.json();
window.location.href = '/dashboard';

// 4. Dashboard should load successfully (no "No authentication token found" error)
```

### Test 2: Email/Password Login
```javascript
// 1. Login with email/password
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123',
        org_slug: 'my-company'
    })
});

// 2. Cookie should be set automatically
// 3. Redirect should work without errors
```

### Test 3: Verify Cookie in Browser
```javascript
// After login, check cookies in browser DevTools
// Application tab → Cookies → localhost
// Should see: auth_token with HttpOnly flag
```

## Backward Compatibility

### API Clients (Mobile Apps, etc.)

API clients can still use the token from JSON response:
```javascript
// Get token from response
const { access_token } = data.data;

// Use in Authorization header for API calls
fetch('/api/v1/users', {
    headers: {
        'Authorization': `Bearer ${access_token}`
    }
});
```

### Web Applications

Web applications benefit from automatic cookie handling:
- Cookie is set automatically
- Cookie is sent automatically with every request
- No need to manually manage tokens
- More secure (HttpOnly)

## Troubleshooting

### Still Getting "No authentication token found"?

1. **Check Cookie is Set**
   - Open DevTools → Application → Cookies
   - Look for `auth_token` cookie
   - Verify it has a value

2. **Check Cookie Domain**
   - Cookie domain should match your app domain
   - If using localhost, cookie should be for localhost

3. **Check Cookie Path**
   - Cookie path should be `/`
   - This makes it available on all routes

4. **Check HTTPS in Production**
   - In production, ensure you're using HTTPS
   - Secure cookies won't work over HTTP

5. **Clear Old Cookies**
   - Clear browser cookies
   - Try login again

### Cookie Not Being Set?

1. **Check Response Headers**
   ```javascript
   const response = await fetch('/api/v1/auth/login', {...});
   console.log(response.headers.get('set-cookie'));
   ```

2. **Check CORS Settings**
   - Ensure `credentials: 'include'` in fetch
   - Ensure CORS allows credentials

3. **Check Browser Console**
   - Look for cookie-related errors
   - Check if cookies are blocked

## Summary

### Problem
- Race condition when setting cookies via JavaScript
- Cookie not available when middleware checks
- "No authentication token found" error

### Solution
- Set cookie on server-side in HTTP response
- Cookie is automatically set by browser
- No race condition
- More secure (HttpOnly)

### Benefits
- ✅ Reliable authentication
- ✅ No race conditions
- ✅ Better security (HttpOnly)
- ✅ Automatic cookie handling
- ✅ Works for both Google OAuth and email/password
- ✅ Backward compatible with API clients

### Files Changed
- `app/Http/Controllers/FirebaseAuthController.php`
- `app/Http/Controllers/AuthController.php`

### Status
✅ Fixed - Authentication tokens are now set reliably via server-side cookies
