# Subscription Plans Usage Guide

## Overview
Subscription plans are now automatically fetched from the `subscription_plans` table and passed to both the landing page and pricing page.

## Backend Implementation

### Controllers Created

#### 1. PublicController
Handles public-facing pages with subscription plan data:

**Methods:**
- `landing()` - Landing page with plans
- `pricing()` - Pricing/subscription selection page with plans
- `register()` - Registration page with optional selected plan
- `login()` - Login page

#### 2. SubscriptionPlanController (API)
Provides API endpoints for fetching subscription plans:

**Endpoints:**
- `GET /api/v1/subscription-plans` - Get all public plans
- `GET /api/v1/subscription-plans/{planCode}` - Get specific plan

### Routes Updated

#### Web Routes (routes/web.php)
```php
// Landing page with subscription plans
Route::get('/', [PublicController::class, 'landing'])->name('home');

// Pricing page with subscription plans
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

// Registration with optional plan selection
Route::get('/register', [PublicController::class, 'register'])->name('register');

// Login page
Route::get('/login', [PublicController::class, 'login'])->name('login');
```

#### API Routes (routes/api.php)
```php
// Public subscription plans API
Route::prefix('subscription-plans')->group(function () {
    Route::get('/', [SubscriptionPlanController::class, 'index']);
    Route::get('/{planCode}', [SubscriptionPlanController::class, 'show']);
});
```

## Frontend Usage

### 1. Landing Page (resources/views/landing.blade.php)

The `$plans` variable is automatically available in the view:

```blade
@extends('layouts.app')

@section('content')
<div class="landing-page">
    <section class="hero">
        <h1>Welcome to Our ERP System</h1>
        <p>Choose the perfect plan for your business</p>
    </section>
    
    <section class="pricing-preview">
        <div class="plans-grid">
            @foreach($plans as $plan)
            <div class="plan-card">
                <h3>{{ $plan->plan_name }}</h3>
                <p class="description">{{ $plan->description }}</p>
                
                <div class="price">
                    <span class="amount">{{ $plan->currency_code }} {{ number_format($plan->price_amount, 2) }}</span>
                    <span class="cycle">/ {{ strtolower($plan->billing_cycle) }}</span>
                </div>
                
                <ul class="features">
                    <li>Up to {{ $plan->max_users }} users</li>
                    <li>{{ $plan->max_warehouses }} warehouses</li>
                    <li>{{ $plan->storage_gb }} GB storage</li>
                    <li>{{ number_format($plan->api_rate_limit_day) }} API calls/day</li>
                </ul>
                
                <div class="modules">
                    <h4>Included Modules:</h4>
                    <ul>
                        @foreach($plan->modules_included as $module)
                        <li>{{ $module }}</li>
                        @endforeach
                    </ul>
                </div>
                
                <a href="{{ route('register', ['plan' => $plan->plan_code]) }}" class="btn btn-primary">
                    Get Started
                </a>
            </div>
            @endforeach
        </div>
        
        <a href="{{ route('pricing') }}" class="btn btn-secondary">View All Plans</a>
    </section>
</div>
@endsection
```

### 2. Pricing Page (resources/views/subscription/select.blade.php)

