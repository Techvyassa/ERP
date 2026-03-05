# ERP Application - Complete Folder Structure

## 📋 Overview
The application follows a **Multi-Tenant SaaS Architecture** with two distinct user interfaces:

1. **Control Panel (Super Admin)** - System-wide management for SaaS administrators
2. **Tenant Portal (Organizations)** - Individual organization management for end users

---

## 📁 Complete Folder Structure

```
resources/views/
│
├── 🌐 PUBLIC PAGES
│   ├── landing.blade.php              # Marketing landing page
│   ├── auth/
│   │   ├── login.blade.php           # User login
│   │   └── register.blade.php        # User registration
│   ├── subscription/
│   │   ├── select.blade.php          # Subscription selection
│   │   └── plans.blade.php           # Pricing plans display
│   └── emails/
│       └── welcome.blade.php         # Welcome email template
│
├── 🎯 MAIN DASHBOARD (Entry Point)
│   └── dashboard/
│       └── main.blade.php            # Main entry dashboard with navigation cards
│
├── 👑 CONTROL PANEL (Super Admin / SaaS Management)
│   └── control/
│       ├── layouts/
│       │   └── app.blade.php         # Purple-themed admin layout
│       ├── dashboard.blade.php       # Super admin dashboard
│       │
│       ├── organizations/            # Tenant Organizations Management
│       │   └── index.blade.php
│       │
│       ├── subscriptions/            # Subscriptions Management
│       │   └── index.blade.php
│       │
│       ├── plans/                    # Subscription Plans Management
│       │   └── index.blade.php
│       │
│       ├── payments/                 # Payments & Billing
│       │   └── index.blade.php
│       │
│       ├── features/                 # Feature Control & Toggles
│       │   └── index.blade.php
│       │
│       ├── settings.blade.php        # System-wide settings
│       └── profile.blade.php         # Admin profile
│
└── 🏢 TENANT PORTAL (Organization Management)
    └── tenant/
        ├── layouts/
        │   └── app.blade.php         # Navy-themed tenant layout with sidebar
        │
        ├── 📊 DASHBOARD & PROFILE
        ├── dashboard.blade.php       # Tenant main dashboard
        ├── profile-completion.blade.php  # Organization profile setup
        ├── profile.blade.php         # User profile
        ├── settings.blade.php        # Organization settings
        │
        ├── 📦 MASTERS (All Master Data)
        └── masters/
            ├── dashboard.blade.php   # Master data setup dashboard
            │
            ├── organization/         # Organization Masters
            │   ├── departments/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── roles/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── users/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   └── approval-matrix/
            │       ├── index.blade.php
            │       └── create.blade.php
            │
            ├── inventory/            # Inventory Masters
            │   ├── materials/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── products/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── warehouses/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── bin-locations/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   └── uom/
            │       ├── index.blade.php
            │       └── create.blade.php
            │
            ├── vendor/               # Vendor Masters
            │   ├── vendors/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── vendor-contacts/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   └── vendor-material-map/
            │       ├── index.blade.php
            │       └── create.blade.php
            │
            ├── tax/                  # Tax & Finance Masters
            │   ├── hsn-codes/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   ├── gst-taxes/
            │   │   ├── index.blade.php
            │   │   └── create.blade.php
            │   └── currency/
            │       ├── index.blade.php
            │       └── create.blade.php
            │
            └── bom/                  # BOM Masters
                ├── bom-header/
                │   ├── index.blade.php
                │   └── create.blade.php
                └── bom-detail/
                    ├── index.blade.php
                    └── create.blade.php
        │
        └── 📋 REPORTS
            └── reports/
                └── index.blade.php
```

---

## 🛣️ Route Structure

### 🌐 Public Routes (No Authentication)
```
/                           → Landing page
/pricing                    → Pricing page
/login                      → Login page
/register                   → Registration page
/auth/google                → Google OAuth
/auth/google/callback       → OAuth callback
```

### 🔐 Protected Routes (Require Authentication)

#### Main Entry Point
```
/dashboard                  → Main dashboard (navigation hub)
```

