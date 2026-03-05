<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose Your Plan - Zap ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-zap text-white text-xl"></i>
                </div>
                <span class="text-xl font-semibold text-gray-900">Zap ERP</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium">Already have an account? Sign In</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Step Indicator -->
        <div class="mb-8 text-center">
            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full mb-4">
                STEP 1 OF 3: CHOOSE YOUR PLAN
            </span>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Select Your Subscription Plan</h1>
            <p class="text-xl text-gray-600">Start with a 14-day free trial. No credit card required.</p>
        </div>

        <!-- Billing Toggle -->
        <div class="flex items-center justify-center mb-12">
            <span class="text-sm font-medium text-gray-700 mr-3">Monthly</span>
            <button type="button" id="billingToggle" onclick="toggleBilling()" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span id="billingToggleCircle" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
            </button>
            <span class="text-sm font-medium text-gray-700 ml-3">Yearly <span class="text-green-600 text-xs font-semibold">(Save 20%)</span></span>
        </div>

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto mb-12" id="pricingCards">
            @forelse($plans as $index => $plan)
            @php
                $isPopular = strtoupper($plan->plan_code) === 'PROFESSIONAL' || $index === 1;
                $cardClasses = $isPopular 
                    ? 'bg-white rounded-2xl shadow-2xl p-8 border-2 border-blue-600 relative transform scale-105' 
                    : 'bg-white rounded-2xl shadow-lg p-8 border-2 border-gray-200 hover:border-blue-400 transition-all';
                $buttonClasses = $isPopular
                    ? 'w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium'
                    : 'w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors font-medium';
            @endphp
            
            <div class="{{ $cardClasses }}" data-plan-code="{{ $plan->plan_code }}">
                @if($isPopular)
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-blue-600 text-white text-sm font-semibold px-4 py-1 rounded-full">MOST POPULAR</span>
                </div>
                @endif
                
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->plan_name }}</h3>
                
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900 plan-price" 
                          data-monthly="{{ $plan->price_amount }}"
                          data-currency="{{ $plan->currency_code }}">
                        {{ $plan->currency_code }} {{ number_format($plan->price_amount, 0) }}
                    </span>
                    <span class="text-gray-600 billing-period">/monthly</span>
                </div>
                
                @if($plan->description)
                <p class="text-sm text-gray-600 mb-4">{{ $plan->description }}</p>
                @endif
                
                @php
                    // Handle double-encoded JSON in modules_included
                    $modules = [];
                    if (is_string($plan->modules_included)) {
                        $decoded = json_decode($plan->modules_included, true);
                        if (is_string($decoded)) {
                            $modules = json_decode($decoded, true) ?? [];
                        } else {
                            $modules = $decoded ?? [];
                        }
                    } elseif (is_array($plan->modules_included)) {
                        $modules = $plan->modules_included;
                    }
                @endphp
                
                <ul class="space-y-3 mb-8"></ul>
                    @if($plan->max_users)
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">
                            @if($plan->max_users >= 999999)
                                Unlimited users
                            @else
                                Up to {{ $plan->max_users }} users
                            @endif
                        </span>
                    </li>
                    @endif
                    
                    @if($plan->storage_gb)
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">
                            @if($plan->storage_gb >= 999999)
                                Unlimited storage
                            @else
                                {{ $plan->storage_gb }}GB storage
                            @endif
                        </span>
                    </li>
                    @endif
                    
                    @if($plan->max_warehouses)
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">
                            @if($plan->max_warehouses >= 999999)
                                Unlimited warehouses
                            @else
                                {{ $plan->max_warehouses }} warehouse{{ $plan->max_warehouses > 1 ? 's' : '' }}
                            @endif
                        </span>
                    </li>
                    @endif
                    
                    @if($plan->max_materials)
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">
                            @if($plan->max_materials >= 999999)
                                Unlimited materials
                            @else
                                {{ number_format($plan->max_materials) }} materials
                            @endif
                        </span>
                    </li>
                    @endif
                    
                    @if($plan->api_rate_limit_day)
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">{{ number_format($plan->api_rate_limit_day) }} API calls/day</span>
                    </li>
                    @endif
                    
                    @if(count($modules) > 0)
                        @foreach(array_slice($modules, 0, 3) as $module)
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                            <span class="text-gray-700">{{ ucfirst(strtolower($module)) }} module</span>
                        </li>
                        @endforeach
                        
                        @if(count($modules) > 3)
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                            <span class="text-gray-700">+{{ count($modules) - 3 }} more modules</span>
                        </li>
                        @endif
                    @endif
                </ul>
                
                <button onclick="selectPlan('{{ strtolower($plan->plan_code) }}')" class="{{ $buttonClasses }}">
                    Select {{ $plan->plan_name }}
                </button>
            </div>
            @empty
            <div class="col-span-3 text-center py-12">
                <p class="text-gray-600 text-lg">No subscription plans available at the moment.</p>
                <p class="text-gray-500 text-sm mt-2">Please contact support for more information.</p>
            </div>
            @endforelse
        </div>

        <!-- Features Comparison -->
        <div class="text-center mb-8">
            <p class="text-gray-600">All plans include 14-day free trial • No credit card required • Cancel anytime</p>
        </div>

        <!-- Detailed Comparison Table -->
        @if($plans->count() > 0)
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Detailed Feature Comparison</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-4 px-4 text-gray-700 font-semibold">Feature</th>
                            @foreach($plans as $plan)
                            <th class="text-center py-4 px-4 text-gray-900 font-bold">{{ $plan->plan_name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Monthly Price</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900 font-semibold">
                                {{ $plan->currency_code }} {{ number_format($plan->price_amount, 2) }}
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Max Users</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900">
                                @if($plan->max_users >= 999999)
                                    <span class="text-green-600 font-semibold">Unlimited</span>
                                @else
                                    {{ number_format($plan->max_users) }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Storage</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900">
                                @if($plan->storage_gb >= 999999)
                                    <span class="text-green-600 font-semibold">Unlimited</span>
                                @else
                                    {{ $plan->storage_gb }} GB
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Warehouses</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900">
                                @if($plan->max_warehouses >= 999999)
                                    <span class="text-green-600 font-semibold">Unlimited</span>
                                @else
                                    {{ number_format($plan->max_warehouses) }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Materials</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900">
                                @if($plan->max_materials >= 999999)
                                    <span class="text-green-600 font-semibold">Unlimited</span>
                                @else
                                    {{ number_format($plan->max_materials) }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">API Calls/Day</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center text-gray-900">
                                {{ number_format($plan->api_rate_limit_day) }}
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium">Modules Included</td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center">
                                @php
                                    // Handle double-encoded JSON in modules_included
                                    $modules = [];
                                    if (is_string($plan->modules_included)) {
                                        $decoded = json_decode($plan->modules_included, true);
                                        if (is_string($decoded)) {
                                            $modules = json_decode($decoded, true) ?? [];
                                        } else {
                                            $modules = $decoded ?? [];
                                        }
                                    } elseif (is_array($plan->modules_included)) {
                                        $modules = $plan->modules_included;
                                    }
                                @endphp
                                <div class="flex flex-wrap gap-1 justify-center">
                                    @if(count($modules) > 0)
                                        @foreach($modules as $module)
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">
                                            {{ $module }}
                                        </span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4 text-gray-700 font-medium"></td>
                            @foreach($plans as $plan)
                            <td class="py-4 px-4 text-center">
                                <button onclick="selectPlan('{{ strtolower($plan->plan_code) }}')" 
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
                                    Select Plan
                                </button>
                            </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-600">
            <span>© 2024 Zap ERP Systems. All rights reserved.</span>
            <div class="flex space-x-6">
                <a href="#" class="hover:text-gray-900">Privacy Policy</a>
                <a href="#" class="hover:text-gray-900">Terms of Service</a>
                <a href="#" class="hover:text-gray-900">Contact Support</a>
            </div>
        </div>
    </footer>

    <script>
        let isYearly = false;

        function toggleBilling() {
            isYearly = !isYearly;
            const toggle = document.getElementById('billingToggle');
            const circle = document.getElementById('billingToggleCircle');
            
            if (isYearly) {
                toggle.classList.remove('bg-gray-300');
                toggle.classList.add('bg-blue-600');
                circle.classList.remove('translate-x-1');
                circle.classList.add('translate-x-6');
                
                // Update all plan prices to yearly (with 20% discount)
                document.querySelectorAll('.plan-price').forEach(priceElement => {
                    const monthlyPrice = parseFloat(priceElement.dataset.monthly);
                    const yearlyPrice = monthlyPrice * 12 * 0.8;
                    const currencyCode = priceElement.dataset.currency;
                    priceElement.textContent = currencyCode + ' ' + Math.round(yearlyPrice).toLocaleString();
                });
                
                // Update billing period text
                document.querySelectorAll('.billing-period').forEach(period => {
                    period.textContent = '/year';
                });
            } else {
                toggle.classList.remove('bg-blue-600');
                toggle.classList.add('bg-gray-300');
                circle.classList.remove('translate-x-6');
                circle.classList.add('translate-x-1');
                
                // Reset to monthly prices
                document.querySelectorAll('.plan-price').forEach(priceElement => {
                    const monthlyPrice = parseFloat(priceElement.dataset.monthly);
                    const currencyCode = priceElement.dataset.currency;
                    priceElement.textContent = currencyCode + ' ' + Math.round(monthlyPrice).toLocaleString();
                });
                
                // Update billing period text
                document.querySelectorAll('.billing-period').forEach(period => {
                    period.textContent = '/monthly';
                });
            }
        }

        function selectPlan(plan) {
            // Store selected plan
            localStorage.setItem('selected_plan', plan);
            localStorage.setItem('billing_period', isYearly ? 'yearly' : 'monthly');
            
            // Redirect to registration
            window.location.href = '/register?plan=' + plan;
        }
    </script>
</body>
</html>