```blade
@extends('layouts.app')

@section('content')
<div class="pricing-page">
    <section class="header">
        <h1>Choose Your Plan</h1>
        <p>Select the perfect plan for your organization</p>
    </section>
    
    <section class="plans-comparison">
        <div class="plans-grid">
            @foreach($plans as $plan)
            <div class="plan-card {{ $plan->plan_code === 'PROFESSIONAL' ? 'featured' : '' }}">
                @if($plan->plan_code === 'PROFESSIONAL')
                <div class="badge">Most Popular</div>
                @endif
                
                <h2>{{ $plan->plan_name }}</h2>
                <p class="description">{{ $plan->description }}</p>
                
                <div class="price-section">
                    <div class="price">
                        <span class="currency">{{ $plan->currency_code }}</span>
                        <span class="amount">{{ number_format($plan->price_amount, 0) }}</span>
                    </div>
                    <div class="billing-cycle">per {{ strtolower($plan->billing_cycle) }}</div>
                </div>
                
                <div class="features-section">
                    <h3>Features</h3>
                    <ul class="features-list">
                        <li>
                            <i class="icon-check"></i>
                            <span>{{ $plan->max_users }} Users</span>
                        </li>
                        <li>
                            <i class="icon-check"></i>
                            <span>{{ $plan->max_warehouses }} Warehouses</span>
                        </li>
                        <li>
                            <i class="icon-check"></i>
                            <span>{{ $plan->max_materials }} Materials</span>
                        </li>
                        <li>
                            <i class="icon-check"></i>
                            <span>{{ $plan->storage_gb }} GB Storage</span>
                        </li>
                        <li>
                            <i class="icon-check"></i>
                            <span>{{ number_format($plan->api_rate_limit_day) }} API Calls/Day</span>
                        </li>
                    </ul>
                </div>
                
                <div class="modules-section">
                    <h3>Included Modules</h3>
                    <div class="modules-grid">
                        @foreach($plan->modules_included as $module)
                        <span class="module-badge">{{ $module }}</span>
                        @endforeach
                    </div>
                </div>
                
                <a href="{{ route('register', ['plan' => $plan->plan_code]) }}" 
                   class="btn {{ $plan->plan_code === 'PROFESSIONAL' ? 'btn-primary' : 'btn-outline' }}">
                    Select {{ $plan->plan_name }}
                </a>
            </div>
            @endforeach
        </div>
    </section>
    
    <section class="comparison-table">
        <h2>Detailed Comparison</h2>
        <table>
            <thead>
                <tr>
                    <th>Feature</th>
                    @foreach($plans as $plan)
                    <th>{{ $plan->plan_name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Price</td>
                    @foreach($plans as $plan)
                    <td>{{ $plan->currency_code }} {{ number_format($plan->price_amount, 2) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Max Users</td>
                    @foreach($plans as $plan)
                    <td>{{ $plan->max_users }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Warehouses</td>
                    @foreach($plans as $plan)
                    <td>{{ $plan->max_warehouses }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Materials</td>
                    @foreach($plans as $plan)
                    <td>{{ $plan->max_materials }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Storage</td>
                    @foreach($plans as $plan)
                    <td>{{ $plan->storage_gb }} GB</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </section>
</div>
@endsection
```

### 3. Registration Page (resources/views/auth/register.blade.php)

The registration page receives an optional `$selectedPlan` variable:

```blade
@extends('layouts.app')

@section('content')
<div class="registration-page">
    <h1>Create Your Organization</h1>
    
    @if($selectedPlan)
    <div class="selected-plan-info">
        <h3>Selected Plan: {{ $selectedPlan->plan_name }}</h3>
        <p>{{ $selectedPlan->currency_code }} {{ number_format($selectedPlan->price_amount, 2) }} / {{ strtolower($selectedPlan->billing_cycle) }}</p>
    </div>
    @endif
    
    <form id="registrationForm">
        <!-- Organization Details -->
        <input type="hidden" name="selected_plan" value="{{ $selectedPlan->plan_code ?? '' }}">
        
        <!-- Rest of registration form -->
    </form>
</div>
@endsection
```

## API Usage (JavaScript/Frontend)

### Fetch All Plans
```javascript
async function fetchSubscriptionPlans() {
    try {
        const response = await fetch('/api/v1/subscription-plans');
        const data = await response.json();
        
        if (data.success) {
            const plans = data.data.plans;
            displayPlans(plans);
        }
    } catch (error) {
        console.error('Failed to fetch plans:', error);
    }
}
```

