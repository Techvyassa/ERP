@extends('tenant.layouts.app')

@section('title', 'Master Data Setup')
@section('page-title', 'Master Data Setup')

@section('content')
    <div x-data="masterSetupData()" x-init="init()">
        <!-- Progress Overview -->
        <div class="bg-white rounded-xl shadow mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Master Data Setup</h2>
                    <p class="text-gray-600 mt-1">Configure essential master data to start using the system</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold text-blue-600" x-text="status.percentage + '%'"></div>
                    <p class="text-sm text-gray-600">Complete</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-4 rounded-full transition-all duration-500"
                     :style="`width: ${status.percentage}%`"></div>
            </div>
            
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    <span x-text="status.setup_count"></span> of <span x-text="status.total_count"></span> masters configured
                </span>
                <span x-show="status.is_complete" class="text-green-600 font-medium">
                    <i class="fas fa-check-circle mr-1"></i>All Masters Setup!
                </span>
            </div>
        </div>

        <!-- Master Data Groups -->
        <template x-for="group in status.groups" :key="group.name">
            <div class="bg-white rounded-xl shadow mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="group.name + ' Masters'"></h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="master in group.masters" :key="master.key">
                            <div class="border-2 rounded-lg p-4 transition-all hover:shadow-md"
                                 :class="master.is_setup ? 'border-green-200 bg-green-50' : 'border-gray-200 hover:border-blue-300'">
                                <div class="flex items-start justify-between mb-3">
                                    <div :class="`w-12 h-12 rounded-lg flex items-center justify-center bg-${master.color}-100`">
                                        <i :class="`fas fa-${master.icon} text-${master.color}-600 text-xl`"></i>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span x-show="master.critical" class="px-2 py-1 bg-red-100 text-red-600 text-xs font-medium rounded">
                                            Critical
                                        </span>
                                        <span x-show="master.is_setup" class="text-green-600">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <h4 class="font-semibold text-gray-900 mb-1" x-text="master.name"></h4>
                                <p class="text-xs text-gray-600 mb-3" x-text="master.description"></p>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium" :class="master.is_setup ? 'text-green-600' : 'text-gray-500'">
                                        <span x-text="master.count"></span> records
                                    </span>
                                    <button @click="openMaster(master)" 
                                            class="px-3 py-1 text-sm font-medium rounded-lg transition-colors"
                                            :class="master.is_setup ? 'bg-blue-100 text-blue-600 hover:bg-blue-200' : 'bg-blue-600 text-white hover:bg-blue-700'">
                                        <span x-text="master.is_setup ? 'Manage' : 'Setup'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <!-- Back Button -->
        <div class="flex justify-start">
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/dashboard' : '/org/' . $organization->org_slug . '/dashboard') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        function masterSetupData() {
            return {
                status: { percentage: 0, groups: [] },

                async init() {
                    await this.loadStatus();
                },

                async loadStatus() {
                    try {
                        const token = localStorage.getItem('access_token');
                        const response = await fetch('/api/v1/profile-completion/master-data-status', {
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.status = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to load master data status:', error);
                    }
                },

                openMaster(master) {
                    // Map master keys to routes
                    const tenantType = '{{ request()->get("tenant_type") }}';
                    const orgSlug = '{{ $organization->org_slug }}';
                    const baseUrl = tenantType === 'subdomain' ? '' : `/org/${orgSlug}`;
                    
                    const routeMap = {
                        // Organization
                        'departments': `${baseUrl}/departments`,
                        'roles': `${baseUrl}/roles`,
                        'users': `${baseUrl}/users`,
                        'zones': `${baseUrl}/zones`,
                        'approval_matrix': `${baseUrl}/approval-matrix`,
                        
                        // Inventory
                        'uom': `${baseUrl}/uom`,
                        'materials': `${baseUrl}/materials`,
                        'products': `${baseUrl}/products`,
                        'warehouses': `${baseUrl}/warehouses`,
                        'bin_locations': `${baseUrl}/bin-locations`,
                        
                        // Tax
                        'hsn_codes': `${baseUrl}/hsn-codes`,
                        'gst_taxes': `${baseUrl}/gst-taxes`,
                        'currency': `${baseUrl}/currency`,
                        
                        // Vendor
                        'vendors': `${baseUrl}/vendors`,
                        'vendor_contacts': `${baseUrl}/vendor-contacts`,
                        'vendor_material_map': `${baseUrl}/vendor-material-map`,
                        
                        // BOM
                        'bom_header': `${baseUrl}/bom`,
                        'bom_detail': `${baseUrl}/bom-detail`
                    };
                    
                    const route = routeMap[master.key];
                    if (route) {
                        window.location.href = route;
                    } else {
                        alert(`${master.name} management page coming soon!`);
                    }
                }
            }
        }
    </script>
@endsection
