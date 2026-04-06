@extends('tenant.layouts.inventory')

@section('title', 'Create Materials')
@section('page-title', 'Create New Materials')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
@endpush

@section('content')
<div x-data="materialForm()" x-init="loadDropdowns()">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-40">
        <div class="bg-white rounded-xl p-6 flex items-center gap-3 shadow-xl">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium" x-text="loadingMessage">Loading...</span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-3 rounded-xl shadow-md">
                        <span class="material-symbols-outlined text-white text-2xl">inventory_2</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Create Materials</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Add one or more raw materials, packaging, consumables or semi-finished items</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-sm text-gray-700">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Mode Toggle -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-700">Entry Mode:</span>
                <div class="inline-flex bg-gray-100 rounded-lg p-1">
                    <button type="button" @click="mode = 'single'"
                        :class="mode === 'single' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                        class="px-4 py-1.5 rounded-md text-sm font-medium transition-all">
                        <span class="material-symbols-outlined text-sm mr-1 align-middle">edit</span>
                        Single (Detailed)
                    </button>
                    <button type="button" @click="mode = 'bulk'"
                        :class="mode === 'bulk' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                        class="px-4 py-1.5 rounded-md text-sm font-medium transition-all">
                        <span class="material-symbols-outlined text-sm mr-1 align-middle">table_rows</span>
                        Bulk (Multiple)
                    </button>
                </div>
                <p class="text-xs text-gray-400" x-show="mode === 'bulk'">Add multiple materials quickly in a table format</p>
            </div>
        </div>

        <!-- ============ SINGLE MODE (original detailed form) ============ -->
        <template x-if="mode === 'single'">
            <form @submit.prevent="submitSingle" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <!-- Basic Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">info</span>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Material Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Material Code <span class="text-red-500" x-show="!form.auto_generate_code">*</span>
                            </label>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" x-model="form.auto_generate_code"
                                        @change="handleAutoGenerateChange()"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label class="text-sm text-gray-700 cursor-pointer">Auto-generate code</label>
                                </div>
                                <div x-show="!form.auto_generate_code" x-transition>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-32">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
                                            <input type="text" x-model="form.manual_prefix" @input="updateManualCode()"
                                                maxlength="10" placeholder="RM"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                                        </div>
                                        <div class="flex items-center pb-6">
                                            <span class="text-gray-500 font-medium">-</span>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                                            <input type="text" x-model="form.manual_number" @input="updateManualCode()"
                                                maxlength="10" placeholder="0001"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                                        </div>
                                    </div>
                                    <input type="text" x-model="form.material_code" :required="!form.auto_generate_code"
                                        maxlength="30" placeholder="RM-0001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent mt-2">
                                    <template x-if="errors.material_code">
                                        <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.material_code) ? errors.material_code[0] : errors.material_code"></p>
                                    </template>
                                </div>
                                <div x-show="form.auto_generate_code" x-transition>
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <span class="material-symbols-outlined text-green-600 mr-2 text-lg">auto_fix_high</span>
                                            <div class="text-sm text-green-800">
                                                <p class="font-medium">Auto-generation enabled</p>
                                                <p class="text-xs mt-0.5">
                                                    Code prefix:
                                                    <span x-show="form.material_type === 'RAW'" class="font-mono font-semibold">RM-XXXX</span>
                                                    <span x-show="form.material_type === 'PACKAGING'" class="font-mono font-semibold">PKG-XXXX</span>
                                                    <span x-show="form.material_type === 'CONSUMABLE'" class="font-mono font-semibold">CON-XXXX</span>
                                                    <span x-show="form.material_type === 'SEMI'" class="font-mono font-semibold">SF-XXXX</span>
                                                    <span x-show="!form.material_type" class="text-gray-500">Select type first</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Material Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Material Name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.material_name" required maxlength="200"
                                placeholder="e.g. Cumin Seeds, Turmeric Powder..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <template x-if="errors.material_name">
                                <p class="mt-1 text-sm text-red-600" x-text="Array.isArray(errors.material_name) ? errors.material_name[0] : errors.material_name"></p>
                            </template>
                        </div>

                        <!-- Material Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Material Type <span class="text-red-500">*</span></label>
                            <select x-model="form.material_type" @change="handleMaterialTypeChange()" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Type</option>
                                <option value="RAW">Raw Material</option>
                                <option value="PACKAGING">Packaging</option>
                                <option value="CONSUMABLE">Consumable</option>
                                <option value="SEMI">Semi-finished</option>
                            </select>
                        </div>

                        <!-- Base UOM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Base UOM <span class="text-red-500">*</span></label>
                            <select x-model="form.uom_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select UOM</option>
                                <template x-for="uom in uoms" :key="uom.id">
                                    <option :value="uom.id" x-text="uom.uom_name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Purchase UOM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Purchase UOM</label>
                            <select x-model="form.purchase_uom_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Same as Base UOM</option>
                                <template x-for="uom in uoms" :key="uom.id">
                                    <option :value="uom.id" x-text="uom.uom_name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- HSN Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">HSN Code</label>
                            <select x-model="form.hsn_code_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select HSN Code</option>
                                <template x-for="hsn in hsnCodes" :key="hsn.id">
                                    <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Inventory Settings -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">warehouse</span>
                        Inventory Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Warehouse</label>
                            <select x-model="form.default_warehouse_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select Warehouse</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.warehouse_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                            <input type="number" x-model="form.reorder_level" min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Minimum stock level</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Safety Stock</label>
                            <input type="number" x-model="form.safety_stock" min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Buffer quantity</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lead Time (Days)</label>
                            <input type="number" x-model="form.lead_time_days" min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Shelf Life (Days)</label>
                            <input type="number" x-model="form.shelf_life_days" min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Quality & Costing -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">verified</span>
                        Quality & Costing
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.qc_required" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">QC Required</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Inspection Type</label>
                            <select x-model="form.inspection_type" :disabled="!form.qc_required"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100">
                                <option value="AQL">AQL</option>
                                <option value="100%">100% Inspection</option>
                                <option value="random">Random Sampling</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.is_batch_tracked" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Lot Tracking</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Standard Cost</label>
                            <input type="number" x-model="form.standard_cost" min="0" step="0.01"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valuation Method</label>
                            <select x-model="form.valuation_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="FIFO">FIFO</option>
                                <option value="LIFO">LIFO</option>
                                <option value="weighted">Weighted Average</option>
                                <option value="standard">Standard Cost</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Active Material</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t">
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm">Cancel</a>
                    <button type="submit" :disabled="loading"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 text-sm font-medium">
                        <span x-show="!loading">Create Material</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </template>

        <!-- ============ BULK MODE ============ -->
        <template x-if="mode === 'bulk'">
            <div class="space-y-6">
                <!-- Common Defaults Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-500">tune</span>
                        Default Values
                        <span class="text-xs font-normal text-gray-400 ml-2">Applied to all rows unless overridden</span>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Material Type</label>
                            <select x-model="bulkDefaults.material_type" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="RAW">Raw Material</option>
                                <option value="PACKAGING">Packaging</option>
                                <option value="CONSUMABLE">Consumable</option>
                                <option value="SEMI">Semi-finished</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Base UOM</label>
                            <select x-model="bulkDefaults.uom_id" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select</option>
                                <template x-for="uom in uoms" :key="uom.id">
                                    <option :value="uom.id" x-text="uom.uom_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">HSN Code</label>
                            <select x-model="bulkDefaults.hsn_code_id" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">None</option>
                                <template x-for="hsn in hsnCodes" :key="hsn.id">
                                    <option :value="hsn.id" x-text="hsn.hsn_code + ' - ' + hsn.description"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Warehouse</label>
                            <select x-model="bulkDefaults.default_warehouse_id" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">None</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.warehouse_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reorder Level</label>
                            <input type="number" x-model="bulkDefaults.reorder_level" min="0" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Safety Stock</label>
                            <input type="number" x-model="bulkDefaults.safety_stock" min="0" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Std. Cost</label>
                            <input type="number" x-model="bulkDefaults.standard_cost" min="0" step="0.01" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Valuation</label>
                            <select x-model="bulkDefaults.valuation_method" @change="applyDefaultsToAll()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="FIFO">FIFO</option>
                                <option value="LIFO">LIFO</option>
                                <option value="weighted">Weighted</option>
                                <option value="standard">Standard</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bulk Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-500">table_rows</span>
                            Materials
                            <span class="bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full" x-text="bulkItems.length + ' rows'"></span>
                        </h3>
                        <button type="button" @click="addBulkRow()"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg transition-colors text-sm font-medium">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Add Row
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-8">#</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Material Name *</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">UOM *</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">HSN</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Warehouse</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-20">Reorder</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-20">Safety</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-24">Std Cost</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-10">QC</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in bulkItems" :key="index">
                                    <tr class="hover:bg-indigo-50/30 transition-colors" :class="item._error ? 'bg-red-50/50' : ''">
                                        <td class="px-3 py-2 text-xs text-gray-400 font-medium" x-text="index + 1"></td>
                                        <td class="px-3 py-2">
                                            <input type="text" x-model="item.material_name" required placeholder="Material name"
                                                class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                :class="item._error ? 'border-red-300' : ''">
                                        </td>
                                        <td class="px-3 py-2">
                                            <select x-model="item.material_type" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <option value="RAW">Raw</option>
                                                <option value="PACKAGING">Pack</option>
                                                <option value="CONSUMABLE">Cons</option>
                                                <option value="SEMI">Semi</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select x-model="item.uom_id" required class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <option value="">Select</option>
                                                <template x-for="uom in uoms" :key="uom.id">
                                                    <option :value="uom.id" x-text="uom.uom_code || uom.uom_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select x-model="item.hsn_code_id" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <option value="">None</option>
                                                <template x-for="hsn in hsnCodes" :key="hsn.id">
                                                    <option :value="hsn.id" x-text="hsn.hsn_code"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select x-model="item.default_warehouse_id" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                <option value="">None</option>
                                                <template x-for="wh in warehouses" :key="wh.id">
                                                    <option :value="wh.id" x-text="wh.warehouse_name"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" x-model="item.reorder_level" min="0" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" x-model="item.safety_stock" min="0" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" x-model="item.standard_cost" min="0" step="0.01" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="checkbox" x-model="item.qc_required" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="removeBulkRow(index)" :disabled="bulkItems.length <= 1"
                                                class="text-red-400 hover:text-red-600 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="addBulkRows(5)"
                                class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">+ Add 5 rows</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="addBulkRows(10)"
                                class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">+ Add 10 rows</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="clearEmptyRows()"
                                class="text-sm text-gray-500 hover:text-gray-700 font-medium">Clear empty rows</button>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}"
                                class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-sm">Cancel</a>
                            <button type="button" @click="submitBulk()" :disabled="loading"
                                class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 text-sm font-medium">
                                <span x-show="!loading" class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">save</span>
                                    Create All (<span x-text="validBulkCount"></span> materials)
                                </span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                    Creating...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function materialForm() {
        return {
            loading: false,
            loadingMessage: 'Loading...',
            mode: 'single', // 'single' or 'bulk'
            errors: {},
            uoms: [],
            hsnCodes: [],
            warehouses: [],

            // Single-mode form
            form: {
                material_code: '',
                material_name: '',
                material_type: 'RAW',
                uom_id: '',
                purchase_uom_id: '',
                hsn_code_id: '',
                default_warehouse_id: '',
                reorder_level: 0,
                safety_stock: 0,
                lead_time_days: 0,
                shelf_life_days: '',
                qc_required: true,
                inspection_type: 'AQL',
                is_batch_tracked: false,
                standard_cost: 0,
                valuation_method: 'FIFO',
                is_active: true,
                auto_generate_code: true,
                manual_prefix: '',
                manual_number: ''
            },

            // Bulk-mode defaults
            bulkDefaults: {
                material_type: 'RAW', uom_id: '', hsn_code_id: '',
                default_warehouse_id: '', reorder_level: 0, safety_stock: 0,
                standard_cost: 0, valuation_method: 'FIFO'
            },

            // Bulk items array
            bulkItems: [],

            get validBulkCount() {
                return this.bulkItems.filter(i => i.material_name && i.material_name.trim() && i.uom_id).length;
            },

            init() {
                // Start with 3 empty rows in bulk mode
                for (let i = 0; i < 3; i++) this.addBulkRow();
            },

            newBulkRow() {
                return {
                    material_name: '',
                    material_type: this.bulkDefaults.material_type || 'RAW',
                    uom_id: this.bulkDefaults.uom_id || '',
                    hsn_code_id: this.bulkDefaults.hsn_code_id || '',
                    default_warehouse_id: this.bulkDefaults.default_warehouse_id || '',
                    reorder_level: this.bulkDefaults.reorder_level || 0,
                    safety_stock: this.bulkDefaults.safety_stock || 0,
                    standard_cost: this.bulkDefaults.standard_cost || 0,
                    valuation_method: this.bulkDefaults.valuation_method || 'FIFO',
                    qc_required: true,
                    _error: null
                };
            },

            addBulkRow() {
                this.bulkItems.push(this.newBulkRow());
            },

            addBulkRows(count) {
                for (let i = 0; i < count; i++) this.addBulkRow();
            },

            removeBulkRow(index) {
                if (this.bulkItems.length > 1) {
                    this.bulkItems.splice(index, 1);
                }
            },

            clearEmptyRows() {
                this.bulkItems = this.bulkItems.filter(i => i.material_name && i.material_name.trim());
                if (this.bulkItems.length === 0) this.addBulkRow();
            },

            applyDefaultsToAll() {
                this.bulkItems.forEach(item => {
                    if (!item.material_name || !item.material_name.trim()) {
                        // Only apply to rows that haven't been filled yet
                        item.material_type = this.bulkDefaults.material_type || item.material_type;
                        item.uom_id = this.bulkDefaults.uom_id || item.uom_id;
                        item.hsn_code_id = this.bulkDefaults.hsn_code_id || item.hsn_code_id;
                        item.default_warehouse_id = this.bulkDefaults.default_warehouse_id || item.default_warehouse_id;
                        item.reorder_level = this.bulkDefaults.reorder_level || item.reorder_level;
                        item.safety_stock = this.bulkDefaults.safety_stock || item.safety_stock;
                        item.standard_cost = this.bulkDefaults.standard_cost || item.standard_cost;
                        item.valuation_method = this.bulkDefaults.valuation_method || item.valuation_method;
                    }
                });
            },

            // ── Code generation helpers ──
            handleMaterialTypeChange() {
                if (!this.form.auto_generate_code) {
                    this.form.manual_prefix = this.getDefaultPrefix(this.form.material_type);
                    this.updateManualCode();
                }
            },
            handleAutoGenerateChange() {
                if (this.form.auto_generate_code) {
                    this.form.material_code = '';
                    this.form.manual_prefix = '';
                    this.form.manual_number = '';
                    this.errors.material_code = null;
                } else {
                    this.form.manual_prefix = this.getDefaultPrefix(this.form.material_type);
                    this.form.manual_number = '0001';
                    this.updateManualCode();
                }
            },
            getDefaultPrefix(type) {
                return {
                    RAW: 'RM',
                    PACKAGING: 'PKG',
                    CONSUMABLE: 'CON',
                    SEMI: 'SF'
                } [type] || 'MAT';
            },
            updateManualCode() {
                this.form.material_code = (this.form.manual_prefix && this.form.manual_number) ?
                    `${this.form.manual_prefix}-${this.form.manual_number}` : '';
            },

            // ── Dropdown loading ──
            async loadDropdowns() {
                this.loading = true;
                this.loadingMessage = 'Loading options...';
                try {
                    const [uomsRes, warehousesRes, hsnRes] = await Promise.all([
                        fetch('/api/v1/uoms', {
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json'
                            }
                        }),
                        fetch('/api/v1/warehouses', {
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json'
                            }
                        }),
                        fetch('/api/v1/hsn-codes', {
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                    ]);
                    if (uomsRes.ok) {
                        const d = await uomsRes.json();
                        this.uoms = Array.isArray(d.data) ? d.data : [];
                    }
                    if (warehousesRes.ok) {
                        const d = await warehousesRes.json();
                        this.warehouses = d.data?.warehouses || [];
                    }
                    if (hsnRes.ok) {
                        const d = await hsnRes.json();
                        this.hsnCodes = d.data?.hsn_codes || [];
                    }
                } catch (e) {
                    console.error('Failed to load dropdowns:', e);
                    this.showNotification('Failed to load options', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // ── Single submit ──
            async submitSingle() {
                this.loading = true;
                this.loadingMessage = 'Creating material...';
                this.errors = {};
                try {
                    const res = await fetch('/api/v1/materials', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.form)
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.error?.details) {
                            this.errors = data.error.details;
                            this.showNotification('Please fix validation errors', 'error');
                        } else {
                            this.showNotification(data.message || 'Failed to create material', 'error');
                        }
                        return;
                    }
                    this.showNotification('Material created successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}";
                    }, 1500);
                } catch (e) {
                    console.error('Failed:', e);
                    this.showNotification('Network error. Please try again.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // ── Bulk submit ──
            async submitBulk() {
                // Filter out empty rows
                const materials = this.bulkItems
                    .filter(i => i.material_name && i.material_name.trim() && i.uom_id)
                    .map(i => ({
                        material_name: i.material_name.trim(),
                        material_type: i.material_type || 'RAW',
                        uom_id: parseInt(i.uom_id),
                        hsn_code_id: i.hsn_code_id ? parseInt(i.hsn_code_id) : null,
                        default_warehouse_id: i.default_warehouse_id ? parseInt(i.default_warehouse_id) : null,
                        reorder_level: parseFloat(i.reorder_level) || 0,
                        safety_stock: parseFloat(i.safety_stock) || 0,
                        standard_cost: parseFloat(i.standard_cost) || 0,
                        qc_required: Boolean(i.qc_required)
                    }));

                if (materials.length === 0) {
                    this.showNotification('Please fill in at least one material name and UOM', 'error');
                    return;
                }

                this.loading = true;
                this.loadingMessage = `Creating ${materials.length} materials...`;
                try {
                    const res = await fetch('/api/v1/materials/bulk', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            materials
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.error?.details) {
                            // Mark errored rows
                            const errorRows = Array.isArray(data.error.details) ? data.error.details : [];
                            errorRows.forEach(err => {
                                if (this.bulkItems[err.row - 1]) {
                                    this.bulkItems[err.row - 1]._error = err.error;
                                }
                            });
                            this.showNotification(data.message || 'Some materials failed to create', 'error');
                        } else {
                            this.showNotification(data.message || 'Failed to create materials', 'error');
                        }
                        return;
                    }
                    const count = data.data?.created_count || materials.length;
                    const errCount = data.data?.error_count || 0;
                    this.showNotification(
                        `${count} material(s) created successfully!` + (errCount > 0 ? ` ${errCount} failed.` : ''),
                        errCount > 0 ? 'warning' : 'success'
                    );
                    setTimeout(() => {
                        window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/materials' : '/org/' . $organization->org_slug . '/materials') }}";
                    }, 1500);
                } catch (e) {
                    console.error('Bulk create failed:', e);
                    this.showNotification('Network error. Please try again.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            showNotification(message, type = 'info') {
                const colors = {
                    success: 'bg-green-500',
                    error: 'bg-red-500',
                    warning: 'bg-yellow-500',
                    info: 'bg-blue-500'
                };
                const icons = {
                    success: 'check_circle',
                    error: 'error',
                    warning: 'warning',
                    info: 'info'
                };
                const el = document.createElement('div');
                el.className = `fixed top-4 right-4 px-5 py-3 rounded-lg shadow-lg z-50 text-white text-sm font-medium flex items-center gap-2 ${colors[type] || colors.info}`;
                el.style.animation = 'slideIn 0.3s ease-out';
                el.innerHTML = `<span class="material-symbols-outlined text-lg">${icons[type] || 'info'}</span>${message}`;
                document.body.appendChild(el);
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transition = 'opacity 0.3s';
                    setTimeout(() => el.remove(), 300);
                }, 3500);
            }
        }
    }
</script>
<style>
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>
@endsection