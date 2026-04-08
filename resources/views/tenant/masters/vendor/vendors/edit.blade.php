@extends('tenant.layouts.vendor')

@section('title', 'Edit Vendor')
@section('page-title', 'Edit Vendor')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="vendorEditForm()" x-init="loadVendor()">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Vendor</h2>
                    <p class="text-gray-600 mt-1">Update vendor information</p>
                </div>
                <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="bg-white rounded-xl shadow p-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                <p class="text-gray-600 mt-2">Loading vendor data...</p>
            </div>
        </template>

        <!-- Form -->
        <template x-if="!loading">
            <form @submit.prevent="submitForm" class="bg-white rounded-xl shadow p-6">
                <!-- Basic Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Vendor Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="form.vendor_code" required maxlength="20"
                                placeholder="VND-001"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Vendor Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="form.vendor_name" required maxlength="200"
                                placeholder="Legal company name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Vendor Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Vendor Type <span class="text-red-500">*</span>
                            </label>
                            <select x-model="temp.vendor_type" @change="form.vendor_type = $event.target.value === '_OTHER_' ? '' : $event.target.value" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Type</option>
                                <option value="SUPPLIER">Supplier</option>
                                <option value="SERVICE">Service Provider</option>
                                <option value="TRADER">Trader</option>
                                <option value="_OTHER_">Other (Type custom)</option>
                            </select>
                            <input x-show="temp.vendor_type === '_OTHER_'" type="text" x-model="form.vendor_type" placeholder="Specify Vendor Type" required
                                class="w-full mt-2 px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- GSTIN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                GSTIN
                            </label>
                            <input type="text" x-model="form.gstin" maxlength="20"
                                placeholder="15-digit GSTIN (unique)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- PAN Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                PAN Number
                            </label>
                            <input type="text" x-model="form.pan_number" maxlength="10"
                                placeholder="10-char PAN"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- MSME Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                MSME Category
                            </label>
                            <select x-model="temp.msme_category" @change="form.msme_category = $event.target.value === '_OTHER_' ? '' : $event.target.value"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Category</option>
                                <option value="MICRO">Micro</option>
                                <option value="SMALL">Small</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="_OTHER_">Other (Type custom)</option>
                            </select>
                            <input x-show="temp.msme_category === '_OTHER_'" type="text" x-model="form.msme_category" placeholder="Specify MSME Category"
                                class="w-full mt-2 px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Payment Terms</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Payment Terms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Payment Terms <span class="text-red-500">*</span>
                            </label>
                            <select x-model="temp.payment_terms" @change="form.payment_terms = $event.target.value === '_OTHER_' ? '' : $event.target.value" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Terms</option>
                                <option value="NET30">NET30</option>
                                <option value="NET60">NET60</option>
                                <option value="ADVANCE">Advance</option>
                                <option value="COD">Cash on Delivery</option>
                                <option value="_OTHER_">Other (Type custom)</option>
                            </select>
                            <input x-show="temp.payment_terms === '_OTHER_'" type="text" x-model="form.payment_terms" placeholder="Specify Payment Terms" required
                                class="w-full mt-2 px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Credit Days -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Credit Days <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="form.credit_days" required min="0"
                                placeholder="30"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Credit period in days</p>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select x-model="temp.currency_id" @change="handleCurrencyChange" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Currency</option>
                                <template x-for="curr in currencies" :key="curr.id">
                                    <option :value="String(curr.id)" x-text="curr.currency_code + ' - ' + curr.currency_name"></option>
                                </template>
                                <option value="_OTHER_">Other (Add New Currency)</option>
                            </select>

                            <!-- Create New Currency Inline Form -->
                            <div x-show="temp.currency_id === '_OTHER_'" class="mt-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h4 class="text-sm font-semibold mb-3">Add New Currency</h4>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Code (e.g. GBP) *</label>
                                        <input type="text" x-model="newCurrency.currency_code" maxlength="3"
                                            class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Symbol (e.g. £) *</label>
                                        <input type="text" x-model="newCurrency.currency_symbol" maxlength="5"
                                            class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Name (e.g. British Pound) *</label>
                                    <input type="text" x-model="newCurrency.currency_name"
                                        class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm">
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="temp.currency_id = ''; form.currency_id = ''" class="px-3 py-1.5 text-xs border rounded-md hover:bg-gray-100">Cancel</button>
                                    <button type="button" @click="saveNewCurrency" :disabled="creatingCurrency" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                        <span x-show="!creatingCurrency">Save & Select</span>
                                        <span x-show="creatingCurrency">Saving...</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- INCO Terms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                INCO Terms
                            </label>
                            <select x-model="temp.delivery_terms" @change="form.delivery_terms = $event.target.value === '_OTHER_' ? '' : $event.target.value"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Terms</option>
                                <option value="EXW">EXW - Ex Works</option>
                                <option value="DDP">DDP - Delivered Duty Paid</option>
                                <option value="CIF">CIF - Cost Insurance Freight</option>
                                <option value="FOB">FOB - Free on Board</option>
                                <option value="_OTHER_">Other (Type custom)</option>
                            </select>
                            <input x-show="temp.delivery_terms === '_OTHER_'" type="text" x-model="form.delivery_terms" placeholder="Specify INCO Terms"
                                class="w-full mt-2 px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Contacts List -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Contact Information</h3>
                        <button type="button" @click="addContact" class="px-3 py-1.5 text-sm bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Add Contact
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="(contact, index) in contacts" :key="contact.id">
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg relative group">
                                <!-- Header Action -->
                                <div class="absolute top-4 right-4 flex items-center space-x-3">
                                    <label class="flex items-center space-x-2 text-sm cursor-pointer">
                                        <input type="radio" :name="`primary_contact_${vendorId}`" :value="contact.id" x-model="primaryContactId" @change="setPrimaryContact(contact)" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="font-medium text-gray-700">Default/Primary</span>
                                    </label>
                                    <button type="button" @click="removeContact(index)" x-show="contacts.length > 1" class="text-red-500 hover:text-red-700 bg-white p-1 rounded-full shadow-sm hover:bg-red-50">
                                        <i class="fas fa-trash-alt px-1"></i>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2 pr-24">
                                    <!-- Contact Name -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Contact Name</label>
                                        <input type="text" x-model="contact.contact_name" maxlength="100" placeholder="Contact Name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    <!-- Contact Type -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Type/Role</label>
                                        <input type="text" x-model="contact.contact_type" maxlength="50" placeholder="e.g. Sales, Finance, Technical"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    <!-- Contact Email -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" x-model="contact.contact_email" maxlength="100" placeholder="[EMAIL_ADDRESS]"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                    <!-- Contact Phone -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                                        <input type="text" x-model="contact.contact_phone" maxlength="20" placeholder="+1 234 567 8900"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Banking Details -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Banking Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Bank Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Name
                            </label>
                            <input type="text" x-model="form.bank_name" maxlength="100"
                                placeholder="Bank name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Bank Account Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Account Number
                            </label>
                            <input type="text" x-model="form.bank_account_no" maxlength="30"
                                placeholder="Encrypted account number"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- IFSC Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                IFSC Code
                            </label>
                            <input type="text" x-model="form.ifsc_code" maxlength="11"
                                placeholder="11-char IFSC code"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Approval & Rating -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Approval & Rating</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Rating Score -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rating Score
                            </label>
                            <input type="number" x-model="form.rating_score" min="0" max="100" step="0.01"
                                placeholder="0-100"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">0-100 vendor performance score</p>
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.is_approved" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Vendor Approved</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.blacklisted" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-2 focus:ring-red-500">
                                <span class="text-sm font-medium text-gray-700">Blacklisted (Block from RFQ/PO)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" x-model="form.is_active" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Active Vendor</span>
                    </label>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">About Vendor Master</p>
                            <p>Vendor master stores supplier information used in RFQ, PO, GRN, Invoice, Payment, and Vendor Rating processes.</p>
                            <p class="mt-2 text-xs">Used in: RFQ, PO, GRN, Invoice, Payment, Vendor Rating</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/vendors' : '/org/' . $organization->org_slug . '/vendors') }}"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving">Update Vendor</span>
                        <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>

