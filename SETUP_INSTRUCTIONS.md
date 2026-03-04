# Setup Instructions for Profile Completion & Master Data Setup

## Quick Start

### Step 1: Run the Migration

```bash
# Run the control database migration to add profile completion fields
php artisan migrate --database=control --path=database/migrations/control/2024_03_04_000001_add_profile_completion_to_organizations.php
```

### Step 2: Clear Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Test the Implementation

1. **Login to your application**
   - Use an existing organization or create a new one

2. **Check the Dashboard**
   - You should see alert banners if profile is incomplete
   - Click "Complete Now" to go to profile completion page

3. **Complete Your Profile**
   - Fill in all required fields
   - Save the form
   - Watch the progress bar update
   - When 100%, you'll be redirected to dashboard

4. **Setup Master Data**
   - Click "Setup Masters" from dashboard alert
   - View all master data categories
   - Click "Setup" on any master to configure it

## URL Structure

### Subdomain Mode
- Dashboard: `https://yourorg.yourdomain.com/dashboard`
- Profile Completion: `https://yourorg.yourdomain.com/profile-completion`
- Master Setup: `https://yourorg.yourdomain.com/master-setup`

### Path-Based Mode
- Dashboard: `https://yourdomain.com/org/yourorg/dashboard`
- Profile Completion: `https://yourdomain.com/org/yourorg/profile-completion`
- Master Setup: `https://yourdomain.com/org/yourorg/master-setup`

## API Endpoints

All endpoints require JWT authentication via Bearer token.

### Get Profile Completion Status
```http
GET /api/v1/profile-completion/status
Authorization: Bearer {token}
```

### Update Organization Profile
```http
PUT /api/v1/profile-completion/organization
Authorization: Bearer {token}
Content-Type: application/json

{
  "org_name": "My Company",
  "primary_phone": "+1234567890",
  "address_line1": "123 Main St",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country_code": "US",
  "timezone": "America/New_York",
  "currency_code": "USD"
}
```

### Get Master Data Status
```http
GET /api/v1/profile-completion/master-data-status
Authorization: Bearer {token}
```

## Troubleshooting

### Issue: Migration fails
**Solution:** Ensure you're running the migration on the control database:
```bash
php artisan migrate --database=control --path=database/migrations/control
```

### Issue: Profile completion not showing
**Solution:** Check browser console for API errors. Ensure:
- JWT token is valid and stored in localStorage
- API endpoints are accessible
- CORS is configured correctly

### Issue: Master data status shows errors
**Solution:** Ensure:
- Tenant database exists and is accessible
- All master tables are created via tenant migrations
- Database connection is properly configured

### Issue: Sidebar menu items not showing
**Solution:** 
- Clear view cache: `php artisan view:clear`
- Check that routes are registered: `php artisan route:list`
- Verify tenant context middleware is working

### Issue: URLs not working (404 errors)
**Solution:**
- Check `.env` file for correct `APP_DOMAIN` setting
- Verify `TENANT_MODE` is set correctly (subdomain or path)
- Check Apache/Nginx virtual host configuration
- Ensure `tenantRoute()` helper is working

## Verification Checklist

Run through this checklist to verify everything is working:

- [ ] Migration completed successfully
- [ ] Can access dashboard
- [ ] Profile completion alert shows (if profile incomplete)
- [ ] Can navigate to profile completion page
- [ ] Profile form loads with current data
- [ ] Can save profile changes
- [ ] Progress bar updates after save
- [ ] Master setup alert shows (if masters incomplete)
- [ ] Can navigate to master setup page
- [ ] All master cards display correctly
- [ ] Master counts are accurate
- [ ] Can click through to master management pages
- [ ] Sidebar shows new menu items
- [ ] Both subdomain and path-based URLs work
- [ ] Mobile responsive design works

## Database Schema Verification

Check that the new columns exist:

```sql
-- Connect to control database
USE erp_control;

-- Check organizations table structure
DESCRIBE organizations;

-- Should see these new columns:
-- profile_completion (json, nullable)
-- profile_completion_percentage (int, default 0)
-- profile_completed_at (timestamp, nullable)
```

## Sample Data for Testing

### Update Organization Profile via SQL (for testing)
```sql
UPDATE organizations 
SET 
  primary_phone = '+1234567890',
  address_line1 = '123 Test Street',
  address_line2 = 'Suite 100',
  city = 'Test City',
  state = 'TS',
  postal_code = '12345',
  country_code = 'US',
  timezone = 'America/New_York',
  currency_code = 'USD'
WHERE org_slug = 'your-org-slug';
```

### Check Profile Completion
```sql
SELECT 
  org_slug,
  org_name,
  profile_completion_percentage,
  profile_completed_at
FROM organizations
WHERE org_slug = 'your-org-slug';
```

## Performance Optimization

### Cache Profile Completion
Consider caching the profile completion status to reduce database queries:

```php
// In ProfileCompletionController
$completion = Cache::remember(
    "org_{$orgId}_profile_completion",
    3600, // 1 hour
    fn() => $this->calculateProfileCompletion($organization)
);
```

### Index Optimization
Ensure proper indexes exist:

```sql
-- Add index on profile_completion_percentage for faster queries
ALTER TABLE organizations 
ADD INDEX idx_profile_completion_percentage (profile_completion_percentage);
```

## Next Development Steps

1. **Create Master Management Pages**
   - Departments CRUD
   - Roles CRUD
   - Users CRUD (already exists)
   - UOM CRUD
   - Materials CRUD
   - Products CRUD
   - Warehouses CRUD
   - Vendors CRUD
   - BOM CRUD

2. **Add Validation**
   - Require critical masters before allowing certain operations
   - Add warnings for incomplete profiles

3. **Add Notifications**
   - Email reminders for incomplete profiles
   - In-app notifications for setup progress

4. **Add Analytics**
   - Track time to complete profile
   - Track which masters are most commonly skipped
   - Dashboard for admin to see organization onboarding status

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify database connections
4. Check middleware is properly applied
5. Verify JWT tokens are valid

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Font Awesome Icons](https://fontawesome.com/icons)
