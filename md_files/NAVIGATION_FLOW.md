# ERP Application - Navigation Flow

## 📍 Complete User Journey

### 🔐 Authentication Flow
```
1. Landing Page (/)
   ↓
2. Pricing (/pricing) - Select Plan
   ↓
3. Register (/register) - Create Account
   ↓
4. Login (/login) - Authenticate
   ↓
5. Main Dashboard (/dashboard)
```

---

## 🎯 Main Dashboard Flow

### After Login → Main Dashboard (`/dashboard`)

The main dashboard serves as the **navigation hub** with cards for:

1. **Organization Profile** → Profile Completion
2. **Master Data Setup** → Masters Dashboard
3. **Departments** → Department Management
4. **Users & Roles** → User Management
5. **Production** → Production Module (Coming Soon)
6. **Inventory** → Inventory Module (Coming Soon)

---

## 🏢 Tenant Organization Flow

### Flow Structure:
```
Main Dashboard (/dashboard)
    ↓
Tenant Dashboard (/org/{org_slug}/dashboard)
    ↓
    ├─→ Profile Completion (/org/{org_slug}/profile-completion)
    │
    ├─→ Master Data Dashboard (/org/{org_slug}/master-setup)
    │   ↓
    │   ├─→ Organization Masters
    │   │   ├─→ Departments (/org/{org_slug}/departments)
    │   │   ├─→ Roles (/org/{org_slug}/roles)
    │   │   ├─→ Users (/org/{org_slug}/users)
    │   │   └─→ Approval Matrix (/org/{org_slug}/approval-matrix)
    │   │
    │   ├─→ Inventory Masters
    │   │   ├─→ Materials (/org/{org_slug}/materials)
    │   │   ├─→ Products (/org/{org_slug}/products)
    │   │   ├─→ Warehouses (/org/{org_slug}/warehouses)
    │   │   ├─→ Bin Locations (/org/{org_slug}/bin-locations)
    │   │   └─→ UOM (/org/{org_slug}/uom)
    │   │
    │   ├─→ Vendor Masters
    │   │   ├─→ Vendors (/org/{org_slug}/vendors)
    │   │   ├─→ Vendor Contacts (/org/{org_slug}/vendor-contacts)
    │   │   └─→ Vendor Material Map (/org/{org_slug}/vendor-material-map)
    │   │
    │   ├─→ Tax Masters
    │   │   ├─→ HSN Codes (/org/{org_slug}/hsn-codes)
    │   │   ├─→ GST Taxes (/org/{org_slug}/gst-taxes)
    │   │   └─→ Currency (/org/{org_slug}/currency)
    │   │
    │   └─→ BOM Masters
    │       ├─→ BOM Header (/org/{org_slug}/bom-header)
    │       └─→ BOM Detail (/org/{org_slug}/bom-detail)
    │
    ├─→ Reports (/org/{org_slug}/reports)
    ├─→ Settings (/org/{org_slug}/settings)
    └─→ Profile (/org/{org_slug}/profile)
```

---

## 👑 Super Admin Flow

### Control Panel Structure:
```
Main Dashboard (/dashboard)
    ↓
Control Dashboard (/control/dashboard)
    ↓
    ├─→ Organizations (/control/organizations)
    ├─→ Subscriptions (/control/subscriptions)
    ├─→ Plans (/control/plans)
    ├─→ Payments (/control/payments)
    ├─→ Features (/control/features)
    ├─→ Settings (/control/settings)
    └─→ Profile (/control/profile)
```

---

## 📂 Folder Organization

### New Organized Structure:

```
tenant/
├── layouts/
│   └── app.blade.php                    # Main layout with sidebar
│
├── dashboard.blade.php                  # Tenant main dashboard
├── profile-completion.blade.php         # Profile setup
├── profile.blade.php                    # User profile
├── settings.blade.php                   # Organization settings
│
├── masters/                             # All Master Data
│   ├── dashboard.blade.php              # Master setup dashboard
│   │
│   ├── organization/                    # Organization Masters
│   │   ├── departments/
│   │   ├── roles/
│   │   ├── users/
│   │   └── approval-matrix/
│   │
│   ├── inventory/                       # Inventory Masters
│   │   ├── materials/
│   │   ├── products/
│   │   ├── warehouses/
│   │   ├── bin-locations/
│   │   └── uom/
│   │
│   ├── vendor/                          # Vendor Masters
│   │   ├── vendors/
│   │   ├── vendor-contacts/
│   │   └── vendor-material-map/
│   │
│   ├── tax/                             # Tax Masters
│   │   ├── hsn-codes/
│   │   ├── gst-taxes/
│   │   └── currency/
│   │
│   └── bom/                             # BOM Masters
│       ├── bom-header/
│       └── bom-detail/
│
└── reports/                             # Reports Module
    └── index.blade.php
```

