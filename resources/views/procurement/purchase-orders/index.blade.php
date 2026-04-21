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
        <button onclick="window.location.href='{{ url("/org/{$organization->org_slug}/procurement/purchase-orders/create") }}'" class="px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-all flex items-center gap-2">
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
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">PR Number</th>
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
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <template x-if="!loading && purchaseOrders.length === 0">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500">
                                No purchase orders found
                            </td>
                        </tr>
                    </template>
                    
                    <template x-for="po in purchaseOrders" :key="po.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-primary" x-text="po.po_number"></span>
                            </td>
                            <td class="py-4 px-6">
                                <span x-show="po.pr_number" class="text-xs font-mono bg-blue-50 text-blue-700 px-2 py-1 rounded" x-text="po.pr_number"></span>
                                <span x-show="!po.pr_number" class="text-gray-400 text-xs">—</span>
                            </td>
                            <td class="py-4 px-6 text-gray-900" x-text="po.vendor ? po.vendor.vendor_name : 'N/A'"></td>
                            <td class="py-4 px-6 text-gray-600" x-text="formatDate(po.po_date)"></td>
                            <td class="py-4 px-6 font-bold text-gray-900" x-text="formatCurrency(po.grand_total || 0)"></td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold" 
                                      :class="getStatusClass(po.status)" x-text="po.status"></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button @click="viewPO(po.id)" class="text-primary hover:text-primary/80 mr-3" title="View">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button @click="editPO(po.id)" class="text-gray-600 hover:text-gray-800 mr-3" title="Edit" x-show="po.status === 'DRAFT'">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button @click="sendForApproval(po.id)" class="text-green-600 hover:text-green-800" title="Send for Approval" x-show="po.status === 'DRAFT'">
                                    <span class="material-symbols-outlined text-lg">send</span>
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
                            <input type="text" :value="getCurrencyDisplay()" readonly 
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
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Your Company GSTIN</label>
                            <input type="text" x-model="form.company_gstin" @input="reapplyGstToAllItems()" 
                                   placeholder="e.g. 27AABCU9603R1ZX"
                                   maxlength="15"
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary uppercase">
                            <p class="text-xs text-gray-500 mt-1">
                                Used to determine 
                                <span x-show="!isInterState()" class="font-medium text-green-700">Intrastate (CGST + SGST)</span>
                                <span x-show="isInterState()" class="font-medium text-orange-600">Interstate (IGST)</span>
                                tax type
                            </p>
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
                            <input type="number" x-model="form.credit_days" min="0"
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Delivery Terms</label>
                            <select x-model="form.delivery_terms" 
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Select Terms</option>
                                <option value="EXW">EXW - Ex Works</option>
                                <option value="FOB">FOB - Free on Board</option>
                                <option value="CIF">CIF - Cost, Insurance & Freight</option>
                                <option value="CFR">CFR - Cost and Freight</option>
                                <option value="DDP">DDP - Delivered Duty Paid</option>
                                <option value="DAP">DAP - Delivered at Place</option>
                                <option value="FCA">FCA - Free Carrier</option>
                                <option value="CPT">CPT - Carriage Paid To</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PO Valid Until</label>
                            <input type="date" x-model="form.valid_until" 
                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Billing Address</label>
                            <textarea x-model="form.billing_address" rows="2" placeholder="Invoice-to address"
                                      class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ship To / Delivery Address</label>
                            <textarea x-model="form.ship_to_address" rows="2" placeholder="Delivery / plant address"
                                      class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Internal Notes</label>
                        <textarea x-model="form.notes" rows="2" 
                                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Terms & Conditions</label>
                        <textarea x-model="form.terms_conditions" rows="3" placeholder="Legal terms and conditions for this PO..."
                                  class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    
                    <!-- Line Items -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-bold text-gray-900">Line Items</h4>
                            <button type="button" @click="addLineItem()" x-show="!editingId" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
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
                            
                            <!-- Dynamic Table with GST Columns -->
                            <div class="overflow-x-auto border border-gray-200 rounded-lg" x-show="form.line_items.length > 0">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">UOM</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Disc %</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase" x-show="!isInterState()">CGST</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase" x-show="!isInterState()">SGST</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase" x-show="isInterState()">IGST</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total GST</th>
                                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase" x-show="!editingId">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, index) in form.line_items" :key="index">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2">
                                                    <!-- Edit mode: show dropdown -->
                                                    <template x-if="!editingId">
                                                        <select x-model="item.material_id" @change="onMaterialSelect(index)" required 
                                                                class="w-full px-2 py-1 border border-gray-200 rounded text-xs">
                                                            <option value="">Select Material</option>
                                                            <template x-for="material in materials" :key="material.id">
                                                                <option :value="material.id" x-text="material.material_code + ' - ' + material.material_name"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <!-- View mode: show read-only material name -->
                                                    <template x-if="editingId">
                                                        <div class="px-2 py-1 text-xs font-medium text-gray-800">
                                                            <span class="font-mono text-gray-500 mr-1" x-text="item.material_code"></span>
                                                            <span x-text="item.material_name"></span>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.material_type" readonly 
                                                           class="w-20 px-2 py-1 border border-gray-200 rounded text-xs bg-gray-50">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" x-model="item.ordered_qty" @input="calculateItemTotal(index)" required min="1" 
                                                           :readonly="editingId"
                                                           class="w-16 px-2 py-1 border border-gray-200 rounded text-xs"
                                                           :class="{'bg-gray-50': editingId}">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.uom" readonly 
                                                           class="w-16 px-2 py-1 border border-gray-200 rounded text-xs bg-gray-50">
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <input type="number" x-model="item.unit_price" @input="calculateItemTotal(index)" required min="0" step="0.01" 
                                                           :readonly="editingId"
                                                           class="w-24 px-2 py-1 border border-gray-200 rounded text-xs text-right"
                                                           :class="{'bg-gray-50': editingId}">
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <input type="number" x-model="item.discount_pct" @input="calculateItemTotal(index)" min="0" max="100" step="0.01" placeholder="0"
                                                           :readonly="editingId"
                                                           class="w-16 px-2 py-1 border border-gray-200 rounded text-xs text-right"
                                                           :class="{'bg-gray-50': editingId}">
                                                </td>
                                                <td class="px-3 py-2 text-right" x-show="!isInterState()">
                                                    <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemCGST(index))"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right" x-show="!isInterState()">
                                                    <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemSGST(index))"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right" x-show="isInterState()">
                                                    <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemIGST(index))"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <span class="text-xs font-semibold text-blue-600" x-text="formatCurrency(getItemTotalGST(index))"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <span class="text-xs font-bold text-gray-900" x-text="formatCurrency(getItemTotal(index))"></span>
                                                </td>
                                                <td class="px-3 py-2 text-center" x-show="!editingId">
                                                    <button type="button" @click="removeLineItem(index)" class="text-red-600 hover:text-red-800" title="Remove">
                                                        <span class="material-symbols-outlined text-base">delete</span>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Additional row for GST Tax selection and other details -->
                                            <tr class="bg-gray-50">
                                                <td :colspan="editingId ? 11 : 12" class="px-3 py-2">
                                                    <div class="grid grid-cols-4 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">GST Tax</label>
                                                            <select x-model="item.gst_tax_id" @change="calculateItemTotal(index)" 
                                                                    :disabled="editingId"
                                                                    class="w-full px-2 py-1 border border-gray-200 rounded text-xs"
                                                                    :class="{'bg-gray-50': editingId}">
                                                                <option value="">Select Tax</option>
                                                                <template x-for="tax in gstTaxes" :key="tax.id">
                                                                    <option :value="tax.id" x-text="tax.tax_code + ' - ' + tax.total_tax_rate + '%'"></option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Scheduled Delivery</label>
                                                            <input type="date" x-model="item.scheduled_delivery" 
                                                                   :readonly="editingId"
                                                                   class="w-full px-2 py-1 border border-gray-200 rounded text-xs"
                                                                   :class="{'bg-gray-50': editingId}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Under Tolerance %</label>
                                                            <input type="number" x-model="item.under_delivery_tolerance" min="0" max="100" step="0.01" placeholder="3"
                                                                   :readonly="editingId"
                                                                   class="w-full px-2 py-1 border border-gray-200 rounded text-xs"
                                                                   :class="{'bg-gray-50': editingId}">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 mb-1">Over Tolerance %</label>
                                                            <input type="number" x-model="item.over_delivery_tolerance" min="0" max="100" step="0.01" placeholder="5"
                                                                   :readonly="editingId"
                                                                   class="w-full px-2 py-1 border border-gray-200 rounded text-xs"
                                                                   :class="{'bg-gray-50': editingId}">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-end">
                            <div class="text-right space-y-2 bg-gray-50 border border-gray-200 rounded-lg p-4 min-w-[300px]">
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Subtotal:</span>
                                    <span class="font-semibold" x-text="formatCurrency(calculateSubtotal())"></span>
                                </div>
                                <div class="flex justify-between text-sm text-blue-600">
                                    <span>Total GST:</span>
                                    <span class="font-semibold" x-text="formatCurrency(calculateTotalGST())"></span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-300">
                                    <span>Total Amount:</span>
                                    <span x-text="formatCurrency(calculateTotal())"></span>
                                </div>
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
        currencies: [],
        gstTaxes: [],
        loading: false,
        saving: false,
        showModal: false,
        modalDataLoaded: false,
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
            credit_days: 0,
            notes: '',
            line_items: []
        },
        
        async init() {
            await this.loadVendors();
            await this.loadPurchaseOrders();
        },
        
        async loadModalData() {
            if (this.modalDataLoaded) return;
            await Promise.all([
                this.loadCurrencies(),
                this.loadMaterials(),
                this.loadGstTaxes()
            ]);
            this.modalDataLoaded = true;
        },
        
        async loadGstTaxes() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/gst-taxes?is_active=true', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('GST Taxes API response:', data);
                
                if (data.success && data.data && data.data.gst_taxes) {
                    this.gstTaxes = data.data.gst_taxes;
                    console.log('Loaded GST taxes:', this.gstTaxes.length);
                } else {
                    console.warn('No GST taxes found in response');
                    this.gstTaxes = [];
                }
            } catch (error) {
                console.error('Error loading GST taxes:', error);
                this.gstTaxes = [];
            }
        },
        
        async loadCurrencies() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/currencies?per_page=100', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                console.log('Currencies API response:', data);
                
                if (data.success && data.data && data.data.currencies) {
                    this.currencies = data.data.currencies;
                    console.log('Loaded currencies:', this.currencies.length);
                } else {
                    console.warn('No currencies found in response');
                    this.currencies = [];
                }
            } catch (error) {
                console.error('Error loading currencies:', error);
                this.currencies = [];
            }
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
                
                if (data.success && Array.isArray(data.data)) {
                    this.materials = data.data;
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
        
        async openCreateModal() {
            await this.loadModalData();
            this.modalTitle = 'Create Purchase Order';
            this.editingId = null;
            this.form = {
                vendor_id: '',
                vendor_gstin: '',
                vendor_name: '',
                company_gstin: '',
                currency_id: '',
                po_date: new Date().toISOString().split('T')[0],
                expected_delivery_date: '',
                payment_terms: '',
                credit_days: 0,
                delivery_terms: '',
                valid_until: '',
                billing_address: '',
                ship_to_address: '',
                notes: '',
                terms_conditions: '',
                line_items: [{ 
                    material_id: '', 
                    material_name: '', 
                    material_code: '', 
                    material_type: '', 
                    ordered_qty: 1, 
                    uom: '', 
                    uom_id: null,
                    unit_price: 0,
                    discount_pct: 0,
                    gst_tax_id: '',
                    scheduled_delivery: '',
                    under_delivery_tolerance: 3,
                    over_delivery_tolerance: 5
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
                this.form.credit_days = vendor.credit_days ?? 0;
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
                unit_price: 0,
                discount_pct: 0,
                gst_tax_id: '',
                scheduled_delivery: '',
                under_delivery_tolerance: 3,
                over_delivery_tolerance: 5
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
                
                // Auto-fill unit_price from material's standard_cost if available and > 0
                const standardCost = parseFloat(material.standard_cost || 0);
                if (standardCost > 0) {
                    this.form.line_items[index].unit_price = standardCost;
                    console.log('Auto-filled unit price from standard_cost:', standardCost);
                }
                
                // Auto-fill GST tax from material's HSN code default_gst_id
                if (material.hsn_code && material.hsn_code.default_gst_id) {
                    const defaultGstId = parseInt(material.hsn_code.default_gst_id);
                    // Verify the tax exists in loaded gstTaxes list
                    const exists = this.gstTaxes.find(t => t.id === defaultGstId);
                    if (exists) {
                        this.form.line_items[index].gst_tax_id = defaultGstId;
                        console.log('Auto-filled GST tax from HSN:', defaultGstId);
                    } else {
                        this.form.line_items[index].gst_tax_id = '';
                    }
                } else {
                    this.form.line_items[index].gst_tax_id = '';
                }
                
                this.calculateItemTotal(index);
                console.log('Line item after material select:', this.form.line_items[index]);
            }
        },
        
        calculateTotal() {
            return this.form.line_items.reduce((sum, item, index) => {
                return sum + this.getItemTotal(index);
            }, 0);
        },
        
        calculateSubtotal() {
            return this.form.line_items.reduce((sum, item) => {
                const qty = parseFloat(item.ordered_qty || 0);
                const price = parseFloat(item.unit_price || 0);
                const discount = parseFloat(item.discount_pct || 0);
                const subtotal = qty * price;
                const discountAmount = subtotal * discount / 100;
                return sum + (subtotal - discountAmount);
            }, 0);
        },
        
        calculateTotalGST() {
            return this.form.line_items.reduce((sum, item, index) => {
                return sum + this.getItemTotalGST(index);
            }, 0);
        },
        
        getItemTotalByItem(item) {
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            return qty * price * (1 - discount / 100);
        },
        
        getItemTotal(index) {
            const item = this.form.line_items[index];
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const subtotal = qty * price;
            const discountAmount = subtotal * discount / 100;
            const taxableAmount = subtotal - discountAmount;
            const gstAmount = this.getItemTotalGST(index);
            return taxableAmount + gstAmount;
        },
        
        calculateItemTotal(index) {
            // Trigger Alpine reactivity by reassigning the item
            const item = this.form.line_items[index];
            // Force update by touching the item
            item._updated = Date.now();
        },
        
        // Determine if transaction is interstate based on GSTIN state codes (first 2 digits)
        isInterState() {
            const vendorGstin = this.form.vendor_gstin || '';
            const companyGstin = this.form.company_gstin || '';
            // If vendor has no GSTIN, treat as intrastate (CGST+SGST)
            if (!vendorGstin || vendorGstin.length < 2) return false;
            // If company has no GSTIN, default to intrastate
            if (!companyGstin || companyGstin.length < 2) return false;
            const vendorState = vendorGstin.substring(0, 2);
            const companyState = companyGstin.substring(0, 2);
            return vendorState !== companyState;
        },
        
        // Re-apply correct GST to all line items when company GSTIN changes
        reapplyGstToAllItems() {
            this.form.line_items.forEach((item, index) => {
                if (item.material_id && item.gst_tax_id) {
                    // Recalculate to trigger reactivity
                    this.calculateItemTotal(index);
                }
            });
        },
        
        // Get the appropriate GST tax for a material based on inter/intra state
        getDefaultGstForMaterial(material) {
            if (!material || !this.gstTaxes.length) return '';
            const interstate = this.isInterState();

            // Try to get GST rate from material's HSN code default_gst_id
            let targetRate = null;
            if (material.hsn_code && material.hsn_code.default_gst_id) {
                const hsnTax = this.gstTaxes.find(t => t.id == material.hsn_code.default_gst_id);
                if (hsnTax) {
                    // Get the total rate from this tax to find matching tax of correct type
                    targetRate = parseFloat(hsnTax.igst_rate || 0) || 
                                 (parseFloat(hsnTax.cgst_rate || 0) + parseFloat(hsnTax.sgst_rate || 0));
                }
            }

            if (interstate) {
                // Find IGST-only tax matching the rate
                if (targetRate) {
                    const match = this.gstTaxes.find(t => 
                        parseFloat(t.igst_rate || 0) === targetRate &&
                        parseFloat(t.cgst_rate || 0) === 0 &&
                        parseFloat(t.sgst_rate || 0) === 0
                    );
                    if (match) return match.id;
                }
                // Fallback: any IGST tax
                const igstTax = this.gstTaxes.find(t => 
                    parseFloat(t.igst_rate || 0) > 0 &&
                    parseFloat(t.cgst_rate || 0) === 0
                );
                return igstTax ? igstTax.id : (this.gstTaxes[0]?.id || '');
            } else {
                // Find CGST+SGST tax matching the rate
                if (targetRate) {
                    const halfRate = targetRate / 2;
                    const match = this.gstTaxes.find(t => 
                        parseFloat(t.cgst_rate || 0) === halfRate &&
                        parseFloat(t.sgst_rate || 0) === halfRate &&
                        parseFloat(t.igst_rate || 0) === 0
                    );
                    if (match) return match.id;
                }
                // Fallback: any CGST+SGST tax
                const cgstTax = this.gstTaxes.find(t => 
                    parseFloat(t.cgst_rate || 0) > 0 &&
                    parseFloat(t.sgst_rate || 0) > 0 &&
                    parseFloat(t.igst_rate || 0) === 0
                );
                return cgstTax ? cgstTax.id : (this.gstTaxes[0]?.id || '');
            }
        },
        
        // Check if any line item uses CGST
        hasAnyCGST() {
            if (this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.cgst_rate || 0) > 0;
            });
        },
        
        // Check if any line item uses SGST
        hasAnySGST() {
            if (this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.sgst_rate || 0) > 0;
            });
        },
        
        // Check if any line item uses IGST
        hasAnyIGST() {
            if (!this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.igst_rate || 0) > 0;
            });
        },
        
        // Get CGST amount for a line item
        getItemCGST(index) {
            if (this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const subtotal = qty * price;
            const discountAmount = subtotal * discount / 100;
            const taxableAmount = subtotal - discountAmount;
            
            const cgstRate = parseFloat(tax.cgst_rate || 0);
            return taxableAmount * cgstRate / 100;
        },
        
        // Get SGST amount for a line item
        getItemSGST(index) {
            if (this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const subtotal = qty * price;
            const discountAmount = subtotal * discount / 100;
            const taxableAmount = subtotal - discountAmount;
            
            const sgstRate = parseFloat(tax.sgst_rate || 0);
            return taxableAmount * sgstRate / 100;
        },
        
        // Get IGST amount for a line item
        getItemIGST(index) {
            if (!this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const subtotal = qty * price;
            const discountAmount = subtotal * discount / 100;
            const taxableAmount = subtotal - discountAmount;
            
            const igstRate = parseFloat(tax.igst_rate || 0);
            return taxableAmount * igstRate / 100;
        },
        
        // Get total GST amount for a line item
        getItemTotalGST(index) {
            const cgst = this.getItemCGST(index);
            const sgst = this.getItemSGST(index);
            const igst = this.getItemIGST(index);
            const total = cgst + sgst + igst;
            
            // Debug logging
            if (index === 0) {
                console.log('GST Calculation Debug:', {
                    isInterState: this.isInterState(),
                    vendorGstin: this.form.vendor_gstin,
                    companyGstin: this.form.company_gstin,
                    gstTaxId: this.form.line_items[index]?.gst_tax_id,
                    cgst, sgst, igst, total
                });
            }
            
            return total;
        },
        
        getCurrencyDisplay() {
            if (!this.form.currency_id) {
                return 'Auto-filled from vendor';
            }
            const currency = this.currencies.find(c => c.id == this.form.currency_id);
            if (currency) {
                return currency.currency_code + ' - ' + currency.currency_name;
            }
            return 'Currency ID: ' + this.form.currency_id;
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
                    credit_days: this.form.credit_days !== '' && this.form.credit_days !== null ? parseInt(this.form.credit_days) : 0,
                    delivery_terms: this.form.delivery_terms || null,
                    valid_until: this.form.valid_until || null,
                    billing_address: this.form.billing_address || null,
                    ship_to_address: this.form.ship_to_address || null,
                    remarks: this.form.notes || null,
                    terms_conditions: this.form.terms_conditions || null,
                    line_items: this.form.line_items.map((item, idx) => ({
                        material_id: parseInt(item.material_id),
                        ordered_qty: parseFloat(item.ordered_qty),
                        uom_id: parseInt(item.uom_id),
                        unit_price: parseFloat(item.unit_price),
                        material_description: item.material_name || null,
                        discount_pct: parseFloat(item.discount_pct) || 0,
                        gst_tax_id: item.gst_tax_id ? parseInt(item.gst_tax_id) : null,
                        scheduled_delivery: item.scheduled_delivery || null,
                        under_delivery_tolerance: parseFloat(item.under_delivery_tolerance) || 3,
                        over_delivery_tolerance: parseFloat(item.over_delivery_tolerance) || 5
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
                    
                    // Calculate tax breakdown from line items
                    let taxBreakdownHtml = '';
                    const taxSummary = {};
                    
                    // Determine if this is interstate or intrastate based on GSTIN
                    const vendorGstin = po.vendor_gstin || (po.vendor && po.vendor.gstin) || '';
                    const companyGstin = po.company_gstin || '';
                    
                    // Check if interstate (first 2 digits of GSTIN are different)
                    const isInterstate = vendorGstin.length >= 2 && companyGstin.length >= 2 && 
                                        vendorGstin.substring(0, 2) !== companyGstin.substring(0, 2);
                    
                    // Function to calculate correct grand total
                    const calculateCorrectGrandTotal = () => {
                        const subtotal = parseFloat(po.subtotal) || 0;
                        const discount = parseFloat(po.discount_amount) || 0;
                        const freight = parseFloat(po.freight_charges) || 0;
                        
                        // Calculate total tax from taxSummary
                        let totalTax = 0;
                        for (const amount of Object.values(taxSummary)) {
                            totalTax += amount;
                        }
                        
                        return subtotal - discount + freight + totalTax;
                    };
                    
                    if (po.line_items && po.line_items.length > 0) {
                        po.line_items.forEach(item => {
                            if (item.gst_tax && item.gst_tax_id) {
                                const tax = item.gst_tax;
                                const taxCode = tax.tax_code || 'GST';
                                const itemSubtotal = (parseFloat(item.ordered_qty) || 0) * (parseFloat(item.unit_price) || 0);
                                const discountAmount = itemSubtotal * (parseFloat(item.discount_pct) || 0) / 100;
                                const taxableAmount = itemSubtotal - discountAmount;
                                
                                // Calculate individual tax components based on transaction type
                                const cgstRate = parseFloat(tax.cgst_rate) || 0;
                                const sgstRate = parseFloat(tax.sgst_rate) || 0;
                                const igstRate = parseFloat(tax.igst_rate) || 0;
                                
                                if (isInterstate) {
                                    // Interstate: Only IGST applies
                                    const igstAmount = taxableAmount * igstRate / 100;
                                    if (igstAmount > 0) {
                                        if (!taxSummary['IGST']) taxSummary['IGST'] = 0;
                                        taxSummary['IGST'] += igstAmount;
                                    }
                                } else {
                                    // Intrastate: Only CGST + SGST apply
                                    const cgstAmount = taxableAmount * cgstRate / 100;
                                    const sgstAmount = taxableAmount * sgstRate / 100;
                                    
                                    if (cgstAmount > 0) {
                                        if (!taxSummary['CGST']) taxSummary['CGST'] = 0;
                                        taxSummary['CGST'] += cgstAmount;
                                    }
                                    if (sgstAmount > 0) {
                                        if (!taxSummary['SGST']) taxSummary['SGST'] = 0;
                                        taxSummary['SGST'] += sgstAmount;
                                    }
                                }
                            }
                        });
                        
                        // Build tax breakdown HTML - only show tax types that have values
                        if (Object.keys(taxSummary).length > 0) {
                            for (const [taxType, amount] of Object.entries(taxSummary)) {
                                // Only display if amount is greater than 0
                                if (amount > 0) {
                                    taxBreakdownHtml += `<div class="text-sm text-gray-600">${taxType}: <span class="font-semibold">${this.formatCurrency(amount)}</span></div>`;
                                }
                            }
                        } else {
                            taxBreakdownHtml = '<div class="text-sm text-gray-600">Tax: <span class="font-semibold">${this.formatCurrency(po.tax_amount || 0)}</span></div>';
                        }
                    } else {
                        taxBreakdownHtml = '<div class="text-sm text-gray-600">Tax: <span class="font-semibold">${this.formatCurrency(po.tax_amount || 0)}</span></div>';
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
                                ${taxBreakdownHtml}
                                <div class="text-lg font-bold text-gray-900 pt-2 border-t">Grand Total: ${this.formatCurrency(calculateCorrectGrandTotal())}</div>
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
        
        async sendForApproval(id) {
            if (!confirm('Are you sure you want to send this purchase order for approval?')) {
                return;
            }
            
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch(`/api/v1/purchase-orders/${id}/submit`, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                console.log('Submit response:', data);
                
                if (data.success) {
                    alert('Purchase order sent for approval successfully!');
                    await this.loadPurchaseOrders();
                } else {
                    alert(data.message || 'Failed to send purchase order for approval');
                }
            } catch (error) {
                console.error('Error sending PO for approval:', error);
                alert('Error sending purchase order for approval: ' + error.message);
            }
        },
        
        async editPO(id) {
            await this.loadModalData();
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
                        company_gstin: po.company_gstin || '',
                        currency_id: po.currency_id || '',
                        po_date: formatDateForInput(po.po_date),
                        expected_delivery_date: formatDateForInput(po.expected_delivery),
                        payment_terms: po.payment_terms || '',
                        credit_days: po.credit_days ?? 0,
                        delivery_terms: po.delivery_terms || '',
                        valid_until: formatDateForInput(po.valid_until),
                        billing_address: po.billing_address || '',
                        ship_to_address: po.ship_to_address || '',
                        notes: po.remarks || '',
                        terms_conditions: po.terms_conditions || '',
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
                            unit_price: parseFloat(item.unit_price) || 0,
                            discount_pct: parseFloat(item.discount_pct) || 0,
                            gst_tax_id: item.gst_tax_id || '',
                            scheduled_delivery: item.scheduled_delivery ? formatDateForInput(item.scheduled_delivery) : '',
                            under_delivery_tolerance: parseFloat(item.under_delivery_tolerance) || 3,
                            over_delivery_tolerance: parseFloat(item.over_delivery_tolerance) || 5,
                            material: item.material // Keep the full material object for GST calculation
                        }));
                        
                        // Recalculate GST tax for each item based on current Vendor/Company GSTIN
                        this.$nextTick(() => {
                            this.form.line_items.forEach((item, index) => {
                                console.log(`Line item ${index}:`, {
                                    material_id: item.material_id,
                                    gst_tax_id: item.gst_tax_id,
                                    qty: item.ordered_qty,
                                    price: item.unit_price,
                                    discount: item.discount_pct
                                });
                                
                                // Recalculate GST tax based on material and inter/intra state
                                if (item.material) {
                                    const newGstTaxId = this.getDefaultGstForMaterial(item.material);
                                    if (newGstTaxId) {
                                        item.gst_tax_id = newGstTaxId;
                                        console.log(`Updated GST tax for item ${index} from ${item.gst_tax_id} to ${newGstTaxId}`);
                                    }
                                }
                                
                                // Find the GST tax
                                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                                console.log(`GST Tax for item ${index}:`, tax);
                                
                                this.calculateItemTotal(index);
                            });
                        });
                    } else {
                        this.form.line_items = [{ 
                            material_id: '', 
                            material_name: '', 
                            material_code: '', 
                            material_type: '', 
                            ordered_qty: 1, 
                            uom: '', 
                            uom_id: null,
                            unit_price: 0,
                            discount_pct: 0,
                            gst_tax_id: '',
                            scheduled_delivery: '',
                            under_delivery_tolerance: 3,
                            over_delivery_tolerance: 5
                        }];
                    }
                    
                    console.log('Form populated for editing:', this.form);
                    console.log('GST Taxes available:', this.gstTaxes.length);
                    console.log('Is Interstate?', this.isInterState());
                    console.log('Vendor GSTIN:', this.form.vendor_gstin);
                    console.log('Company GSTIN:', this.form.company_gstin);
                    console.log('Line items:', this.form.line_items);
                    console.log('Has CGST?', this.hasAnyCGST());
                    console.log('Has SGST?', this.hasAnySGST());
                    console.log('Has IGST?', this.hasAnyIGST());
                    
                    // Force Alpine to re-evaluate by showing modal after a tick
                    this.$nextTick(() => {
                        this.showModal = true;
                    });
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
