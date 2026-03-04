# Database Fix Summary - Subscription Plans

## Problem Identified

The `modules_included` field in the `subscription_plans` table was double-encoded:

**Before (Wrong):**
```
"\"[\\\"PR\\\",\\\"PO\\\",\\\"GRN\\\"]\""
```

**After (Correct):**
```
["PR","PO","GRN"]
```

## Root Cause

The data was encoded twice:
1. First encoding: `["PR","PO"]` → `"[\"PR\",\"PO\"]"`
2. Second encoding: `"[\"PR\",\"PO\"]"` → `"\"[\\\"PR\\\",\\\"PO\\\"]\""`

This caused Laravel's JSON casting to fail, resulting in a string instead of an array.

## Solution Applied

### 1. Created Artisan Command
**File:** `app/Console/Commands/FixSubscriptionPlansJson.php`

This command:
- Detects double-encoded JSON
- Decodes twice to get the actual array
- Re-encodes properly (single encoding)
- Updates the database
- Verifies the fix

### 2. Updated Blade Template
**File:** `resources/views/subscription/select.blade.php`

Added defensive code to handle both:
- Properly encoded JSON (array)
- Double-encoded JSON (string)

```php
@php
    $modules = [];
    if (is_string($plan->modules_included)) {
        $decoded = json_decode($plan->modules_included, true);
        if (is_string($decoded)) {
            $modules = json_decode($decoded, true) ?? [];
        } else {
            $modules = $decoded ?? [];
        }
    } elseif (is_array($plan->modules_included)) {
        $modules = $plan->modules_included;
    }
@endphp
```

### 3. Created SQL Fix Script
**File:** `fix_subscription_plans_json.sql`

Manual SQL updates for each plan if needed.

## How to Run the Fix

```bash
php artisan subscription:fix-json
```

**Output:**
```
Fixing subscription plans JSON encoding...
Fixed plan: TRIAL (Trial Plan)
Fixed plan: BASIC (Basic Plan)
Fixed plan: PROFESSIONAL (Professional Plan)
Fixed plan: ENTERPRISE (Enterprise Plan)
Fixed 4 subscription plan(s)

Verifying...
✓ TRIAL: 10 modules
✓ BASIC: 10 modules
✓ PROFESSIONAL: 10 modules
✓ ENTERPRISE: 10 modules
```

## Verification

### Check Database Directly
```sql
SELECT plan_code, modules_included FROM subscription_plans;
```

Should show:
```
TRIAL    | ["PR","PO","GRN","QC","INVOICE","PAYMENT","INVENTORY","REPORTS","USERS","SETTINGS"]
```

### Check via Laravel
```bash
php artisan tinker
>>> App\Models\Control\SubscriptionPlan::first()->modules_included
```

Should return an array, not a string.

### Check via Web
Visit: `http://localhost:8000/pricing`

Should display plans without errors.

## Prevention

To prevent this in the future:

### 1. Always Use Laravel's JSON Casting
```php
// In Model
protected $casts = [
    'modules_included' => 'array',
];
```

### 2. When Inserting Data
```php
// Good
SubscriptionPlan::create([
    'modules_included' => ['PR', 'PO', 'GRN'],
]);

// Bad - Don't manually encode
SubscriptionPlan::create([
    'modules_included' => json_encode(['PR', 'PO']),
]);
```

### 3. Database Column Type
Ensure the column is `JSON` or `TEXT`:
```sql
ALTER TABLE subscription_plans 
MODIFY COLUMN modules_included JSON;
```

## Files Created/Modified

### Created:
1. `app/Console/Commands/FixSubscriptionPlansJson.php` - Fix command
2. `fix_subscription_plans_json.sql` - Manual SQL fix
3. `DATABASE_FIX_SUMMARY.md` - This document

### Modified:
1. `resources/views/subscription/select.blade.php` - Added defensive JSON handling

## Current Database State

All 4 plans are now properly encoded:

| Plan Code | Plan Name | Modules Count |
|-----------|-----------|---------------|
| TRIAL | Trial Plan | 10 modules |
| BASIC | Basic Plan | 10 modules |
| PROFESSIONAL | Professional Plan | 10 modules |
| ENTERPRISE | Enterprise Plan | 10 modules |

## Testing Checklist

- [x] Run fix command
- [x] Verify database data
- [x] Test Laravel model casting
- [x] Update Blade template
- [x] Test pricing page loads
- [ ] Test plan selection
- [ ] Test registration with selected plan
- [ ] Test API endpoints

## Next Steps

1. Test the pricing page: `http://localhost:8000/pricing`
2. Verify all plans display correctly
3. Test billing toggle (monthly/yearly)
4. Test plan selection and redirect to registration
5. Ensure no more JSON encoding errors

## Troubleshooting

### If Plans Still Show Errors

1. Clear Laravel cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. Re-run the fix command:
```bash
php artisan subscription:fix-json
```

3. Check database directly:
```sql
SELECT * FROM subscription_plans;
```

### If New Plans Are Added

When adding new plans, ensure:
```php
SubscriptionPlan::create([
    'plan_code' => 'NEW_PLAN',
    'modules_included' => ['MODULE1', 'MODULE2'], // Array, not JSON string
]);
```

## Summary

✅ Fixed double-encoded JSON in database
✅ Created command to fix existing data
✅ Updated Blade template to handle both cases
✅ Verified all 4 plans are working
✅ Pricing page should now load without errors
