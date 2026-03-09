# Test Firebase Login

## Test Case 1: Login WITHOUT org_slug (Email Fallback)

This should work if the user's email matches an organization's primary_email.

### Request
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/firebase-login \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_token": "YOUR_FIREBASE_TOKEN",
    "email": "developer.amit25@gmail.com",
    "provider": "google",
    "display_name": "Amit A",
    "photo_url": "https://lh3.googleusercontent.com/a/ACg8ocK5LDSxwAO6G7yiYHbj4Wd08jZl49xLBjnWzVtmaYIjwZIZgnc=s96-c"
  }'
```

### Expected Behavior

**If organization exists with this email:**
```json
{
  "success": true,
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "user": {...},
    "organization": {
      "org_slug": "...",
      "org_name": "..."
    }
  }
}
```

**If organization NOT found:**
```json
{
  "success": false,
  "error": {
    "code": "ORGANIZATION_NOT_FOUND"
  },
  "message": "No organization found with this email. Please register first or provide your organization slug."
}
```

## Test Case 2: Login WITH org_slug

### Request
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/firebase-login \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_token": "YOUR_FIREBASE_TOKEN",
    "email": "developer.amit25@gmail.com",
    "org_slug": "test-company",
    "provider": "google",
    "display_name": "Amit A",
    "photo_url": "https://lh3.googleusercontent.com/a/ACg8ocK5LDSxwAO6G7yiYHbj4Wd08jZl49xLBjnWzVtmaYIjwZIZgnc=s96-c"
  }'
```

### Expected Behavior

**If organization exists:**
```json
{
  "success": true,
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "user": {...},
    "organization": {...}
  }
}
```

**If organization NOT found:**
```json
{
  "success": false,
  "error": {
    "code": "ORGANIZATION_NOT_FOUND"
  },
  "message": "Organization not found. Please check your organization URL."
}
```

## What to Do Next

### If You Get "ORGANIZATION_NOT_FOUND"

You need to register an organization first:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/organizations/register \
  -H "Content-Type: application/json" \
  -d '{
    "org_name": "My Test Company",
    "org_slug": "test-company",
    "primary_email": "developer.amit25@gmail.com",
    "first_name": "Amit",
    "last_name": "A",
    "country_code": "IN",
    "firebase_uid": "tomrnv2qlJVGsSntzSMgpA5otit1",
    "provider": "google"
  }'
```

### If You Get "USER_NOT_FOUND"

The organization exists but the user doesn't exist in the tenant database. This happens if:
1. Organization was created but provisioning failed
2. User was deleted
3. User email doesn't match

**Solution:** Re-register or contact admin to add user.

## Check Database

### Check if organization exists
```sql
SELECT org_id, org_slug, org_name, primary_email, registration_status 
FROM organizations 
WHERE primary_email = 'developer.amit25@gmail.com';
```

### Check if user exists in tenant database
```sql
-- First, get the tenant_db_name from organizations table
-- Then switch to that database and check:
USE erp_test_company;
SELECT user_id, email, first_name, last_name, is_active 
FROM users 
WHERE email = 'developer.amit25@gmail.com';
```

## Frontend Integration

```javascript
// In your Firebase auth handler
async function handleGoogleSignIn(user, idToken) {
  try {
    const response = await fetch('/api/v1/auth/firebase-login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        firebase_token: idToken,
        email: user.email,
        provider: 'google',
        display_name: user.displayName,
        photo_url: user.photoURL,
        firebase_uid: user.uid
        // org_slug is optional - will use email fallback
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store tokens
      localStorage.setItem('access_token', data.data.access_token);
      localStorage.setItem('refresh_token', data.data.refresh_token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      
      // Redirect to dashboard
      window.location.href = `/${data.data.organization.org_slug}/dashboard`;
    } else {
      if (data.error.code === 'ORGANIZATION_NOT_FOUND') {
        // Redirect to registration
        window.location.href = '/register';
      } else {
        alert(data.message);
      }
    }
  } catch (error) {
    console.error('Login error:', error);
    alert('Login failed. Please try again.');
  }
}
```
