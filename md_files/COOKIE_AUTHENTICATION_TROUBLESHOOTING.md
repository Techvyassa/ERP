# Cookie Authentication Troubleshooting Guide

## Issue: "No authentication token found"

This error occurs when the `WebJWTAuth` middleware cannot find the authentication token in either the Authorization header or the `auth_token` cookie.

## Changes Made to Fix

### 1. Excluded auth_token from Cookie Encryption
**File:** `bootstrap/app.php`

Laravel encrypts all cookies by default. JWT tokens should NOT be encrypted because they are already signed and tamper-proof.

```php
$middleware->encryptCookies(except: [
    'auth_token',
]);
```

### 2. Added Debug Logging
**File:** `app/Http/Middleware/WebJWTAuth.php`

Added logging to track:
- Whether bearer token exists
- Whether cookie token exists
- Cookie value (first 20 chars)
- All available cookies

### 3. Added Login Success Logging
**File:** `app/Http/Controllers/AuthController.php`

Logs successful login with user_id, org_slug, and token length.

## Debugging Steps

### Step 1: Check if Cookie is Being Set

After login, visit: `http://your-domain/test-cookie`

This page shows:
- Server-side cookies (PHP $_COOKIE)
- Laravel request cookies
- Auth token cookie specifically
- Client-side cookies (JavaScript document.cookie)
- LocalStorage contents

**Expected Result:**
```
Auth Token Cookie:
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**If NOT FOUND:**
- Cookie is not being set by the server
- Check browser console for errors
- Check if response includes Set-Cookie header

### Step 2: Check Browser Developer Tools

1. Open browser DevTools (F12)
2. Go to Application/Storage tab
3. Check Cookies section
4. Look for `auth_token` cookie

**Cookie Properties Should Be:**
- Name: `auth_token`
- Value: JWT token (long string starting with eyJ...)
- Domain: your domain
- Path: `/`
- HttpOnly: ✓ (checked)
- Secure: ✓ (if HTTPS)
- SameSite: Lax

### Step 3: Check Laravel Logs

**File:** `storage/logs/laravel.log`

Look for:
```
[timestamp] local.INFO: Login successful {"user_id":1,"org_slug":"acme","token_length":xxx}
[timestamp] local.INFO: WebJWTAuth Debug {"has_bearer_token":false,"has_cookie_token":true,...}
```

**If you see:**
```
[timestamp] local.WARNING: No authentication token found
```

This means the cookie is not reaching the server.

### Step 4: Check Network Tab

1. Open DevTools Network tab
2. Login
3. Check the login API response
4. Look for `Set-Cookie` header in Response Headers

**Expected:**
```
Set-Cookie: auth_token=eyJ0eXAiOiJKV1QiLCJhbGc...; expires=...; Max-Age=86400; path=/; httponly; samesite=lax
```

### Step 5: Check Subsequent Request

1. After login, when redirected to `/dashboard`
2. Check the dashboard request in Network tab
3. Look for `Cookie` header in Request Headers

**Expected:**
```
Cookie: auth_token=eyJ0eXAiOiJKV1QiLCJhbGc...
```

**If missing:**
- Browser is not sending the cookie
- Check cookie domain/path settings
- Check if cookie expired
- Check SameSite policy

## Common Issues and Solutions

### Issue 1: Cookie Encrypted
**Symptom:** Cookie exists but token is garbled/encrypted

**Solution:** Exclude from encryption (already done in bootstrap/app.php)

### Issue 2: Cookie Not Set (HTTPS/Secure Flag)
**Symptom:** Cookie not set in production (HTTPS)

**Solution:** The `request()->secure()` flag should automatically detect HTTPS. If not working, manually set:

```php
->cookie(
    'auth_token',
    $result->accessToken,
    60 * 24,
    '/',
    null,
    true, // Force secure=true for HTTPS
    true,
    false,
    'lax'
);
```

### Issue 3: Cookie Not Set (Domain Mismatch)
**Symptom:** Cookie set but not sent on subsequent requests

**Solution:** Check cookie domain. If your app is on `app.example.com`, set domain:

```php
->cookie(
    'auth_token',
    $result->accessToken,
    60 * 24,
    '/',
    '.example.com', // Set domain
    request()->secure(),
    true,
    false,
    'lax'
);
```

### Issue 4: SameSite Policy
**Symptom:** Cookie not sent on cross-site requests

**Solution:** Already set to `lax`. For stricter security, use `strict`, but this may cause issues with redirects.

### Issue 5: Cookie Expired
**Symptom:** Cookie was set but disappeared

**Solution:** Check cookie expiration. Currently set to 24 hours (60 * 24 minutes).

### Issue 6: Browser Blocking Cookies
**Symptom:** Cookie not set in browser

**Solution:**
- Check browser privacy settings
- Disable "Block third-party cookies"
- Check if browser extensions are blocking cookies
- Try incognito/private mode

### Issue 7: Localhost Cookie Issues
**Symptom:** Cookies not working on localhost

**Solution:**
- Use `127.0.0.1` instead of `localhost`
- Or set domain to `null` (already done)
- Ensure secure flag is `false` for HTTP

## Testing Checklist

- [ ] Login with email/password
- [ ] Check `/test-cookie` page shows auth_token
- [ ] Check browser DevTools shows cookie
- [ ] Check Network tab shows Set-Cookie header
- [ ] Check dashboard request includes Cookie header
- [ ] Check Laravel logs for "Login successful"
- [ ] Check Laravel logs for "WebJWTAuth Debug"
- [ ] Login with Google OAuth
- [ ] Repeat above checks for Google login

## Alternative: Use LocalStorage Instead of Cookies

If cookies continue to cause issues, you can use localStorage exclusively:

### Frontend Changes:
```javascript
// After login, manually add token to requests
const response = await fetch('/dashboard', {
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('access_token')}`
    }
});
```

### Backend Changes:
```php
// WebJWTAuth middleware - prioritize Authorization header
$token = $request->bearerToken();
if (!$token) {
    // Only fall back to cookie if no Authorization header
    $token = $request->cookie('auth_token');
}
```

## Production Checklist

Before deploying to production:

1. [ ] Set `APP_ENV=production` in `.env`
2. [ ] Set `APP_DEBUG=false` in `.env`
3. [ ] Ensure HTTPS is enabled
4. [ ] Verify `request()->secure()` returns true
5. [ ] Test cookie setting in production environment
6. [ ] Remove debug logging from WebJWTAuth
7. [ ] Set appropriate cookie domain for your domain
8. [ ] Test cross-subdomain access if needed
9. [ ] Configure CORS if frontend is on different domain
10. [ ] Set up proper session/cookie configuration in `config/session.php`

## Quick Fix: Force Cookie in JavaScript

If server-side cookies aren't working, you can manually set the cookie in JavaScript after login:

```javascript
// After successful login
const data = await response.json();
if (data.success) {
    // Set cookie manually
    document.cookie = `auth_token=${data.data.access_token}; path=/; max-age=86400; samesite=lax`;
    
    // Also store in localStorage
    localStorage.setItem('access_token', data.data.access_token);
    
    window.location.href = '/dashboard';
}
```

**Note:** This bypasses HttpOnly flag, which is less secure but may work if server-side cookies fail.

## Contact Support

If none of these solutions work:

1. Share the output from `/test-cookie` page
2. Share relevant Laravel logs
3. Share browser console errors
4. Share Network tab screenshots
5. Specify your environment (local/staging/production)
6. Specify your domain setup
