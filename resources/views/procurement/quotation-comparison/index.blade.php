@extends('layouts.procurement')

@section('title', 'Quotation Comparison')
@section('page-title', 'Quotation Comparison')

@section('content')
<div x-data="quotationComparisonData()" x-init="init()">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Vendor Quotation Comparison</h2>
                <p class="text-gray-600 mt-1">Compare vendor quotations for purchase requisitions</p>
            </div>
            <button @click="showUploadModal = true" 
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">upload</span>
                Upload Quotation
            </button>
        </div>
    </div>

    <!-- PR List -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Purchase Requisitions</h3>
                <div class="flex items-center gap-3">
                    <button @click="loadData()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">refresh</span>
                        <span class="text-sm">Refresh</span>
                    </button>
                    <input type="text" x-model="search" @input="loadData()" placeholder="Search PR Number..." 
                           class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PR Number</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Quotations</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendors</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created At</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No quotations found</td></tr>
                    </template>
                    <template x-for="item in items" :key="item.pr_number">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-primary" x-text="item.pr_number"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium" x-text="item.quotation_count + ' quotations'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="vendor in item.vendors" :key="vendor">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs" x-text="vendor"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <template x-if="item.is_selected">
                                    <div class="space-y-1">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold flex items-center gap-1 w-fit">
                                            <span class="material-symbols-outlined text-xs">check_circle</span>
                                            Selected
                                        </span>
                                        <p class="text-xs text-gray-600">Vendor: <span class="font-semibold" x-text="item.selected_vendor"></span></p>
                                    </div>
                                </template>
                                <template x-if="!item.is_selected">
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">Pending Selection</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-gray-600" x-text="formatDate(item.created_at)"></td>
                            <td class="px-6 py-4 text-center">
                                <template x-if="!item.is_selected">
                                    <button @click="viewComparison(item.pr_number)" 
                                            class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-xs">
                                        Compare
                                    </button>
                                </template>
                                <template x-if="item.is_selected">
                                    <button @click="viewComparison(item.pr_number)" 
                                            class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors text-xs">
                                        View
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload Modal -->
    <div x-show="showUploadModal" x-cloak 
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showUploadModal = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 sticky top-0 bg-white">
                <h2 class="text-lg font-bold text-gray-900">Upload Vendor Quotation</h2>
            </div>
            <form @submit.prevent="uploadQuotation()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PR Number *</label>
                        <select x-model="uploadForm.pr_number" required 
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select Approved PR</option>
                            <template x-for="pr in approvedPRs" :key="pr.id">
                                <option :value="pr.pr_number" x-text="pr.pr_number"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Only APPROVED purchase requisitions are shown</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor *</label>
                        <select x-model="uploadForm.vendor_id" required 
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select Vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option :value="vendor.id" x-text="vendor.vendor_code + ' - ' + vendor.vendor_name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Type *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" x-model="uploadForm.upload_type" value="form" class="text-primary">
                            <span class="text-sm">Manual Form</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" x-model="uploadForm.upload_type" value="csv" class="text-primary">
                            <span class="text-sm">CSV Upload</span>
                        </label>
                    </div>
                </div>

                <!-- CSV Upload -->
                <div x-show="uploadForm.upload_type === 'csv'">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">CSV File *</label>
                        <button type="button" @click="downloadCSVTemplate()" 
                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">download</span>
                            Download Template
                        </button>
                    </div>
                    <input type="file" @change="uploadForm.csv_file = $event.target.files[0]" accept=".csv" 
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Format: item_name, quantity, unit_price, delivery_date, remarks</p>
                </div>

                <!-- Manual Form -->
                <div x-show="uploadForm.upload_type === 'form'">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Line Items *</label>
                        <button type="button" @click="addQuotationItem()" 
                                class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">
                            Add Item
                        </button>
                    </div>
                    <div class="space-y-3 max-h-60 overflow-y-auto">
                        <template x-for="(item, index) in uploadForm.quotations" :key="index">
                            <div class="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded-lg">
                                <select x-model="item.item_name" required 
                                        class="col-span-3 px-2 py-1 border border-gray-200 rounded text-sm">
                                    <option value="">Select Material</option>
                                    <template x-for="material in materials" :key="material.id">
                                        <option :value="material.material_name" x-text="material.material_code + ' - ' + material.material_name"></option>
                                    </template>
                                </select>
                                <input type="number" x-model="item.quantity" placeholder="Qty" required min="0.001" step="0.001" 
                                       class="col-span-2 px-2 py-1 border border-gray-200 rounded text-sm">
                                <input type="number" x-model="item.unit_price" placeholder="Price" required min="0" step="0.01" 
                                       class="col-span-2 px-2 py-1 border border-gray-200 rounded text-sm">
                                <input type="date" x-model="item.delivery_date" 
                                       class="col-span-2 px-2 py-1 border border-gray-200 rounded text-sm">
                                <input type="text" x-model="item.remarks" placeholder="Remarks" 
                                       class="col-span-2 px-2 py-1 border border-gray-200 rounded text-sm">
                                <button type="button" @click="uploadForm.quotations.splice(index, 1)" 
                                        class="col-span-1 text-red-600 hover:text-red-800">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showUploadModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="uploading" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50">
                        <span x-show="!uploading">Upload</span>
                        <span x-show="uploading">Uploading...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-cloak 
         class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function quotationComparisonData() {
    return {
        items: [],
        vendors: [],
        approvedPRs: [],
        materials: [],
        loading: false,
        uploading: false,
        search: '',
        showUploadModal: false,
        uploadForm: {
            pr_number: '',
            vendor_id: '',
            upload_type: 'form',
            csv_file: null,
            quotations: []
        },
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await Promise.all([this.loadData(), this.loadVendors(), this.loadApprovedPRs(), this.loadMaterials()]);
        },

        async loadData() {
            this.loading = true;
            try {
                const token = localStorage.getItem('access_token');
                const url = '/api/v1/quotation-comparison' + (this.search ? '?pr_number=' + this.search : '');
                const response = await fetch(url, {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.items = data.data;
                }
            } catch (error) {
                console.error('Error loading data:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadVendors() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/quotation-comparison/vendors', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.vendors = data.data.vendors;
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
            }
        },

        async loadApprovedPRs() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/purchase-requisitions?status=APPROVED', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.approvedPRs = data.data.data || [];
                }
            } catch (error) {
                console.error('Error loading approved PRs:', error);
            }
        },

        async loadMaterials() {
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/purchase-requisitions/master/materials', {
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.materials = data.data.materials || [];
                }
            } catch (error) {
                console.error('Error loading materials:', error);
            }
        },

        addQuotationItem() {
            this.uploadForm.quotations.push({
                item_name: '',
                quantity: 1,
                unit_price: 0,
                delivery_date: '',
                remarks: ''
            });
        },

        async uploadQuotation() {
            if (!this.uploadForm.pr_number || !this.uploadForm.vendor_id) {
                this.showToast('Please fill all required fields', 'error');
                return;
            }

            if (this.uploadForm.upload_type === 'form' && this.uploadForm.quotations.length === 0) {
                this.showToast('Please add at least one item', 'error');
                return;
            }

            this.uploading = true;
            try {
                const token = localStorage.getItem('access_token');
                
                let response;
                if (this.uploadForm.upload_type === 'csv') {
                    // Use FormData for CSV upload
                    const formData = new FormData();
                    formData.append('pr_number', this.uploadForm.pr_number);
                    formData.append('vendor_id', this.uploadForm.vendor_id);
                    formData.append('upload_type', 'csv');
                    formData.append('csv_file', this.uploadForm.csv_file);

                    response = await fetch('/api/v1/quotation-comparison/upload', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                        body: formData
                    });
                } else {
                    // Use JSON for form upload
                    response = await fetch('/api/v1/quotation-comparison/upload', {
                        method: 'POST',
                        headers: { 
                            'Authorization': `Bearer ${token}`, 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json' 
                        },
                        body: JSON.stringify({
                            pr_number: this.uploadForm.pr_number,
                            vendor_id: this.uploadForm.vendor_id,
                            upload_type: 'form',
                            quotations: this.uploadForm.quotations
                        })
                    });
                }

                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.showUploadModal = false;
                    this.resetUploadForm();
                    await this.loadData();
                } else {
                    this.showToast(data.message || 'Upload failed', 'error');
                }
            } catch (error) {
                console.error('Error uploading:', error);
                this.showToast('Upload failed', 'error');
            } finally {
                this.uploading = false;
            }
        },

        resetUploadForm() {
            this.uploadForm = {
                pr_number: '',
                vendor_id: '',
                upload_type: 'form',
                csv_file: null,
                quotations: []
            };
        },

        viewComparison(prNumber) {
            window.location.href = '{{ url("/org/{$organization->org_slug}/procurement/quotation-comparison") }}/' + prNumber;
        },

        formatDate(val) {
            if (!val) return '—';
            const d = new Date(val);
            return isNaN(d) ? val : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        downloadCSVTemplate() {
            // Create CSV content with headers and sample row
            const csvContent = [
                'item_name,quantity,unit_price,delivery_date,remarks',
                'Sample Item,10,100.50,2026-04-15,Sample remarks here'
            ].join('\n');

            // Create blob and download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'quotation_upload_template.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showToast('CSV template downloaded', 'success');
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        }
    }
}
</script>
@endsection
