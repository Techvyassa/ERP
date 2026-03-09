# Quick Reference: Profile Completion & Master Setup

## 🚀 Quick Start (3 Steps)

```bash
# 1. Run migration
php artisan migrate --database=control --path=database/migrations/control

# 2. Clear caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# 3. Test - Login and check dashboard
```

## 📍 URLs

| Page | Subdomain | Path-Based |
|------|-----------|------------|
| Dashboard | `https://org.domain.com/dashboard` | `https://domain.com/org/org/dashboard` |
| Profile | `https://org.domain.com/profile-completion` | `https://domain.com/org/org/profile-completion` |
| Masters | `https://org.domain.com/master-setup` | `https://domain.com/org/org/master-setup` |

## 🔌 API Endpoints

```http
GET  /api/v1/profile-completion/status
PUT  /api/v1/profile-completion/organization
GET  /api/v1/profile-completion/master-data-status
```

All require: `Authorization: Bearer {token}`

## 📊 Profile Fields (10 total)

### Basic Info (3)
- org_name ✅
- primary_email ✅
- primary_phone

### Address (5)
- address_line1
- city
- state
- postal_code
- country_code ✅

### Regional (2)
- timezone ✅
- currency_code ✅

✅ = Required field

## 🗄️ Master Tables (11 total)

### Organization (5)
- ✅ Departments
- ✅ Roles
- 🔴 Users (Critical)
- ✅ Zones
- ✅ Approval Matrix

### Inventory (4)
- ✅ UOM
- 🔴 Materials (Critical)
- 🔴 Products (Critical)
- ✅ Warehouses

### Vendor (1)
- 🔴 Vendors (Critical)

### BOM (1)
- 🔴 BOM (Critical)

🔴 = Critical master

## 📁 Files Created

```
database/migrations/control/
  └── 2024_03_04_000001_add_profile_completion_to_organizations.php

app/Http/Controllers/
  └── ProfileCompletionController.php

resources/views/tenant/
  ├── profile-completion.blade.php
  └── master-setup.blade.php

Documentation/
  ├── PROFILE_COMPLETION_AND_MASTER_SETUP.md
  ├── SETUP_INSTRUCTIONS.md
  ├── IMPLEMENTATION_SUMMARY.md
  └── QUICK_REFERENCE.md (this file)
```

## 📝 Files Modified

```
routes/
  ├── api.php (added 3 endpoints)
  └── tenant.php (added 2 routes)

resources/views/tenant/
  ├── dashboard.blade.php (added alerts)
  └── layouts/app.blade.php (updated sidebar)

app/Models/Control/
  └── Organization.php (added fields)
```

## 🎨 UI Components

### Dashboard Alerts
```html
<!-- Profile Incomplete -->
<div class="bg-yellow-50 border-l-4 border-yellow-400">
  Profile is X% complete. Complete it to unlock all features.
  [Complete Now Button]
</div>

<!-- Masters Incomplete -->
<div class="bg-blue-50 border-l-4 border-blue-400">
  X of Y masters configured. Setup essential data.
  [Setup Masters Button]
</div>
```

### Progress Bar
```html
<div class="w-full bg-gray-200 rounded-full h-4">
  <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-4"
       style="width: X%"></div>
</div>
```

## 🔧 Common Commands

```bash
# Check migration status
php artisan migrate:status --database=control

# Rollback migration
php artisan migrate:rollback --database=control --step=1

# Check routes
php artisan route:list | grep profile-completion

# Clear specific cache
php artisan cache:forget "org_{orgId}_profile_completion"

# Check logs
tail -f storage/logs/laravel.log
```

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Migration fails | Use `--database=control` flag |
| 404 on pages | Clear route cache |
| API returns 401 | Check JWT token in localStorage |
| Progress not updating | Check browser console for errors |
| Master counts wrong | Verify tenant database connection |

## 📊 Database Queries

```sql
-- Check profile completion
SELECT org_slug, profile_completion_percentage, profile_completed_at
FROM organizations
WHERE org_slug = 'your-org';

-- Update profile manually
UPDATE organizations
SET profile_completion_percentage = 100,
    profile_completed_at = NOW()
WHERE org_slug = 'your-org';

-- Check master data counts
SELECT 
  (SELECT COUNT(*) FROM department_master) as departments,
  (SELECT COUNT(*) FROM role_master) as roles,
  (SELECT COUNT(*) FROM users) as users;
```

## 🎯 Testing Checklist

```
[ ] Migration successful
[ ] Dashboard loads
[ ] Alerts show when incomplete
[ ] Profile page loads
[ ] Profile saves correctly
[ ] Progress updates
[ ] Master page loads
[ ] Master counts accurate
[ ] Sidebar navigation works
[ ] Mobile responsive
[ ] No console errors
```

## 📈 Completion Calculation

```
Profile Percentage = (Completed Fields / Total Fields) × 100
Master Percentage = (Setup Masters / Total Masters) × 100

Profile Complete = All 10 fields filled
Master Complete = All 11 masters have records
```

## 🔐 Security

- ✅ JWT authentication required
- ✅ Tenant context validated
- ✅ Organization data scoped
- ✅ SQL injection prevented
- ✅ XSS prevented
- ✅ CSRF protected

## 🎨 Color Coding

| Color | Usage |
|-------|-------|
| Blue | Primary actions, progress |
| Green | Completed items |
| Yellow | Warnings, incomplete profile |
| Red | Critical items |
| Gray | Disabled, inactive |
| Purple | Gradients, highlights |

## 📱 Responsive Breakpoints

```css
sm: 640px   /* Mobile landscape */
md: 768px   /* Tablet */
lg: 1024px  /* Desktop */
xl: 1280px  /* Large desktop */
```

## 🚦 Status Indicators

| Status | Icon | Color |
|--------|------|-------|
| Complete | ✓ | Green |
| Incomplete | ○ | Gray |
| Critical | ! | Red |
| Warning | ⚠ | Yellow |

## 📞 Support Resources

- Laravel Docs: https://laravel.com/docs
- Alpine.js Docs: https://alpinejs.dev
- Tailwind Docs: https://tailwindcss.com/docs
- Font Awesome: https://fontawesome.com/icons

## 💡 Pro Tips

1. **Cache completion status** for better performance
2. **Add indexes** on profile_completion_percentage
3. **Use queues** for heavy calculations
4. **Add notifications** for incomplete profiles
5. **Track analytics** on completion rates

## 🎓 Key Concepts

**Profile Completion**: Tracks organization profile fields
**Master Setup**: Tracks master data table records
**Tenant Context**: Each org has separate database
**JWT Auth**: Token-based authentication
**Alpine.js**: Reactive UI without full reloads

---

**Quick Help**: Check SETUP_INSTRUCTIONS.md for detailed steps
**Full Docs**: See PROFILE_COMPLETION_AND_MASTER_SETUP.md
**Overview**: Read IMPLEMENTATION_SUMMARY.md
