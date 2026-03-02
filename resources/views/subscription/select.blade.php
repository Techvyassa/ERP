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
            <!-- Starter Plan -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-gray-200 hover:border-blue-400 transition-all">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900" id="starter-price">$49</span>
                    <span class="text-gray-600">/month</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Up to 10 users</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">10GB storage</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Basic features</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Email support</span>
                    </li>
                </ul>
                <button onclick="selectPlan('starter')" class="w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Select Starter
                </button>
            </div>

            <!-- Professional Plan (Popular) -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-blue-600 relative transform scale-105">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-blue-600 text-white text-sm font-semibold px-4 py-1 rounded-full">MOST POPULAR</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900" id="pro-price">$149</span>
                    <span class="text-gray-600">/month</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Up to 50 users</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">100GB storage</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">All features</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Priority support</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">API access</span>
                    </li>
                </ul>
                <button onclick="selectPlan('professional')" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Select Professional
                </button>
            </div>

            <!-- Enterprise Plan -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-gray-200 hover:border-blue-400 transition-all">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">Custom</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Unlimited users</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Unlimited storage</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Custom features</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">24/7 phone support</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                        <span class="text-gray-700">Dedicated account manager</span>
                    </li>
                </ul>
                <button onclick="selectPlan('enterprise')" class="w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    Contact Sales
                </button>
            </div>
        </div>

        <!-- Features Comparison -->
        <div class="text-center mb-8">
            <p class="text-gray-600">All plans include 14-day free trial • No credit card required • Cancel anytime</p>
        </div>
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
                
                // Update prices (20% discount)
                document.getElementById('starter-price').textContent = '$470';
                document.getElementById('pro-price').textContent = '$1,430';
            } else {
                toggle.classList.remove('bg-blue-600');
                toggle.classList.add('bg-gray-300');
                circle.classList.remove('translate-x-6');
                circle.classList.add('translate-x-1');
                
                // Reset to monthly prices
                document.getElementById('starter-price').textContent = '$49';
                document.getElementById('pro-price').textContent = '$149';
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