#### 👑 Super Admin Routes (Control Panel)
```
/control/dashboard          → Super admin dashboard
/control/organizations      → Manage all organizations
/control/subscriptions      → Manage subscriptions
/control/plans              → Manage subscription plans
/control/payments           → View payments & transactions
/control/features           → Feature control toggles
/control/settings           → System-wide settings
/control/profile            → Admin profile
```

#### 🏢 Tenant Routes (Organization-specific)
**Pattern:** `/org/{org_slug}/...`

**Dashboard & Setup:**
```
/org/{org_slug}/dashboard              → Tenant dashboard
/org/{org_slug}/profile-completion     → Profile setup wizard
/org/{org_slug}/master-setup           → Master data dashboard
```

**Organization Masters:**
```
/org/{org_slug}/departments            → Department management
/org/{org_slug}/roles                  → Role management
/org/{org_slug}/users                  → User management
/org/{org_slug}/approval-matrix        → Approval workflows
```

**Inventory Masters:**
```
/org/{org_slug}/materials              → Material master
/org/{org_slug}/products               → Product master
/org/{org_slug}/warehouses             → Warehouse master
/org/{org_slug}/bin-locations          → Bin location master
/org/{org_slug}/uom                    → UOM master
```

**Vendor Masters:**
```
/org/{org_slug}/vendors                → Vendor master
/org/{org_slug}/vendor-contacts        → Vendor contacts
/org/{org_slug}/vendor-material-map    → Vendor-Material mapping (AVL)
```

**Tax Masters:**
```
/org/{org_slug}/hsn-codes              → HSN codes
/org/{org_slug}/gst-taxes              → GST tax rates
/org/{org_slug}/currency               → Currency master
```

**BOM Masters:**
```
/org/{org_slug}/bom-header             → BOM header
/org/{org_slug}/bom-detail             → BOM details
```

**Other:**
```
/org/{org_slug}/reports                → Reports & analytics
/org/{org_slug}/settings               → Organization settings
/org/{org_slug}/profile                → User profile
```

---

## 🎨 Design System

### Color Palette
| Context | Color | Hex Code | Usage |
|---------|-------|----------|-------|
| **Tenant Primary** | Navy Blue | `#193261` | Buttons, links, active states |
| **Admin Primary** | Purple | `#7c3aed` | Admin interface theme |
| **Success** | Green | Various | Success messages, completed states |
| **Warning** | Amber | Various | Warnings, pending states |
| **Error** | Red | Various | Errors, destructive actions |
| **Info** | Blue | Various | Information, neutral states |

### Typography
- **Font Family:** Inter (Google Fonts)
- **Headings:** Bold weight (600-800)
- **Body Text:** Regular weight (400)
- **Small Text:** Regular weight (400), reduced size

### Icons
- **Icon Library:** Material Symbols Outlined
- **Style:** Consistent outlined style across all pages
- **Size:** Responsive based on context (text-sm to text-2xl)

### Components
- **Borders:** Rounded corners (rounded-lg, rounded-xl)
- **Shadows:** Subtle shadows on cards (shadow, shadow-lg)
- **Transitions:** Smooth transitions on hover (transition-all, transition-colors)
- **Gradients:** Used for admin sidebar and card backgrounds

---

## 🔑 Key Features

### Tenant Layout (`tenant/layouts/app.blade.php`)
✅ Collapsible sidebar navigation  
✅ Material Symbols icons throughout  
✅ Organization name display in header  
✅ User dropdown with profile & logout  
✅ Responsive design for mobile/tablet  
✅ Active route highlighting  
✅ Grouped navigation by module type  

### Control Layout (`control/layouts/app.blade.php`)
✅ Purple gradient sidebar (distinct from tenant)  
✅ Super admin branding  
✅ System-wide navigation  
✅ Admin-specific color scheme  
✅ Separate from tenant interface  
✅ Quick access to all organizations  

### Dashboard Features
✅ Card-based navigation  
✅ Progress tracking (profile & master data)  
✅ Quick stats display  
✅ Visual progress bars  
✅ Dismissible completion banner  
✅ Responsive grid layout  

