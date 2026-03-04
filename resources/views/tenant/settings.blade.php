@extends('tenant.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Organization Settings')

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Organization Information -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Organization Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                        <input type="text" 
                               value="{{ $organization->org_name }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization Slug</label>
                        <input type="text" 
                               value="{{ $organization->org_slug }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary Email</label>
                        <input type="email" 
                               value="{{ $organization->primary_email }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary Phone</label>
                        <input type="tel" 
                               value="{{ $organization->primary_phone ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <div class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($organization->registration_status === 'ACTIVE') bg-green-100 text-green-800
                                @elseif($organization->registration_status === 'PENDING') bg-yellow-100 text-yellow-800
                                @elseif($organization->registration_status === 'SUSPENDED') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $organization->registration_status }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Users</label>
                        <input type="number" 
                               value="{{ $organization->max_users }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Information -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Location Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                        <input type="text" 
                               value="{{ $organization->address_line1 ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                        <input type="text" 
                               value="{{ $organization->address_line2 ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <input type="text" 
                               value="{{ $organization->city ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                        <input type="text" 
                               value="{{ $organization->state ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                        <input type="text" 
                               value="{{ $organization->postal_code ?? 'N/A' }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <input type="text" 
                               value="{{ $organization->country_code }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Regional Settings -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Regional Settings</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                        <input type="text" 
                               value="{{ $organization->timezone }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                        <input type="text" 
                               value="{{ $organization->currency_code }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Information -->
        <div class="bg-white rounded-xl shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Technical Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tenant Database</label>
                        <input type="text" 
                               value="{{ $organization->tenant_db_name }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization ID</label>
                        <input type="text" 
                               value="{{ $organization->org_id }}"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Note -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                <div>
                    <p class="text-sm text-blue-900 font-medium">Settings are read-only</p>
                    <p class="text-sm text-blue-700 mt-1">To modify organization settings, please contact your system administrator.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
