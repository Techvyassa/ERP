# Login & Navigation Flow

## 🔐 Complete Login Flow

### Step-by-Step Journey:

```
1. User visits Landing Page (/)
   ↓
2. Clicks "Login" or "Get Started"
   ↓
3. Login Page (/login)
   ↓ (Enter credentials)
4. API Authentication (/api/v1/auth/login)
   ↓ (Success)
5. Store in localStorage:
   - user (user object)
   - access_token
   - refresh_token
   - org_slug
   - org_data (organization object)
   ↓
6. Check User Type:
   ├─ Super Admin → Redirect to /control/dashboard
   └─ Tenant User → Redirect to /org/{org_slug}/dashboard
```

### Super Admin Identification:
```javascript
const isSuperAdmin = user.email === 'admin@zaperp.com' || 
                   orgSlug === 'super-admin' ||
                   user.is_super_admin === true;
```

---

## 🎯 Main Dashboard (Navigation Hub)

### Purpose:
The main dashboard serves as an **optional navigation hub** for tenant users. Super admins are automatically redirected to the control panel.

### Auto-Redirect Logic:
- **Super Admin** → Automatically redirected to `/control/dashboard`
- **Tenant User** → Can use as navigation hub or go directly to tenant dashboard

### Available Cards (for Tenant Users):

1. **Go to Dashboard** (Featured - Blue gradient)
   - Direct access to tenant dashboard with sidebar
   - Full navigation capabilities
   - Main workspace

2. **Organization Profile**
   - Complete profile setup
   - Progress tracking
   - Direct link to profile completion

3. **Master Data Setup**
   - Configure all master data
   - Progress tracking
   - Direct link to master dashboard

4. **Departments**
   - Manage departments
   - Direct link to department management

5. **Users & Roles**
   - Manage team members
   - Direct link to user management

6. **Production** (Coming Soon)
   - Production module
   - Work orders, shop floor

7. **Inventory** (Coming Soon)
   - Inventory module
   - Stock management

---

## 🏢 Navigation Options

### For Super Admin:
```
Login
  ↓
Auto-redirect to Control Panel (/control/dashboard)
  ↓
Manage all organizations, subscriptions, plans, etc.
```

### For Tenant Users - Option 1: Direct Access (Recommended)
```
Login
  ↓
Direct to Tenant Dashboard (/org/{org_slug}/dashboard)
  ↓ (Use sidebar or cards)
Navigate to any module
```

**Benefits:**
- Fastest access to workspace
- No intermediate steps
- Familiar for returning users

### For Tenant Users - Option 2: Via Main Dashboard
```
Login
  ↓
Main Dashboard (/dashboard) - Optional
  ↓ (Click "Go to Dashboard" card)
Tenant Dashboard (/org/{org_slug}/dashboard)
  ↓ (Use sidebar or cards)
Navigate to any module
```

**Benefits:**
- Overview of all options
- Progress tracking visible
- Good for new users

---

## 📊 Dashboard Hierarchy

```
┌─────────────────────────────────────────┐
│     Main Dashboard (/dashboard)         │
│     ┌─────────────────────────────┐     │
│     │  Navigation Hub             │     │
│     │  • Cards for all modules    │     │
│     │  • Progress tracking        │     │
│     │  • Quick stats              │     │
│     └─────────────────────────────┘     │
└─────────────────┬───────────────────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
    ▼             ▼             ▼
┌─────────┐  ┌─────────┐  ┌─────────┐
│ Tenant  │  │ Profile │  │ Masters │
│Dashboard│  │ Setup   │  │Dashboard│
└─────────┘  └─────────┘  └─────────┘
    │             │             │
    ▼             ▼             ▼
┌─────────────────────────────────┐
│  All Modules with Sidebar      │
│  • Organization Masters         │
│  • Inventory Masters            │
│  • Vendor Masters               │
│  • Tax Masters                  │
│  • BOM Masters                  │
│  • Reports                      │
└─────────────────────────────────┘
```

---

## 🔄 Navigation Patterns

### Pattern 1: Hub & Spoke (Main Dashboard)
```
Main Dashboard (Hub)
    ├─→ Tenant Dashboard (Spoke)
    ├─→ Profile Setup (Spoke)
    ├─→ Master Dashboard (Spoke)
    ├─→ Departments (Spoke)
    ├─→ Users (Spoke)
    └─→ Other Modules (Spokes)
```

### Pattern 2: Hierarchical (Tenant Dashboard)
```
Tenant Dashboard
    ├─→ Profile Completion
    ├─→ Master Dashboard
    │   ├─→ Organization Masters
    │   ├─→ Inventory Masters
    │   ├─→ Vendor Masters
    │   ├─→ Tax Masters
    │   └─→ BOM Masters
    ├─→ Reports
    ├─→ Settings
    └─→ Profile
```

### Pattern 3: Sidebar (All Master Pages)
```
Any Master Page
    ├─→ Sidebar Navigation (Always visible)
    │   ├─→ Dashboard
    │   ├─→ Profile Setup
    │   ├─→ Master Setup
    │   ├─→ Organization (Group)
    │   ├─→ Inventory (Group)
    │   ├─→ Vendor (Group)
    │   ├─→ Tax (Group)
    │   ├─→ BOM (Group)
    │   └─→ Other (Group)
    └─→ Content Area
```

---

## 💾 LocalStorage Data