---

## 🎨 Layout Hierarchy

### Tenant Layout (`tenant/layouts/app.blade.php`)

**Features:**
- Collapsible sidebar
- Organization name in header
- User dropdown menu
- Grouped navigation by module
- Active route highlighting
- Material Symbols icons
- Primary color: #193261

**Sidebar Navigation Groups:**
1. **Dashboard & Setup**
   - Dashboard
   - Profile Setup
   - Master Setup

2. **Organization**
   - Users
   - Departments
   - Roles
   - Approval Matrix

3. **Inventory**
   - Materials
   - Products
   - Warehouses
   - Bin Locations
   - UOM

4. **Vendor**
   - Vendors
   - Vendor Contacts
   - Vendor Material Map

5. **Tax & Finance**
   - HSN Codes
   - GST Taxes
   - Currency

6. **BOM**
   - BOM Header
   - BOM Detail

7. **Other**
   - Reports
   - Settings

---

## 🔄 Navigation Patterns

### Card-Based Navigation (Dashboards)
- Main Dashboard → Cards for major modules
- Tenant Dashboard → Cards for setup & modules
- Master Dashboard → Cards for all master categories

### Sidebar Navigation (Master Pages)
- Always visible sidebar
- Grouped by module type
- Active state highlighting
- Quick access to all masters

### Breadcrumb Navigation (Coming Soon)
```
Dashboard > Masters > Inventory > Materials
```

---

## 📊 Progress Tracking

### Profile Completion
- Tracked on tenant dashboard
- Shows percentage complete
- Banner notification if incomplete
- Progress bars on cards

### Master Data Setup
- Tracked on master dashboard
- Shows setup status per master
- Color-coded indicators
- Count of records per master

---

## 🎯 User Actions

### From Main Dashboard:
1. Click "Organization Profile" → Go to Profile Completion
2. Click "Master Data Setup" → Go to Master Dashboard
3. Click "Departments" → Go to Department Management
4. Click "Users & Roles" → Go to User Management

### From Tenant Dashboard:
1. Click profile card → Go to Profile Completion
2. Click master card → Go to Master Dashboard
3. Use sidebar → Navigate to any module

### From Master Dashboard:
1. Click any master card → Go to that master's management page
2. Use sidebar → Navigate to other masters
3. Click "Back to Dashboard" → Return to tenant dashboard

### From Master Pages:
1. Use sidebar → Navigate to other masters
2. Click "Add" button → Create new record
3. Click "Edit" icon → Edit existing record
4. Click "Delete" icon → Delete record
5. Use filters → Search and filter records

---

## 🔐 Access Control

### Route Protection:
- All tenant routes require authentication (`web.jwt` middleware)
- Organization slug validation
- Tenant database connection
- Role-based permissions (to be implemented)

### Data Isolation:
- Each organization has separate database
- Tenant context required for all operations
- No cross-tenant data access

---

## 📱 Responsive Design

### Mobile Navigation:
- Collapsible sidebar
- Hamburger menu
- Touch-friendly buttons
- Responsive grid layouts

### Tablet Navigation:
- Sidebar visible by default
- Optimized card layouts
- Touch and mouse support

### Desktop Navigation:
- Full sidebar always visible
- Multi-column layouts
- Hover effects
- Keyboard shortcuts (coming soon)

---

## 🚀 Future Enhancements

1. **Breadcrumb Navigation** - Show current location
2. **Quick Search** - Global search across all modules
3. **Recent Items** - Quick access to recently viewed items
4. **Favorites** - Pin frequently used pages
5. **Keyboard Shortcuts** - Quick navigation
6. **Mobile App** - Native mobile experience
7. **Offline Mode** - Work without internet
8. **Multi-language** - Support multiple languages

---

## 📝 Development Notes

### Adding New Master:
1. Create folder in appropriate category (organization/inventory/vendor/tax/bom)
2. Add index.blade.php and create.blade.php
3. Add route in web.php under appropriate section
4. Add navigation link in tenant layout sidebar
5. Add card in master dashboard
6. Update FOLDER_STRUCTURE.md

### Route Naming Convention:
- Tenant routes: `tenant.{module}.{action}`
- Control routes: `control.{module}.{action}`
- Example: `tenant.materials.index`, `tenant.materials.create`

### View Data Requirements:
All tenant views must receive:
```php
[
    'organization' => $org,      // Organization model
    'tenantType' => $tenantType  // 'path' or 'subdomain'
]
```

---

**Last Updated:** 2026,05 March  
**Version:** 1.0  
**Status:** Development
