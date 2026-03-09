# Route Reference Guide

Quick reference for all application routes and navigation patterns.

---

## 🌐 Public Routes (No Authentication)

| Route | Name | Description |
|-------|------|-------------|
| `/` | `home` | Landing page |
| `/pricing` | `pricing` | Pricing plans page |
| `/login` | `login` | Login page |
| `/register` | `register` | Registration page |
| `/auth/google` | `auth.google` | Google OAuth redirect |
| `/auth/google/callback` | `auth.google.callback` | Google OAuth callback |

---

## 🔐 Protected Routes (Require Authentication)

### Main Dashboard (Navigation Hub)

| Route | Name | Description |
|-------|------|-------------|
| `/dashboard` | `dashboard` | Main navigation dashboard |

**Purpose:** Central hub for navigating to all modules. Shows quick stats, progress tracking, and navigation cards.

---

### Control Panel (Super Admin)

| Route | Name | Description |
|-------|------|-------------|
| `/control/dashboard` | `control.dashboard` | Control panel dashboard |
| `/control/organizations` | `control.organizations.index` | Manage organizations |
| `/control/subscriptions` | `control.subscriptions.index` | Manage subscriptions |
| `/control/plans` | `control.plans.index` | Manage subscription plans |
| `/control/payments` | `control.payments.index` | View payment records |
| `/control/features` | `control.features.index` | Feature control management |
| `/control/settings` | `control.settings` | System settings |
| `/control/profile` | `control.profile` | Admin profile |

**Purpose:** Super admin interface for managing the entire platform.

---

### Tenant Routes (Organization-Specific)

**Pattern:** `/org/{org_slug}/...`

#### Dashboard & Setup

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/dashboard` | `tenant.dashboard` | Tenant main dashboard |
| `/org/{org_slug}/profile-completion` | `tenant.profile-completion` | Organization profile setup |
| `/org/{org_slug}/master-setup` | `tenant.master-setup` | Master data dashboard |

#### Organization Masters

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/departments` | `tenant.departments.index` | Department management |
| `/org/{org_slug}/roles` | `tenant.roles.index` | Role management |
| `/org/{org_slug}/users` | `tenant.users.index` | User management |
| `/org/{org_slug}/approval-matrix` | `tenant.approval-matrix.index` | Approval workflow |

#### Inventory Masters

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/materials` | `tenant.materials.index` | Material master |
| `/org/{org_slug}/products` | `tenant.products.index` | Product master |
| `/org/{org_slug}/warehouses` | `tenant.warehouses.index` | Warehouse master |
| `/org/{org_slug}/bin-locations` | `tenant.bin-locations.index` | Bin location master |
| `/org/{org_slug}/uom` | `tenant.uom.index` | Unit of measure |

#### Vendor Masters

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/vendors` | `tenant.vendors.index` | Vendor master |
| `/org/{org_slug}/vendor-contacts` | `tenant.vendor-contacts.index` | Vendor contacts |
| `/org/{org_slug}/vendor-material-map` | `tenant.vendor-material-map.index` | Vendor-material mapping |

#### Tax Masters

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/hsn-codes` | `tenant.hsn-codes.index` | HSN code master |
| `/org/{org_slug}/gst-taxes` | `tenant.gst-taxes.index` | GST tax configuration |
| `/org/{org_slug}/currency` | `tenant.currency.index` | Currency master |

#### BOM Masters

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/bom-header` | `tenant.bom-header.index` | BOM header |
| `/org/{org_slug}/bom-detail` | `tenant.bom-detail.index` | BOM detail |

#### Other Pages

| Route | Name | Description |
|-------|------|-------------|
| `/org/{org_slug}/reports` | `tenant.reports.index` | Reports |
| `/org/{org_slug}/settings` | `tenant.settings` | Tenant settings |
| `/org/{org_slug}/profile` | `tenant.profile` | User profile |

---

## 🔄 Navigation Patterns

### Pattern 1: Login → Main Dashboard → Module
```
/login
  ↓
/dashboard (Main Dashboard)
  ↓ (Click card)
/org/{org_slug}/dashboard (Tenant Dashboard)
  ↓ (Use sidebar)
/org/{org_slug}/{module}
```

### Pattern 2: Direct Module Access
```
/login
  ↓
/dashboard (Main Dashboard)
  ↓ (Click specific module card)
/org/{org_slug}/{module}
```

