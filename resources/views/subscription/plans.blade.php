<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose Subscription Plan - Fabricate ERP</title>
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
                    <i class="fas fa-industry text-white text-xl"></i>
                </div>
                <span class="text-xl font-semibold text-gray-900">Fabricate ERP</span>
            </div>
            <div class="flex items-center space-x-6">
                <button class="text-gray-600 hover:text-gray-900">
                    <i class="far fa-bell text-xl"></i>
                </button>
                <button class="text-gray-600 hover:text-gray-900">
                    <i class="far fa-question-circle text-xl"></i>
                </button>
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-gray-600"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-80px)]">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 p-6">
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-500 mb-4">SETUP PROGRESS</h3>
                <div class="space-y-4">
                    <!-- Step 1: Account Login -->
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Account Login</div>
                            <div class="text-xs text-green-600">Completed</div>
                        </div>
                    </div>

                    <!-- Step 2: Organization Info -->
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Organization Info</div>
                            <div class="text-xs text-green-600">Completed</div>
                        </div>
                    </div>

                    <!-- Step 3: Subscription -->
                    <div class="flex items-center space-x-3 bg-blue-50 -mx-3 px-3 py-2 rounded-lg">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-credit-card text-white text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Subscription</div>
                            <div class="text-xs text-blue-600">Current Step</div>
                        </div>
                    </div>

                    <!-- Step 4: Final Setup -->
                    <div class="flex items-center space-x-3 opacity-50">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-flag-checkered text-gray-400 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Final Setup</div>
                            <div class="text-xs text-gray-500">Upcoming</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-8">
                <div class="flex justify-between text-xs text-gray-600 mb-2">
                    <span>Wizard Progress</span>
                    <span class="font-semibold">75%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-2">STEP 3 OF 4</div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="max-w-6xl mx-auto">
                <!-- Step Indicator -->
                <div class="mb-6">
                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                        STEP 3: SUBSCRIPTION
                    </span>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Choose Your Subscription Plan</h1>
                <p class="text-gray-600 mb-8">Select the plan that best fits your manufacturing enterprise needs. You can upgrade or downgrade anytime.</p>

                <!-- Billing Toggle -->
                <div class="flex items-center justify-center mb-8">
                    <span class="text-sm font-medium text-gray-700 mr-3">Monthly</span>
                    <button type="button" id="billingToggle" onclick="toggleBilling()" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span id="billingToggleCircle" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                    </button>
                    <span class="text-sm font-medium text-gray-700 ml-3">Yearly <span class="text-green-600 text-xs">(Save 20%)</span></span>
                </div>

                <!-- Pricing Cards -->
                <div class="grid grid-cols-3 gap-6 mb-8" id="pricingCards">
                    <!-- Loading state -->
                    <div class="col-span-3 text-center py-12">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-gray-600">Loading subscription plans...</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <button type="button" onclick="window.location.href='/register'"
                        class="flex items-center text-gray-600 hover:text-gray-900 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Organization Info
                    </button>
                    <button type="button" id="skipBtn" onclick="skipSubscription()"
                        class="px-8 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                        Skip for Now (Start Trial)
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-600">
            <span>© 2024 Fabricate ERP Systems. All rights reserved.</span>
            <div class="flex space-x-6">
                <a href="#" class="hover:text-gray-900">Privacy Policy</a>
                <a href="#" class="hover:text-gray-900">Terms of Service</a>
                <a href="#" class="hover:text-gray-900">Contact Support</a>
            </div>
        </div>
    </footer>

    <script>
        let isYearly = false;
        let plans = [];

        // Toggle billing period
        function toggleBilling() {
            isYearly = !isYearly;
            const toggle = document.getElementById('billingToggle');
            const circle = document.getElementById('billingToggleCircle');
            
            if (isYearly) {
                toggle.classList.remove('bg-gray-300');
                toggle.classList.add('bg-blue-600');
                circle.classList.remove('translate-x-1');
                circle.classList.add('translate-x-6');
            } else {
                toggle.classList.remove('bg-blue-600');
                toggle.classList.add('bg-gray-300');
                circle.classList.remove('translate-x-6');
                circle.classList.add('translate-x-1');
            }
            
            renderPlans();
        }

        // Fetch subscription plans
        async function fetchPlans() {
            try {
                const response = await fetch('/api/v1/subscriptions/plans', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    plans = data.data;
                    renderPlans();
                } else {
                    showError('Failed to load subscription plans');
                }
            } catch (error) {
                showError('Network error. Please refresh the page.');
            }
        }

        // Render pricing cards
        function renderPlans() {
            const container = document.getElementById('pricingCards');
            
            if (plans.length === 0) {
                container.innerHTML = `
                    <div class="col-span-3 text-center py-12">
                        <i class="fas fa-exclamation-circle text-4xl text-red-600 mb-4"></i>
                        <p class="text-gray-600">No subscription plans available</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = plans.map(plan => {
                const price = isYearly ? (plan.price_monthly * 12 * 0.8).toFixed(2) : plan.price_monthly;
                const period = isYearly ? 'year' : 'month';
                const isPopular = plan.plan_name.toLowerCase().includes('professional');
                
                return `
                    <div class="relative bg-white rounded-xl shadow-lg p-6 border-2 ${isPopular ? 'border-blue-600' : 'border-gray-200'} hover:shadow-xl transition-shadow">
                        ${isPopular ? '<div class="absolute -top-3 left-1/2 transform -translate-x-1/2"><span class="bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full">MOST POPULAR</span></div>' : ''}
                        
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">${plan.plan_name}</h3>
                            <div class="flex items-baseline justify-center mb-2">
                                <span class="text-4xl font-bold text-gray-900">$${price}</span>
                                <span class="text-gray-600 ml-2">/${period}</span>
                            </div>
                            <p class="text-sm text-gray-600">${plan.description || ''}</p>
                        </div>
                        
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                <span class="text-sm text-gray-700">Up to ${plan.max_users} users</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                <span class="text-sm text-gray-700">${plan.storage_gb}GB storage</span>
                            </li>
                            ${plan.features ? plan.features.split(',').map(f => `
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-600 mt-1 mr-2"></i>
                                    <span class="text-sm text-gray-700">${f.trim()}</span>
                                </li>
                            `).join('') : ''}
                        </ul>
                        
                        <button 
                            onclick="selectPlan('${plan.plan_id}')"
                            class="w-full px-6 py-3 ${isPopular ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'} font-medium rounded-lg transition-colors">
                            Select ${plan.plan_name}
                        </button>
                    </div>
                `;
            }).join('');
        }

        // Select a plan
        async function selectPlan(planId) {
            // Store selected plan and redirect to final setup
            localStorage.setItem('selected_plan', planId);
            window.location.href = '/setup/final';
        }

        // Skip subscription
        function skipSubscription() {
            if (confirm('Are you sure you want to skip? You will start with a 14-day trial.')) {
                window.location.href = '/setup/final';
            }
        }

        function showError(message) {
            const container = document.getElementById('pricingCards');
            container.innerHTML = `
                <div class="col-span-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
        }

        // Load plans on page load
        fetchPlans();
    </script>
</body>
</html>
