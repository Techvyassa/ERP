<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Organization Setup - Zap ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-zap text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-semibold text-gray-900">Zap ERP</span>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="/dashboard" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="/setup/organization" class="text-blue-600 font-medium">Setup</a>
                </nav>
            </div>
            <div class="flex items-center space-x-6">
                <button class="text-gray-600 hover:text-gray-900">
                    <i class="far fa-bell text-xl"></i>
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
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Organization Setup</h1>
            <p class="text-gray-600">Configure your departments, roles, and master data to get started</p>
        </div>

        <!-- Setup Progress -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Setup Progress</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <span class="text-gray-900">Organization Created</span>
                    </div>
                    <span class="text-sm text-green-600 font-medium">Complete</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-building text-blue-600"></i>
                        </div>
                        <span class="text-gray-900">Departments Setup</span>
                    </div>
                    <span class="text-sm text-gray-600">0 departments</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-tag text-gray-400"></i>
                        </div>
                        <span class="text-gray-900">Roles & Permissions</span>
                    </div>
                    <span class="text-sm text-gray-600">0 roles</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-gray-400"></i>
                        </div>
                        <span class="text-gray-900">Team Members</span>
                    </div>
                    <span class="text-sm text-gray-600">1 user</span>
                </div>
            </div>
        </div>

        <!-- Setup Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Departments -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Departments</h3>
                    <button onclick="openDepartmentModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i>Add Department
                    </button>
                </div>
                <div id="departmentsList" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-building text-4xl mb-2"></i>
                        <p>No departments yet. Add your first department to get started.</p>
                    </div>
                </div>
            </div>

            <!-- Roles -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Roles & Permissions</h3>
                    <button onclick="openRoleModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i>Add Role
                    </button>
                </div>
                <div id="rolesList" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-user-tag text-4xl mb-2"></i>
                        <p>No roles yet. Create roles to manage permissions.</p>
                    </div>
                </div>
            </div>

            <!-- Users -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Team Members</h3>
                    <button onclick="openUserModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i>Invite User
                    </button>
                </div>
                <div id="usersList" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-2"></i>
                        <p>Invite team members to collaborate.</p>
                    </div>
                </div>
            </div>

            <!-- Master Data -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Master Data</h3>
                </div>
                <div class="space-y-3">
                    <button class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-boxes text-blue-600 text-xl"></i>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">Products & Materials</div>
                                <div class="text-sm text-gray-600">Manage inventory items</div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    
                    <button class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-truck text-blue-600 text-xl"></i>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">Suppliers & Vendors</div>
                                <div class="text-sm text-gray-600">Manage supplier database</div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    
                    <button class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-industry text-blue-600 text-xl"></i>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">Production Lines</div>
                                <div class="text-sm text-gray-600">Configure manufacturing lines</div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Setup Guide</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold">1</div>
                    <div>
                        <div class="font-medium text-gray-900">Create Departments</div>
                        <div class="text-sm text-gray-600">Organize your company structure</div>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold">2</div>
                    <div>
                        <div class="font-medium text-gray-900">Define Roles</div>
                        <div class="text-sm text-gray-600">Set up permissions and access</div>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold">3</div>
                    <div>
                        <div class="font-medium text-gray-900">Invite Team</div>
                        <div class="text-sm text-gray-600">Add users to your workspace</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Modal -->
    <div id="departmentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Add Department</h3>
            <form id="departmentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                    <input type="text" id="dept_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g. Production">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="dept_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Department description"></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeDepartmentModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Add Role</h3>
            <form id="roleForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                    <input type="text" id="role_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g. Production Manager">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="role_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Role description"></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeRoleModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Invite User</h3>
            <form id="userForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" id="user_email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="user@company.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select id="user_role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Role</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select id="user_department" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Department</option>
                    </select>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeUserModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/api/v1';
        const token = localStorage.getItem('auth_token');

        // Modal functions
        function openDepartmentModal() {
            document.getElementById('departmentModal').classList.remove('hidden');
        }
        function closeDepartmentModal() {
            document.getElementById('departmentModal').classList.add('hidden');
        }
        function openRoleModal() {
            document.getElementById('roleModal').classList.remove('hidden');
        }
        function closeRoleModal() {
            document.getElementById('roleModal').classList.add('hidden');
        }
        function openUserModal() {
            document.getElementById('userModal').classList.remove('hidden');
        }
        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        // Department form submission
        document.getElementById('departmentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                dept_name: document.getElementById('dept_name').value,
                description: document.getElementById('dept_description').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/departments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    closeDepartmentModal();
                    loadDepartments();
                }
            } catch (error) {
                console.error('Error creating department:', error);
            }
        });

        // Role form submission
        document.getElementById('roleForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                role_name: document.getElementById('role_name').value,
                description: document.getElementById('role_description').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/roles`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    closeRoleModal();
                    loadRoles();
                }
            } catch (error) {
                console.error('Error creating role:', error);
            }
        });

        // User form submission
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                email: document.getElementById('user_email').value,
                role_id: document.getElementById('user_role').value,
                dept_id: document.getElementById('user_department').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/users`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    closeUserModal();
                    loadUsers();
                }
            } catch (error) {
                console.error('Error inviting user:', error);
            }
        });

        // Load data functions
        async function loadDepartments() {
            // Implement API call to load departments
        }

        async function loadRoles() {
            // Implement API call to load roles
        }

        async function loadUsers() {
            // Implement API call to load users
        }

        // Load initial data
        loadDepartments();
        loadRoles();
        loadUsers();
    </script>
</body>
</html>
