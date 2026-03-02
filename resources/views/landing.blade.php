<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zap ERP - Manufacturing Enterprise Resource Planning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="fixed w-full bg-white/95 backdrop-blur-sm shadow-sm z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-industry text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Zap ERP</span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition-colors">Features</a>
                    <a href="#solutions" class="text-gray-700 hover:text-blue-600 transition-colors">Solutions</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 transition-colors">Pricing</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600 transition-colors">About</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Sign In</a>
                    <a href="/pricing" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-16 gradient-bg hero-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center py-12">
                <div class="text-white">
                    <h1 class="text-5xl font-bold mb-6 leading-tight">
                        Transform Your Manufacturing Operations
                    </h1>
                    <p class="text-xl text-blue-100 mb-8">
                        Complete ERP solution designed for modern manufacturing enterprises. Streamline production, manage inventory, and scale your business with confidence.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/pricing" class="px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition-colors font-semibold text-center">
                            <i class="fas fa-rocket mr-2"></i>Start Free Trial
                        </a>
                        <a href="#demo" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg hover:bg-white/10 transition-colors font-semibold text-center">
                            <i class="fas fa-play mr-2"></i>Watch Demo
                        </a>
                    </div>
                    <div class="mt-8 flex items-center space-x-6 text-sm text-blue-100">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>14-day free trial</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>No credit card required</span>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-2xl p-8">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3Crect fill='%23f3f4f6' width='800' height='600'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='48' fill='%239ca3af'%3EDashboard Preview%3C/text%3E%3C/svg%3E" alt="Dashboard Preview" class="rounded-lg">
                    </div>
                    <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-yellow-400 rounded-full opacity-20 blur-2xl"></div>
                    <div class="absolute -top-6 -left-6 w-32 h-32 bg-pink-400 rounded-full opacity-20 blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white border-y border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">500+</div>
                    <div class="text-gray-600">Active Companies</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">99.9%</div>
                    <div class="text-gray-600">Uptime SLA</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">50K+</div>
                    <div class="text-gray-600">Users Worldwide</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2">24/7</div>
                    <div class="text-gray-600">Support Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful Features for Modern Manufacturing</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Everything you need to manage your manufacturing operations efficiently</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-cogs text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Production Management</h3>
                    <p class="text-gray-600">Plan, schedule, and track production orders with real-time visibility into your manufacturing floor.</p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-boxes text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Inventory Control</h3>
                    <p class="text-gray-600">Manage raw materials, work-in-progress, and finished goods with automated tracking and alerts.</p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Analytics & Reports</h3>
                    <p class="text-gray-600">Make data-driven decisions with comprehensive reports and real-time analytics dashboards.</p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-yellow-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-users text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Team Collaboration</h3>
                    <p class="text-gray-600">Role-based access control and department management for seamless team coordination.</p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-red-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Enterprise Security</h3>
                    <p class="text-gray-600">SOC2 compliant with enterprise-grade security, encryption, and regular backups.</p>
                </div>

                <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-plug text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">API Integration</h3>
                    <p class="text-gray-600">Connect with your existing tools through our comprehensive REST API and webhooks.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- Solutions Section -->
    <section id="solutions" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Built for Every Manufacturing Industry</h2>
                <p class="text-xl text-gray-600">Tailored solutions for your specific industry needs</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:shadow-lg transition-all">
                    <i class="fas fa-car text-3xl text-blue-600 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Automotive</h3>
                    <p class="text-gray-600 text-sm">Parts manufacturing and assembly line management</p>
                </div>
                
                <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:shadow-lg transition-all">
                    <i class="fas fa-microchip text-3xl text-blue-600 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Electronics</h3>
                    <p class="text-gray-600 text-sm">PCB assembly and component tracking</p>
                </div>
                
                <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:shadow-lg transition-all">
                    <i class="fas fa-tshirt text-3xl text-blue-600 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Textile</h3>
                    <p class="text-gray-600 text-sm">Fabric production and garment manufacturing</p>
                </div>
                
                <div class="p-6 border-2 border-gray-200 rounded-xl hover:border-blue-600 hover:shadow-lg transition-all">
                    <i class="fas fa-pills text-3xl text-blue-600 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Pharmaceutical</h3>
                    <p class="text-gray-600 text-sm">Batch tracking and compliance management</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
                <p class="text-xl text-gray-600">Choose the plan that fits your business needs</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Starter Plan -->
                <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$49</span>
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
                    <a href="/pricing" class="block w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium">
                        Get Started
                    </a>
                </div>

                <!-- Professional Plan (Popular) -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-blue-600 relative transform scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-600 text-white text-sm font-semibold px-4 py-1 rounded-full">MOST POPULAR</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">$149</span>
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
                    <a href="/pricing" class="block w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-center font-medium">
                        Get Started
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-gray-200">
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
                    <a href="/pricing" class="block w-full px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- Testimonials Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Trusted by Manufacturing Leaders</h2>
                <p class="text-xl text-gray-600">See what our customers have to say</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-xl p-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <p class="text-gray-700 mb-6">"Zap ERP transformed our production workflow. We've seen a 40% increase in efficiency since implementation."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold mr-3">JD</div>
                        <div>
                            <div class="font-semibold text-gray-900">John Doe</div>
                            <div class="text-sm text-gray-600">CEO, Manufacturing Co.</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <p class="text-gray-700 mb-6">"The best ERP solution we've used. Intuitive interface and powerful features that actually work for manufacturers."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold mr-3">SM</div>
                        <div>
                            <div class="font-semibold text-gray-900">Sarah Miller</div>
                            <div class="text-sm text-gray-600">Operations Director</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                        <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <p class="text-gray-700 mb-6">"Outstanding support team and continuous improvements. This platform grows with our business needs."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white font-bold mr-3">RK</div>
                        <div>
                            <div class="font-semibold text-gray-900">Robert Kim</div>
                            <div class="text-sm text-gray-600">Plant Manager</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg hero-pattern">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Manufacturing?</h2>
            <p class="text-xl text-blue-100 mb-8">Join hundreds of companies already using Zap ERP to streamline their operations.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/pricing" class="px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition-colors font-semibold">
                    <i class="fas fa-rocket mr-2"></i>Start Free Trial
                </a>
                <a href="/login" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg hover:bg-white/10 transition-colors font-semibold">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </a>
            </div>
            <p class="text-blue-100 mt-6 text-sm">No credit card required • 14-day free trial • Cancel anytime</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-industry text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-white">Zap ERP</span>
                    </div>
                    <p class="text-sm">Complete ERP solution for modern manufacturing enterprises.</p>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Integrations</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Docs</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#about" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm">© 2024 Zap ERP Systems. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="hover:text-white transition-colors"><i class="fab fa-twitter text-xl"></i></a>
                    <a href="#" class="hover:text-white transition-colors"><i class="fab fa-linkedin text-xl"></i></a>
                    <a href="#" class="hover:text-white transition-colors"><i class="fab fa-github text-xl"></i></a>
                    <a href="#" class="hover:text-white transition-colors"><i class="fab fa-youtube text-xl"></i></a>
                </div>
            </div>
        </div>
    </footer>

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
    </script>
</body>
</html>
