# Firebase Google Authentication Troubleshooting Guide

## 🔍 Quick Diagnosis

Visit the test page to diagnose Firebase issues:
```
http://localhost:8000/test-firebase
```

This page will show:
- ✅ Firebase initialization status
- ✅ Configuration details
- ✅ Test Google Sign-In button
- ✅ Detailed error messages

## 🚨 Common Issues & Solutions

### 1. "Firebase not initialized" or blank config

**Problem:** Environment variables not loaded

**Solution:**
```bash
# Clear config cache
php artisan config:clear

# Restart server
php artisan serve
```

**Verify .env has:**
```env
FIREBASE_API_KEY=AIzaSyDovrAcdrgd4I_EP-TZG4hXfsP9zh7tjEQ
FIREBASE_AUTH_DOMAIN=zap-erp.firebaseapp.com
FIREBASE_PROJECT_ID=zap-erp
FIREBASE_STORAGE_BUCKET=zap-erp.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=261164236932
FIREBASE_APP_ID=1:261164236932:web:b2aa88c5b33d099f2782d1
FIREBASE_MEASUREMENT_ID=G-JPFBV0KCKH
```

---

### 2. "auth/unauthorized-domain" Error

**Problem:** Your domain is not authorized in Firebase Console

**Solution:**
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select "zap-erp" project
3. Go to Authentication → Settings → Authorized domains
4. Add your domain:
   - `localhost` (for development)
   - `127.0.0.1` (for local testing)
   - Your production domain

**Screenshot location:**
Firebase Console → Authentication → Settings → Authorized domains → Add domain

---

### 3. Google Sign-In Popup Blocked

**Problem:** Browser blocking popup

**Solution:**
- Allow popups for localhost in browser settings
- Check browser console for popup blocker messages
- Try in incognito/private mode
- Disable browser extensions temporarily

---

### 4. "auth/popup-closed-by-user" Error

**Problem:** User closed popup before completing sign-in

**Solution:**
- This is normal user behavior
- Ensure error handling shows friendly message
- User can try again

---

### 5. "auth/network-request-failed" Error

**Problem:** Network connectivity issues

**Solution:**
- Check internet connection
- Verify firewall isn't blocking Firebase domains
- Check if behind corporate proxy
- Try different network

---

### 6. Google Sign-In Button Not Working

**Problem:** JavaScript errors or Firebase not loaded

**Solution:**

1. **Open Browser Console** (F12)
2. **Check for errors:**
   ```
   Look for:
   - "Firebase not defined"
   - "signInWithPopup is not a function"
   - Module loading errors
   ```

3. **Verify Firebase loaded:**
   ```javascript
   // In console, type:
   window.firebaseAuth
   window.googleProvider
   window.firebaseSignInWithPopup
   
   // All should return objects/functions, not undefined
   ```

4. **Check Network Tab:**
   - Verify Firebase SDK files are loading
   - Look for 404 errors on Firebase CDN

---

### 7. "auth/operation-not-allowed" Error

**Problem:** Google Sign-In not enabled in Firebase

**Solution:**
1. Go to Firebase Console
2. Authentication → Sign-in method
3. Click on "Google" provider
4. Toggle "Enable"
5. Add support email
6. Save

---

### 8. CORS Errors

**Problem:** Cross-origin request blocked

**Solution:**
- Ensure using `http://localhost:8000` not `127.0.0.1:8000`
- Or add both to Firebase authorized domains
- Check APP_URL in .env matches your access URL

---

## 🔧 Debug Steps

### Step 1: Check Browser Console

Open Developer Tools (F12) and look for:
```
✅ Firebase initialized successfully
✅ Auth: [object]
✅ Google Provider: [object]
```

If you see errors, note the exact error message.

### Step 2: Test Firebase Config

In browser console, run:
```javascript
console.log(window.firebaseAuth.app.options);
```

Should show:
```javascript
{
  apiKey: "AIzaSy...",
  authDomain: "zap-erp.firebaseapp.com",
  projectId: "zap-erp",
  // ... other config
}
```

### Step 3: Test Google Provider

In browser console, run:
```javascript
console.log(window.googleProvider);
```

Should show GoogleAuthProvider object.

### Step 4: Manual Sign-In Test

In browser console, run:
```javascript
window.firebaseSignInWithPopup(window.firebaseAuth, window.googleProvider)
  .then(result => console.log('Success:', result))
  .catch(error => console.error('Error:', error));
```

This will attempt sign-in and show detailed error.

---

## 📋 Checklist

Before reporting issues, verify:

- [ ] `.env` file has all Firebase credentials
- [ ] Config cache cleared (`php artisan config:clear`)
- [ ] Server restarted
- [ ] Browser console shows no errors
- [ ] Firebase Console shows Google Sign-In enabled
- [ ] Domain authorized in Firebase Console
- [ ] Popup blocker disabled
- [ ] Internet connection working
- [ ] Tested in incognito mode
- [ ] Tested in different browser

---

## 🔐 Firebase Console Setup

### Enable Google Sign-In:

1. **Go to Firebase Console:**
   https://console.firebase.google.com/

2. **Select Project:**
   Click on "zap-erp"

3. **Navigate to Authentication:**
   Left sidebar → Build → Authentication

4. **Go to Sign-in method tab:**
   Click "Sign-in method" at the top

5. **Enable Google:**
   - Find "Google" in the list
   - Click on it
   - Toggle "Enable" switch
   - Enter support email (required)
   - Click "Save"

6. **Add Authorized Domains:**
   - Scroll down to "Authorized domains"
   - Click "Add domain"
   - Add: `localhost`
   - Add: `127.0.0.1`
   - Add your production domain when ready

---

## 🧪 Testing Workflow

### Test Page Method:
```
1. Visit: http://localhost:8000/test-firebase
2. Check Firebase Status section
3. Click "Test Google Sign-In"
4. Complete Google authentication
5. Check result or error message
```

### Login Page Method:
```
1. Visit: http://localhost:8000/login
2. Open browser console (F12)
3. Click "Continue with Google"
4. Watch console for errors
5. Complete authentication
```

---

## 📞 Getting Help

If still not working, provide:

1. **Browser Console Screenshot**
   - Show any red errors
   - Show Firebase config output

2. **Network Tab Screenshot**
   - Filter by "firebase"
   - Show any failed requests

3. **Firebase Console Screenshot**
   - Authentication → Sign-in method
   - Show Google provider status

4. **Error Message**
   - Exact error code (e.g., auth/unauthorized-domain)
   - Full error message

---

## ✅ Success Indicators

When working correctly, you should see:

1. **Browser Console:**
   ```
   ✅ Firebase initialized successfully
   Auth: Auth {app: FirebaseAppImpl, ...}
   Google Provider: GoogleAuthProvider {...}
   ```

2. **Test Page:**
   - Green "Firebase Initialized" status
   - Config details displayed
   - Test button works without errors

3. **Login Page:**
   - Google button opens popup
   - Can select Google account
   - Redirects to dashboard after success

---

## 🎯 Quick Fix Commands

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart server
php artisan serve

# Check environment
php artisan tinker
>>> env('FIREBASE_API_KEY')
# Should show: "AIzaSyDovrAcdrgd4I_EP-TZG4hXfsP9zh7tjEQ"
```

---

## 📚 Resources

- [Firebase Auth Docs](https://firebase.google.com/docs/auth/web/google-signin)
- [Firebase Console](https://console.firebase.google.com/)
- [Common Auth Errors](https://firebase.google.com/docs/reference/js/auth#autherrorcodes)

---

**Last Updated:** 2024
**Project:** Zap ERP
**Firebase Project:** zap-erp
