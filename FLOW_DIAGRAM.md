# ERP Application - Visual Flow Diagram

## 🎯 Complete Application Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        PUBLIC PAGES                              │
└─────────────────────────────────────────────────────────────────┘
                                │
                    ┌───────────┴───────────┐
                    │   Landing Page (/)    │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │   Pricing (/pricing)  │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │  Register (/register) │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │    Login (/login)     │
                    └───────────┬───────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATED AREA                            │
└─────────────────────────────────────────────────────────────────┘
                                │
                    ┌───────────┴───────────┐
                    │  Main Dashboard       │
                    │  (/dashboard)         │
                    │                       │
                    │  Navigation Hub with  │
                    │  Cards for:           │
                    │  • Profile            │
                    │  • Masters            │
                    │  • Departments        │
                    │  • Users & Roles      │
                    │  • Production         │
                    │  • Inventory          │
                    └───────────┬───────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌───────────┐   ┌───────────┐   ┌───────────┐
        │  Control  │   │  Tenant   │   │  Direct   │
        │  Panel    │   │  Portal   │   │  Access   │
        └───────────┘   └───────────┘   └───────────┘
                │               │               │
                │               │               │
┌───────────────┴───┐   ┌───────┴───────┐   ┌─┴──────────┐
│                   │   │               │   │            │
│  SUPER ADMIN      │   │  TENANT       │   │  QUICK     │
│  CONTROL PANEL    │   │  DASHBOARD    │   │  LINKS     │
│                   │   │               │   │            │
│  /control/        │   │  /org/{slug}/ │   │  Direct to │
│                   │   │               │   │  modules   │
└───────────────────┘   └───────────────┘   └────────────┘
        │                       │
        │                       │
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│ Organizations │       │ Profile Setup │
│ Subscriptions │       │ Master Setup  │
│ Plans         │       │ Settings      │
│ Payments      │       │ Profile       │
│ Features      │       └───────┬───────┘
│ Settings      │               │
└───────────────┘               │
                                ▼
                        ┌───────────────┐
                        │ MASTER DATA   │
                        │ DASHBOARD     │
                        │               │
                        │ /master-setup │
                        └───────┬───────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────┐       ┌───────────────┐     ┌───────────────┐
│ ORGANIZATION  │       │  INVENTORY    │     │   VENDOR      │
│               │       │               │     │               │
│ • Departments │       │ • Materials   │     │ • Vendors     │
│ • Roles       │       │ • Products    │     │ • Contacts    │
│ • Users       │       │ • Warehouses  │     │ • Material    │
│ • Approval    │       │ • Bin Loc.    │     │   Map         │
│   Matrix      │       │ • UOM         │     │               │
└───────────────┘       └───────────────┘     └───────────────┘
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────┐       ┌───────────────┐     ┌───────────────┐
│   TAX         │       │     BOM       │     │   REPORTS     │
│               │       │               │     │               │
│ • HSN Codes   │       │ • BOM Header  │     │ • Analytics   │
│ • GST Taxes   │       │ • BOM Detail  │     │ • Exports     │
│ • Currency    │       │               │     │ • Dashboards  │
└───────────────┘       └───────────────┘     └───────────────┘
```

---

## 📊 Folder Structure Visualization

```
resources/views/
│
├── 🌐 PUBLIC
│   ├── landing.blade.php
│   ├── auth/
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   ├── subscription/
│   │   ├── select.blade.php
│   │   └── plans.blade.php
│   └── emails/
│       └── welcome.blade.php
│
├── 🎯 DASHBOARD
│   └── dashboard/
│       └── main.blade.php ◄── ENTRY POINT
│
├── 👑 CONTROL (Super Admin)
│   └── control/
│       ├── layouts/app.blade.php
│       ├── dashboard.blade.php
│       ├── organizations/
│       ├── subscriptions/
│       ├── plans/
│       ├── payments/
│       ├── features/
│       ├── settings.blade.php
│       └── profile.blade.php
│
└── 🏢 TENANT (Organizations)
    └── tenant/
        ├── layouts/
        │   └── app.blade.php ◄── TENANT LAYOUT
        │
        ├── dashboard.blade.php ◄── TENANT HUB
        ├── profile-completion.blade.php
        ├── profile.blade.php
        ├── settings.blade.php
        │
        ├── masters/ ◄── ALL MASTER DATA
        │   ├── dashboard.blade.php ◄── MASTER HUB
        │   │
        │   ├── organization/
        │   │   ├── departments/
        │   │   ├── roles/
        │   │   ├── users/
        │   │   └── approval-matrix/
        │   │
        │   ├── inventory/
        │   │   ├── materials/
        │   │   ├── products/
        │   │   ├── warehouses/
        │   │   ├── bin-locations/
        │   │   └── uom/
        │   │
        │   ├── vendor/
        │   │   ├── vendors/
        │   │   ├── vendor-contacts/
        │   │   └── vendor-material-map/
        │   │
        │   ├── tax/
        │   │   ├── hsn-codes/
        │   │   ├── gst-taxes/
        │   │   └── currency/
        │   │
        │   └── bom/
        │       ├── bom-header/
        │       └── bom-detail/
        │
        └── reports/
            └── index.blade.php