### Fetch Specific Plan
```javascript
async function fetchPlanDetails(planCode) {
    try {
        const response = await fetch(`/api/v1/subscription-plans/${planCode}`);
        const data = await response.json();
        
        if (data.success) {
            const plan = data.data.plan;
            displayPlanDetails(plan);
        }
    } catch (error) {
        console.error('Failed to fetch plan:', error);
    }
}
```

### Example Response
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "plan_id": 1,
        "plan_code": "STARTER",
        "plan_name": "Starter",
        "description": "Perfect for small businesses",
        "billing_cycle": "MONTHLY",
        "price_amount": "29.99",
        "currency_code": "USD",
        "max_users": 5,
        "max_warehouses": 1,
        "max_materials": 100,
        "storage_gb": 10,
        "api_rate_limit_day": 1000,
        "modules_included": ["USERS", "INVENTORY", "REPORTS"],
        "billing_cycle_days": 30
      }
    ]
  },
  "message": "Subscription plans retrieved successfully"
}
```

## Plan Data Structure

Each plan object contains:

| Field | Type | Description |
|-------|------|-------------|
| plan_id | integer | Unique plan identifier |
| plan_code | string | Plan code (e.g., STARTER, PROFESSIONAL) |
| plan_name | string | Display name |
| description | string | Plan description |
| billing_cycle | string | MONTHLY, QUARTERLY, or ANNUAL |
| price_amount | decimal | Price in specified currency |
| currency_code | string | 3-letter currency code (USD, EUR, etc.) |
| max_users | integer | Maximum number of users |
| max_warehouses | integer | Maximum number of warehouses |
| max_materials | integer | Maximum number of materials |
| storage_gb | integer | Storage limit in GB |
| api_rate_limit_day | integer | Daily API call limit |
| modules_included | array | List of included module codes |
| billing_cycle_days | integer | Billing cycle duration in days |

## Registration Flow with Plan Selection

1. User visits landing page (`/`) - sees plan preview
2. User clicks "View All Plans" or navigates to `/pricing`
3. User selects a plan by clicking "Select Plan"
4. User is redirected to `/register?plan=STARTER`
5. Registration form shows selected plan details
6. On form submission, `selected_plan` is included in the request
7. Organization is created with the selected plan

## Testing

### Test Web Routes
```bash
# Landing page with plans
curl http://localhost:8000/

# Pricing page with plans
curl http://localhost:8000/pricing

# Registration with selected plan
curl http://localhost:8000/register?plan=STARTER
```

### Test API Endpoints
```bash
# Get all plans
curl http://localhost:8000/api/v1/subscription-plans

# Get specific plan
curl http://localhost:8000/api/v1/subscription-plans/STARTER
```

## Database Seeding

Ensure you have subscription plans in the database:

```sql
INSERT INTO subscription_plans (plan_code, plan_name, description, billing_cycle, price_amount, currency_code, max_users, max_warehouses, max_materials, storage_gb, api_rate_limit_day, modules_included, is_active, is_public) VALUES
('STARTER', 'Starter', 'Perfect for small businesses', 'MONTHLY', 29.99, 'USD', 5, 1, 100, 10, 1000, '["USERS","INVENTORY","REPORTS"]', 1, 1),
('PROFESSIONAL', 'Professional', 'For growing businesses', 'MONTHLY', 99.99, 'USD', 25, 5, 1000, 50, 10000, '["USERS","INVENTORY","REPORTS","ANALYTICS","API"]', 1, 1),
('ENTERPRISE', 'Enterprise', 'For large organizations', 'MONTHLY', 299.99, 'USD', 100, 20, 10000, 200, 100000, '["USERS","INVENTORY","REPORTS","ANALYTICS","API","CUSTOM"]', 1, 1);
```

## Notes

- Only plans with `is_active = true` and `is_public = true` are shown
- Plans are ordered by price (ascending) by default
- The `modules_included` field is a JSON array of module codes
- Currency formatting should be handled in the frontend based on locale
- Plan selection is optional during registration (can be selected later)
