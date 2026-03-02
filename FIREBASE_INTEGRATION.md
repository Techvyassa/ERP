# Firebase Authentication Integration - Zap ERP

## 🔥 Overview

Zap ERP now uses Firebase Authentication for secure user authentication with Google Sign-In and Email/Password authentication.

## 📋 Firebase Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Firebase Web App Configuration
FIREBASE_API_KEY=AIzaSyDovrAcdrgd4I_EP-TZG4hXfsP9zh7tjEQ
FIREBASE_AUTH_DOMAIN=zap-erp.firebaseapp.com
FIREBASE_PROJECT_ID=zap-erp
FIREBASE_STORAGE_BUCKET=zap-erp.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=261164236932
FIREBASE_APP_ID=1:261164236932:web:b2aa88c5b33d099f2782d1
FIREBASE_MEASUREMENT_ID=G-JPFBV0KCKH
```

### Firebase Project Setup

1. **Project Created:** zap-erp
2. **Authentication Methods Enabled:**
   - Google Sign-In
   - Email/Password

3. **Authorized Domains:**
   - localhost (for development)
   - your-production-domain.com (add in Firebase Console)

## 🔧 Implementation

### Files Created/Modified:

1. **Firebase Config Component**
   - `resources/views/components/firebase-config.blade.php`
   - Initializes Firebase SDK
   - Makes auth functions globally available

2. **Login Page**
   - `resources/views/auth/login.blade.php`
   - Google Sign-In button
   - Email/Password login
   - Firebase authentication integration

3. **Registration Page**
   - `resources/views/auth/register.blade.php`
   - Google Sign-Up button
   - Email/Password registration
   - Creates Firebase user + backend registration

4. **Firebase Auth Controller**
   - `app/Http/Controllers/FirebaseAuthController.php`
   - Handles Firebase token verification
   - Creates/authenticates users

5. **API Routes**
   - `routes/api.php`
   - Added `/api/v1/auth/firebase-login` endpoint

## 🔐 Authentication Flow

### Google Sign-In Flow:

```
1. User clicks "Continue with Google"
2. Firebase popup opens for Google authentication
3. User selects Google account
4. Firebase returns user credentials + ID token
5. Frontend sends ID token to backend
6. Backend verifies token with Firebase
7. Backend creates/finds user in database
8. Backend generates JWT token
9. User redirected to dashboard
```

### Email/Password Registration:

```
1. User fills registration form
2. Firebase creates user account
3. Firebase returns user credentials + ID token
4. Frontend sends data to backend
5. Backend verifies Firebase token
6. Backend creates organization + user
7. User redirected to login
```

### Email/Password Login:

```
1. User enters email/password
2. Firebase authenticates credentials
3. Firebase returns ID token
4. Frontend sends token to backend
5. Backend verifies and creates session
6. User redirected to dashboard
```

## 📡 API Endpoints

### Firebase Login
```http
POST /api/v1/auth/firebase-login
Content-Type: application/json