<script>
    function vendorEditForm() {
        return {
            loading: true,
            saving: false,
            currencies: [],
            vendorId: '{{ $vendorId }}',
            temp: {
                vendor_type: '',
                msme_category: '',
                payment_terms: '',
                delivery_terms: '',
                currency_id: ''
            },
            newCurrency: {
                currency_code: '',
                currency_symbol: '',
                currency_name: ''
            },
            creatingCurrency: false,
            primaryContactId: null,
            contacts: [],
            form: {
                vendor_code: '',
                vendor_name: '',
                vendor_type: '',
                gstin: '',
                pan_number: '',
                msme_category: '',
                payment_terms: '',
                credit_days: 0,
                currency_id: '',
                delivery_terms: '',
                bank_name: '',
                bank_account_no: '',
                ifsc_code: '',
                is_approved: false,
                rating_score: '',
                blacklisted: false,
                is_active: true
            },

            async loadVendor() {
                this.loading = true;
                try {
                    await this.loadCurrencies();

                    const response = await fetch(`/api/v1/vendors/${this.vendorId}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) throw new Error('Failed to load vendor');

                    const data = await response.json();

                    if (data && data.success && data.data && data.data.vendor) {
                        const vendor = data.data.vendor;
                        
                        this.form = {
                            vendor_code: vendor.vendor_code || '',
                            vendor_name: vendor.vendor_name || '',
                            vendor_type: vendor.vendor_type || '',
                            gstin: vendor.gstin || '',
                            pan_number: vendor.pan_number || '',
                            msme_category: vendor.msme_category || '',
                            payment_terms: vendor.payment_terms || '',
                            credit_days: vendor.credit_days || 0,
                            currency_id: vendor.currency_id || '',
                            delivery_terms: vendor.delivery_terms || '',
                            bank_name: vendor.bank_name || '',
                            bank_account_no: vendor.bank_account_no || '',
                            ifsc_code: vendor.ifsc_code || '',
                            is_approved: vendor.is_approved || false,
                            rating_score: vendor.rating_score || '',
                            blacklisted: vendor.blacklisted || false,
                            is_active: vendor.is_active !== false
                        };

                        // Map Contacts
                        if (vendor.contacts && vendor.contacts.length > 0) {
                            this.contacts = vendor.contacts.map(c => ({
                                id: c.id,
                                contact_name: c.contact_name || '',
                                contact_email: c.email || '', // mapping 'email' from API to 'contact_email'
                                contact_phone: c.phone || '', // mapping 'phone' from API to 'contact_phone'
                                contact_type: c.contact_type || 'PRIMARY',
                                is_primary: !!c.is_primary
                            }));
                            const primary = this.contacts.find(c => c.is_primary);
                            if (primary) this.primaryContactId = primary.id;
                        } else {
                            this.addContact();
                        }

                        // Map Temp fields for select validation logic
                        const standardTypes = ['SUPPLIER', 'SERVICE', 'TRADER'];
                        this.temp.vendor_type = standardTypes.includes(this.form.vendor_type) ? this.form.vendor_type : (this.form.vendor_type ? '_OTHER_' : '');
                        
                        const standardMSME = ['MICRO', 'SMALL', 'MEDIUM'];
                        this.temp.msme_category = standardMSME.includes(this.form.msme_category) ? this.form.msme_category : (this.form.msme_category ? '_OTHER_' : '');
                        
                        const standardTerms = ['NET30', 'NET60', 'ADVANCE', 'COD'];
                        this.temp.payment_terms = standardTerms.includes(this.form.payment_terms) ? this.form.payment_terms : (this.form.payment_terms ? '_OTHER_' : '');
                        
                        const standardInco = ['EXW', 'DDP', 'CIF', 'FOB'];
                        this.temp.delivery_terms = standardInco.includes(this.form.delivery_terms) ? this.form.delivery_terms : (this.form.delivery_terms ? '_OTHER_' : '');
                        
                        // Sync currency select with a tick to ensure options have rendered
                        this.$nextTick(() => {
                            this.temp.currency_id = vendor.currency_id ? String(vendor.currency_id) : '';
                        });

                    } else {
                        throw new Error('Invalid vendor data received');
                    }
                } catch (error) {
                    console.error('Failed to load vendor:', error);
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: 'Failed to load vendor data.', type: 'error' }
                    }));
                    setTimeout(() => {
                        window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/vendors" : "/org/" . $organization->org_slug . "/vendors") }}';
                    }, 1500);
                } finally {
                    this.loading = false;
                }
            },

            addContact() {
                this.contacts.push({ 
                    id: Date.now(), 
                    contact_name: '', 
                    contact_email: '', 
                    contact_phone: '', 
                    contact_type: 'SUPPORT', 
                    is_primary: this.contacts.length === 0 
                });
                if (this.contacts.length === 1) this.primaryContactId = this.contacts[0].id;
            },

            removeContact(index) {
                if (this.contacts.length > 1) {
                    const removed = this.contacts.splice(index, 1)[0];
                    if (removed.id === this.primaryContactId) {
                        this.setPrimaryContact(this.contacts[0]);
                    }
                }
            },

            setPrimaryContact(contact) {
                this.contacts.forEach(c => { 
                    c.is_primary = false;
                    if (c.contact_type === 'PRIMARY') c.contact_type = 'SUPPORT';
                });
                contact.is_primary = true;
                contact.contact_type = 'PRIMARY';
                this.primaryContactId = contact.id;
            },

            handleCurrencyChange() {
                this.form.currency_id = this.temp.currency_id === '_OTHER_' ? '' : this.temp.currency_id;
            },

            async saveNewCurrency() {
                if (!this.newCurrency.currency_code || !this.newCurrency.currency_name) {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: 'Currency Code and Name are required.', type: 'error' }
                    }));
                    return;
                }
                this.creatingCurrency = true;
                try {
                    const response = await fetch('/api/v1/currencies', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ ...this.newCurrency, is_active: true })
                    });
                    const data = await response.json();
                    if (!response.ok || !data || data.success !== true) throw new Error(data.message || 'Error creating currency');
                    
                    const newCurr = data.data.currency || data.data;
                    this.currencies.push(newCurr);
                    this.form.currency_id = newCurr.id;
                    this.temp.currency_id = newCurr.id;
                    this.newCurrency = { currency_code: '', currency_symbol: '', currency_name: '' };
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: 'Currency created dynamically.', type: 'success' }
                    }));
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: error.message || 'Failed to create currency.', type: 'error' }
                    }));
                } finally {
                    this.creatingCurrency = false;
                }
            },

            async loadCurrencies() {
                try {
                    const response = await fetch('/api/v1/currencies', {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.data) this.currencies = data.data.currencies || [];
                    }
                } catch (error) {
                    console.error('Failed to load currencies:', error);
                }
            },

            async submitForm() {
                this.saving = true;
                try {
                    const submitData = { ...this.form, contacts: this.contacts };
                    const response = await fetch(`/api/v1/vendors/${this.vendorId}`, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(submitData)
                    });

                    const data = await response.json();
                    if (!response.ok || !data || data.success !== true) throw new Error(data.message || 'Failed to update vendor');

                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: 'Vendor updated successfully!', type: 'success' }
                    }));
                    setTimeout(() => {
                        window.location.href = '{{ url(request()->get("tenant_type") === "subdomain" ? "/vendors" : "/org/" . $organization->org_slug . "/vendors") }}';
                    }, 1500);
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: error.message || 'Failed to update vendor. Please try again.', type: 'error' }
                    }));
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endsection