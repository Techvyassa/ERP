# Tax Master API Testing Guide

## Prerequisites
1. Valid JWT token in `auth_token` cookie
2. Active tenant context
3. SETTINGS module permission

---

## 1. HSN Code Master Testing

### List HSN Codes
```bash
GET /api/v1/hsn-codes
Headers: Authorization: Bearer {token}

# With filters
GET /api/v1/hsn-codes?search=0904&is_active=1
```

### Create HSN Code
```bash
POST /api/v1/hsn-codes
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "hsn_code": "0904",
  "hsn_description": "Pepper of the genus Piper",
  "gst_rate": 5.00
}
```

### Update HSN Code
```bash
PUT /api/v1/hsn-codes/1
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "hsn_code": "0904",
  "hsn_description": "Pepper of the genus Piper - Updated",
  "gst_rate": 12.00,
  "is_active": true
}
```

### Delete HSN Code
```bash
DELETE /api/v1/hsn-codes/1
Headers: Authorization: Bearer {token}
```

---

## 2. GST Tax Master Testing

### List GST Taxes
```bash
GET /api/v1/gst-taxes
Headers: Authorization: Bearer {token}

# With filters
GET /api/v1/gst-taxes?search=GST&tax_type=CGST_SGST&is_active=1
```

### Create GST Tax
```bash
POST /api/v1/gst-taxes
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "tax_name": "GST @ 12%",
  "tax_type": "CGST_SGST",
  "cgst_rate": 6.00,
  "sgst_rate": 6.00,
  "igst_rate": 12.00,
  "cess_rate": 0.00
}
```

### Update GST Tax
```bash
PUT /api/v1/gst-taxes/1
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "tax_name": "GST @ 18%",
  "tax_type": "CGST_SGST",
  "cgst_rate": 9.00,
  "sgst_rate": 9.00,
  "igst_rate": 18.00,
  "cess_rate": 0.00,
  "is_active": true
}
```

### Delete GST Tax
```bash
DELETE /api/v1/gst-taxes/1
Headers: Authorization: Bearer {token}
```

---

## 3. Currency Master Testing

### List Currencies
```bash
GET /api/v1/currencies
Headers: Authorization: Bearer {token}

# With filters
GET /api/v1/currencies?search=USD&is_active=1
```

### Create Currency
```bash
POST /api/v1/currencies
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "currency_code": "USD",
  "currency_name": "US Dollar",
  "currency_symbol": "$",
  "exchange_rate": 83.50,
  "is_base_currency": false
}
```

### Update Currency
```bash
PUT /api/v1/currencies/1
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "currency_code": "USD",
  "currency_name": "US Dollar",
  "currency_symbol": "$",
  "exchange_rate": 84.00,
  "is_base_currency": false,
  "is_active": true
}
```

### Delete Currency
```bash
DELETE /api/v1/currencies/1
Headers: Authorization: Bearer {token}
```

---

## Frontend Testing Steps

### HSN Codes Page
1. Navigate to `/org/{org_slug}/hsn-codes`
2. **Test Read**: Verify HSN codes load from API
3. **Test Search**: Type in search box, verify filtering
4. **Test Filter**: Select status filter, verify results
5. **Test Create**: 
   - Click "Add HSN Code" button
   - Fill form fields
   - Submit and verify success message
   - Verify new record appears in list
6. **Test Update**:
   - Click edit icon on any record
   - Modify fields
   - Submit and verify success message
   - Verify changes appear in list
7. **Test Delete**:
   - Click delete icon
   - Confirm deletion
   - Verify record is deactivated

### GST Taxes Page
1. Navigate to `/org/{org_slug}/gst-taxes`
2. **Test Read**: Verify GST taxes load from API
3. **Test Search**: Type in search box, verify filtering
4. **Test Filter**: Select type and status filters, verify results
5. **Test Create**: 
   - Click "Add GST Tax" button
   - Fill form fields (tax name, type, rates)
   - Submit and verify success message
   - Verify new record appears in list
6. **Test Update**:
   - Click edit icon on any record
   - Modify fields
   - Submit and verify success message
   - Verify changes appear in list
7. **Test Delete**:
   - Click delete icon
   - Confirm deletion
   - Verify record is deactivated

### Currency Page
1. Navigate to `/org/{org_slug}/currency`
2. **Test Read**: Verify currencies load from API
3. **Test Search**: Type in search box, verify filtering
4. **Test Filter**: Select status filter, verify results
5. **Test Create**: 
   - Click "Add Currency" button
   - Fill form fields (code, name, symbol, rate)
   - Submit and verify success message
   - Verify new record appears in list
6. **Test Update**:
   - Click edit icon on any record
   - Modify fields
   - Submit and verify success message
   - Verify changes appear in list
7. **Test Delete**:
   - Click delete icon
   - Confirm deletion
   - Verify record is deactivated

### Tax Dashboard
1. Navigate to `/org/{org_slug}/tax-dashboard`
2. **Test Statistics**: Verify counts load from API
3. **Test Navigation**: Click each module card, verify navigation
4. **Test Base Currency**: Verify base currency displays correctly

---

## Expected Responses

### Success Response (200/201)
```json
{
  "success": true,
  "data": {
    "hsn_code": {
      "id": 1,
      "hsn_code": "0904",
      "hsn_description": "Pepper of the genus Piper",
      "gst_rate": "5.00",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  },
  "message": "HSN code created successfully",
  "request_id": "uuid",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### Validation Error (422)
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "hsn_code": ["The hsn code field is required."]
    }
  },
  "message": "Validation failed",
  "request_id": "uuid",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### Not Found Error (404)
```json
{
  "success": false,
  "error": {
    "code": "HSN_CODE_NOT_FOUND",
    "details": []
  },
  "message": "HSN code not found",
  "request_id": "uuid",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### Server Error (500)
```json
{
  "success": false,
  "error": {
    "code": "HSN_CODE_CREATION_FAILED",
    "details": []
  },
  "message": "Failed to create HSN code: {error details}",
  "request_id": "uuid",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

---

## Browser Console Testing

Open browser console and test API calls:

```javascript
// Get auth token
const token = document.cookie.split('; ').find(row => row.startsWith('auth_token='))?.split('=')[1];

// Test HSN Codes List
fetch('/api/v1/hsn-codes', {
  headers: { 'Authorization': `Bearer ${token}` }
})
.then(r => r.json())
.then(console.log);

// Test Create HSN Code
fetch('/api/v1/hsn-codes', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    hsn_code: '0904',
    hsn_description: 'Test HSN Code',
    gst_rate: 5.00
  })
})
.then(r => r.json())
.then(console.log);

// Test Update HSN Code
fetch('/api/v1/hsn-codes/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    hsn_description: 'Updated Description',
    gst_rate: 12.00
  })
})
.then(r => r.json())
.then(console.log);

// Test Delete HSN Code
fetch('/api/v1/hsn-codes/1', {
  method: 'DELETE',
  headers: { 'Authorization': `Bearer ${token}` }
})
.then(r => r.json())
.then(console.log);
```

---

## Common Issues & Solutions

### Issue: 401 Unauthorized
**Solution**: Check if JWT token is valid and present in cookie

### Issue: 403 Forbidden
**Solution**: Verify user has SETTINGS module permission

### Issue: 404 Not Found
**Solution**: Check if tenant context is properly resolved

### Issue: 422 Validation Error
**Solution**: Review validation rules and ensure all required fields are provided

### Issue: 500 Server Error
**Solution**: Check Laravel logs for detailed error information

---

## Status: ✅ ALL TESTS READY

All API endpoints and frontend functionality are ready for testing.
