# Authentication Token - Final Fix & Troubleshooting

## Changes Made

### 1. Backend - Cookie Settings Updated
**Files:** `FirebaseAuthController.php`, `AuthController.php`

Updated cookie parameters for better compatibility:
```php
->cookie(
    'auth_token',
    $accessToken,
    60 * 24,      // 24 hours
    '/',          // path
    null,         // domain
    false,        // secure: false for localhost, true for production
    true,         // httpOnly: prevents JavaScript access
    false,        // raw
    'lax'         // sameSite: allows same-site requests
)
```

### 2. Frontend - Removed Manual Cookie Setting
**File:** `resources/views/auth/login.blade.php`

- ❌ Removed: `document.cookie = 'auth_token=...'`
- ✅ Added: `credentials: 'include'` in fetch requests
- ✅ Cookie is now set automatically by server

### 3. CORS Configuration
**File:** `config/cors.php` (created)

Added CORS support with credentials:
```php
'supports_credentials' => true,
```

### 4. Cache Cleared
Cleared all Laravel caches to ensure changes take effect.

## How to Test

### Step 1: Clear Browser Data
1. Open DevTools (F12)
2. Go to Application tab
3. Clear all cookies for localhost
4. Clear localStorage
5. Close and reopen browser

### Step 2: Try Login
1. Go to login page
2. Click "Continue with Google"
3. Complete Google authentication
4. Should redirect to dashboard successfully

### Step 3: Verify Cookie
1. After successful login, open DevTools
2. Go to Application → Cookies → localhost
3. Should see `auth_token` cookie with:
   - Value: JWT token
   - Path: /
   - HttpOnly: ✓
   - SameSite: Lax

## Troubleshooting

### Still Getting "No authentication token found"?

#### Check 1: Cookie is Being Set
```javascript
// In browser console after login
document.cookie
// Should show auth_token
```

If cookie is NOT there:
1. Check browser console for errors
2. Check Network tab → Response Headers → Should see `Set-Cookie`
3. Ensure `credentials: 'include'` is in fetch request

#### Check 2: Cookie is Being Sent
```javascript
// Check request headers when accessing /dashboard
// Network tab → dashboard request → Request Headers
// Should include: Cookie: auth_token=...
```

If cookie is NOT being sent:
1. Check cookie domain matches current domain
2. Check cookie path is `/`
3. Check SameSite setting

#### Check 3: Middleware is Reading Cookie
Add debug logging to `WebJWTAuth` middleware:
```php
// In handle() method
\Log::info('Cookie check', [
    'bearer' => $request->bearerToken(),
    'cookie' => $request->cookie('auth_token'),
    'all_cookies' => $request->cookies->all()
]);
```

### Common Issues & Solutions

#### Issue 1: Cookie Not Persisting
**Symptom:** Cookie disappears after page refresh

**Solution:**
- Check cookie expiry time
- Ensure browser allows cookies
- Check if browser is in incognito mode

#### Issue 2: CORS Error
**Symptom:** "CORS policy: No 'Access-Control-Allow-Credentials' header"

**Solution:**
```bash
# Clear config cache
php artisan config:clear

# Verify CORS config
php artisan tinker
>>> config('cors.supports_credentials')
# Should return: true
```

#### Issue 3: Cookie Domain Mismatch
**Symptom:** Cookie set but not sent with requests

**Solution:**
- Ensure you're accessing via same domain (e.g., all localhost or all 127.0.0.1)
- Don't mix localhost and 127.0.0.1

#### Issue 4: HttpOnly Preventing Access
**Symptom:** JavaScript can't read cookie

**Solution:**
- This is CORRECT behavior (security feature)
- Cookie should only be read by server
- Don't try to access via JavaScript

## Testing Checklist

- [ ] Clear browser cookies and cache
- [ ] Login with Google OAuth
- [ ] Check cookie is set in DevTools
- [ ] Redirect to dashboard works
- [ ] No "No authentication token found" error
- [ ] Cookie persists on page refresh
- [ ] Logout clears cookie

## Debug Commands

### Check Laravel Config
```bash
php artisan config:show cors
php artisan config:show session
```

### Check Routes
```bash
php artisan route:list --path=auth
php artisan route:list --path=dashboard
```

### Test Cookie Manually
```bash
# Test login endpoint
curl -X POST http://localhost:8000/api/v1/auth/firebase-login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "firebase_token": "...",
    "email": "test@example.com",
    "provider": "google"
  }'

# Check cookies.txt file - should contain auth_token

# Test dashboard with cookie
curl http://localhost:8000/dashboard \
  -b cookies.txt
```

## Environment Variables

Ensure these are set in `.env`:

```env
# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

# App
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

# CORS (if needed)
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## Production Considerations

When deploying to production:

1. **Enable Secure Flag**
   ```php
   'secure' => env('APP_ENV') === 'production', // true for HTTPS
   ```

2. **Update CORS Origins**
   ```php
   'allowed_origins' => [env('FRONTEND_URL')],
   ```

3. **Set Proper Domain**
   ```php
   'domain' => env('SESSION_DOMAIN', null),
   ```

4. **Use HTTPS**
   - Secure cookies only work over HTTPS
   - Ensure SSL certificate is valid

## Summary of Changes

✅ Backend sets cookie with proper SameSite=Lax
✅ Frontend removed manual cookie setting
✅ Frontend added credentials: 'include'
✅ CORS configured to support credentials
✅ Cache cleared
✅ Secure flag disabled for localhost

## Next Steps

1. **Clear browser data completely**
2. **Try login again**
3. **Check DevTools for cookie**
4. **If still failing, check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Support

If issue persists:
1. Check `storage/logs/laravel.log` for errors
2. Check browser console for JavaScript errors
3. Check Network tab for failed requests
4. Verify database has organization and user records