---

## 📊 Master Data Categories

### Organization Masters (4 modules)
1. **Departments** - Business units with cost centers
2. **Roles** - System roles and permissions
3. **Users** - Team members with role assignments
4. **Approval Matrix** - Configurable approval workflows

### Inventory Masters (5 modules)
1. **Materials** - Raw materials, packaging, consumables
2. **Products** - Finished goods
3. **Warehouses** - Physical storage locations
4. **Bin Locations** - Rack/Bin structural master
5. **UOM** - Units of measurement with conversions

### Vendor Masters (3 modules)
1. **Vendors** - Approved supplier registry
2. **Vendor Contacts** - Multiple contacts per vendor
3. **Vendor Material Map** - Approved Vendor List (AVL)

### Tax Masters (3 modules)
1. **HSN Codes** - Harmonized System of Nomenclature
2. **GST Taxes** - GST rate slab master
3. **Currency** - Multi-currency support

### BOM Masters (2 modules)
1. **BOM Header** - Bill of Materials with version management
2. **BOM Detail** - Component lines per BOM

**Total: 17 Master Data Modules**

---

## 🚀 Implementation Status

### ✅ Completed
- [x] Folder structure organization
- [x] Route structure (public, control, tenant)
- [x] Tenant layout with sidebar
- [x] Control panel layout
- [x] Main dashboard with cards
- [x] Tenant dashboard
- [x] Control dashboard
- [x] Profile completion page
- [x] Master setup dashboard
- [x] All 17 master data index pages
- [x] Material Symbols icons integration
- [x] Consistent color scheme
- [x] Responsive design

### 🔄 In Progress / Next Steps
1. **API Integration** - Connect all pages to backend APIs
2. **CRUD Operations** - Implement create, edit, delete for all masters
3. **Form Modals** - Add modal forms for data entry
4. **Validation** - Client-side and server-side validation
5. **Permissions** - Role-based access control (RBAC)
6. **Search & Filters** - Enhanced filtering capabilities
7. **Pagination** - Proper pagination for large datasets
8. **Export** - Export functionality (Excel, PDF)
9. **Audit Logs** - Track all changes
10. **Notifications** - Real-time notification system
11. **Production Module** - Work orders, shop floor tracking
12. **Inventory Module** - Stock management, transfers
13. **Reporting** - Advanced analytics and reports

---

## 📝 Development Guidelines

### File Naming Conventions
- **Layouts:** `app.blade.php`
- **Index Pages:** `index.blade.php`
- **Create Forms:** `create.blade.php`
- **Edit Forms:** `edit.blade.php`
- **Show Pages:** `show.blade.php`

### Route Naming Conventions
- **Control Routes:** `control.{module}.{action}`
- **Tenant Routes:** `tenant.{module}.{action}`
- **Example:** `tenant.materials.index`, `control.organizations.index`

### View Data Requirements
All tenant views require:
```php
[
    'organization' => $org,      // Organization model
    'tenantType' => $tenantType  // 'path' or 'subdomain'
]
```

### Sidebar Active State
Use route name matching:
```php
{{ request()->routeIs('tenant.materials.*') ? 'active-class' : 'inactive-class' }}
```

---

## 🔒 Security Considerations

1. **Authentication:** All protected routes use `web.jwt` middleware
2. **Authorization:** Implement role-based permissions per organization
3. **Tenant Isolation:** Ensure data isolation between organizations
4. **Input Validation:** Validate all user inputs
5. **CSRF Protection:** Laravel CSRF tokens on all forms
6. **SQL Injection:** Use Eloquent ORM and parameterized queries
7. **XSS Protection:** Blade template escaping by default

---

## 📚 Additional Resources

- **Laravel Documentation:** https://laravel.com/docs
- **Tailwind CSS:** https://tailwindcss.com/docs
- **Alpine.js:** https://alpinejs.dev/
- **Material Symbols:** https://fonts.google.com/icons

---

**Last Updated:** 2024  
**Version:** 1.0  
**Status:** Development
