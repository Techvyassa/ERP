# Firebase Login Fix - org_slug Optional

## Issue

Firebase login was failing with error:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "org_slug": ["The org slug field is required."]
    }
  }
}
```

## Root Cause

The `FirebaseAuthController::firebaseLogin()` method was updated to require `org_slug` parameter, but the frontend wasn't sending it during Google OAuth login.

## Solution

Made `org_slug` optional and implemented fallback logic:

1. **If org_slug provided:** Find organization by slug
2. **If org_slug not provided:** Find organization by primary email
3. **If not found:** Return appropriate error message

## Updated Validation

### Before (Required)
```php
'org_slug' => 'required|string',
```

### After (Optional)
```php
'org_slug' => 'nullable|string',
```

## Updated Logic

```php
if ($orgSlug) {
    // Find by slug if provided
    $organization = Organization::where('org_slug', $orgSlug)->first();
} else {
    // Fallback to finding by email
    $organization = Organization::where('primary_email', $email)->first();
}
```

## API Usage

### Option 1: With org_slug (Recommended)
```json
POST /api/v1/auth/firebase-login
{
  "firebase_token": "...",
  "email": "developer.amit25@gmail.com",
  "org_slug": "my-company",
  "provider": "google",
  "display_name": "Amit A",
  "photo_url": "https://..."
}
```

### Option 2: Without org_slug (Fallback)
```json
POST /api/v1/auth/firebase-login
{
  "firebase_token": "...",
  "email": "developer.amit25@gmail.com",
  "provider": "google",
  "display_name": "Amit A",
  "photo_url": "https://..."
}
```

## Error Messages

### With org_slug (not found)
```json
{
  "error": {
    "code": "ORGANIZATION_NOT_FOUND"
  },
  "message": "Organization not found. Please check your organization URL."
}
```

### Without org_slug (not found)
```json
{
  "error": {
    "code": "ORGANIZATION_NOT_FOUND"
  },
  "message": "No organization found with this email. Please register first or provide your organization slug."
}
```

## Use Cases

### Use Case 1: Organization Owner Login
User who registered the organization can login without knowing the slug:
```javascript
// Frontend code
const response = await fetch('/api/v1/auth/firebase-login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    firebase_token: idToken,
    email: user.email,
    provider: 'google',
    display_name: user.displayName,
    photo_url: user.photoURL
    // No org_slug needed
  })
});
```

### Use Case 2: Team Member Login
Team member who knows the organization slug:
```javascript
// Frontend code
const orgSlug = window.location.pathname.split('/')[1]; // From URL
const response = await fetch('/api/v1/auth/firebase-login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    firebase_token: idToken,
    email: user.email,
    org_slug: orgSlug, // From URL
    provider: 'google',
    display_name: user.displayName,
    photo_url: user.photoURL
  })
});
```

## Frontend Implementation

### Recommended Approach
```javascript
async function handleGoogleLogin(idToken, user) {
  // Try to get org_slug from URL
  const pathParts = window.location.pathname.split('/');
  const orgSlug = pathParts.length > 1 ? pathParts[1] : null;
  
  const payload = {
    firebase_token: idToken,
    email: user.email,
    provider: 'google',
    display_name: user.displayName,
    photo_url: user.photoURL
  };
  
  // Add org_slug if available
  if (orgSlug && orgSlug !== 'login' && orgSlug !== 'register') {
    payload.org_slug = orgSlug;
  }
  
  const response = await fetch('/api/v1/auth/firebase-login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  
  const data = await response.json();
  
  if (data.success) {
    // Store tokens
    localStorage.setItem('access_token', data.data.access_token);
    localStorage.setItem('refresh_token', data.data.refresh_token);
    
    // Redirect to dashboard
    const orgSlug = data.data.organization.org_slug;
    window.location.href = `/${orgSlug}/dashboard`;
  } else {
    // Handle error
    if (data.error.code === 'ORGANIZATION_NOT_FOUND') {
      // Redirect to registration
      window.location.href = '/register';
    } else {
      alert(data.message);
    }
  }
}
```

## Testing

### Test 1: Login with org_slug
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase-login \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_token": "...",
    "email": "developer.amit25@gmail.com",
    "org_slug": "test-company",
    "provider": "google",
    "display_name": "Amit A"
  }'
```

### Test 2: Login without org_slug
```bash
curl -X POST http://localhost:8000/api/v1/auth/firebase-login \
  -H "Content-Type: application/json" \
  -d '{
    "firebase_token": "...",
    "email": "developer.amit25@gmail.com",
    "provider": "google",
    "display_name": "Amit A"
  }'
```

## Migration Guide

### For Existing Frontend Code

If your frontend is already sending `org_slug`, no changes needed.

If your frontend is NOT sending `org_slug`:
1. No immediate changes required (will work with email fallback)
2. Recommended: Update to include org_slug from URL for better UX

### URL Structure

Recommended URL structure for login pages:
```
/{org_slug}/login  - Organization-specific login
/login             - Generic login (uses email fallback)
```

## Benefits

1. **Backward Compatible:** Existing code without org_slug still works
2. **Flexible:** Supports both organization-specific and generic login
3. **Better UX:** Organization owners don't need to remember slug
4. **Secure:** Still validates organization exists and is active

## Limitations

1. **Email Uniqueness:** Assumes primary_email is unique per organization
2. **Multiple Organizations:** If a user belongs to multiple organizations, they must provide org_slug
3. **Team Members:** Team members should use organization-specific URL with slug

## Recommendations

### For Organization Owners
- Can login from generic `/login` page
- System finds organization by email automatically

### For Team Members
- Should use organization-specific URL: `/{org_slug}/login`
- Ensures they login to correct organization

### For Multi-Organization Users
- Must provide org_slug to specify which organization to access
- Consider implementing organization selector UI

## Security Considerations

1. **Email Verification:** Ensure Firebase email is verified
2. **Organization Status:** Check organization is ACTIVE or PENDING
3. **User Status:** Verify user is active in tenant database
4. **Rate Limiting:** Implement rate limiting on login endpoint

## Next Steps

1. ✅ Update FirebaseAuthController to make org_slug optional
2. ✅ Implement fallback logic to find by email
3. ✅ Update error messages
4. [ ] Update frontend to send org_slug when available
5. [ ] Test both scenarios (with and without org_slug)
6. [ ] Update user documentation

## Summary

The Firebase login endpoint now supports both:
- **With org_slug:** Direct organization lookup (faster, recommended)
- **Without org_slug:** Email-based organization lookup (fallback, convenient)

This provides flexibility while maintaining security and proper organization isolation.
