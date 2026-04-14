@extends('layouts.procurement')

@section('title', 'Create Purchase Order')
@section('page-title', 'Create Purchase Order')

@section('content')
<div x-data="createPOData()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create Purchase Order</h2>
                    <p class="text-gray-600 mt-1">Create a new purchase order for vendor</p>
                </div>
                <a href="{{ url("/org/{$organization->org_slug}/procurement/purchase-orders") }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>Back to List
                </a>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="savePO()" class="space-y-6">
            <!-- PR Selection (Optional) -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Link to Purchase Requisition (Optional)</h3>
                        <p class="text-sm text-gray-600 mt-1">Select a PR to auto-fill vendor and line items from selected quotation</p>
                    </div>
                    <button type="button" @click="clearPRSelection()" x-show="form.pr_number" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Clear PR Selection
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PR Number</label>
                        <select x-model="form.pr_number" @change="onPRSelect()" 
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white">
                            <option value="">-- Select PR (Optional) --</option>
                            <template x-for="pr in selectedPRs" :key="pr.pr_number + '|' + pr.vendor_id">
                                <option :value="pr.pr_number + '|' + pr.vendor_id" x-text="pr.pr_number + ' - ' + pr.vendor_name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="form.pr_number">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Selected Vendor</label>
                        <input type="text" :value="getSelectedPRVendor()" readonly 
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                    </div>
                </div>
            </div>

            <!-- PO Details -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Purchase Order Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor *</label>
                        <select x-model="form.vendor_id" @change="onVendorSelect()" required 
                                :disabled="form.pr_number !== ''"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                :class="{'bg-gray-50': form.pr_number !== ''}">
                            <option value="">Select Vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option :value="vendor.id" x-text="vendor.vendor_code + ' - ' + vendor.vendor_name"></option>
                            </template>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Currency *</label>
                        <input type="text" :value="getCurrencyDisplay()" readonly 
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor GSTIN</label>
                        <input type="text" x-model="form.vendor_gstin" readonly 
                               class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Your Company GSTIN</label>
                        <input type="text" x-model="companyGstin" @input="reapplyGstToAllItems()" 
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
                </div>
            </div>

            <!-- PR Items (Read-Only) - Shown when PR is selected -->
            <div x-show="form.pr_number && prItems.length > 0" class="bg-blue-50 border border-blue-200 rounded-xl shadow p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Selected PR Items (Reference)</h3>
                    <p class="text-sm text-gray-600 mt-1">These are the items from the selected Purchase Requisition with quotation prices</p>
                </div>
                
                <div class="overflow-x-auto border border-blue-300 rounded-lg bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-blue-100 border-b border-blue-300">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Item Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Item Name</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Total Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in prItems" :key="index">
                                <tr class="hover:bg-blue-50">
                                    <td class="px-4 py-3 text-gray-700" x-text="index + 1"></td>
                                    <td class="px-4 py-3 text-gray-600 font-mono text-xs" x-text="item.item_code || '—'"></td>
                                    <td class="px-4 py-3 text-gray-900 font-medium" x-text="item.item_name"></td>
                                    <td class="px-4 py-3 text-right text-gray-700" x-text="item.quantity.toFixed(3)"></td>
                                    <td class="px-4 py-3 text-right text-gray-700" x-text="formatCurrency(item.unit_price)"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="formatCurrency(item.total_price)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-900">Grand Total:</td>
                                <td class="px-4 py-3 text-right font-bold text-primary text-lg" x-text="formatCurrency(calculatePRTotal())"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Line Items</h3>
                    <button type="button" @click="addLineItem()" x-show="!form.pr_number"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Add Item
                    </button>
                </div>
                
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
                                <template x-if="hasAnyCGST()">
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">CGST</th>
                                </template>
                                <template x-if="hasAnySGST()">
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">SGST</th>
                                </template>
                                <template x-if="hasAnyIGST()">
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">IGST</th>
                                </template>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total GST</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                <!-- <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Action</th> -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in form.line_items" :key="index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <!-- PR-sourced items: show read-only material name -->
                                        <template x-if="item.from_pr">
                                            <div class="px-2 py-1 text-xs font-medium text-gray-800">
                                                <span class="font-mono text-gray-500 mr-1" x-text="item.item_code"></span>
                                                <span x-text="item.item_name"></span>
                                            </div>
                                        </template>
                                        <!-- Manually added items: show dropdown -->
                                        <template x-if="!item.from_pr">
                                            <select x-model="item.material_id" @change="onMaterialSelect(index)" required 
                                                    class="w-full px-2 py-1 border border-gray-200 rounded text-xs">
                                                <option value="">Select Material</option>
                                                <template x-if="loading">
                                                    <option value="" disabled>Loading materials...</option>
                                                </template>
                                                <template x-if="!loading && materials.length === 0">
                                                    <option value="" disabled>No materials available - Add in Master Data</option>
                                                </template>
                                                <template x-for="material in getAvailableMaterials(index)" :key="material.id">
                                                    <option :value="String(material.id)" x-text="material.material_code + ' - ' + material.material_name"></option>
                                                </template>
                                            </select>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.material_type" readonly 
                                               class="w-20 px-2 py-1 border border-gray-200 rounded text-xs bg-gray-50">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" x-model="item.ordered_qty" @input="calculateItemTotal(index)" required min="1" 
                                               class="w-16 px-2 py-1 border border-gray-200 rounded text-xs">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.uom" readonly 
                                               class="w-16 px-2 py-1 border border-gray-200 rounded text-xs bg-gray-50">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" x-model="item.unit_price" @input="calculateItemTotal(index)" required min="0" step="0.01" 
                                               class="w-24 px-2 py-1 border border-gray-200 rounded text-xs text-right">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" x-model="item.discount_pct" @input="calculateItemTotal(index)" min="0" max="100" step="0.01" placeholder="0"
                                               class="w-16 px-2 py-1 border border-gray-200 rounded text-xs text-right">
                                    </td>
                                    <template x-if="hasAnyCGST()">
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemCGST(index))"></span>
                                        </td>
                                    </template>
                                    <template x-if="hasAnySGST()">
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemSGST(index))"></span>
                                        </td>
                                    </template>
                                    <template x-if="hasAnyIGST()">
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs font-medium text-gray-700" x-text="formatCurrency(getItemIGST(index))"></span>
                                        </td>
                                    </template>
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-xs font-semibold text-blue-600" x-text="formatCurrency(getItemTotalGST(index))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-xs font-bold text-gray-900" x-text="formatCurrency(getItemTotal(index))"></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeLineItem(index)" x-show="!item.from_pr"
                                                class="text-red-600 hover:text-red-800" title="Remove">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Additional row for GST Tax selection -->
                                <tr class="bg-gray-50">
                                    <td colspan="12" class="px-3 py-2">
                                        <div class="grid grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">GST Tax</label>
                                                <select x-model="item.gst_tax_id" @change="calculateItemTotal(index)" 
                                                        class="w-full px-2 py-1 border border-gray-200 rounded text-xs">
                                                    <option value="">Select Tax</option>
                                                    <template x-for="tax in gstTaxes" :key="tax.id">
                                                        <option :value="tax.id" x-text="tax.tax_code + ' - ' + tax.total_tax_rate + '%'"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Scheduled Delivery</label>
                                                <input type="date" x-model="item.scheduled_delivery" 
                                                       class="w-full px-2 py-1 border border-gray-200 rounded text-xs">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Total Summary -->
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

            <!-- Form Actions -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ url("/org/{$organization->org_slug}/procurement/purchase-orders") }}"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" :disabled="saving" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50">
                        <span x-show="!saving">Save Purchase Order</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function createPOData() {
    return {
        vendors: [],
        materials: [],
        currencies: [],
        gstTaxes: [],
        selectedPRs: [],
        prItems: [],
        saving: false,
        loading: true,
        companyGstin: '',
        form: {
            pr_number: '',
            pr_vendor_id: '',
            vendor_id: '',
            vendor_gstin: '',
            currency_id: '',
            po_date: new Date().toISOString().split('T')[0],
            expected_delivery_date: '',
            payment_terms: '',
            line_items: []
        },
        
        async init() {
            this.loading = true;
            // Load company GSTIN from profile if available
            this.companyGstin = '{{ $organization->gstin ?? "" }}';
            await Promise.all([
                this.loadVendors(),
                this.loadMaterials(),
                this.loadCurrencies(),
                this.loadGstTaxes(),
                this.loadSelectedPRs()
            ]);
            this.loading = false;
            console.log('Initialization complete. Materials count:', this.materials.length);
        },

        // Determine if transaction is interstate based on GSTIN state codes (first 2 digits)
        isInterState() {
            const vendorGstin = this.form.vendor_gstin || '';
            const companyGstin = this.companyGstin || '';
            // If vendor has no GSTIN, treat as intrastate (CGST+SGST)
            if (!vendorGstin || vendorGstin.length < 2) return false;
            // If company has no GSTIN, default to intrastate
            if (!companyGstin || companyGstin.length < 2) return false;
            const vendorState = vendorGstin.substring(0, 2);
            const companyState = companyGstin.substring(0, 2);
            return vendorState !== companyState;
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

        // Re-apply correct GST to all line items when vendor changes
        reapplyGstToAllItems() {
            this.form.line_items.forEach((item, index) => {
                if (item.material_id) {
                    const material = this.materials.find(m => String(m.id) === String(item.material_id));
                    if (material) {
                        item.gst_tax_id = this.getDefaultGstForMaterial(material);
                        this.calculateItemTotal(index);
                    }
                }
            });
        },
        
        async loadSelectedPRs() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/quotation-comparison/selected-prs', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data && data.data.selected_prs) {
                    this.selectedPRs = data.data.selected_prs;
                    console.log('Selected PRs loaded:', this.selectedPRs.length);
                }
            } catch (error) {
                console.error('Error loading selected PRs:', error);
            }
        },
        
        async onPRSelect() {
            if (!this.form.pr_number) {
                this.prItems = [];
                return;
            }
            
            // Parse composite key "pr_number|vendor_id"
            const [prNumber, vendorId] = this.form.pr_number.split('|');
            
            try {
                const token = localStorage.getItem('access_token');
                const url = `/api/v1/quotation-comparison/pr-quotation/${prNumber}?vendor_id=${vendorId}`;
                const response = await fetch(url, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.success && data.data) {
                    const prData = data.data;
                    
                    // Store PR items for read-only display
                    this.prItems = prData.line_items.map(item => ({
                        item_code: item.item_code || '',
                        item_name: item.item_name,
                        quantity: parseFloat(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        total_price: parseFloat(item.total_price || (item.quantity * item.unit_price))
                    }));
                    
                    // Auto-fill vendor details — vendor is always known from the composite key
                    if (prData.vendor_id) {
                        this.form.vendor_id = prData.vendor_id;
                        this.form.vendor_gstin = prData.vendor_gstin || '';
                        this.form.currency_id = prData.currency_id || '';
                        this.form.payment_terms = prData.payment_terms || '';
                    }
                    
                    // Build line items with full material details before assigning to form
                    // so Alpine renders the selects with correct values from the start
                    const lineItems = [];
                    for (const item of prData.line_items) {
                        const materialId = item.material_id ? String(item.material_id) : '';
                        const material = materialId
                            ? this.materials.find(m => String(m.id) === materialId)
                            : null;

                        lineItems.push({
                            from_pr:           true,
                            item_code:         item.item_code || '',
                            item_name:         item.item_name || '',
                            material_id:       materialId,
                            material_type:     material?.material_type || '',
                            ordered_qty:       parseFloat(item.quantity) || 1,
                            uom:               material?.uom?.uom_code || '',
                            uom_id:            material?.uom_id ? String(material.uom_id) : '',
                            unit_price:        parseFloat(item.unit_price) || 0,
                            discount_pct:      0,
                            gst_tax_id:        material ? this.getDefaultGstForMaterial(material) : '',
                            scheduled_delivery: item.delivery_date || ''
                        });
                    }
                    this.form.line_items = lineItems;
                    
                    console.log('PR data loaded and form auto-filled');                } else {
                    alert('Failed to load PR quotation details');
                }
            } catch (error) {
                console.error('Error loading PR quotation:', error);
                alert('Error loading PR quotation details');
            }
        },
        
        async loadMaterialDetails(index, materialId) {
            try {
                const material = this.materials.find(m => String(m.id) === String(materialId));
                if (material) {
                    const item = this.form.line_items[index];
                    // Ensure material_id is always a string to match select option values
                    item.material_id = String(material.id);
                    item.material_type = material.material_type || '';
                    item.uom = material.uom?.uom_code || '';
                    item.uom_id = material.uom_id || '';
                    
                    // Auto-select GST tax based on inter/intra state logic
                    item.gst_tax_id = this.getDefaultGstForMaterial(material);
                    
                    // Calculate totals after loading material details
                    this.calculateItemTotal(index);
                }
            } catch (error) {
                console.error('Error loading material details:', error);
            }
        },
        
        clearPRSelection() {
            this.form.pr_number = '';
            this.form.pr_vendor_id = '';
            this.form.vendor_id = '';
            this.form.vendor_gstin = '';
            this.form.currency_id = '';
            this.form.payment_terms = '';
            this.form.line_items = [];
            this.prItems = [];
            console.log('PR selection cleared');
        },
        
        getSelectedPRVendor() {
            if (!this.form.pr_number) return '';
            const pr = this.selectedPRs.find(p => (p.pr_number + '|' + p.vendor_id) === this.form.pr_number);
            return pr ? pr.vendor_name : '';
        },
        
        async loadVendors() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/vendors?per_page=1000', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data && data.data.vendors) {
                    this.vendors = data.data.vendors.data || data.data.vendors;
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
            }
        },
        
        async loadMaterials() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/materials?per_page=1000&is_active=1', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                console.log('Materials API response:', data);
                
                if (data.success && data.data) {
                    // API returns data.data as a direct array (not nested under materials key)
                    if (Array.isArray(data.data)) {
                        this.materials = data.data;
                    } else if (data.data.materials) {
                        this.materials = data.data.materials.data || data.data.materials;
                    } else {
                        this.materials = [];
                    }
                    console.log('Loaded materials:', this.materials.length);
                } else {
                    console.warn('No materials found in response');
                    this.materials = [];
                }
            } catch (error) {
                console.error('Error loading materials:', error);
                this.materials = [];
            }
        },
        
        async loadCurrencies() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/currencies?per_page=1000', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data && data.data.currencies) {
                    this.currencies = data.data.currencies.data || data.data.currencies;
                }
            } catch (error) {
                console.error('Error loading currencies:', error);
            }
        },
        
        async loadGstTaxes() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/gst-taxes?is_active=true', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data && data.data.gst_taxes) {
                    this.gstTaxes = data.data.gst_taxes;
                }
            } catch (error) {
                console.error('Error loading GST taxes:', error);
            }
        },
        
        onVendorSelect() {
            const vendor = this.vendors.find(v => v.id == this.form.vendor_id);
            if (vendor) {
                this.form.vendor_gstin = vendor.gstin || '';
                this.form.currency_id = vendor.currency_id || '';
                this.form.payment_terms = vendor.payment_terms || '';
                // Re-apply correct GST type (CGST+SGST vs IGST) based on new vendor state
                this.reapplyGstToAllItems();
            }
        },
        
        onMaterialSelect(index) {
            const item = this.form.line_items[index];

            // Prevent duplicate materials
            const isDuplicate = this.form.line_items.some((li, i) => 
                i !== index && li.material_id && String(li.material_id) === String(item.material_id)
            );
            if (isDuplicate) {
                alert('This material is already added. Please select a different material or update the quantity on the existing row.');
                item.material_id = '';
                return;
            }

            const material = this.materials.find(m => String(m.id) === String(item.material_id));
            console.log('Selected material:', material);
            
            if (material) {
                item.material_type = material.material_type || '';
                
                // Handle UOM - store the ID, not just the code
                if (material.uom && material.uom.id) {
                    item.uom_id = material.uom.id;
                    item.uom = material.uom.uom_code;
                } else if (material.uom_id) {
                    item.uom_id = material.uom_id;
                    item.uom = material.uom_code || '';
                } else if (material.purchase_uom && material.purchase_uom.id) {
                    item.uom_id = material.purchase_uom.id;
                    item.uom = material.purchase_uom.uom_code;
                } else {
                    item.uom_id = '';
                    item.uom = '';
                }
                
                // Set unit price from standard cost
                item.unit_price = parseFloat(material.standard_cost) || 0;
                
                // Auto-select GST tax based on inter/intra state logic
                item.gst_tax_id = this.getDefaultGstForMaterial(material);
                
                console.log('Updated item:', item);
            }
            this.calculateItemTotal(index);
        },
        
        addLineItem() {
            this.form.line_items.push({
                from_pr: false,
                item_code: '',
                item_name: '',
                material_id: '',
                material_type: '',
                ordered_qty: 1,
                uom: '',
                uom_id: '',
                unit_price: 0,
                discount_pct: 0,
                gst_tax_id: '',
                scheduled_delivery: ''
            });
        },

        // Returns materials not already selected in other rows
        getAvailableMaterials(index) {
            const selectedIds = this.form.line_items
                .filter((li, i) => i !== index && li.material_id)
                .map(li => String(li.material_id));
            return this.materials.filter(m => !selectedIds.includes(String(m.id)));
        },
        
        removeLineItem(index) {
            this.form.line_items.splice(index, 1);
        },
        
        calculateItemTotal(index) {
            const item = this.form.line_items[index];
            item._updated = Date.now();
        },
        
        hasAnyCGST() {
            if (this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.cgst_rate || 0) > 0;
            });
        },
        
        hasAnySGST() {
            if (this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.sgst_rate || 0) > 0;
            });
        },
        
        hasAnyIGST() {
            if (!this.isInterState()) return false;
            return this.form.line_items.some(item => {
                if (!item.gst_tax_id) return false;
                const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
                return tax && parseFloat(tax.igst_rate || 0) > 0;
            });
        },
        
        getItemCGST(index) {
            if (this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const taxableAmount = (qty * price) * (1 - discount / 100);
            return taxableAmount * parseFloat(tax.cgst_rate || 0) / 100;
        },
        
        getItemSGST(index) {
            if (this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const taxableAmount = (qty * price) * (1 - discount / 100);
            return taxableAmount * parseFloat(tax.sgst_rate || 0) / 100;
        },
        
        getItemIGST(index) {
            if (!this.isInterState()) return 0;
            const item = this.form.line_items[index];
            if (!item.gst_tax_id) return 0;
            const tax = this.gstTaxes.find(t => t.id == item.gst_tax_id);
            if (!tax) return 0;
            const qty = parseFloat(item.ordered_qty || 0);
            const price = parseFloat(item.unit_price || 0);
            const discount = parseFloat(item.discount_pct || 0);
            const taxableAmount = (qty * price) * (1 - discount / 100);
            return taxableAmount * parseFloat(tax.igst_rate || 0) / 100;
        },
        
        getItemTotalGST(index) {
            return this.getItemCGST(index) + this.getItemSGST(index) + this.getItemIGST(index);
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
        
        calculateTotal() {
            return this.form.line_items.reduce((sum, item, index) => {
                return sum + this.getItemTotal(index);
            }, 0);
        },
        
        calculatePRTotal() {
            return this.prItems.reduce((sum, item) => {
                return sum + parseFloat(item.total_price || 0);
            }, 0);
        },
        
        getCurrencyDisplay() {
            if (!this.form.currency_id) return 'Auto-filled from vendor';
            const currency = this.currencies.find(c => c.id == this.form.currency_id);
            return currency ? currency.currency_code + ' - ' + currency.currency_name : 'Currency ID: ' + this.form.currency_id;
        },
        
        formatCurrency(val) {
            return '₹ ' + (parseFloat(val) || 0).toFixed(2);
        },
        
        async savePO() {
            if (!this.form.vendor_id) {
                alert('Please select a vendor');
                return;
            }
            if (this.form.line_items.length === 0) {
                alert('Please add at least one line item');
                return;
            }
            
            this.saving = true;
            try {
                const token = localStorage.getItem('access_token');
                
                // Strip the composite key — send only the actual pr_number to the API
                const payload = {
                    ...this.form,
                    pr_number: this.form.pr_number ? this.form.pr_number.split('|')[0] : '',
                };
                delete payload.pr_vendor_id;
                
                const response = await fetch('/api/v1/purchase-orders', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Purchase Order created successfully!');
                    window.location.href = '{{ url("/org/{$organization->org_slug}/procurement/purchase-orders") }}';
                } else {
                    alert('Error: ' + (data.message || 'Failed to create purchase order'));
                }
            } catch (error) {
                console.error('Error saving PO:', error);
                alert('Error saving purchase order: ' + error.message);
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