### After Login:
```javascript
localStorage.setItem('user', JSON.stringify({
    id: 1,
    first_name: "John",
    last_name: "Doe",
    email: "john@example.com",
    // ... other user fields
}));

localStorage.setItem('access_token', 'eyJ0eXAiOiJKV1QiLCJhbGc...');
localStorage.setItem('refresh_token', 'eyJ0eXAiOiJKV1QiLCJhbGc...');
localStorage.setItem('org_slug', 'acme-corp');
localStorage.setItem('org_data', JSON.stringify({
    id: 1,
    org_name: "Acme Corporation",
    org_slug: "acme-corp",
    // ... other org fields
}));
```

### Usage in Navigation:
```javascript
// Get org_slug for routing
const orgSlug = localStorage.getItem('org_slug');
window.location.href = `/org/${orgSlug}/dashboard`;

// Get user data for display
const user = JSON.parse(localStorage.getItem('user'));
console.log(`Welcome ${user.first_name}!`);

// Get org data for display
const orgData = JSON.parse(localStorage.getItem('org_data'));
console.log(`Organization: ${orgData.org_name}`);
```

---

## 🎨 Visual Indicators

### Main Dashboard:
- **Featured Card** (Go to Dashboard): Blue gradient, prominent
- **Progress Cards**: Show completion percentage
- **Action Cards**: Standard white with hover effects

### Tenant Dashboard:
- **Sidebar**: Always visible, grouped navigation
- **Cards**: Module access with icons
- **Progress Banner**: Shows overall completion

### Master Pages:
- **Sidebar**: Active state highlighting
- **Breadcrumbs**: Show current location (coming soon)
- **Action Buttons**: Add, Edit, Delete

---

## 🔐 Route Protection

### All Protected Routes:
```php
Route::middleware(['web.jwt'])->group(function () {
    // Main dashboard
    Route::get('/dashboard', ...);
    
    // Control panel (super admin)
    Route::prefix('control')->group(...);
    
    // Tenant routes
    Route::prefix('org/{org_slug}')->group(...);
});
```

### Authentication Check:
```javascript
// Check if user is authenticated
const accessToken = localStorage.getItem('access_token');
if (!accessToken) {
    window.location.href = '/login';
}
```

---

## 📱 Responsive Behavior

### Desktop:
- Main dashboard: 3-column grid
- All cards visible
- Full navigation

### Tablet:
- Main dashboard: 2-column grid
- Cards stack appropriately
- Touch-friendly

### Mobile:
- Main dashboard: 1-column stack
- Cards full width
- Optimized for touch

---

## 🚀 Quick Actions

### From Main Dashboard:
```javascript
// Navigate to tenant dashboard
navigateTo('dashboard');

// Navigate to profile setup
navigateTo('profile');

// Navigate to master dashboard
navigateTo('masters');

// Navigate to specific module
navigateTo('departments');
navigateTo('users');
```

### From Tenant Dashboard:
- Use sidebar for navigation
- Click cards for quick access
- Use breadcrumbs (coming soon)

---

## 🔄 Alternative Flows

### For Super Admin:
```
Login
  ↓
Main Dashboard
  ↓ (Detect admin role)
Show "Control Panel" option
  ↓
Control Dashboard (/control/dashboard)
```

### For New Users:
```
Login
  ↓
Main Dashboard
  ↓ (Check profile completion)
Show completion banner
  ↓
Guide to profile setup
```

### For Returning Users:
```
Login
  ↓
Main Dashboard
  ↓ (Profile complete)
Direct access to all modules
```

---

## 📝 Developer Notes

### Adding New Navigation:
1. Add route in `routes/web.php`
2. Add card in main dashboard
3. Add to `navigateTo()` function
4. Add sidebar link (if applicable)
5. Update documentation

### Testing Navigation:
1. Test login flow
2. Test main dashboard loads
3. Test all cards navigate correctly
4. Test sidebar navigation
5. Test back button behavior

---

## ✅ Current Implementation

### Login Redirect:
- ✅ Redirects to `/dashboard` (main dashboard)
- ✅ Stores all required data in localStorage (user, tokens, org_slug, org_data)
- ✅ Sets authentication cookie via API

### Main Dashboard (`/dashboard`):
- ✅ Shows navigation cards for all modules
- ✅ Tracks profile and master data progress
- ✅ Featured "Go to Dashboard" card (blue gradient)
- ✅ navigateTo() function properly routes to tenant pages
- ✅ Displays organization name and user info
- ✅ Quick stats cards (materials, production, vendors, team)
- ✅ User dropdown menu with profile and logout

### Tenant Dashboard (`/org/{org_slug}/dashboard`):
- ✅ Sidebar navigation with grouped modules
- ✅ Module cards for quick access
- ✅ Progress tracking banner
- ✅ Organization name display in header
- ✅ All routes use path-based tenant routing

### Route Structure:
- ✅ Public routes: `/`, `/pricing`, `/login`, `/register`
- ✅ Main dashboard: `/dashboard` (navigation hub)
- ✅ Control panel: `/control/*` (super admin)
- ✅ Tenant routes: `/org/{org_slug}/*` (organization-specific)

### Navigation Flow:
```
Login → Main Dashboard → Choose Module
                ↓
        Tenant Dashboard (with sidebar)
                ↓
        Navigate to any module
```

---

**Last Updated:** March 5, 2026  
**Version:** 1.1  
**Status:** Fully Implemented ✅
