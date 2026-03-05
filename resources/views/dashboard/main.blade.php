<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Zap ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
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
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 font-display">
    <!-- Top Navigation -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <div class="bg-primary p-1.5 rounded-lg">
                        <span class="material-symbols-outlined text-white text-xl">precision_manufacturing</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900" id="orgName">Loading...</h1>
                        <p class="text-xs text-gray-500">Manufacturing ERP</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <button class="text-gray-600 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <button class="text-gray-600 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">help</span>
                    </button>
                    <div class="relative">
                        <button class="flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors" onclick="toggleUserMenu()">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">person</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900" id="userName">User</span>
                            <span class="material-symbols-outlined text-gray-600 text-sm">expand_more</span>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-bold text-gray-900" id="menuUserName">User Name</p>
                                <p class="text-xs text-gray-500" id="menuUserEmail">user@example.com</p>
                            </div>
                            
                            <div class="py-2">
                                <a href="#" onclick="navigateTo('profile'); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <span class="material-symbols-outlined text-gray-600">person</span>
                                    <span>My Profile</span>
                                </a>
                                <a href="#" onclick="alert('Settings coming soon'); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <span class="material-symbols-outlined text-gray-600">settings</span>
                                    <span>Settings</span>
                                </a>
                                <a href="#" onclick="alert('Notifications coming soon'); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <span class="material-symbols-outlined text-gray-600">notifications</span>
                                    <span>Notifications</span>
                                </a>
                                <a href="#" onclick="alert('Help Center coming soon'); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <span class="material-symbols-outlined text-gray-600">help</span>
                                    <span>Help Center</span>
                                </a>
                            </div>
                            
                            <div class="border-t border-gray-100 py-2">
                                <button onclick="logout()" class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <span class="material-symbols-outlined">logout</span>
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Profile Completion Banner -->
    <div id="completionBanner" class="hidden bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 flex-1">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <span class="material-symbols-outlined text-blue-600">info</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-gray-900">Complete Your Profile Setup</h3>
                        <p class="text-xs text-gray-600">Finish setting up your organization profile and master data to unlock all features</p>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="flex-1 max-w-md">
                                <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                    <span>Overall Progress</span>
                                    <span id="overallPercentage" class="font-bold">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="overallProgress" class="bg-primary h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button onclick="dismissBanner()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, <span id="welcomeName">User</span>!</h2>
            <p class="text-gray-600">Here's what's happening with your manufacturing operations today.</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-blue-600">inventory_2</span>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">Live</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-sm text-gray-600">Active Materials</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-green-600">factory</span>
                    </div>
                    <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Active</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-sm text-gray-600">Production Orders</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-amber-100 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-amber-600">handshake</span>
                    </div>
                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">Approved</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-sm text-gray-600">Active Vendors</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-purple-600">groups</span>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Team</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-sm text-gray-600">Team Members</p>
            </div>
        </div>

        <!-- Main Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Go to Tenant Dashboard Card (Featured) -->
            <div class="bg-gradient-to-br from-primary to-blue-700 rounded-xl border-2 border-primary shadow-xl cursor-pointer group hover:shadow-2xl transition-all" onclick="navigateTo('dashboard')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-white/20 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">dashboard</span>
                        </div>
                        <span class="material-symbols-outlined text-white group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Go to Dashboard</h3>
                    <p class="text-sm text-white/90 mb-4">Access your organization dashboard with full navigation</p>
                    <div class="flex items-center gap-2 text-white/80 text-xs">
                        <span class="material-symbols-outlined text-sm">info</span>
                        <span>Main workspace with sidebar navigation</span>
                    </div>
                </div>
            </div>
            
            <!-- Organization Profile Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('profile')"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-blue-500 to-primary p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">business</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Organization Profile</h3>
                    <p class="text-sm text-gray-600 mb-4">Manage company details, address, and settings</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div id="profileProgress" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        <span id="profilePercentage" class="text-xs font-bold text-gray-600">0%</span>
                    </div>
                </div>
            </div>

            <!-- Master Data Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('masters')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">database</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Master Data Setup</h3>
                    <p class="text-sm text-gray-600 mb-4">Configure materials, vendors, BOMs, and more</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div id="masterProgress" class="bg-emerald-500 h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        <span id="masterPercentage" class="text-xs font-bold text-gray-600">0%</span>
                    </div>
                </div>
            </div>

            <!-- Departments Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('departments')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">apartment</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Departments</h3>
                    <p class="text-sm text-gray-600 mb-4">Manage organizational departments and cost centers</p>
                    <div class="text-xs text-gray-500">Click to manage</div>
                </div>
            </div>

            <!-- Users & Roles Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('users')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">groups</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Users & Roles</h3>
                    <p class="text-sm text-gray-600 mb-4">Manage team members and access permissions</p>
                    <div class="text-xs text-gray-500">Click to manage</div>
                </div>
            </div>

            <!-- Production Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('production')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-orange-500 to-red-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">precision_manufacturing</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Production</h3>
                    <p class="text-sm text-gray-600 mb-4">Work orders, shop floor, and production tracking</p>
                    <div class="text-xs text-gray-500">Click to manage</div>
                </div>
            </div>

            <!-- Inventory Card -->
            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all cursor-pointer group" onclick="navigateTo('inventory')">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-br from-cyan-500 to-blue-600 p-3 rounded-xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-white text-2xl">inventory</span>
                        </div>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-primary transition-colors">arrow_forward</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Inventory</h3>
                    <p class="text-sm text-gray-600 mb-4">Stock management, warehouses, and transfers</p>
                    <div class="text-xs text-gray-500">Click to manage</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let profileCompletion = null;
        let masterDataStatus = null;

        // Check if user should be redirected
        function checkUserRedirect() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            const orgData = JSON.parse(localStorage.getItem('org_data') || '{}');
            const orgSlug = localStorage.getItem('org_slug');
            
            // Check if super admin
            const isSuperAdmin = user.email === 'admin@zaperp.com' || 
                               orgSlug === 'super-admin' ||
                               user.is_super_admin === true;
            
            if (isSuperAdmin) {
                // Redirect super admin to control panel
                window.location.href = '/control/dashboard';
                return true;
            }
            
            // For regular users, this dashboard is just a navigation hub
            // They can choose where to go
            return false;
        }

        // Load user and organization data
        async function loadDashboardData() {
            // Check if user should be redirected first
            if (checkUserRedirect()) {
                return; // Stop loading if redirecting
            }
            
            try {
                const user = JSON.parse(localStorage.getItem('user') || '{}');
                const orgSlug = localStorage.getItem('org_slug');
                const accessToken = localStorage.getItem('access_token');

                if (!accessToken || !orgSlug) {
                    window.location.href = '/login';
                    return;
                }

                // Update UI with user data
                document.getElementById('userName').textContent = user.first_name || 'User';
                document.getElementById('welcomeName').textContent = user.first_name || 'User';
                document.getElementById('menuUserName').textContent = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
                document.getElementById('menuUserEmail').textContent = user.email || 'user@example.com';

                // Fetch organization data from profile completion status
                const profileResponse = await fetch('/api/v1/profile-completion/status', {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });

                if (profileResponse.ok) {
                    const profileData = await profileResponse.json();
                    profileCompletion = profileData.data;
                    updateProfileProgress();
                    
                    // Try to get organization name from the response or localStorage
                    const orgData = JSON.parse(localStorage.getItem('org_data') || '{}');
                    const orgName = orgData.organization?.org_name || orgData.org_name || 'Organization';
                    document.getElementById('orgName').textContent = orgName;
                } else {
                    // Fallback to localStorage if API fails
                    const orgData = JSON.parse(localStorage.getItem('org_data') || '{}');
                    const orgName = orgData.organization?.org_name || orgData.org_name || 'Organization';
                    document.getElementById('orgName').textContent = orgName;
                }

                // Fetch master data status
                const masterResponse = await fetch('/api/v1/profile-completion/master-data-status', {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });

                if (masterResponse.ok) {
                    const masterData = await masterResponse.json();
                    masterDataStatus = masterData.data;
                    updateMasterProgress();
                }

                // Show completion banner if not complete
                updateCompletionBanner();

            } catch (error) {
                console.error('Error loading dashboard data:', error);
                // Fallback to localStorage
                const orgData = JSON.parse(localStorage.getItem('org_data') || '{}');
                const orgName = orgData.organization?.org_name || orgData.org_name || 'Organization';
                document.getElementById('orgName').textContent = orgName;
            }
        }

        function updateProfileProgress() {
            if (!profileCompletion) return;
            
            const percentage = profileCompletion.percentage;
            document.getElementById('profileProgress').style.width = percentage + '%';
            document.getElementById('profilePercentage').textContent = percentage + '%';
        }

        function updateMasterProgress() {
            if (!masterDataStatus) return;
            
            const percentage = masterDataStatus.percentage;
            document.getElementById('masterProgress').style.width = percentage + '%';
            document.getElementById('masterPercentage').textContent = percentage + '%';
        }

        function updateCompletionBanner() {
            if (!profileCompletion || !masterDataStatus) return;

            const overallPercentage = Math.round((profileCompletion.percentage + masterDataStatus.percentage) / 2);
            
            if (overallPercentage < 100) {
                document.getElementById('completionBanner').classList.remove('hidden');
                document.getElementById('overallProgress').style.width = overallPercentage + '%';
                document.getElementById('overallPercentage').textContent = overallPercentage + '%';
            }
        }

        function dismissBanner() {
            document.getElementById('completionBanner').classList.add('hidden');
            localStorage.setItem('completion_banner_dismissed', 'true');
        }

        function navigateTo(section) {
            const orgSlug = localStorage.getItem('org_slug');
            
            if (!orgSlug) {
                alert('Organization not found. Please login again.');
                window.location.href = '/login';
                return;
            }
            
            const routes = {
                'dashboard': `/org/${orgSlug}/dashboard`,
                'profile': `/org/${orgSlug}/profile-completion`,
                'masters': `/org/${orgSlug}/master-setup`,
                'departments': `/org/${orgSlug}/departments`,
                'users': `/org/${orgSlug}/users`,
                'production': `/org/${orgSlug}/production`,
                'inventory': `/org/${orgSlug}/inventory`
            };
            
            if (routes[section]) {
                window.location.href = routes[section];
            } else {
                alert(`${section} page coming soon!`);
            }
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        function logout() {
            // Clear all stored data
            localStorage.removeItem('user');
            localStorage.removeItem('access_token');
            localStorage.removeItem('refresh_token');
            localStorage.removeItem('org_slug');
            localStorage.removeItem('firebase_uid');
            
            // Clear cookie by making a POST request to logout endpoint
            fetch('/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).finally(() => {
                // Redirect to login page
                window.location.href = '/login';
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('userMenu');
            const button = event.target.closest('button');
            
            if (!menu.contains(event.target) && (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserMenu') === -1)) {
                menu.classList.add('hidden');
            }
        });

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