### Pattern 3: Sidebar Navigation
```
Any tenant page
  ↓ (Click sidebar link)
/org/{org_slug}/{another-module}
```

---

## 📱 API Routes

### Authentication

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/api/v1/auth/login` | Email/password login |
| POST | `/api/v1/auth/firebase-login` | Firebase/Google login |
| POST | `/api/v1/auth/register` | User registration |
| POST | `/api/v1/auth/refresh` | Refresh access token |
| POST | `/api/v1/auth/logout` | Logout |

### Profile & Setup

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/api/v1/profile-completion/status` | Get profile completion status |
| GET | `/api/v1/profile-completion/master-data-status` | Get master data status |
| PUT | `/api/v1/profile-completion/update` | Update profile |

### Subscription Plans

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/api/v1/subscription-plans` | Get all subscription plans |

---

## 💾 LocalStorage Keys

After successful login, the following data is stored:

| Key | Type | Description |
|-----|------|-------------|
| `user` | JSON | User object (id, name, email, etc.) |
| `access_token` | String | JWT access token |
| `refresh_token` | String | JWT refresh token |
| `org_slug` | String | Organization slug for routing |
| `org_data` | JSON | Organization object (id, name, slug, etc.) |
| `firebase_uid` | String | Firebase UID (if Google login) |

---

## 🎯 JavaScript Navigation Helper

```javascript
// Navigate to tenant module
function navigateTo(section) {
    const orgSlug = localStorage.getItem('org_slug');
    
    if (!orgSlug) {
        alert('Organization not found. Please login again.');
        window.location.href = '/login';
        return;
    }
    
    const routes = {
        'dashboard': `/org/${orgSlug}/dashboard`,
        'profile': `/org/${orgSlug}/profile-completion`,
        'masters': `/org/${orgSlug}/master-setup`,
        'departments': `/org/${orgSlug}/departments`,
        'users': `/org/${orgSlug}/users`,
        'production': `/org/${orgSlug}/production`,
        'inventory': `/org/${orgSlug}/inventory`
    };
    
    if (routes[section]) {
        window.location.href = routes[section];
    } else {
        alert(`${section} page coming soon!`);
    }
}
```

---

## 🔒 Middleware

All protected routes use the `web.jwt` middleware:

```php
Route::middleware(['web.jwt'])->group(function () {
    // All protected routes here
});
```

**Middleware checks:**
- Valid JWT token in cookie
- Token not expired
- User exists and is active

---

## 🎨 Route Naming Convention

| Prefix | Example | Usage |
|--------|---------|-------|
| (none) | `home`, `login` | Public pages |
| `control.` | `control.dashboard` | Super admin pages |
| `tenant.` | `tenant.dashboard` | Tenant pages |
| `tenant.{category}.` | `tenant.departments.index` | Tenant module pages |

---

## 📝 Adding New Routes

### Step 1: Add Route Definition
```php
// In routes/web.php
Route::get('/org/{org_slug}/new-module', function ($orgSlug) use ($getOrg) {
    extract($getOrg($orgSlug));
    return view('tenant.new-module.index', [
        'organization' => $org,
        'tenantType' => $tenantType
    ]);
})->name('tenant.new-module.index');
```

### Step 2: Add to Navigation
```javascript
// In main dashboard navigateTo()
const routes = {
    // ... existing routes
    'new-module': `/org/${orgSlug}/new-module`
};
```

### Step 3: Add Sidebar Link
```html
<!-- In tenant/layouts/app.blade.php -->
<a href="{{ route('tenant.new-module.index', ['org_slug' => $organization->org_slug]) }}">
    <span class="material-symbols-outlined">icon_name</span>
    <span>New Module</span>
</a>
```

### Step 4: Create View
```
resources/views/tenant/new-module/index.blade.php
```

---

## ✅ Route Testing Checklist

- [ ] Route is defined in `routes/web.php`
- [ ] Route uses correct middleware (`web.jwt`)
- [ ] Route name follows naming convention
- [ ] View file exists
- [ ] Navigation link added (if applicable)
- [ ] JavaScript navigation updated (if applicable)
- [ ] Route accepts `org_slug` parameter
- [ ] Organization data passed to view
- [ ] Route tested with valid org_slug
- [ ] Route tested with invalid org_slug
- [ ] Route tested without authentication

---

**Last Updated:** March 5, 2026  
**Version:** 1.0  
**Maintained by:** Development Team
