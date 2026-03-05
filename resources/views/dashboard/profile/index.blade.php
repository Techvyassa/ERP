@extends('dashboard.layouts.app')

@section('title', 'Organization Profile')
@section('page-title', 'Organization Profile')

@section('content')
<div x-data="profileData()" x-init="init()">
    <!-- Profile Overview -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Organization Profile</h2>
                <p class="text-gray-600 mt-1">Manage your organization details and settings</p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-primary" x-text="completion.percentage + '%'">0%</div>
                <p class="text-sm text-gray-600">Complete</p>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-primary to-blue-600 h-3 rounded-full transition-all duration-500"
                 :style="`width: ${completion.percentage}%`"></div>
        </div>
    </div>

    <!-- Profile Form -->
    <form @submit.prevent="saveProfile">
        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Organization Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="formData.org_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Primary Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" x-model="formData.primary_email" disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary Phone</label>
                        <input type="tel" x-model="formData.primary_phone"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="bg-white rounded-xl shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Address Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                        <input type="text" x-model="formData.address_line1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                        <input type="text" x-model="formData.address_line2"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <input type="text" x-model="formData.city"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                            <input type="text" x-model="formData.state"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" x-model="formData.postal_code"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <a href="/dashboard" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Back to Dashboard
            </a>
            <button type="submit" :disabled="saving"
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-900 transition-colors disabled:opacity-50">
                <span x-show="!saving">Save Profile</span>
                <span x-show="saving">Saving...</span>
            </button>
        </div>
    </form>
</div>

<script>
function profileData() {
    return {
        completion: { percentage: 0 },
        formData: {},
        saving: false,

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = localStorage.getItem('org_slug');
                
                const response = await fetch('/api/v1/profile-completion/status', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.completion = data.data;
                }
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async saveProfile() {
            this.saving = true;
            try {
                const token = localStorage.getItem('access_token');
                const orgSlug = localStorage.getItem('org_slug');
                
                const response = await fetch('/api/v1/profile-completion/organization', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Org-Slug': orgSlug
                    },
                    body: JSON.stringify(this.formData)
                });
                
                if (response.ok) {
                    alert('Profile updated successfully!');
                    await this.loadData();
                } else {
                    alert('Failed to update profile');
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
