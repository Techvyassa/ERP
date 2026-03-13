@extends('layouts.procurement')

@section('title', 'Purchase Orders - ' . $organization->org_name)
@section('page-title', 'Purchase Orders')

@section('content')
<div x-data="purchaseOrdersData()" x-init="init()">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Purchase Orders</h2>
            <p class="text-gray-600">Manage and track all purchase orders</p>
        </div>
        <button @click="openCreateModal()" class="px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined">add</span>
            Create Purchase Order
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" x-model="filters.search" @input="loadPurchaseOrders()" 
                       placeholder="PO Number, Vendor..." 
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select x-model="filters.status" @change="loadPurchaseOrders()" 
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="PENDING_APPROVAL">Pending Approval</option>
                    <option value="APPROVED">Approved</option>
                    <option value="OPEN">Open</option>
                    <option value="PARTIAL">Partial</option>
                    <option value="CLOSED">Closed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor</label>
                <select x-model="filters.vendor" @change="loadPurchaseOrders()" 
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Vendors</option>
                    <template x-for="vendor in vendors" :key="vendor.id">
                        <option :value="vendor.id" x-text="vendor.name"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()" class="w-full px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Total Amount</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-right py-4 px-6 text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <template x-if="!loading && purchaseOrders.length === 0">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                No purchase orders found
                            </td>
                        </tr>
                    </template>
                    
                    <template x-for="po in purchaseOrders" :key="po.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-primary" x-text="po.po_number"></span>
                            </td>
                            <td class="py-4 px-6 text-gray-900" x-text="po.vendor ? po.vendor.vendor_name : 'N/A'"></td>
                            <td class="py-4 px-6 text-gray-600" x-text="formatDate(po.po_date)"></td>
                            <td class="py-4 px-6 font-bold text-gray-900" x-text="formatCurrency(po.grand_total || 0)"></td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold" 
                                      :class="getStatusClass(po.status)" x-text="po.status"></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button @click="viewPO(po.id)" class="text-primary hover:text-primary/80 mr-3">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button @click="editPO(po.id)" class="text-gray-600 hover:text-gray-800">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> results
            </div>
            <div class="flex gap-2">
                <button @click="previousPage()" :disabled="pagination.current_page === 1" 
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <button @click="nextPage()" :disabled="pagination.current_page === pagination.last_page" 
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" x-text="modalTitle"></h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <form @submit.prevent="savePO()" class="p-6 space-y-6">
                    <!-- PO Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor *</label>
                            <select x-model="form.vendor_id" @change="onVendorSelect()" required 
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select Vendor</option>
                                <template x-if="vendors.length === 0">
                                    <option value="" disabled>No vendors available - Please add vendors in Master Data</option>
                                </template>
                                <template x-for="vendor in vendors" :key="vendor.id">
                                    <option :value="vendor.id" x-text="vendor.vendor_code + ' - ' + vendor.vendor_name"></option>
                                </template>
                            </select>
                            <p class="text-xs text-gray-500 mt-1" x-show="vendors.length === 0">
                                <a href="{{ url("/org/{$organization->org_slug}/vendors") }}" class="text-primary hover:underline">Add vendors in Master Data →</a>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Currency *</label>
                            <input type="text" :value="form.currency_id ? 'Currency ID: ' + form.currency_id : 'Auto-filled from vendor'" readonly 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50"
                                   :class="{'border-red-300': !form.currency_id && form.vendor_id}">
                            <p class="text-xs text-red-600 mt-1" x-show="!form.currency_id && form.vendor_id">
                                Vendor must have a currency assigned
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor GSTIN</label>
                            <input type="text" x-model="form.vendor_gstin" readonly 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PO Date *</label>
                            <input type="date" x-model="form.po_date" required 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Expected Delivery Date</label>
                            <input type="date" x-model="form.expected_delivery_date" 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Terms</label>
                            <input type="text" x-model="form.payment_terms" placeholder="e.g., Net 30" 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Credit Days</label>
                            <input type="number" x-model="form.credit_days" readonly 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea x-model="form.notes" rows="3" 
                                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    
                    <!-- Line Items -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-bold text-gray-900">Line Items</h4>
                            <button type="button" @click="addLineItem()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                Add Item
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                            <template x-if="materials.length === 0">
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                    <p class="text-sm text-amber-800">
                                        <strong>No materials available.</strong> Please add materials in Master Data first.
                                        <a href="{{ url("/org/{$organization->org_slug}/materials") }}" class="text-primary hover:underline ml-2">Go to Materials →</a>
                                    </p>
                                </div>
                            </template>
                            
                            <template x-for="(item, index) in form.line_items" :key="index">
                                <div class="grid grid-cols-12 gap-3 items-start p-3 bg-gray-50 rounded-lg">
                                    <div class="col-span-3">
                                        <select x-model="item.material_id" @change="onMaterialSelect(index)" required 
                                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                            <option value="">Select Material</option>
                                            <template x-if="materials.length === 0">
                                                <option value="" disabled>No materials - Add in Master Data</option>
                                            </template>
                                            <template x-for="material in materials" :key="material.id">
                                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" x-model="item.material_type" placeholder="Type" readonly 
                                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-100">
                                    </div>
                                    <div class="col-span-1">
                                        <input type="number" x-model="item.ordered_qty" placeholder="Qty" required min="1" 
                                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                    </div>
                                    <div class="col-span-1">
                                        <input type="text" x-model="item.uom" placeholder="UOM" readonly 
                                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-100">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" x-model="item.unit_price" placeholder="Unit Price" required min="0" step="0.01" 
                                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" :value="(item.ordered_qty * item.unit_price).toFixed(2)" readonly 
                                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-100 font-semibold">
                                    </div>
                                    <div class="col-span-1 flex justify-end">
                                        <button type="button" @click="removeLineItem(index)" class="text-red-600 hover:text-red-800">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <div class="mt-4 flex justify-end">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Total Amount</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(calculateTotal())"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="closeModal()" class="px-6 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="saving" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50">
                            <span x-show="!saving">Save Purchase Order</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function purchaseOrdersData() {
    return {
        purchaseOrders: [],
        vendors: [],
        materials: [],
        loading: false,
        saving: false,
        showModal: false,
        modalTitle: 'Create Purchase Order',
        editingId: null,
        filters: {
            search: '',
            status: '',
            vendor: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },
        form: {
            vendor_id: '',
            vendor_gstin: '',
            vendor_name: '',
            currency_id: '',
            po_date: new Date().toISOString().split('T')[0],
            expected_delivery_date: '',
            payment_terms: '',
            credit_days: '',
            notes: '',
            line_items: []
        },
        
        async init() {
            await this.loadVendors();
            await this.loadMaterials();
            await this.loadPurchaseOrders();
        },
        
        async loadVendors() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/vendors?per_page=100', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('Vendors API response:', data);
                
                if (data.success && data.data && data.data.vendors) {
                    this.vendors = data.data.vendors;
                    console.log('Loaded vendors:', this.vendors.length);
                } else {
                    console.warn('No vendors found in response');
                    this.vendors = [];
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
                alert('Error loading vendors. Please check console for details.');
                this.vendors = [];
            }
        },
        
        async loadMaterials() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/materials?per_page=100&is_active=true', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('Materials API response:', data);
                
                if (data.success && data.data && data.data.materials) {
                    this.materials = data.data.materials;
                    console.log('Loaded materials:', this.materials.length);
                } else {
                    console.warn('No materials found in response');
                    this.materials = [];
                }
            } catch (error) {
                console.error('Error loading materials:', error);
                alert('Error loading materials. Please check console for details.');
                this.materials = [];
            }
        },
        
        async loadPurchaseOrders(page = 1) {
            this.loading = true;
            try {
                const token = localStorage.getItem('access_token');
                const params = new URLSearchParams({
                    page: page,
                    per_page: 15
                });
                
                // Only add filters if they have values
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.vendor) params.append('vendor_id', this.filters.vendor);
                
                const response = await fetch(`/api/v1/purchase-orders?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('Purchase Orders API response:', data);
                
                if (data.success && data.data) {
                    // The paginated data is directly in data.data (Laravel pagination structure)
                    this.purchaseOrders = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page || 1,
                        last_page: data.data.last_page || 1,
                        from: data.data.from || 0,
                        to: data.data.to || 0,
                        total: data.data.total || 0
                    };
                    console.log('Loaded purchase orders:', this.purchaseOrders.length);
                } else {
                    console.warn('No purchase orders found or API error:', data);
                    this.purchaseOrders = [];
                }
            } catch (error) {
                console.error('Error loading purchase orders:', error);
                alert('Error loading purchase orders. Please check console for details.');
                this.purchaseOrders = [];
            } finally {
                this.loading = false;
            }
        },
        
        openCreateModal() {
            this.modalTitle = 'Create Purchase Order';
            this.editingId = null;
            this.form = {
                vendor_id: '',
                vendor_gstin: '',
                vendor_name: '',
                currency_id: '',
                po_date: new Date().toISOString().split('T')[0],
                expected_delivery_date: '',
                payment_terms: '',
                credit_days: '',
                notes: '',
                line_items: [{ 
                    material_id: '', 
                    material_name: '', 
                    material_code: '', 
                    material_type: '', 
                    ordered_qty: 1, 
                    uom: '', 
                    uom_id: null,
                    unit_price: 0 
                }]
            };
            this.showModal = true;
            console.log('Create modal opened');
        },
        
        closeModal() {
            this.showModal = false;
            this.editingId = null;
            console.log('Modal closed');
        },
        
        onVendorSelect() {
            const vendorId = this.form.vendor_id;
            const vendor = this.vendors.find(v => v.id == vendorId);
            if (vendor) {
                console.log('Selected vendor:', vendor);
                this.form.vendor_name = vendor.vendor_name;
                this.form.vendor_gstin = vendor.gstin || '';
                this.form.payment_terms = vendor.payment_terms || '';
                this.form.credit_days = vendor.credit_days || '';
                // Set currency_id from vendor - this is REQUIRED
                this.form.currency_id = vendor.currency_id || '';
                
                if (!this.form.currency_id) {
                    console.warn('Warning: Vendor does not have a currency_id set. This is required for PO creation.');
                    alert('Warning: This vendor does not have a currency assigned. Please update the vendor master data to add a currency.');
                }
            }
        },
        
        addLineItem() {
            this.form.line_items.push({ 
                material_id: '', 
                material_name: '', 
                material_code: '', 
                material_type: '', 
                ordered_qty: 1, 
                uom: '', 
                uom_id: null,
                unit_price: 0 
            });
        },
        
        removeLineItem(index) {
            this.form.line_items.splice(index, 1);
        },
        
        onMaterialSelect(index) {
            const materialId = this.form.line_items[index].material_id;
            const material = this.materials.find(m => m.id == materialId);
            if (material) {
                console.log('Selected material:', material);
                this.form.line_items[index].material_name = material.material_name;
                this.form.line_items[index].material_code = material.material_code;
                this.form.line_items[index].material_type = material.material_type || '';
                
                // Get UOM from the material's uom relationship or uom_id
                if (material.uom) {
                    this.form.line_items[index].uom = material.uom.uom_code || material.uom.uom_name || '';
                    this.form.line_items[index].uom_id = material.uom.id;
                } else if (material.uom_id) {
                    // If uom relationship not loaded, use uom_id directly
                    this.form.line_items[index].uom_id = material.uom_id;
                    this.form.line_items[index].uom = 'UOM-' + material.uom_id;
                } else {
                    console.warn('Warning: Material does not have a UOM set');
                    this.form.line_items[index].uom = '';
                    this.form.line_items[index].uom_id = null;
                }
                
                console.log('Line item after material select:', this.form.line_items[index]);
            }
        },
        
        calculateTotal() {
            return this.form.line_items.reduce((sum, item) => {
                return sum + (parseFloat(item.ordered_qty || 0) * parseFloat(item.unit_price || 0));
            }, 0);
        },
        
        async savePO() {
            // Validate required fields before submission
            if (!this.form.vendor_id) {
                alert('Please select a vendor');
                return;
            }
            
            if (!this.form.currency_id) {
                alert('Currency is required. Please ensure the selected vendor has a currency assigned in Master Data.');
                return;
            }
            
            if (!this.form.po_date) {
                alert('PO Date is required');
                return;
            }
            
            if (this.form.line_items.length === 0) {
                alert('Please add at least one line item');
                return;
            }
            
            // Validate line items
            for (let i = 0; i < this.form.line_items.length; i++) {
                const item = this.form.line_items[i];
                if (!item.material_id) {
                    alert(`Line item ${i + 1}: Please select a material`);
                    return;
                }
                if (!item.ordered_qty || item.ordered_qty <= 0) {
                    alert(`Line item ${i + 1}: Please enter a valid quantity`);
                    return;
                }
                if (!item.uom_id) {
                    alert(`Line item ${i + 1}: UOM is missing. Please ensure the material has a UOM assigned in Master Data.`);
                    return;
                }
                if (!item.unit_price || item.unit_price < 0) {
                    alert(`Line item ${i + 1}: Please enter a valid unit price`);
                    return;
                }
            }
            
            this.saving = true;
            try {
                const token = localStorage.getItem('access_token');
                const url = this.editingId ? `/api/v1/purchase-orders/${this.editingId}` : '/api/v1/purchase-orders';
                const method = this.editingId ? 'PUT' : 'POST';
                
                // Prepare payload according to API requirements
                const payload = {
                    vendor_id: parseInt(this.form.vendor_id),
                    currency_id: parseInt(this.form.currency_id),
                    po_date: this.form.po_date,
                    expected_delivery: this.form.expected_delivery_date || null,
                    payment_terms: this.form.payment_terms || null,
                    credit_days: this.form.credit_days ? parseInt(this.form.credit_days) : null,
                    remarks: this.form.notes || null,
                    line_items: this.form.line_items.map(item => ({
                        material_id: parseInt(item.material_id),
                        ordered_qty: parseFloat(item.ordered_qty),
                        uom_id: parseInt(item.uom_id),
                        unit_price: parseFloat(item.unit_price),
                        material_description: item.material_name || null
                    }))
                };
                
                console.log('Submitting PO:', payload);
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                console.log('PO Response:', data);
                
                if (data.success) {
                    this.closeModal();
                    await this.loadPurchaseOrders();
                    alert('Purchase order saved successfully!');
                } else {
                    const errorMsg = data.message || 'Error saving purchase order';
                    const errorDetails = data.error?.details ? JSON.stringify(data.error.details, null, 2) : '';
                    console.error('API Error:', data);
                    alert(errorMsg + (errorDetails ? '\n\nDetails:\n' + errorDetails : ''));
                }
            } catch (error) {
                console.error('Error saving purchase order:', error);
                alert('Error saving purchase order: ' + error.message);
            } finally {
                this.saving = false;
            }
        },
        
        async viewPO(id) {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch(`/api/v1/purchase-orders/${id}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('PO Details:', data);
                
                if (data.success && data.data && data.data.purchase_order) {
                    const po = data.data.purchase_order;
                    
                    // Create a detailed view modal
                    let lineItemsHtml = '';
                    if (po.line_items && po.line_items.length > 0) {
                        lineItemsHtml = '<div class="overflow-x-auto"><table class="w-full mt-4 border border-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-bold text-gray-500">Material</th><th class="px-4 py-2 text-left text-xs font-bold text-gray-500">Qty</th><th class="px-4 py-2 text-left text-xs font-bold text-gray-500">UOM</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500">Unit Price</th><th class="px-4 py-2 text-right text-xs font-bold text-gray-500">Total</th></tr></thead><tbody>';
                        po.line_items.forEach(item => {
                            const materialName = (item.material && item.material.material_name) ? item.material.material_name : 'N/A';
                            const materialCode = (item.material && item.material.material_code) ? item.material.material_code : '';
                            const uomCode = (item.uom && item.uom.uom_code) ? item.uom.uom_code : 'N/A';
                            const qty = parseFloat(item.ordered_qty) || 0;
                            const price = parseFloat(item.unit_price) || 0;
                            const total = qty * price;
                            lineItemsHtml += `<tr class="border-t"><td class="px-4 py-2"><div class="font-semibold">${materialName}</div><div class="text-xs text-gray-500">${materialCode}</div></td><td class="px-4 py-2">${qty}</td><td class="px-4 py-2">${uomCode}</td><td class="px-4 py-2 text-right">${this.formatCurrency(price)}</td><td class="px-4 py-2 text-right font-semibold">${this.formatCurrency(total)}</td></tr>`;
                        });
                        lineItemsHtml += '</tbody></table></div>';
                    } else {
                        lineItemsHtml = '<p class="text-gray-500 mt-4">No line items</p>';
                    }
                    
                    const vendorName = (po.vendor && po.vendor.vendor_name) ? po.vendor.vendor_name : 'N/A';
                    const vendorCode = (po.vendor && po.vendor.vendor_code) ? po.vendor.vendor_code : '';
                    const currencyCode = (po.currency && po.currency.currency_code) ? po.currency.currency_code : 'N/A';
                    
                    const detailsHtml = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div><strong class="text-gray-700">PO Number:</strong> <span class="text-gray-900">${po.po_number || 'N/A'}</span></div>
                                <div><strong class="text-gray-700">Status:</strong> <span class="px-2 py-1 rounded text-xs ${this.getStatusClass(po.status)}">${po.status}</span></div>
                                <div><strong class="text-gray-700">Vendor:</strong> <span class="text-gray-900">${vendorName}</span> <span class="text-xs text-gray-500">${vendorCode}</span></div>
                                <div><strong class="text-gray-700">Currency:</strong> <span class="text-gray-900">${currencyCode}</span></div>
                                <div><strong class="text-gray-700">PO Date:</strong> <span class="text-gray-900">${po.po_date ? this.formatDate(po.po_date) : 'N/A'}</span></div>
                                <div><strong class="text-gray-700">Expected Delivery:</strong> <span class="text-gray-900">${po.expected_delivery ? this.formatDate(po.expected_delivery) : 'N/A'}</span></div>
                                <div><strong class="text-gray-700">Payment Terms:</strong> <span class="text-gray-900">${po.payment_terms || 'N/A'}</span></div>
                                <div><strong class="text-gray-700">Credit Days:</strong> <span class="text-gray-900">${po.credit_days || 'N/A'}</span></div>
                            </div>
                            ${po.remarks ? '<div class="pt-2"><strong class="text-gray-700">Remarks:</strong> <p class="text-gray-900 mt-1">' + po.remarks + '</p></div>' : ''}
                            <div class="pt-4 border-t"><strong class="text-gray-700">Line Items:</strong>${lineItemsHtml}</div>
                            <div class="text-right pt-4 border-t space-y-1">
                                <div class="text-sm text-gray-600">Subtotal: <span class="font-semibold">${this.formatCurrency(po.subtotal || 0)}</span></div>
                                ${po.discount_amount ? '<div class="text-sm text-gray-600">Discount: <span class="font-semibold">-' + this.formatCurrency(po.discount_amount) + '</span></div>' : ''}
                                ${po.freight_charges ? '<div class="text-sm text-gray-600">Freight: <span class="font-semibold">' + this.formatCurrency(po.freight_charges) + '</span></div>' : ''}
                                <div class="text-sm text-gray-600">Tax: <span class="font-semibold">${this.formatCurrency(po.tax_amount || 0)}</span></div>
                                <div class="text-lg font-bold text-gray-900 pt-2 border-t">Grand Total: ${this.formatCurrency(po.grand_total || 0)}</div>
                            </div>
                        </div>
                    `;
                    
                    // Show in a custom modal
                    const viewModal = document.createElement('div');
                    viewModal.className = 'fixed inset-0 z-50 overflow-y-auto';
                    viewModal.innerHTML = `
                        <div class="flex items-center justify-center min-h-screen px-4 py-8">
                            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
                            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-y-auto">
                                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                                    <h3 class="text-xl font-bold text-gray-900">Purchase Order Details</h3>
                                    <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>
                                <div class="p-6">${detailsHtml}</div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(viewModal);
                } else {
                    alert('Failed to load purchase order details');
                }
            } catch (error) {
                console.error('Error loading PO details:', error);
                alert('Error loading purchase order details: ' + error.message);
            }
        },
        
        async editPO(id) {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch(`/api/v1/purchase-orders/${id}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('PO to edit:', data);
                
                if (data.success && data.data && data.data.purchase_order) {
                    const po = data.data.purchase_order;
                    
                    // Check if PO is in DRAFT status (only DRAFT can be edited)
                    if (po.status !== 'DRAFT') {
                        alert(`Cannot edit purchase order. Only DRAFT purchase orders can be edited. Current status: ${po.status}`);
                        return;
                    }
                    
                    // Populate form with PO data
                    this.editingId = po.id;
                    this.modalTitle = 'Edit Purchase Order - ' + po.po_number;
                    
                    // Format dates for HTML date input (YYYY-MM-DD)
                    const formatDateForInput = (dateStr) => {
                        if (!dateStr) return '';
                        // If already in YYYY-MM-DD format, return as is
                        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
                        // Otherwise parse and format
                        const date = new Date(dateStr);
                        return date.toISOString().split('T')[0];
                    };
                    
                    this.form = {
                        vendor_id: po.vendor_id || '',
                        vendor_gstin: (po.vendor && po.vendor.gstin) ? po.vendor.gstin : '',
                        vendor_name: (po.vendor && po.vendor.vendor_name) ? po.vendor.vendor_name : '',
                        currency_id: po.currency_id || '',
                        po_date: formatDateForInput(po.po_date),
                        expected_delivery_date: formatDateForInput(po.expected_delivery),
                        payment_terms: po.payment_terms || '',
                        credit_days: po.credit_days || '',
                        notes: po.remarks || '',
                        line_items: []
                    };
                    
                    // Load line items
                    if (po.line_items && po.line_items.length > 0) {
                        this.form.line_items = po.line_items.map(item => ({
                            id: item.id || null,
                            material_id: item.material_id || '',
                            material_name: (item.material && item.material.material_name) ? item.material.material_name : '',
                            material_code: (item.material && item.material.material_code) ? item.material.material_code : '',
                            material_type: (item.material && item.material.material_type) ? item.material.material_type : '',
                            ordered_qty: parseFloat(item.ordered_qty) || 1,
                            uom: (item.uom && item.uom.uom_code) ? item.uom.uom_code : '',
                            uom_id: item.uom_id || null,
                            unit_price: parseFloat(item.unit_price) || 0
                        }));
                    } else {
                        this.form.line_items = [{ 
                            material_id: '', 
                            material_name: '', 
                            material_code: '', 
                            material_type: '', 
                            ordered_qty: 1, 
                            uom: '', 
                            uom_id: null,
                            unit_price: 0 
                        }];
                    }
                    
                    console.log('Form populated for editing:', this.form);
                    this.showModal = true;
                } else {
                    alert('Failed to load purchase order for editing');
                }
            } catch (error) {
                console.error('Error loading PO for edit:', error);
                alert('Error loading purchase order for editing: ' + error.message);
            }
        },
        
        resetFilters() {
            this.filters = { search: '', status: '', vendor: '' };
            this.loadPurchaseOrders();
        },
        
        previousPage() {
            if (this.pagination.current_page > 1) {
                this.loadPurchaseOrders(this.pagination.current_page - 1);
            }
        },
        
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.loadPurchaseOrders(this.pagination.current_page + 1);
            }
        },
        
        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(amount || 0);
        },
        
        getStatusClass(status) {
            const classes = {
                'DRAFT': 'bg-gray-100 text-gray-700',
                'PENDING_APPROVAL': 'bg-amber-100 text-amber-700',
                'APPROVED': 'bg-green-100 text-green-700',
                'OPEN': 'bg-blue-100 text-blue-700',
                'PARTIAL': 'bg-purple-100 text-purple-700',
                'CLOSED': 'bg-gray-100 text-gray-700',
                'CANCELLED': 'bg-red-100 text-red-700'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        }
    }
}
</script>
@endsection
