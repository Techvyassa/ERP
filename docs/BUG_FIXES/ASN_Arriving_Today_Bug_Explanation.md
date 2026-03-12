# ASN "Arriving Today" Bug Explanation

## Issue Report
**Date**: March 11, 2026  
**Reporter**: User  
**Module**: ASN (Advance Shipping Notice)  
**Endpoint**: `GET /api/v1/asn/arriving-today`

## Problem Description
The `/api/v1/asn/arriving-today` endpoint is not returning any results even though there is an ASN with ETA set to a future date.

### Test Data
```json
{
  "eta": "2026-03-12T14:00:00.000000Z"
}
```

### Current Date
Wednesday, March 11, 2026

### Expected Behavior
User expected the ASN to appear in the "arriving today" list.

### Actual Behavior
The endpoint returns an empty array.

---

## Root Cause Analysis

### Code Investigation
The issue is in the `scopeArrivingToday` method in `app/Models/Tenant/ASN.php`:

```php
public function scopeArrivingToday($query)
{
    return $query->whereDate('eta', today())
        ->whereIn('status', ['SENT', 'IN_TRANSIT']);
}
```

### The Logic
The scope uses `whereDate('eta', today())` which:
1. Extracts only the DATE portion from the `eta` column
2. Compares it to TODAY's date (March 11, 2026)

### The Bug
**This is NOT a bug - it's working as designed!**

The test data has:
- ETA: `2026-03-12T14:00:00.000000Z` (March 12, 2026 at 2:00 PM)
- Today: March 11, 2026

The ASN is scheduled to arrive TOMORROW (March 12), not TODAY (March 11).

---

## Explanation

The `arrivingToday()` scope is functioning correctly. It filters ASNs where:
1. The ETA date equals TODAY's date
2. The status is either SENT or IN_TRANSIT

### Why No Results?
- Your test ASN has ETA = March 12, 2026
- Today is March 11, 2026
- March 12 ≠ March 11
- Therefore, the ASN does NOT appear in "arriving today"

---

## Solution

### Option 1: Update Test Data (Recommended)
Change the ETA to today's date:

```bash
curl -X PUT "http://localhost/api/v1/asn/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd" \
  -d '{
    "eta": "2026-03-11T14:00:00.000000Z"
  }'
```

### Option 2: Wait Until Tomorrow
On March 12, 2026, the ASN will appear in the "arriving today" list.

### Option 3: Use Different Endpoint
If you want to see ASNs arriving in the near future, you could:

1. Use the regular list endpoint with date filtering:
```bash
curl -X GET "http://localhost/api/v1/asn?eta_from=2026-03-11&eta_to=2026-03-13" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```

2. Create a new scope for "arriving this week" or "arriving soon":
```php
public function scopeArrivingSoon($query, int $days = 3)
{
    return $query->whereBetween('eta', [now(), now()->addDays($days)])
        ->whereIn('status', ['SENT', 'IN_TRANSIT']);
}
```

---

## Testing Verification

### Test Case 1: ASN Arriving Today
```json
{
  "eta": "2026-03-11T14:00:00.000000Z",
  "status": "IN_TRANSIT"
}
```
**Expected**: Should appear in `/api/v1/asn/arriving-today`

### Test Case 2: ASN Arriving Tomorrow
```json
{
  "eta": "2026-03-12T14:00:00.000000Z",
  "status": "IN_TRANSIT"
}
```
**Expected**: Should NOT appear in `/api/v1/asn/arriving-today`

### Test Case 3: ASN Arriving Today but Already Received
```json
{
  "eta": "2026-03-11T14:00:00.000000Z",
  "status": "RECEIVED"
}
```
**Expected**: Should NOT appear in `/api/v1/asn/arriving-today` (status filter)

---

## Conclusion

**Status**: NOT A BUG - Working as designed

The `arrivingToday()` scope is functioning correctly. The confusion arose from test data having an ETA of March 12 (tomorrow) while testing on March 11 (today).

The endpoint correctly filters ASNs where:
- ETA date = Current date
- Status = SENT or IN_TRANSIT

To see results, update the test data to have today's date as the ETA.

---

## Related Endpoints

### Get Overdue ASNs
```bash
curl -X GET "http://localhost/api/v1/asn/overdue" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```
Returns ASNs where ETA < now() and status is SENT or IN_TRANSIT.

### Get All ASNs
```bash
curl -X GET "http://localhost/api/v1/asn" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-Tenant: amit-tech-solutions-pvt-ltd"
```
Returns all ASNs regardless of ETA.
