<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Fabricate ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/api-client.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-industry text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-semibold text-gray-900">Fabricate ERP</span>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="/dashboard" class="text-blue-600 font-medium">Dashboard</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Production</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Inventory</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Reports</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Settings</a>
                </nav>
            </div>
            <div class="flex items-center space-x-6">
                <button class="text-gray-600 hover:text-gray-900 relative">
                    <i class="far fa-bell text-xl"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                </button>
                <button class="text-gray-600 hover:text-gray-900">
                    <i class="far fa-question-circle text-xl"></i>
                </button>
                <div class="flex items-center space-x-2 cursor-pointer">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">AD</span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-600 text-sm"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-8 mb-8 text-white">
            <h1 class="text-3xl font-bold mb-2">Welcome to Your Dashboard! 👋</h1>
            <p class="text-blue-100 mb-4">Your manufacturing workspace is ready. Start by exploring the features below.</p>
            <button class="px-6 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-play mr-2"></i>Take a Quick Tour
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+12%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Total Users</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cogs text-green-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+8%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Active Orders</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-red-600 text-sm font-semibold">-3%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Inventory Items</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+15%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">$0</h3>
                <p class="text-gray-600 text-sm">Revenue</p>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-4">
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-user-plus text-3xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add User</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-green-600 hover:bg-green-50 transition-colors">
                        <i class="fas fa-plus-circle text-3xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">New Order</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-yellow-600 hover:bg-yellow-50 transition-colors">
                        <i class="fas fa-building text-3xl text-yellow-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add Department</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-purple-600 hover:bg-purple-50 transition-colors">
                        <i class="fas fa-file-alt text-3xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Generate Report</span>
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Organization setup completed</p>
                            <p class="text-xs text-gray-600">Just now</p>
                        </div>
                    </div>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p class="text-sm">No recent activity yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
