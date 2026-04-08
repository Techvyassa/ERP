@extends('tenant.layouts.vendor')

@section('title', 'Vendors')
@section('page-title', 'Vendor Master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="vendorData()" x-init="loadData()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Master</h2>
                <p class="text-gray-600 mt-1">Manage suppliers and vendor information</p>
            </div>
            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors/create' : '/org/' . $organization->org_slug . '/vendors/create') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-shadow hover:shadow-lg inline-flex items-center">
                <span class="material-symbols-outlined text-sm mr-2">add</span>Add Vendor
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input="loadData"
                   placeholder="Search by code or name..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <select x-model="filters.vendor_type" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Types</option>
                <option value="SUPPLIER">Supplier</option>
                <option value="SERVICE">Service Provider</option>
                <option value="TRADER">Trader</option>
            </select>
            <select x-model="filters.is_approved" @change="loadData"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="1">Approved</option>
                <option value="0">Pending</option>
                <option value="blacklisted">Blacklisted</option>
            </select>
            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors inline-flex items-center justify-center">
                <span class="material-symbols-outlined text-sm mr-2">restart_alt</span>Reset
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor Info</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type & Category</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Financials</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rating & Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-10 h-10 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                                    <p class="text-gray-500 font-medium">Fetching vendors...</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-50 rounded-full mb-4">
                                    <span class="material-symbols-outlined text-3xl text-gray-300">handshake</span>
                                </div>
                                <p class="text-gray-600 font-medium">No vendors found matching your criteria</p>
                                <button @click="resetFilters" class="mt-2 text-blue-600 hover:underline text-sm font-medium">Clear all filters</button>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-blue-50/30 transition-colors group">
                            <!-- Vendor Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 font-bold text-xs">
                                        <span x-text="item.vendor_name.substring(0, 2).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900 leading-tight" x-text="item.vendor_name"></div>
                                        <div class="text-xs text-blue-600 font-mono mt-0.5" x-text="item.vendor_code"></div>
                                    </div>
                                </div>
                            </td>
                            <!-- Type & Category -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border"
                                          :class="{
                                              'bg-blue-50 text-blue-700 border-blue-100': item.vendor_type === 'SUPPLIER',
                                              'bg-emerald-50 text-emerald-700 border-emerald-100': item.vendor_type === 'SERVICE',
                                              'bg-amber-50 text-amber-700 border-amber-100': item.vendor_type === 'TRADER',
                                              'bg-slate-50 text-slate-700 border-slate-100': !['SUPPLIER', 'SERVICE', 'TRADER'].includes(item.vendor_type)
                                          }">
                                        <span class="material-symbols-outlined text-xs mr-1 opacity-60">label</span>
                                        <span x-text="item.vendor_type"></span>
                                    </span>
                                    <template x-if="item.msme_category">
                                        <span class="text-[10px] text-gray-500 italic">MSME: <span x-text="item.msme_category"></span></span>
                                    </template>
                                </div>
                            </td>
                            <!-- Financials -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs space-y-1">
                                    <div class="text-gray-900"><span class="text-gray-400 font-medium">GST:</span> <span x-text="item.gstin || 'N/A'"></span></div>
                                    <div class="text-gray-600"><span class="text-gray-400 font-medium">Terms:</span> <span x-text="item.payment_terms || 'Standard'"></span></div>
                                </div>
                            </td>
                            <!-- Rating & Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-0.5">
                                        <template x-for="i in 5">
                                            <span class="material-symbols-outlined text-[14px]" 
                                                  :class="i <= (parseFloat(item.rating_score)/20) ? 'text-amber-400' : 'text-gray-200'"
                                                  style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 20">star</span>
                                        </template>
                                        <span class="text-xs font-bold text-gray-700 ml-1" x-text="parseFloat(item.rating_score || 0).toFixed(1)"></span>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                              :class="item.is_approved ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'">
                                            <span class="material-symbols-outlined text-[10px] mr-1" style="font-variation-settings: 'FILL' 1">circle</span>
                                            <span x-text="item.is_approved ? 'Approved' : 'Pending Approval'"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewDetails(item)" class="p-2 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all" title="View Full Details">
                                        <span class="material-symbols-outlined text-xl">visibility</span>
                                    </button>
                                    <button @click="openEmailModal(item)" class="p-2 text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 rounded-lg transition-all" title="Send Direct Email">
                                        <span class="material-symbols-outlined text-xl">mail</span>
                                    </button>
                                    <button @click="edit(item)" class="p-2 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all" title="Edit Vendor">
                                        <span class="material-symbols-outlined text-xl">edit</span>
                                    </button>
                                    <template x-if="!item.blacklisted">
                                        <button @click="blockVendor(item)" class="p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 rounded-lg transition-all" title="Blacklist Vendor">
                                            <span class="material-symbols-outlined text-xl">block</span>
                                        </button>
                                    </template>
                                    <template x-if="item.blacklisted">
                                        <span class="p-2 text-red-600 rounded-lg bg-red-50" title="This vendor is blacklisted">
                                            <span class="material-symbols-outlined text-xl">warning</span>
                                        </span>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50/30 border-t border-gray-100 flex items-center justify-between">
            <div class="text-xs font-medium text-gray-500">
                Displaying <span class="text-gray-900" x-text="pagination.from"></span> - <span class="text-gray-900" x-text="pagination.to"></span> of <span class="text-gray-900" x-text="pagination.total"></span> records
            </div>
            <div class="flex items-center gap-2">
                <button @click="loadPage(pagination.current_page - 1)" 
                        :disabled="pagination.current_page === 1"
                        class="p-2 border border-gray-200 rounded-lg disabled:opacity-30 hover:bg-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <div class="px-3 py-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700">
                    <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                </div>
                <button @click="loadPage(pagination.current_page + 1)" 
                        :disabled="pagination.current_page === pagination.last_page"
                        class="p-2 border border-gray-200 rounded-lg disabled:opacity-30 hover:bg-white transition-colors flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Vendor Detail Modal -->
    <template x-if="viewModalOpen">
        <div class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="viewModalOpen = false" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-gray-50 border-b flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white mr-4 shadow-lg shadow-blue-200">
                                <span class="material-symbols-outlined text-2xl">account_balance</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900" x-text="selectedItem?.vendor_name"></h3>
                                <p class="text-sm text-blue-600 font-mono tracking-tight" x-text="selectedItem?.vendor_code"></p>
                            </div>
                        </div>
                        <button @click="viewModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <!-- Left: Main Details -->
                            <div class="md:col-span-2 space-y-8">
                                <section>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center text-balance">
                                        <span class="material-symbols-outlined text-sm mr-2">info</span> General Information
                                    </h4>
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
                                        <div>
                                            <label class="text-gray-500 block mb-1">Vendor Type</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.vendor_type"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">MSME Category</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.msme_category || 'Not Specified'"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">GST Number</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.gstin || 'None'"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">PAN Number</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.pan_number || 'None'"></p>
                                        </div>
                                    </div>
                                </section>

                                <section>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center text-balance">
                                        <span class="material-symbols-outlined text-sm mr-2">account_balance_wallet</span> Financial & Compliance
                                    </h4>
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
                                        <div>
                                            <label class="text-gray-500 block mb-1">Payment Terms</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.payment_terms"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">Credit Days</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.credit_days + ' Days'"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">Currency</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.currency?.currency_code + ' - ' + selectedItem?.currency?.currency_name"></p>
                                        </div>
                                        <div>
                                            <label class="text-gray-500 block mb-1">INCO Terms</label>
                                            <p class="font-bold text-gray-900" x-text="selectedItem?.delivery_terms || 'N/A'"></p>
                                        </div>
                                    </div>
                                </section>

                                <section>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center text-balance">
                                        <span class="material-symbols-outlined text-sm mr-2">groups</span> Contact Persons
                                    </h4>
                                    <div class="space-y-3">
                                        <template x-for="c in selectedItem?.contacts" :key="c.id">
                                            <div class="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                                                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-blue-500 border shadow-sm mr-3">
                                                    <span class="material-symbols-outlined text-lg" x-text="c.is_primary ? 'badge' : 'person'"></span>
                                                </div>
                                                <div class="flex-grow">
                                                    <div class="text-sm font-bold text-gray-900" x-text="c.contact_name"></div>
                                                    <div class="text-[10px] text-gray-500 uppercase font-semibold" x-text="c.contact_type"></div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-[11px] font-medium text-gray-700" x-text="c.phone || 'No Phone'"></div>
                                                    <div class="text-[11px] text-blue-600 hover:underline" x-text="c.email || 'No Email'"></div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </section>
                            </div>

                            <!-- Right: Badges & Banking -->
                            <div class="space-y-6">
                                <div class="p-6 bg-blue-50 rounded-2xl border border-blue-100">
                                    <h4 class="text-xs font-bold text-blue-400 uppercase tracking-widest mb-4">Performance</h4>
                                    <div class="text-center mb-4">
                                        <div class="text-4xl font-black text-blue-900" x-text="parseFloat(selectedItem?.rating_score || 0).toFixed(1)"></div>
                                        <div class="text-[10px] text-blue-400 font-bold uppercase">Vendor Score</div>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600">Approval Status</span>
                                            <span class="font-bold" :class="selectedItem?.is_approved ? 'text-green-600' : 'text-orange-600'" x-text="selectedItem?.is_approved ? 'Approved' : 'Pending'"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600">Blacklisted</span>
                                            <span class="font-bold" :class="selectedItem?.blacklisted ? 'text-red-600' : 'text-slate-400'" x-text="selectedItem?.blacklisted ? 'Yes' : 'No'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Bank Details</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Bank Name</label>
                                            <p class="text-sm font-bold text-gray-900" x-text="selectedItem?.bank_name || '-'"></p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">Account Number</label>
                                            <p class="text-sm font-bold text-gray-900" x-text="selectedItem?.bank_account_no || '-'"></p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-400 font-bold uppercase block mb-1">IFSC Code</label>
                                            <p class="text-sm font-bold text-gray-900 font-mono" x-text="selectedItem?.ifsc_code || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-end gap-3">
                        <button @click="viewModalOpen = false" class="px-5 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 transition-colors">Close</button>
                        <button @click="edit(selectedItem); viewModalOpen = false" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition-shadow hover:shadow-lg shadow-blue-200">
                            Edit Vendor Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Send Email Modal -->
    <div x-show="emailModalOpen" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="emailModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden">
            
            <div class="px-8 py-6 border-b flex items-center justify-between bg-emerald-50/50">
                <div class="flex items-center gap-3 text-emerald-700">
                    <span class="material-symbols-outlined text-2xl">mail</span>
                    <div>
                        <h3 class="text-xl font-bold">Compose Email</h3>
                    </div>
                </div>
                <button @click="emailModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Subject</label>
                    <input type="text" x-model="emailForm.subject" placeholder="Enter email subject"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all font-medium text-gray-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Message Content</label>
                    <textarea x-model="emailForm.message" rows="6" placeholder="Type your message here..."
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all font-medium text-gray-700 resize-none"></textarea>
                </div>
            </div>

            <div class="px-8 py-6 bg-gray-50 border-t flex items-center justify-end gap-3">
                <button @click="emailModalOpen = false" class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                <template x-if="!emailForm.to_email">
                    <p class="text-xs text-red-500 font-medium mr-auto">
                        <span class="material-symbols-outlined text-sm align-middle">warning</span>
                        No email address found for this vendor's contacts.
                    </p>
                </template>
                <button @click="openMailClient" :disabled="!emailForm.to_email"
                        class="px-8 py-2.5 bg-emerald-600 text-white rounded-2xl text-sm font-bold hover:bg-emerald-700 transition-all hover:shadow-lg disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    <span>Open in Mail App</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function vendorData() {
    return {
        items: [],
        loading: false,
        viewModalOpen: false,
        emailModalOpen: false,
        selectedItem: null,
        emailForm: { id: null, subject: '', message: '', vendor_name: '', to_email: '' },
        sendingEmail: false,
        filters: { search: '', vendor_type: '', is_approved: '' },
        pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                
                if (this.filters.is_approved === 'blacklisted') {
                    params.append('blacklisted', '1');
                } else {
                    params.append('blacklisted', '0');
                }
                
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.vendor_type) params.append('vendor_type', this.filters.vendor_type);
                if (this.filters.is_approved !== '' && this.filters.is_approved !== null && this.filters.is_approved !== 'blacklisted') {
                    params.append('is_approved', this.filters.is_approved);
                }
                params.append('page', this.pagination.current_page);
                params.append('per_page', this.pagination.per_page);

                const response = await fetch(`/api/v1/vendors?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data || data.success !== true) {
                    throw new Error(data.message || 'Failed to load vendors');
                }

                this.items = (data && data.data && data.data.vendors) ? data.data.vendors : [];
                
                if (data && data.data && data.data.pagination) {
                    this.pagination = {
                        current_page: data.data.pagination.current_page,
                        last_page: data.data.pagination.last_page,
                        per_page: data.data.pagination.per_page,
                        total: data.data.pagination.total,
                        from: data.data.pagination.total > 0 ? ((data.data.pagination.current_page - 1) * data.data.pagination.per_page) + 1 : 0,
                        to: Math.min(data.data.pagination.current_page * data.data.pagination.per_page, data.data.pagination.total)
                    };
                }
            } catch (error) {
                console.error('Failed to load vendors:', error);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Failed to load vendors.', type: 'error' } }));
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        
        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadData();
            }
        },
        
        resetFilters() {
            this.filters = { search: '', vendor_type: '', is_approved: '' };
            this.pagination.current_page = 1;
            this.loadData();
        },
        
        viewDetails(item) {
            this.selectedItem = item;
            this.viewModalOpen = true;
        },
        
        edit(item) {
            const baseUrl = '{{ url(request()->get("tenant_type") === "subdomain" ? "/vendors" : "/org/" . $organization->org_slug . "/vendors") }}';
            window.location.href = `${baseUrl}/${item.id}/edit`;
        },
        
        blockVendor(item) {
            window.dispatchEvent(new CustomEvent('open-confirm', {
                detail: {
                    title: 'Block Vendor',
                    message: `Are you sure you want to block vendor "${item.vendor_name}"?\n\nThis action will prevent them from participating in new RFQs and POs.`,
                    confirmText: 'Block Vendor',
                    confirmColor: 'red',
                    onConfirm: async () => {
                        try {
                            const response = await fetch(`/api/v1/vendors/${item.id}`, {
                                method: 'DELETE',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            
                            const data = await response.json();
                            if (response.ok && data.success) {
                                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Vendor blocked successfully.', type: 'success' } }));
                                this.loadData();
                            } else {
                                throw new Error(data.message || 'Failed to block vendor.');
                            }
                        } catch (error) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message, type: 'error' } }));
                        }
                    }
                }
            }));
        },

        openEmailModal(item) {
            // Find primary contact email, fallback to first contact with email
            const primaryContact = (item.contacts || []).find(c => c.is_primary && c.email);
            const anyContact = (item.contacts || []).find(c => c.email);
            const toEmail = primaryContact?.email || anyContact?.email || '';

            this.emailForm = { 
                id: item.id, 
                to_email: toEmail,
                subject: `Important update from ERP — Vendor Panel`,
                message: `Dear ${item.vendor_name},\n\n`,
                vendor_name: item.vendor_name
            };
            this.emailModalOpen = true;
        },

        openMailClient() {
            const to = encodeURIComponent(this.emailForm.to_email);
            const subject = encodeURIComponent(this.emailForm.subject);
            const body = encodeURIComponent(this.emailForm.message);
            const mailtoLink = `mailto:${to}?subject=${subject}&body=${body}`;
            window.open(mailtoLink, '_blank');
            this.emailModalOpen = false;
        },

        async sendDirectEmail() {
            // Kept for API-based sending if needed in future
            if (!this.emailForm.subject || !this.emailForm.message) {
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Subject and Message are required.', type: 'error' } }));
                return;
            }
            this.openMailClient();
        }
    }
}
</script>
@endsection
