# 🚀 Zap ERP - Quick Start Guide

## ⚡ Get Started in 3 Steps

### 1️⃣ Configure Environment

Ensure `.env` has Firebase credentials:
```bash
# Check if Firebase config exists
grep FIREBASE .env

# If not found, add these lines:
FIREBASE_API_KEY=AIzaSyDovrAcdrgd4I_EP-TZG4hXfsP9zh7tjEQ
FIREBASE_AUTH_DOMAIN=zap-erp.firebaseapp.com
FIREBASE_PROJECT_ID=zap-erp
FIREBASE_STORAGE_BUCKET=zap-erp.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=261164236932
FIREBASE_APP_ID=1:261164236932:web:b2aa88c5b33d099f2782d1
FIREBASE_MEASUREMENT_ID=G-JPFBV0KCKH
```

### 2️⃣ Clear Cache & Restart

```bash
php artisan config:clear
php artisan serve
```

### 3️⃣ Test Firebase

Visit: **http://localhost:8000/test-firebase**

Click "Test Google Sign-In" button.

---

## 🎯 User Flow

```
Landing (/) 
    ↓ Click "Get Started"
Pricing (/pricing) - Select Plan
    ↓ Click "Select Plan"
Register (/register) - Create Account
    ↓ Fill form or use Google
Login (/login) - Sign In
    ↓ Enter credentials or use Google
Dashboard (/dashboard) - Main App
    ↓ Click "Setup"
Organization Setup (/setup/organization) - Configure
```

---

## 🔑 Key Pages

| Page | URL | Purpose |
|------|-----|---------|
| Landing | `/` | Marketing homepage |
| Pricing | `/pricing` | Select subscription plan |
| Register | `/register` | Create account |
| Login | `/login` | Sign in |
| Dashboard | `/dashboard` | Main application |
| Setup | `/setup/organization` | Configure organization |
| Test | `/test-firebase` | Debug Firebase |

---

## 🔐 Authentication Methods

### Google Sign-In:
- Click "Continue with Google" button
- Select Google account
- Automatic account creation
- Redirects to dashboard

### Email/Password:
- Enter email and password
- Click "Sign in with Email"
- Manual account creation
- Redirects to dashboard

---

## 🐛 Troubleshooting

### Firebase Not Working?

1. **Visit test page:** http://localhost:8000/test-firebase
2. **Check status:** Should show "Firebase Initialized"
3. **Test button:** Click "Test Google Sign-In"
4. **Check console:** Open F12, look for errors

### Common Fixes:

```bash
# Clear config
php artisan config:clear

# Restart server
php artisan serve

# Check environment
php artisan tinker
>>> env('FIREBASE_API_KEY')
```

### Still Not Working?

See: **FIREBASE_TROUBLESHOOTING.md**

---

## 📱 Firebase Console Setup

**Required Steps:**

1. Go to: https://console.firebase.google.com/
2. Select: "zap-erp" project
3. Enable: Authentication → Google Sign-In
4. Add domains: localhost, 127.0.0.1

**Detailed Guide:** See FIREBASE_INTEGRATION.md

---

## 🎨 Branding

- **Name:** Zap ERP
- **Icon:** Lightning bolt (fa-zap)
- **Colors:** Blue (#2563eb), Purple (#764ba2)
- **Theme:** Modern, Professional

---

## 📊 Features

✅ Firebase Authentication
✅ Google Sign-In
✅ Email/Password Auth
✅ Multi-step Registration
✅ Subscription Plans
✅ Organization Setup
✅ Department Management
✅ Role-Based Access
✅ User Invitations

---

## 🔗 API Endpoints

### Authentication:
- `POST /api/v1/auth/login` - Email/Password login
- `POST /api/v1/auth/firebase-login` - Firebase auth
- `POST /api/v1/auth/logout` - Sign out

### Organization:
- `POST /api/v1/organizations/register` - Create org
- `GET /api/v1/subscriptions/plans` - Get plans

### Protected (requires auth):
- `GET /api/v1/users` - List users
- `POST /api/v1/departments` - Create department
- `POST /api/v1/roles` - Create role

---

## 📚 Documentation

- **FIREBASE_INTEGRATION.md** - Complete Firebase setup
- **FIREBASE_TROUBLESHOOTING.md** - Debug guide
- **NEW_FLOW_DOCUMENTATION.md** - User flow details
- **USER_FLOW.md** - Original flow documentation

---

## ✨ Quick Commands

```bash
# Start development
php artisan serve

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Create admin user
php artisan tinker
>>> User::create([...])
```

---

## 🎉 You're Ready!

Visit: **http://localhost:8000**

Click "Get Started" and begin your journey!

---

**Need Help?** Check FIREBASE_TROUBLESHOOTING.md
**Questions?** Open an issue or contact support