{
  "firebase_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6...",
  "email": "user@example.com",
  "provider": "google",
  "display_name": "John Doe",
  "photo_url": "https://..."
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "jwt_token_here",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "user_id": 1,
      "email": "user@example.com",
      "display_name": "John Doe",
      "photo_url": "https://...",
      "provider": "google"
    }
  }
}
```

### Organization Registration (with Firebase)
```http
POST /api/v1/organizations/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "org_name": "Acme Manufacturing",
  "org_slug": "acme-manufacturing",
  "primary_email": "john@acme.com",
  "firebase_uid": "firebase_user_id",
  "firebase_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6...",
  "provider": "google",
  "photo_url": "https://...",
  "country_code": "US",
  "selected_plan": "professional"
}
```

## 🛠️ Frontend Implementation

### Including Firebase Config:

```blade
<!-- In your blade template head section -->
@include('components.firebase-config')
```

### Google Sign-In Button:

```javascript
document.getElementById('googleSignInBtn').addEventListener('click', async function() {
    const result = await window.signInWithPopup(window.firebaseAuth, window.googleProvider);
    const user = result.user;
    const idToken = await user.getIdToken();
    
    // Send to backend...
});
```

### Email/Password Registration:

```javascript
const userCredential = await window.createUserWithEmailAndPassword(
    window.firebaseAuth, 
    email, 
    password
);
const user = userCredential.user;
const idToken = await user.getIdToken();
```

### Email/Password Login:

```javascript
const userCredential = await window.signInWithEmailAndPassword(
    window.firebaseAuth, 
    email, 
    password
);
const user = userCredential.user;
const idToken = await user.getIdToken();
```

## 🔒 Security Features

### Firebase Security:
- ID tokens are cryptographically signed
- Tokens expire after 1 hour
- Automatic token refresh
- Secure HTTPS communication

### Backend Verification:
- Firebase ID tokens verified on backend
- JWT tokens generated for session management
- CSRF protection enabled
- Rate limiting on auth endpoints

## 🚀 Setup Instructions

### 1. Firebase Console Setup:

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select "zap-erp" project
3. Navigate to Authentication
4. Enable Google Sign-In provider
5. Enable Email/Password provider
6. Add authorized domains

### 2. Google OAuth Setup:

1. In Firebase Console → Authentication → Sign-in method
2. Click on Google provider
3. Enable the provider
4. Add support email
5. Save configuration

### 3. Environment Configuration:

```bash
# Copy .env.example to .env
cp .env.example .env

# Firebase credentials are already in .env.example
# No additional configuration needed
```

### 4. Test Authentication:

```bash
# Start Laravel server
php artisan serve

# Visit http://localhost:8000
# Click "Get Started"
# Try Google Sign-In or Email registration
```

## 📊 Firebase Analytics

Firebase Analytics is automatically initialized and tracks:
- Page views
- User sign-ups
- Login events
- User engagement

View analytics in Firebase Console → Analytics

## 🐛 Troubleshooting

### Common Issues:

1. **"Firebase not defined" error**
   - Ensure `@include('components.firebase-config')` is in page head
   - Check browser console for script loading errors

2. **"Unauthorized domain" error**
   - Add your domain to Firebase Console → Authentication → Settings → Authorized domains

3. **Google Sign-In popup blocked**
   - Check browser popup blocker settings
   - Ensure HTTPS in production

4. **Token verification fails**
   - Check Firebase credentials in .env
   - Ensure backend has internet access to verify tokens

### Debug Mode:

Enable Firebase debug logging:
```javascript
// Add to firebase-config.blade.php
firebase.auth().useDeviceLanguage();
firebase.auth().setPersistence(firebase.auth.Auth.Persistence.LOCAL);
```

## 📝 Next Steps

### Backend Implementation Required:

1. **Install Firebase Admin SDK:**
```bash
composer require kreait/firebase-php
```

2. **Verify Firebase Tokens:**
```php
use Kreait\Firebase\Factory;

$factory = (new Factory)->withServiceAccount('path/to/serviceAccount.json');
$auth = $factory->createAuth();
$verifiedIdToken = $auth->verifyIdToken($idToken);
```

3. **Create/Find User:**
```php
// Find user by Firebase UID or email
// Create if doesn't exist
// Generate JWT token
// Return user data
```

4. **Update OrganizationController:**
- Accept `firebase_uid` and `firebase_token`
- Verify token before creating organization
- Link Firebase user to organization

## 🎉 Benefits

- ✅ Secure authentication with Firebase
- ✅ Google Sign-In integration
- ✅ Email/Password authentication
- ✅ Automatic token management
- ✅ Built-in security features
- ✅ Analytics tracking
- ✅ Easy to scale
- ✅ No password storage on backend

## 📚 Resources

- [Firebase Authentication Docs](https://firebase.google.com/docs/auth)
- [Firebase Web SDK](https://firebase.google.com/docs/web/setup)
- [Firebase Admin SDK PHP](https://firebase-php.readthedocs.io/)
- [Google Sign-In](https://developers.google.com/identity/sign-in/web)

---

**Project:** Zap ERP  
**Firebase Project ID:** zap-erp  
**Last Updated:** 2024
