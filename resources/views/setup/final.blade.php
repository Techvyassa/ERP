<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Complete - Fabricate ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .checkmark-animate {
            animation: checkmark 0.6s ease-in-out;
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
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Subscription</div>
                            <div class="text-xs text-green-600">Completed</div>
                        </div>
                    </div>

                    <!-- Step 4: Final Setup -->
                    <div class="flex items-center space-x-3 bg-green-50 -mx-3 px-3 py-2 rounded-lg">
                        <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center checkmark-animate">
                            <i class="fas fa-check text-white text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Final Setup</div>
                            <div class="text-xs text-green-600">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-8">
                <div class="flex justify-between text-xs text-gray-600 mb-2">
                    <span>Wizard Progress</span>
                    <span class="font-semibold text-green-600">100%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                </div>
                <div class="text-xs text-green-600 mt-2 font-semibold">SETUP COMPLETE!</div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 flex items-center justify-center">
            <div class="max-w-2xl w-full text-center">
                <!-- Success Icon -->
                <div class="mb-8">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto checkmark-animate">
                        <i class="fas fa-check text-green-600 text-5xl"></i>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to Fabricate ERP!</h1>
                <p class="text-xl text-gray-600 mb-8">Your manufacturing enterprise workspace is ready. Let's get started with your journey.</p>

                <!-- Setup Summary -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-8 text-left">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Setup Summary</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Organization</span>
                            <span class="font-medium text-gray-900" id="orgName">Loading...</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Subscription Plan</span>
                            <span class="font-medium text-gray-900" id="planName">Loading...</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Database Status</span>
                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-circle text-xs mr-1"></i> Provisioned
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-gray-600">Account Status</span>
                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-circle text-xs mr-1"></i> Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Start Guide -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8 text-left">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                        Quick Start Guide
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                            <span>Invite team members to your workspace</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                            <span>Configure departments and roles</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                            <span>Set up your production workflows</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-1 mr-2"></i>
                            <span>Import your existing data</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button 
                        onclick="window.location.href='/dashboard'"
                        class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i>
                        Go to Dashboard
                    </button>
                    <button 
                        onclick="window.location.href='/help'"
                        class="px-8 py-4 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:bg-gray-50 transition-colors flex items-center justify-center">
                        <i class="fas fa-book mr-2"></i>
                        View Documentation
                    </button>
                </div>

                <!-- Support Info -->
                <div class="mt-8 text-sm text-gray-600">
                    <p>Need help getting started? <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Contact our support team</a></p>
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
        // Load organization and plan info
        function loadSetupInfo() {
            // Get from localStorage or API
            const orgData = localStorage.getItem('org_data');
            const selectedPlan = localStorage.getItem('selected_plan');
            
            if (orgData) {
                const org = JSON.parse(orgData);
                document.getElementById('orgName').textContent = org.org_name || 'Your Organization';
            }
            
            if (selectedPlan) {
                document.getElementById('planName').textContent = selectedPlan || 'Trial Plan';
            } else {
                document.getElementById('planName').textContent = '14-Day Trial';
            }
        }

        // Confetti effect (optional)
        function celebrate() {
            // Add confetti animation if desired
            console.log('🎉 Setup complete!');
        }

        // Load info on page load
        loadSetupInfo();
        celebrate();
    </script>
</body>
</html>
