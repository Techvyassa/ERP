@extends('tenant.layouts.app')

@section('title', 'Profile Completion')
@section('page-title', 'Complete Your Profile')

@section('content')
    <div x-data="profileCompletionData()" x-init="init()">
        <!-- Progress Overview -->
        <div class="bg-white rounded-xl shadow mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Profile Completion</h2>
                    <p class="text-gray-600 mt-1">Complete your organization profile to unlock all features</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold text-blue-600" x-text="completion.percentage + '%'"></div>
                    <p class="text-sm text-gray-600">Complete</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-4 rounded-full transition-all duration-500"
                     :style="`width: ${completion.percentage}%`"></div>
            </div>
            
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    <span x-text="completion.completed_fields"></span> of <span x-text="completion.total_fields"></span> fields completed
                </span>
                <span x-show="completion.is_complete" class="text-green-600 font-medium">
                    <i class="fas fa-check-circle mr-1"></i>Profile Complete!
                </span>
            </div>
        </div>

        <!-- Organization Profile Form -->
        <form @submit.prevent="saveProfile">
            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                        <p class="text-sm text-gray-600 mt-1">Essential organization details</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                             :class="completion.sections?.basic_info?.percentage === 100 ? 'bg-green-100' : 'bg-gray-100'">
                            <span class="font-semibold text-sm"
                                  :class="completion.sections?.basic_info?.percentage === 100 ? 'text-green-600' : 'text-gray-600'"
                                  x-text="completion.sections?.basic_info?.percentage + '%'"></span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Organization Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="formData.org_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Primary Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" x-model="formData.primary_email" disabled
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Primary Phone
                            </label>
                            <input type="tel" x-model="formData.primary_phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
                        <p class="text-sm text-gray-600 mt-1">Physical location details</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                             :class="completion.sections?.address?.percentage === 100 ? 'bg-green-100' : 'bg-gray-100'">
                            <span class="font-semibold text-sm"
                                  :class="completion.sections?.address?.percentage === 100 ? 'text-green-600' : 'text-gray-600'"
                                  x-text="completion.sections?.address?.percentage + '%'"></span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                            <input type="text" x-model="formData.address_line1"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                            <input type="text" x-model="formData.address_line2"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" x-model="formData.city"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                                <input type="text" x-model="formData.state"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" x-model="formData.postal_code"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country Code</label>
                            <select x-model="formData.country_code"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="IN">India (IN)</option>
                                <option value="US">United States (US)</option>
                                <option value="GB">United Kingdom (GB)</option>
                                <option value="AE">United Arab Emirates (AE)</option>
                                <option value="SG">Singapore (SG)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regional Settings -->
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Regional Settings</h3>
                        <p class="text-sm text-gray-600 mt-1">Timezone and currency preferences</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                             :class="completion.sections?.regional?.percentage === 100 ? 'bg-green-100' : 'bg-gray-100'">
                            <span class="font-semibold text-sm"
                                  :class="completion.sections?.regional?.percentage === 100 ? 'text-green-600' : 'text-gray-600'"
                                  x-text="completion.sections?.regional?.percentage + '%'"></span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select x-model="formData.timezone"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                                <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                            <select x-model="formData.currency_code"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="INR">Indian Rupee (INR)</option>
                                <option value="USD">US Dollar (USD)</option>
                                <option value="GBP">British Pound (GBP)</option>
                                <option value="AED">UAE Dirham (AED)</option>
                                <option value="SGD">Singapore Dollar (SGD)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center">
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/dashboard' : '/org/' . $organization->org_slug . '/dashboard') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Back to Dashboard
                </a>
                <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <span x-show="!saving">Save Profile</span>
                    <span x-show="saving">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function profileCompletionData() {
            return {
                completion: { percentage: 0, sections: {} },
                formData: {},
                saving: false,

                async init() {
                    await this.loadCompletion();
                    this.loadFormData();
                },

                async loadCompletion() {
                    try {
                        const token = localStorage.getItem('access_token');
                        const response = await fetch('/api/v1/profile-completion/status', {
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.completion = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to load completion:', error);
                    }
                },

                loadFormData() {
                    this.formData = {
                        org_name: '{{ $organization->org_name }}',
                        primary_email: '{{ $organization->primary_email }}',
                        primary_phone: '{{ $organization->primary_phone }}' || '',
                        address_line1: '{{ $organization->address_line1 }}' || '',
                        address_line2: '{{ $organization->address_line2 }}' || '',
                        city: '{{ $organization->city }}' || '',
                        state: '{{ $organization->state }}' || '',
                        postal_code: '{{ $organization->postal_code }}' || '',
                        country_code: '{{ $organization->country_code }}',
                        timezone: '{{ $organization->timezone }}',
                        currency_code: '{{ $organization->currency_code }}'
                    };
                },

                async saveProfile() {
                    this.saving = true;
                    try {
                        const token = localStorage.getItem('access_token');
                        const response = await fetch('/api/v1/profile-completion/organization', {
                            method: 'PUT',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.formData)
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok) {
                            this.completion = data.data.completion;
                            alert('Profile updated successfully!');
                            
                            if (this.completion.percentage === 100) {
                                window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/dashboard" : "/org/" . $organization->org_slug . "/dashboard") }}';
                            }
                        } else {
                            alert('Failed to update profile: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Failed to save profile:', error);
                        alert('Failed to save profile. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }
    </script>
@endsection
