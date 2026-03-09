# JWT Middleware & Currency API Fix

## Issues Found

### 1. JWT Middleware Error (500 Internal Server Error)
**Error:** `Method Illuminate\Http\Request::id does not exist.`

**Location:** `app/Http/Middleware/ValidateJWT.php` line 74

**Problem:**
```php
'request_id' => request()->id() ?? uniqid('req_'),
```

The `request()->id()` method doesn't exist in Laravel's Request class.

**Solution:**
```php
'request_id' => \Illuminate\Support\Str::uuid()->toString(),
```

Use Laravel's `Str::uuid()` helper to generate a proper UUID for request tracking.

---

### 2. Currency Exchange Rate Validation
**Issue:** Exchange rate was required, but should be optional with a default value.

**Location:** `app/Http/Controllers/CurrencyController.php`

**Changes Made:**

#### Store Method
**Before:**
```php
'exchange_rate' => 'required|numeric|min:0',
```

**After:**
```php
'exchange_rate' => 'nullable|numeric|min:0',
```

And in the create logic:
```php
'exchange_rate' => $request->input('exchange_rate', 1.0), // Default to 1.0
```

#### Update Method
**Before:**
```php
'exchange_rate' => 'sometimes|numeric|min:0',
```

**After:**
```php
'exchange_rate' => 'nullable|numeric|min:0',
```

---

## Root Cause Analysis

### JWT Middleware Issue
The middleware was trying to call a non-existent method on the Request object. This happened because:
1. The code assumed `request()->id()` exists (it doesn't in Laravel)
2. This caused a `BadMethodCallException` when the middleware tried to generate error responses
3. Any API request without a valid JWT token would trigger this error

### Impact
- All API endpoints requiring authentication would fail with 500 error
- Error responses couldn't be generated properly
- Made debugging difficult as the real error was masked

---

## Testing

### Test JWT Middleware Fix

**Test 1: Missing Token**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/currencies \
  -H "Content-Type: application/json" \
  -d '{"currency_code": "USD"}'
```

**Expected Response (401):**
```json
{
  "success": false,
  "error": {
    "code": "TOKEN_REQUIRED",
    "details": []
  },
  "message": "Token required",
  "request_id": "uuid-here",
  "timestamp": "2026-03-05T11:06:09+00:00"
}
```

**Test 2: Invalid Token**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/currencies \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid-token" \
  -d '{"currency_code": "USD"}'
```

**Expected Response (401):**
```json
{
  "success": false,
  "error": {
    "code": "TOKEN_INVALID",
    "details": []
  },
  "message": "Invalid token",
  "request_id": "uuid-here",
  "timestamp": "2026-03-05T11:06:09+00:00"
}
```

---

### Test Currency API with Optional Exchange Rate

**Test 1: Create Currency Without Exchange Rate**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/currencies \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {valid-token}" \
  -d '{
    "currency_code": "INR",
    "currency_name": "Indian Rupee",
    "currency_symbol": "₹",
    "is_base_currency": true
  }'
```

**Expected:** Success with `exchange_rate: 1.0` (default)

**Test 2: Create Currency With Exchange Rate**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/currencies \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {valid-token}" \
  -d '{
    "currency_code": "USD",
    "currency_name": "US Dollar",
    "currency_symbol": "$",
    "exchange_rate": 83.50,
    "is_base_currency": false
  }'
```

**Expected:** Success with `exchange_rate: 83.50`

**Test 3: Update Currency Without Exchange Rate**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/currencies/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {valid-token}" \
  -d '{
    "currency_name": "Indian Rupee Updated"
  }'
```

**Expected:** Success, exchange_rate remains unchanged

---

## Files Modified

1. **app/Http/Middleware/ValidateJWT.php**
   - Fixed `request()->id()` to use `Str::uuid()->toString()`
   - Now properly generates UUID for request tracking

2. **app/Http/Controllers/CurrencyController.php**
   - Changed `exchange_rate` validation from `required` to `nullable`
   - Added default value of `1.0` in store method
   - Changed validation from `sometimes` to `nullable` in update method

---

## Benefits

### JWT Middleware Fix
✅ Proper error responses for authentication failures
✅ Consistent request ID generation using UUID
✅ Better debugging with unique request tracking
✅ No more 500 errors for missing/invalid tokens

### Currency Exchange Rate Fix
✅ Exchange rate is now optional
✅ Defaults to 1.0 for base currency
✅ More flexible API usage
✅ Better user experience (less required fields)

---

## Status: ✅ FIXED

Both issues have been resolved:
1. JWT middleware now properly generates error responses
2. Currency exchange rate is optional with sensible default
