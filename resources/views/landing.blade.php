<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Zap ERP - The System for Modern Manufacturing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#193261",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>

<body class="bg-gray-50 font-display text-gray-900">
    <div class="relative flex min-h-screen flex-col overflow-x-hidden">
        <!-- Top Navigation -->
        <header
            class="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/95 backdrop-blur-md px-6 lg:px-40 py-4 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="bg-primary p-1.5 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-2xl">precision_manufacturing</span>
                </div>
                <h2 class="text-gray-900 text-xl font-bold tracking-tight">Zap ERP</h2>
            </div>

            <div class="hidden md:flex flex-1 justify-end gap-10 items-center">
                <nav class="flex items-center gap-8">
                    <a class="text-gray-600 hover:text-primary text-sm font-medium transition-colors"
                        href="#modules">Modules</a>
                    <a class="text-gray-600 hover:text-primary text-sm font-medium transition-colors"
                        href="#industries">Industries</a>
                    <a class="text-gray-600 hover:text-primary text-sm font-medium transition-colors"
                        href="#pricing">Pricing</a>
                    <a class="text-gray-600 hover:text-primary text-sm font-medium transition-colors"
                        href="#integrations">Integrations</a>
                </nav>
                <div class="flex gap-3">
                    <a href="/pricing"
                        class="bg-primary hover:bg-primary/90 text-white px-5 py-2 rounded-lg text-sm font-bold transition-all">
                        Get Started
                    </a>
                    <a href="/login"
                        class="bg-white hover:bg-gray-50 text-gray-900 px-5 py-2 rounded-lg text-sm font-bold transition-all border border-gray-300">
                        Login
                    </a>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <button class="md:hidden text-gray-900" onclick="toggleMobileMenu()">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </header>

        <main class="flex-1 max-w-7xl mx-auto px-6 lg:px-10 py-12 space-y-32">
            <!-- Hero Section -->
            <section class="text-center space-y-6 pt-10">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-xs font-bold uppercase tracking-widest text-primary">
                    New: AI-Powered Manufacturing Insights
                </div>
                <h1
                    class="text-gray-900 text-4xl md:text-6xl font-black leading-tight tracking-tight max-w-4xl mx-auto">
                    The System for
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-primary">Modern
                        Manufacturing</span>
                </h1>
                <p class="text-gray-600 text-lg md:text-xl max-w-2xl mx-auto">
                    A unified platform to manage production, inventory, procurement, and finances without the
                    complexity.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                    <a href="/pricing"
                        class="inline-flex items-center justify-center px-8 py-4 bg-primary hover:bg-primary/90 text-white rounded-lg font-bold transition-all shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined mr-2">rocket_launch</span>
                        Start Free Trial
                    </a>
                    <a href="#demo"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white hover:bg-gray-50 text-gray-900 rounded-lg font-bold transition-all border-2 border-gray-300">
                        <span class="material-symbols-outlined mr-2">play_circle</span>
                        Watch Demo
                    </a>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600 pt-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600 text-lg">check_circle</span>
                        <span>14-day free trial</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600 text-lg">check_circle</span>
                        <span>No credit card required</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600 text-lg">check_circle</span>
                        <span>Cancel anytime</span>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center py-10 border-y border-gray-200 bg-white rounded-xl shadow-sm">
                <div>
                    <div class="text-4xl font-bold text-primary mb-2">500+</div>
                    <div class="text-gray-600 text-sm">Active Companies</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary mb-2">99.9%</div>
                    <div class="text-gray-600 text-sm">Uptime SLA</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary mb-2">50K+</div>
                    <div class="text-gray-600 text-sm">Users Worldwide</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary mb-2">24/7</div>
                    <div class="text-gray-600 text-sm">Support Available</div>
                </div>
            </section>

            <!-- Core Modules Grid -->
            <section class="space-y-12" id="modules">
                <div class="flex flex-col items-center text-center gap-4">
                    <h2 class="text-gray-900 text-3xl font-bold">Core Manufacturing Modules</h2>
                    <div class="w-12 h-1 bg-primary rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-blue-50 text-blue-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">precision_manufacturing</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Production Planning</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">MRP, BOM management, work orders, and
                                real-time shop floor tracking.</p>
                        </div>
                    </div>

                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-emerald-50 text-emerald-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Inventory Control</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">Multi-warehouse stock tracking, automated
                                reordering, and batch/serial tracking.</p>
                        </div>
                    </div>

                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-amber-50 text-amber-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">shopping_cart_checkout</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Procurement</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">Purchase requisitions, RFQ, PO management
                                with intelligent approval workflows.</p>
                        </div>
                    </div>

                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-indigo-50 text-indigo-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">verified</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Quality Control</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">GRN inspection, AQL sampling, quality
                                certificates, and non-conformance tracking.</p>
                        </div>
                    </div>

                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-rose-50 text-rose-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">account_balance_wallet</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Finance & Accounting</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">Automated GL, AP/AR, GST compliance, and
                                real-time financial reporting.</p>
                        </div>
                    </div>

                    <div
                        class="group flex flex-col gap-4 p-8 rounded-xl border border-gray-200 bg-white hover:border-primary hover:shadow-lg transition-all">
                        <div
                            class="bg-cyan-50 text-cyan-600 w-12 h-12 flex items-center justify-center rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined">monitoring</span>
                        </div>
                        <div>
                            <h3 class="text-gray-900 text-xl font-bold mb-2">Analytics & Reports</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">Real-time dashboards, custom reports, and
                                AI-powered business insights.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Industry Solutions -->
            <section class="space-y-12" id="industries">
                <div class="flex flex-col items-center text-center gap-4">
                    <h2 class="text-gray-900 text-3xl font-bold">Industry Specific Solutions</h2>
                    <div class="w-12 h-1 bg-primary rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:-translate-y-2 transition-transform shadow-lg">
                        <div class="h-48 bg-gradient-to-br from-blue-600 to-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-6xl">factory</span>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="text-gray-900 text-lg font-bold">Spice & Food Processing</h4>
                            <p class="text-gray-600 text-sm">Batch tracking, FEFO, shelf-life management, and FSSAI
                                compliance.</p>
                            <a class="inline-flex items-center text-primary font-bold text-sm mt-4 hover:underline"
                                href="#">
                                Learn more <span class="material-symbols-outlined text-xs ml-1">arrow_forward</span>
                            </a>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:-translate-y-2 transition-transform shadow-lg">
                        <div
                            class="h-48 bg-gradient-to-br from-emerald-600 to-teal-600 flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-6xl">local_shipping</span>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="text-gray-900 text-lg font-bold">Automotive Parts</h4>
                            <p class="text-gray-600 text-sm">Serial number tracking, warranty management, and JIT
                                inventory.</p>
                            <a class="inline-flex items-center text-primary font-bold text-sm mt-4 hover:underline"
                                href="#">
                                Learn more <span class="material-symbols-outlined text-xs ml-1">arrow_forward</span>
                            </a>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:-translate-y-2 transition-transform shadow-lg">
                        <div
                            class="h-48 bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                            <span class="material-symbols-outlined text-white text-6xl">category</span>
                        </div>
                        <div class="p-6 space-y-2">
                            <h4 class="text-gray-900 text-lg font-bold">FMCG & Packaging</h4>
                            <p class="text-gray-600 text-sm">Multi-SKU management, expiry tracking, and distribution
                                planning.</p>
                            <a class="inline-flex items-center text-primary font-bold text-sm mt-4 hover:underline"
                                href="#">
                                Learn more <span class="material-symbols-outlined text-xs ml-1">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Integrations -->
            <section class="py-10 border-y border-gray-200 bg-white overflow-hidden rounded-xl shadow-sm" id="integrations">
                <p class="text-center text-gray-500 text-sm font-semibold uppercase tracking-widest mb-10">Integrates
                    with your favorite tools</p>
                <div
                    class="flex items-center gap-12 opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all flex-wrap justify-center px-4">
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">payments</span> Razorpay
                    </div>
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">credit_card</span> Stripe
                    </div>
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">mail</span> Gmail
                    </div>
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">chat</span> Slack
                    </div>
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">cloud</span> AWS
                    </div>
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-2xl px-6">
                        <span class="material-symbols-outlined text-3xl">api</span> REST API
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section class="space-y-16 py-10" id="pricing">
                <div class="flex flex-col items-center text-center gap-4">
                    <h2 class="text-gray-900 text-3xl font-bold">Scalable Pricing</h2>
                    <p class="text-gray-600 max-w-lg">Choose a plan that fits your current needs and scale as you grow.
                    </p>
                    <div class="w-12 h-1 bg-primary rounded-full"></div>
                </div>

                <div id="pricing-container" class="grid grid-cols-1 md:grid-cols-4 gap-8 items-stretch">
                    <!-- Plans will be loaded here -->
                    <div class="col-span-4 text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                        <p class="text-gray-600 mt-4">Loading pricing plans...</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 border-t border-gray-800 py-12 px-6 lg:px-40">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-primary p-1.5 rounded-lg">
                                <span
                                    class="material-symbols-outlined text-white text-xl">precision_manufacturing</span>
                            </div>
                            <h2 class="text-white text-lg font-bold tracking-tight">Zap ERP</h2>
                        </div>
                        <p class="text-gray-400 text-sm">Complete ERP solution for modern manufacturing enterprises.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Product</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#modules" class="hover:text-white transition-colors">Modules</a></li>
                            <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                            <li><a href="#integrations" class="hover:text-white transition-colors">Integrations</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">API Docs</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Company</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Compliance</a></li>
                        </ul>
                    </div>
                </div>

                <div
                    class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-500 text-sm">© 2024 Zap ERP Systems. All rights reserved.</p>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">link</span>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">mail</span>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">chat</span>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Load pricing plans from API
        async function loadPricingPlans() {
            try {
                const response = await fetch('/api/v1/subscription-plans');
                const data = await response.json();

                if (data.success && data.data.plans.length > 0) {
                    renderPricingPlans(data.data.plans);
                } else {
                    showPricingError();
                }
            } catch (error) {
                console.error('Failed to load pricing plans:', error);
                showPricingError();
            }
        }

        function renderPricingPlans(plans) {
            const container = document.getElementById('pricing-container');
            container.innerHTML = '';

            plans.forEach((plan, index) => {
                const isPopular = plan.plan_code === 'PROFESSIONAL' || index === 1;
                const planCard = createPlanCard(plan, isPopular);
                container.appendChild(planCard);
            });
        }

        function createPlanCard(plan, isPopular) {
            const div = document.createElement('div');
            div.className = `bg-white border-2 ${isPopular ? 'border-primary shadow-xl' : 'border-gray-200'} rounded-2xl p-8 flex flex-col relative overflow-hidden hover:shadow-lg transition-all`;

            const features = [];
            if (plan.max_users) features.push(`${plan.max_users >= 999999 ? 'Unlimited' : plan.max_users} User${plan.max_users > 1 ? 's' : ''}`);
            if (plan.storage_gb) features.push(`${plan.storage_gb >= 999999 ? 'Unlimited' : plan.storage_gb + 'GB'} Storage`);
            if (plan.max_warehouses) features.push(`${plan.max_warehouses >= 999999 ? 'Unlimited' : plan.max_warehouses} Warehouse${plan.max_warehouses > 1 ? 's' : ''}`);
            if (plan.api_rate_limit_day) features.push(`${plan.api_rate_limit_day.toLocaleString()} API Calls/Day`);

            div.innerHTML = `
                ${isPopular ? '<div class="absolute top-0 right-0 bg-primary text-white text-[10px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-bl-xl">Popular</div>' : ''}
                <div class="mb-8">
                    <h4 class="${isPopular ? 'text-primary' : 'text-gray-600'} text-sm font-bold uppercase tracking-widest mb-2">${plan.plan_name}</h4>
                    <div class="flex items-baseline gap-1">
                        <span class="text-gray-900 text-4xl font-black">${plan.currency_code} ${Math.round(plan.price_amount).toLocaleString()}</span>
                        <span class="text-gray-600">/mo</span>
                    </div>
                    <p class="text-gray-600 text-sm mt-4">${plan.description || 'Perfect for your business needs.'}</p>
                </div>
                <ul class="flex-1 space-y-4 mb-8">
                    ${features.map(feature => `
                        <li class="flex items-center gap-3 text-gray-700 text-sm">
                            <span class="material-symbols-outlined ${isPopular ? 'text-primary' : 'text-green-600'} text-lg">check_circle</span>
                            ${feature}
                        </li>
                    `).join('')}
                    ${plan.modules_included && plan.modules_included.length > 0 ? `
                        <li class="flex items-center gap-3 text-gray-700 text-sm">
                            <span class="material-symbols-outlined ${isPopular ? 'text-primary' : 'text-green-600'} text-lg">check_circle</span>
                            ${plan.modules_included.length} Modules Included
                        </li>
                    ` : ''}
                </ul>
                <a href="/register?plan=${plan.plan_code.toLowerCase()}" class="w-full ${isPopular ? 'bg-primary hover:bg-primary/90' : 'bg-gray-900 hover:bg-gray-800'} text-white font-bold py-3 rounded-lg transition-all text-center block">
                    Start 14-Day Trial
                </a>
            `;

            return div;
        }

        function showPricingError() {
            const container = document.getElementById('pricing-container');
            container.innerHTML = `
                <div class="col-span-4 text-center py-12">
                    <span class="material-symbols-outlined text-gray-400 text-6xl mb-4">error</span>
                    <p class="text-gray-600">Unable to load pricing plans. Please try again later.</p>
                    <a href="/pricing" class="inline-block mt-4 text-primary hover:underline">View all plans →</a>
                </div>
            `;
        }

        // Load plans on page load
        document.addEventListener('DOMContentLoaded', loadPricingPlans);
    </script>
</body>

</html>