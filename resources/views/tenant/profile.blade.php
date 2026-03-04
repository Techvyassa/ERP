@extends('tenant.layouts.app')

@section('title', 'Profile')
@section('page-title', 'My Profile')

@section('content')
    <div x-data="profileData()" x-init="init()">
        <div class="max-w-4xl mx-auto">
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-6">
                        <div class="w-24 h-24 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-3xl" x-text="getInitials()"></span>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900" x-text="user.first_name + ' ' + user.last_name"></h2>
                            <p class="text-gray-600" x-text="user.email"></p>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-id-badge mr-1"></i>
                                    <span x-text="user.employee_code || 'N/A'"></span>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-building mr-1"></i>
                                    Department ID: <span x-text="user.dept_id || 'N/A'"></span>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-user-shield mr-1"></i>
                                    Role ID: <span x-text="user.role_id || 'N/A'"></span>
                                </span>
                            </div>
                        </div>
                        <button @click="editMode = !editMode" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            <span x-text="editMode ? 'Cancel' : 'Edit Profile'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Profile Information</h3>
                    
                    <form @submit.prevent="saveProfile" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" 
                                       x-model="formData.first_name"
                                       :disabled="!editMode"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
                                       required>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" 
                                       x-model="formData.last_name"
                                       :disabled="!editMode"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500"
                                       required>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" 
                                       x-model="formData.email"
                                       disabled
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" 
                                       x-model="formData.phone"
                                       :disabled="!editMode"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50 disabled:text-gray-500">
                            </div>

                            <!-- Employee Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee Code</label>
                                <input type="text" 
                                       x-model="formData.employee_code"
                                       disabled
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                                <p class="text-xs text-gray-500 mt-1">Assigned by system</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <div class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                                    <span :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                          class="px-3 py-1 rounded-full text-sm font-medium">
                                        <i :class="user.is_active ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-1"></i>
                                        <span x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div x-show="editMode" class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                            <button type="button" 
                                    @click="cancelEdit"
                                    class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    :disabled="saving"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                                <span x-show="!saving">Save Changes</span>
                                <span x-show="saving">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="bg-white rounded-xl shadow mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h3>
                    
                    <form @submit.prevent="changePassword" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 max-w-md">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" 
                                       x-model="passwordData.current_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password" 
                                       x-model="passwordData.new_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       minlength="8"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" 
                                       x-model="passwordData.confirm_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                        </div>

                        <div class="flex justify-start">
                            <button type="submit" 
                                    :disabled="changingPassword"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                                <span x-show="!changingPassword">Change Password</span>
                                <span x-show="changingPassword">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Changing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function profileData() {
            return {
                user: {},
                formData: {},
                passwordData: {
                    current_password: '',
                    new_password: '',
                    confirm_password: ''
                },
                editMode: false,
                saving: false,
                changingPassword: false,

                init() {
                    this.user = JSON.parse(localStorage.getItem('user') || '{}');
                    this.formData = { ...this.user };
                },

                getInitials() {
                    if (this.user.first_name && this.user.last_name) {
                        return (this.user.first_name.charAt(0) + this.user.last_name.charAt(0)).toUpperCase();
                    }
                    return 'U';
                },

                cancelEdit() {
                    this.editMode = false;
                    this.formData = { ...this.user };
                },

                async saveProfile() {
                    this.saving = true;
                    try {
                        // TODO: Implement API call to update profile
                        console.log('Saving profile:', this.formData);
                        
                        // Simulate API call
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        
                        // Update localStorage
                        this.user = { ...this.formData };
                        localStorage.setItem('user', JSON.stringify(this.user));
                        
                        this.editMode = false;
                        alert('Profile updated successfully!');
                    } catch (error) {
                        console.error('Error saving profile:', error);
                        alert('Failed to update profile. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                },

                async changePassword() {
                    if (this.passwordData.new_password !== this.passwordData.confirm_password) {
                        alert('New passwords do not match!');
                        return;
                    }

                    this.changingPassword = true;
                    try {
                        // TODO: Implement API call to change password
                        console.log('Changing password');
                        
                        // Simulate API call
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        
                        // Reset form
                        this.passwordData = {
                            current_password: '',
                            new_password: '',
                            confirm_password: ''
                        };
                        
                        alert('Password changed successfully!');
                    } catch (error) {
                        console.error('Error changing password:', error);
                        alert('Failed to change password. Please try again.');
                    } finally {
                        this.changingPassword = false;
                    }
                }
            }
        }
    </script>
@endsection
