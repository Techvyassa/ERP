# ERP Application Folder Structure

## Overview
The application is now organized into two main sections:
1. **Tenant (Organization)** - For regular users managing their organization
2. **Control (Super Admin)** - For system administrators managing all organizations

---

## Folder Structure

```
resources/views/
├── auth/                           # Authentication pages
│   ├── login.blade.php
│   └── register.blade.php
│
├── control/                        # Super Admin / SaaS Control Panel
│   ├── layouts/
│   │   └── app.blade.php          # Super admin layout (purple theme)
│   ├── dashboard.blade.php        # Super admin dashboard
│   ├── organizations/
│   │   └── index.blade.php        # Manage all organizations
│   ├── subscriptions/
│   │   └── index.blade.php        # Manage subscriptions
│   ├── plans/
│   │   └── index.blade.php        # Manage subscription plans
│   ├── payments/
│   │   └── index.blade.php        # View payments
│   ├── features/
│   │   └── index.blade.php        # Feature control
│   ├── settings.blade.php         # System settings
│   └── profile.blade.php          # Admin profile
│
├── tenant/                         # Tenant Organization Views
│   ├── layouts/
│   │   └── app.blade.php          # Tenant layout with sidebar
│   ├── dashboard.blade.php        # Tenant dashboard
│   ├── profile-completion.blade.php
│   ├── master-setup.blade.php
│   │
│   ├── departments/               # Organization Masters
│   │   └── index.blade.php
│   ├── roles/
│   │   └── index.blade.php
│   ├── users/
│   │   └── index.blade.php
│   ├── approval-matrix/
│   │   └── index.blade.php
│   │
│   ├── materials/                 # Inventory Masters
│   │   └── index.blade.php
│   ├── products/
│   │   └── index.blade.php
│   ├── warehouses/
│   │   └── index.blade.php
│   ├── bin-locations/
│   │   └── index.blade.php
│   ├── uom/
│   │   └── index.blade.php
│   │
│   ├── vendors/                   # Vendor Masters
│   │   └── index.blade.php
│   ├── vendor-contacts/
│   │   └── index.blade.php
│   ├── vendor-material-map/
│   │   └── index.blade.php
│   │
│   ├── hsn-codes/                 # Tax Masters
│   │   └── index.blade.php
│   ├── gst-taxes/
│   │   └── index.blade.php
│   ├── currency/
│   │   └── index.blade.php
│   │
│   ├── bom-header/                # BOM Masters
│   │   └── index.blade.php
│   ├── bom-detail/
│   │   └── index.blade.php
│   │
│   ├── reports/                   # Other
│   │   └── index.blade.php
│   ├── settings.blade.php
│   └── profile.blade.php
│
├── dashboard/                      # Legacy/Redirect Dashboard
│   └── main.blade.php             # Main entry dashboard
│
├── subscription/
│   ├── select.blade.php
│   └── plans.blade.php
│
├── emails/
│   └── welcome.blade.php
│
└── landing.blade.php              # Public landing page
```

---

## Route Structure

### Public Routes
- `/` - Landing page
- `/pricing` - Pricing page
- `/login` - Login page
- `/register` - Registration page

### Authenticated Routes

#### Main Dashboard (Entry Point)
- `/dashboard` - Main dashboard (shows cards for navigation)

#### Super Admin Routes (Control Panel)
- `/control/dashboard` - Super admin dashboard
- `/control/organizations` - Manage organizations
- `/control/subscriptions` - Manage subscriptions
- `/control/plans` - Manage subscription plans
- `/control/payments` - View payments
- `/control/features` - Feature control
- `/control/settings` - System settings
- `/control/profile` - Admin profile

#### Tenant Routes (Organization-specific)
All tenant routes follow the pattern: `/org/{org_slug}/...`

**Dashboard & Setup:**
- `/org/{org_slug}/dashboard` - Tenant dashboard
- `/org/{org_slug}/profile-completion` - Profile setup
- `/org/{org_slug}/master-setup` - Master data dashboard

**Organization Masters:**
- `/org/{org_slug}/departments`
- `/org/{org_slug}/roles`
- `/org/{org_slug}/users`
- `/org/{org_slug}/approval-matrix`

**Inventory Masters:**
- `/org/{org_slug}/materials`
- `/org/{org_slug}/products`
- `/org/{org_slug}/warehouses`
- `/org/{org_slug}/bin-locations`
- `/org/{org_slug}/uom`

**Vendor Masters:**
- `/org/{org_slug}/vendors`
- `/org/{org_slug}/vendor-contacts`
- `/org/{org_slug}/vendor-material-map`

**Tax Masters:**
- `/org/{org_slug}/hsn-codes`
- `/org/{org_slug}/gst-taxes`
- `/org/{org_slug}/currency`

**BOM Masters:**
- `/org/{org_slug}/bom-header`
- `/org/{org_slug}/bom-detail`

**Other:**
- `/org/{org_slug}/reports`
- `/org/{org_slug}/settings`
- `/org/{org_slug}/profile`

---

## Key Features

### Tenant Layout (`tenant/layouts/app.blade.php`)
- Collapsible sidebar with all master data navigation
- Material Symbols icons
- Primary color: #193261
- Organization name display
- User dropdown with logout
- Responsive design

### Control Layout (`control/layouts/app.blade.php`)
- Purple gradient sidebar (admin theme)
- Super admin navigation
- Admin color: #7c3aed (purple)
- System-wide management tools
- Separate from tenant interface

### Dashboard Cards
Both dashboards use card-based navigation:
- Organization Profile
- Master Data Setup
- Departments
- Users & Roles
- Production
- Inventory

### Progress Tracking
- Profile completion percentage
- Master data setup percentage
- Overall progress banner
- Visual progress bars on cards

---

## Design System

### Colors
- **Primary (Tenant):** #193261 (Navy Blue)
- **Admin (Control):** #7c3aed (Purple)
- **Success:** Green shades
- **Warning:** Amber shades
- **Error:** Red shades

### Icons
- Material Symbols Outlined
- Consistent icon usage across all pages

### Typography
- Font Family: Inter
- Headings: Bold, various sizes
- Body: Regular weight

### Components
- Rounded corners (xl)
- Subtle shadows
- Hover effects
- Smooth transitions
- Gradient backgrounds for cards

---

## Next Steps

1. **API Integration:** Connect all pages to backend APIs
2. **CRUD Operations:** Implement create, edit, delete for all masters
3. **Form Modals:** Add modal forms for data entry
4. **Validation:** Add client-side and server-side validation
5. **Permissions:** Implement role-based access control
6. **Search & Filters:** Enhance filtering capabilities
7. **Pagination:** Add proper pagination for large datasets
8. **Export:** Add export functionality (Excel, PDF)
9. **Audit Logs:** Track all changes
10. **Notifications:** Real-time notifications system
