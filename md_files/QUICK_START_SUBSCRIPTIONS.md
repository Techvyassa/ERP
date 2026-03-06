# Quick Start - Subscription Plans

## Setup in 3 Steps

### Step 1: Add Plans to Database

Run this SQL to add sample plans:

```sql
INSERT INTO subscription_plans (
    plan_code, plan_name, description, billing_cycle, 
    price_amount, currency_code, max_users, max_warehouses, 
    max_materials, storage_gb, api_rate_limit_day, 
    modules_included, is_active, is_public, created_at, updated_at
) VALUES
('STARTER', 'Starter', 'Perfect for small businesses', 'MONTHLY', 49.00, 'USD', 10, 2, 500, 10, 1000, '["USERS","INVENTORY","REPORTS"]', 1, 1, NOW(), NOW()),
('PROFESSIONAL', 'Professional', 'Ideal for growing businesses', 'MONTHLY', 149.00, 'USD', 50, 10, 5000, 100, 10000, '["USERS","INVENTORY","REPORTS","ANALYTICS","API","WAREHOUSE"]', 1, 1, NOW(), NOW()),
('ENTERPRISE', 'Enterprise', 'For large organizations', 'MONTHLY', 499.00, 'USD', 999999, 999999, 999999, 999999, 100000, '["USERS","INVENTORY","REPORTS","ANALYTICS","API","WAREHOUSE","CUSTOM","INTEGRATIONS"]', 1, 1, NOW(), NOW());
```

### Step 2: Test the Pages

```bash
# Start your server
php artisan serve

# Visit these URLs:
# Landing page: http://localhost:8000/
# Pricing page: http://localhost:8000/pricing
# Registration: http://localhost:8000/register?plan=starter
```

### Step 3: Verify Everything Works

1. ✅ Plans display on pricing page
2. ✅ Billing toggle switches between monthly/yearly
3. ✅ Click "Select Plan" redirects to registration
4. ✅ Selected plan appears in URL: `/register?plan=starter`

## How It Works

```
User visits /pricing
    ↓
PublicController fetches plans from database
    ↓
Plans passed to Blade view
    ↓
Dynamic cards rendered
    ↓
User selects plan
    ↓
Redirects to /register?plan=CODE
```

## Key Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/PublicController.php` | Fetches plans from DB |
| `resources/views/subscription/select.blade.php` | Displays plans |
| `routes/web.php` | Routes for public pages |
| `routes/api.php` | API endpoints for plans |

## API Endpoints

```bash
# Get all plans
GET /api/v1/subscription-plans

# Get specific plan
GET /api/v1/subscription-plans/STARTER
```

## Customization

### Change "Most Popular" Plan
Edit line in `select.blade.php`:
```php
$isPopular = strtoupper($plan->plan_code) === 'PROFESSIONAL' || $index === 1;
```

### Change Yearly Discount
Edit JavaScript in `select.blade.php`:
```javascript
const yearlyPrice = parseFloat(priceElement.dataset.monthly) * 12 * 0.8; // 0.8 = 20% off
```

### Add More Features
Add to the features list in the Blade template:
```blade
<li class="flex items-start">
    <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
    <span class="text-gray-700">Your custom feature</span>
</li>
```

## Troubleshooting

**Problem:** No plans showing
**Solution:** Check database has plans with `is_active=1` and `is_public=1`

**Problem:** Prices not updating on toggle
**Solution:** Check browser console for JavaScript errors

**Problem:** 404 on /pricing
**Solution:** Run `php artisan route:clear` and `php artisan route:cache`

## What's Included

✅ Dynamic plan cards from database
✅ Monthly/yearly billing toggle
✅ Automatic "Most Popular" badge
✅ Detailed comparison table
✅ Responsive design (mobile/tablet/desktop)
✅ Empty state handling
✅ Plan selection with localStorage
✅ Redirect to registration with plan code

## Next Steps

1. Add more plans to database
2. Customize colors and styling
3. Add FAQ section
4. Add testimonials
5. Connect payment gateway
6. Set up email notifications

## Support

For issues or questions, check:
- `SUBSCRIPTION_PLANS_USAGE.md` - Detailed usage guide
- `SUBSCRIPTION_PAGE_UPDATE.md` - Technical details
- `AUTHENTICATION_FLOW.md` - Complete auth flow
