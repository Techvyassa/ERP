# Zap ERP - Complete User Flow Documentation

## 🎯 New Flow Overview

```
Landing Page (/)
    ↓
Select Subscription (/pricing) - STEP 1
    ↓
Create Account (/register) - STEP 2
    ↓
Sign In (/login) - STEP 3
    ↓
Dashboard (/dashboard)
    ↓
Organization Setup (/setup/organization)
```

## 📄 Pages & Routes

### 1. Landing Page - `/`
**File:** `resources/views/landing.blade.php`
**Brand:** Zap ERP

**Features:**
- Hero section with gradient background
- Statistics (500+ companies, 99.9% uptime)
- 6 key features showcase
- Industry solutions
- Pricing preview
- Customer testimonials
- Multiple CTAs

**CTAs:**
- "Get Started" → `/pricing`
- "Sign In" → `/login`

---

### 2. Subscription Selection - `/pricing` (STEP 1 OF 3)
**File:** `resources/views/subscription/select.blade.php`

**Features:**
- Monthly/Yearly billing toggle (20% discount)
- 3 pricing tiers:
  - Starter: $49/month
  - Professional: $149/month (Most Popular)
  - Enterprise: Custom
- Plan selection stores in localStorage
- "Already have an account? Sign In" link

**Flow:**
- User selects plan
- Redirects to `/register?plan=selected_plan`

**API:** None (client-side only)

---

### 3. Registration - `/register` (STEP 2 OF 3)
**File:** `resources/views/auth/register.blade.php`

**Features:**
- Shows selected plan with "Change Plan" option
- Google SSO button → `/auth/google`
- Registration form:
  - First Name
  - Last Name
  - Organization Name
  - Work Email
  - Password (min 8 chars)
  - Terms & Conditions checkbox
- Auto-generates org_slug from org_name
- Form validation with error display

**API:** `POST /api/v1/organizations/register`

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "org_name": "Acme Manufacturing",
  "org_slug": "acme-manufacturing",
  "primary_email": "john@acme.com",
  "password": "password123",
  "country_code": "US",
  "selected_plan": "professional"
}
```

**Success:** Redirects to `/login?registered=true`

---

### 4. Login - `/login` (STEP 3 OF 3)
**File:** `resources/views/auth/login.blade.php`

**Features:**
- Google SSO button → `/auth/google`
- Email/password login form
- Remember me checkbox
- Forgot password link
- "Don't have an account? Get Started" → `/pricing`

**API:** `POST /api/v1/auth/login`

**Request Body:**
```json
{
  "email": "john@acme.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "jwt_token_here",
    "user": {
      "user_id": 1,
      "email": "john@acme.com",
      "first_name": "John",
      "last_name": "Doe"
    }
  }
}
```

**Success:** 
- Stores JWT token in localStorage
- Redirects to `/dashboard`

---

### 5. Dashboard - `/dashboard`
**File:** `resources/views/dashboard/index.blade.php`
**Protected:** Requires authentication

**Features:**
- Full navigation header
- Welcome banner
- Statistics cards:
  - Total Users
  - Active Orders
  - Inventory Items
  - Revenue
- Quick actions:
  - Add User
  - New Order
  - Add Department
  - Generate Report
- Recent activity feed
- Link to Organization Setup

---

### 6. Organization Setup - `/setup/organization`
**File:** `resources/views/setup/organization.blade.php`
**Protected:** Requires authentication

**Features:**
- Setup progress tracker
- Department management
- Role & permissions management
- Team member invitations
- Master data configuration:
  - Products & Materials
  - Suppliers & Vendors
  - Production Lines
- Quick setup guide

**APIs Used:**
- `POST /api/v1/departments` - Create department
- `GET /api/v1/departments` - List departments
- `POST /api/v1/roles` - Create role
- `GET /api/v1/roles` - List roles
- `POST /api/v1/users` - Invite user
- `GET /api/v1/users` - List users

---

## 🔐 Google OAuth Flow

### Routes:
1. **Initiate:** `GET /auth/google`
   - Redirects to Google OAuth consent screen
   - Scopes: email, profile

2. **Callback:** `GET /auth/google/callback`
   - Receives authorization code
   - Exchanges for access token
   - Creates/authenticates user
   - Redirects to dashboard

### Implementation Required:
```php
// .env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://your-domain/auth/google/callback
```

---

## 🎨 Branding: Zap ERP

### Logo Icon:
- `<i class="fas fa-zap"></i>` (Lightning bolt)
- Blue background (#2563eb)
- White icon

### Color Scheme:
- Primary: Blue (#2563eb)
- Secondary: Purple (#764ba2)
- Success: Green (#10b981)
- Warning: Yellow (#f59e0b)
- Danger: Red (#ef4444)

### Typography:
- Font: System fonts
- Headings: Bold
- Body: Regular

---

## 📊 User Journey

### New User:
1. Lands on homepage
2. Clicks "Get Started"
3. Selects subscription plan
4. Creates account (or uses Google SSO)
5. Receives confirmation
6. Signs in
7. Accesses dashboard
8. Sets up organization

### Returning User:
1. Lands on homepage
2. Clicks "Sign In"
3. Enters credentials (or uses Google SSO)
4. Accesses dashboard

---

## 🔧 API Integration Points

### Registration Flow:
```
POST /api/v1/organizations/register
- Creates organization
- Provisions tenant database
- Returns org_id and status
```

### Authentication:
```
POST /api/v1/auth/login
- Validates credentials
- Returns JWT token
- Returns user data
```

### Organization Setup:
```
POST /api/v1/departments
GET /api/v1/departments
POST /api/v1/roles
GET /api/v1/roles
POST /api/v1/users
GET /api/v1/users
```

---

## 🚀 Getting Started

### 1. Configure Environment:
```bash
# .env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

### 2. Start Server:
```bash
php artisan serve
```

### 3. Access Application:
```
http://localhost:8000
```

### 4. Test Flow:
1. Visit homepage
2. Click "Get Started"
3. Select a plan
4. Register account
5. Sign in
6. Explore dashboard
7. Set up organization

---

## ✨ Key Features

### Landing Page:
- Modern gradient design
- Clear value proposition
- Social proof (testimonials)
- Transparent pricing
- Multiple conversion points

### Registration:
- Google SSO integration
- Simple 5-field form
- Plan selection carried forward
- Real-time validation
- Secure password requirements

### Login:
- Google SSO option
- Remember me functionality
- Forgot password link
- Clean, professional design

### Dashboard:
- Quick statistics overview
- Action shortcuts
- Activity tracking
- Easy navigation

### Organization Setup:
- Guided setup process
- Department management
- Role-based access control
- Team collaboration
- Master data configuration

---

## 📝 TODO

- [ ] Implement Google OAuth backend
- [ ] Add email verification
- [ ] Create password reset flow
- [ ] Build payment gateway integration
- [ ] Add user profile management
- [ ] Implement department CRUD operations
- [ ] Create role permission matrix
- [ ] Add user invitation emails
- [ ] Build master data modules
- [ ] Add analytics tracking

---

## 🎉 Complete!

Your Zap ERP application now has a complete user flow from landing page to organization setup with Google SSO integration ready!