```

---

## 🔄 Navigation Patterns

### Pattern 1: Dashboard → Master → CRUD
```
Main Dashboard
    ↓ (Click Card)
Tenant Dashboard
    ↓ (Click "Master Data Setup")
Master Dashboard
    ↓ (Click Master Card)
Master List Page
    ↓ (Click "Add" or "Edit")
Master Form
    ↓ (Save)
Back to Master List
```

### Pattern 2: Sidebar Navigation
```
Any Master Page
    ↓ (Click Sidebar Link)
Another Master Page
    ↓ (Always visible sidebar)
Quick Navigation
```

### Pattern 3: Breadcrumb (Coming Soon)
```
Dashboard > Masters > Inventory > Materials
    ↑           ↑          ↑           ↑
  Click      Click      Click      Current
```

---

## 🎨 Layout Inheritance

```
┌─────────────────────────────────────┐
│  tenant/layouts/app.blade.php       │
│  ┌───────────────────────────────┐  │
│  │  Sidebar + Header + Content   │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │  @yield('content')      │  │  │
│  │  │  ┌───────────────────┐  │  │  │
│  │  │  │  Page Content     │  │  │  │
│  │  │  │  • Dashboard      │  │  │  │
│  │  │  │  • Master Pages   │  │  │  │
│  │  │  │  • Forms          │  │  │  │
│  │  │  └───────────────────┘  │  │  │
│  │  └─────────────────────────┘  │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

---

## 📱 Responsive Behavior

### Desktop (> 1024px)
```
┌────────────────────────────────────────┐
│  Header                                │
├──────────┬─────────────────────────────┤
│          │                             │
│ Sidebar  │  Content Area               │
│ (Fixed)  │  (Scrollable)               │
│          │                             │
│ • Nav    │  • Cards                    │
│ • Links  │  • Tables                   │
│ • User   │  • Forms                    │
│          │                             │
└──────────┴─────────────────────────────┘
```

### Tablet (768px - 1024px)
```
┌────────────────────────────────────────┐
│  Header                    [☰]         │
├────────────────────────────────────────┤
│                                        │
│  Content Area (Full Width)             │
│  (Sidebar toggles on hamburger)        │
│                                        │
│  • Cards (2 columns)                   │
│  • Tables (Horizontal scroll)          │
│  • Forms (Stacked)                     │
│                                        │
└────────────────────────────────────────┘
```

### Mobile (< 768px)
```
┌──────────────────────┐
│  Header      [☰]     │
├──────────────────────┤
│                      │
│  Content             │
│  (Full Width)        │
│                      │
│  • Cards (1 column)  │
│  • Tables (Cards)    │
│  • Forms (Stacked)   │
│                      │
└──────────────────────┘
```

---

## 🎯 User Journey Examples

### Example 1: New User Setup
```
1. Register → Create Account
2. Login → Authenticate
3. Main Dashboard → See welcome
4. Click "Organization Profile" → Complete profile
5. Click "Master Data Setup" → See master dashboard
6. Click "Materials" → Add first material
7. Use sidebar → Navigate to other masters
```

### Example 2: Daily Operations
```
1. Login → Authenticate
2. Main Dashboard → Quick overview
3. Click "Inventory" → Go to inventory module
4. Use sidebar → Navigate between masters
5. Add/Edit records → CRUD operations
6. View reports → Analytics
```

### Example 3: Super Admin
```
1. Login → Authenticate
2. Main Dashboard → See admin option
3. Click "Control Panel" → Admin dashboard
4. Manage organizations → View all tenants
5. Configure plans → Update pricing
6. View payments → Monitor revenue
```

---

## 🔐 Access Control Flow

```
User Login
    ↓
Check Authentication
    ↓
    ├─→ Not Authenticated → Redirect to Login
    │
    └─→ Authenticated
            ↓
        Check Role
            ↓
            ├─→ Super Admin → Control Panel Access
            │
            └─→ Tenant User
                    ↓
                Get Organization
                    ↓
                Check Permissions
                    ↓
                    ├─→ Has Access → Show Content
                    │
                    └─→ No Access → 403 Error
```

---

## 📊 Data Flow

```
User Action (Click/Submit)
    ↓
Frontend (Blade + Alpine.js)
    ↓
API Call (Fetch/Axios)
    ↓
Laravel Route
    ↓
Middleware (Auth, Tenant)
    ↓
Controller
    ↓
Service Layer
    ↓
Model/Database
    ↓
Response (JSON)
    ↓
Frontend Update
    ↓
UI Refresh
```

---

**Created:** 2024  
**Version:** 1.0  
**Purpose:** Visual reference for application flow
