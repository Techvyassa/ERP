@extends('layouts.warehouse')

@section('title', 'Review MIR - ' . $organization->org_name)
@section('page-title', 'Review & Issue Material')

@section('content')
<div x-data="mirShowData()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="/org/{{ $organization->org_slug }}/warehouse/mir" 
               class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-slate-900 hover:border-slate-400 transition-all shadow-sm active:scale-95">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded uppercase tracking-widest">Logistic Request</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-[10px] font-bold text-slate-400 font-mono" x-text="mir?.created_at_human"></span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight" x-text="'MIR: ' + (mir?.mir_no || '...')"></h2>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <template x-if="mir?.status === 'PENDING'">
                <div class="flex items-center gap-3">
                    <button @click="openRejectModal()" :disabled="processing"
                        class="px-5 py-2.5 bg-white border border-red-200 text-red-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-red-50 transition-all shadow-sm active:scale-95 disabled:opacity-50">
                        Reject Request
                    </button>
                    <button @click="approveMIR()" :disabled="processing"
                        class="px-6 py-2.5 bg-slate-900 text-white font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all shadow-md active:scale-95 flex items-center gap-2 disabled:opacity-50">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        Approve For Picking
                    </button>
                </div>
            </template>
            <template x-if="mir?.status === 'APPROVED'">
                <span class="px-4 py-2 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    Ready for fulfillment
                </span>
            </template>
            <template x-if="mir?.status === 'REJECTED'">
                <span class="px-4 py-2 bg-red-50 text-red-700 ring-1 ring-red-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">block</span>
                    Request Rejected
                </span>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details (Left/Center) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Production Order Info Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-gray-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-xl">precision_manufacturing</span>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Origin Reference</h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Order No</p>
                        <p class="text-sm font-black text-slate-900 font-mono" x-text="mir?.order_no || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Final Product</p>
                        <p class="text-sm font-bold text-slate-800" x-text="mir?.product_name || '—'"></p>
                        <p class="text-[10px] font-black text-slate-400 uppercase" x-text="mir?.product_code || ''"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Batch Target</p>
                        <p class="text-sm font-black text-slate-900">
                            <span x-text="mir?.target_qty || '0'"></span>
                            <span class="text-[10px] text-slate-400 uppercase ml-1" x-text="mir?.uom"></span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Department</p>
                        <p class="text-sm font-bold text-slate-800">Production</p>
                    </div>
                </div>
                <template x-if="mir?.rejection_reason">
                    <div class="px-6 py-4 bg-red-50 border-t border-red-100 flex items-start gap-3">
                        <span class="material-symbols-outlined text-red-500 mt-0.5">report</span>
                        <div>
                            <p class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-1">Rejection Basis</p>
                            <p class="text-sm font-semibold text-red-800" x-text="mir?.rejection_reason"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Material Lines Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400 text-xl">inventory_2</span>
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Bill of Materials</h3>
                    </div>
                    <span class="px-2 py-1 bg-slate-200 text-slate-600 rounded text-[10px] font-black uppercase tracking-widest" x-text="mir?.lines?.length + ' Components'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/20 border-b border-gray-50">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Component</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Required</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Fitted</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Operation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="line in mir?.lines" :key="line.id">
                                <tr class="hover:bg-slate-50/30 transition-all group">
                                    <td class="px-6 py-4 leading-none">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-bold text-slate-800" x-text="line.material_name"></span>
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter" x-text="line.material_code"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center leading-none">
                                        <span class="text-sm font-black text-slate-700" x-text="line.required_qty"></span>
                                        <span class="text-[10px] font-medium text-slate-400 uppercase ml-1" x-text="line.uom"></span>
                                    </td>
                                    <td class="px-6 py-4 text-center leading-none">
                                        <span class="text-sm font-black text-emerald-600" x-text="line.issued_qty"></span>
                                        <span class="text-[10px] font-medium text-slate-400 uppercase ml-1" x-text="line.uom"></span>
                                    </td>
                                    <td class="px-6 py-4 text-center leading-none">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                            :class="scanStatusClass(line.scan_status)" x-text="line.scan_status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right leading-none">
                                        <button x-show="mir?.status === 'APPROVED' && line.scan_status !== 'ISSUED'"
                                            @click="openScanModal(line)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all shadow-md active:scale-95">
                                            <span class="material-symbols-outlined text-sm">qr_code_scanner</span>
                                            SCAN
                                        </button>
                                        <div x-show="line.scan_status === 'ISSUED'" class="flex flex-col items-end gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-xs text-emerald-500">shelves</span>
                                                <span class="text-[10px] text-slate-900 font-mono font-black" x-text="line.bin_barcode"></span>
                                            </div>
                                            <span class="text-[9px] text-slate-400 font-medium" x-text="line.scanned_at"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Availability Check & Help -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-6">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 mb-4 text-sm">
                    <span class="material-symbols-outlined text-warehouse">info</span>
                    Guidelines
                </h3>
                <ul class="space-y-3">
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold shrink-0">1</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Review the requested quantities against physical availability in the warehouse.</p>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold shrink-0">2</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Approve the MIR to allow the operator to start scanning and issuing materials.</p>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold shrink-0">3</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Both <strong>Bin Barcode</strong> and <strong>Material Barcode</strong> must be scanned for each line.</p>
                    </li>
                    <li class="flex gap-3">
                        <span class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold shrink-0">4</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Partial issuance is allowed; remaining quantities will be flagged as <span class="text-orange-600 font-bold uppercase">Partial</span>.</p>
                    </li>
                </ul>
            </div>

            <div class="bg-primary rounded-xl border border-primary shadow-sm overflow-hidden p-6 text-white">
                <h3 class="font-bold flex items-center gap-2 mb-4 text-sm text-warehouse">
                    <span class="material-symbols-outlined">inventory</span>
                    Quick Stock Look
                </h3>
                <p class="text-[11px] text-blue-200 mb-4">Click on a material in the list to view its current bin locations and available quantities.</p>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <!-- Placeholder or dynamic stock summary could go here -->
                    <p class="text-[10px] text-blue-300 italic">Select a line to check bin locations...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Modal -->
    <div x-show="showScanModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
                 x-show="showScanModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showScanModal = false"></div>
            
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100"
                 x-show="showScanModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                
                {{-- Modal Header --}}
                <div class="px-8 py-6 bg-gradient-to-r from-slate-800 to-slate-900 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 rounded-xl text-emerald-400">
                                <span class="material-symbols-outlined">qr_code_scanner</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight">Material Allocation</h3>
                                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest" x-text="selectedLine?.material_name"></p>
                            </div>
                        </div>
                        <button @click="showScanModal = false" class="text-white/60 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>

                <div class="p-8 space-y-6">
                    <!-- Progress Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Entitlement</p>
                            <p class="text-base font-black text-slate-900" x-text="selectedLine?.required_qty + ' ' + selectedLine?.uom"></p>
                        </div>
                        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100">
                            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Shortfall</p>
                            <p class="text-base font-black text-amber-700" x-text="selectedLine?.remaining_qty + ' ' + selectedLine?.uom"></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitScan()" class="space-y-5">
                        <div class="space-y-4">
                            <!-- Bin Barcode -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">shelves</span>
                                    Verify Storage Bin
                                </label>
                                <input type="text" x-model="scanForm.bin_barcode" required autofocus
                                    placeholder="Enter physical bin label..."
                                    class="w-full px-4 py-3.5 bg-gray-50 border-none rounded-2xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all font-mono placeholder:text-slate-300">
                            </div>

                            <!-- Material Barcode -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">barcode_scanner</span>
                                    Verify Component Tag
                                </label>
                                <input type="text" x-model="scanForm.material_barcode" required
                                    placeholder="Scan material identifier..."
                                    class="w-full px-4 py-3.5 bg-gray-50 border-none rounded-2xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all font-mono placeholder:text-slate-300">
                            </div>

                            <!-- Issue Quantity -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">numbers</span>
                                    Allocation Quantity
                                </label>
                                <div class="relative">
                                    <input type="number" x-model="scanForm.quantity" step="0.001" required
                                        class="w-full px-4 py-3.5 bg-gray-50 border-none rounded-2xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase" x-text="selectedLine?.uom"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <template x-if="scanError">
                            <div class="px-4 py-3 bg-red-50 text-red-600 text-xs rounded-xl font-bold flex items-center gap-2 border border-red-100">
                                <span class="material-symbols-outlined text-sm">error</span>
                                <span x-text="scanError"></span>
                            </div>
                        </template>

                        <div class="pt-2 flex gap-3">
                            <button type="submit" :disabled="processing"
                                class="flex-1 py-4 px-6 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-slate-800 transition-all flex items-center justify-center gap-2 disabled:opacity-50 shadow-lg active:scale-95 shadow-slate-200">
                                <span x-show="!processing" class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">check_circle</span>
                                    Commit Allocation
                                </span>
                                <span x-show="processing" class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    Synchronizing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-red-900 flex items-center gap-2">
                        <span class="material-symbols-outlined">cancel</span>
                        Reject Material Request
                    </h3>
                    <button @click="showRejectModal = false" class="text-red-400 hover:text-red-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">Please provide a reason for rejecting this MIR. This will be visible to the production team.</p>
                    <textarea x-model="rejectionReason" rows="3" required
                        placeholder="e.g., Insufficient stock, material damaged, wrong specification..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all"></textarea>
                    
                    <div class="pt-4 border-t border-gray-100 flex gap-3">
                        <button @click="showRejectModal = false"
                            class="flex-1 py-3 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50">
                            Back
                        </button>
                        <button @click="submitReject()" :disabled="processing || !rejectionReason"
                            class="flex-[2] py-3 bg-red-600 text-white text-sm font-bold rounded-xl hover:bg-red-700 disabled:opacity-50">
                            Confirm Rejection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function mirShowData() {
        const token = () => localStorage.getItem('access_token');
        const orgSlug = '{{ $organization->org_slug }}';
        const mirId = '{{ $mirId }}';
        const headers = () => {
            const h = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Org-Slug': orgSlug
            };
            const t = token();
            if (t && t !== 'null') {
                h['Authorization'] = `Bearer ${t}`;
            }
            return h;
        };

        return {
            mir: null,
            loading: false,
            processing: false,
            showScanModal: false,
            showRejectModal: false,
            selectedLine: null,
            scanForm: {
                bin_barcode: '',
                material_barcode: '',
                quantity: 0
            },
            scanError: '',
            rejectionReason: '',

            async init() {
                await this.loadMIR();
            },

            async loadMIR() {
                this.loading = true;
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}`;
                    const res = await fetch(apiUrl, {
                        headers: headers()
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.mir = data.data.mir;
                    } else {
                        alert(data.message || 'Failed to load MIR');
                        window.location.href = `/org/${orgSlug}/warehouse/mir`;
                    }
                } catch (e) {
                    console.error('Error loading MIR:', e);
                } finally {
                    this.loading = false;
                }
            },

            async approveMIR() {
                if (!confirm('Confirm approval for this MIR? This allows the operator to start issuing materials.')) return;
                
                this.processing = true;
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}/approve`;
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: headers()
                    });
                    const data = await res.json();
                    if (data.success) {
                        await this.loadMIR();
                        alert('MIR approved successfully');
                    } else {
                        alert(data.message || 'Failed to approve MIR');
                    }
                } catch (e) {
                    console.error('Error approving MIR:', e);
                } finally {
                    this.processing = false;
                }
            },

            openRejectModal() {
                this.rejectionReason = '';
                this.showRejectModal = true;
            },

            async submitReject() {
                this.processing = true;
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}/reject`;
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({ reason: this.rejectionReason })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showRejectModal = false;
                        await this.loadMIR();
                        alert('MIR rejected');
                    } else {
                        alert(data.message || 'Failed to reject MIR');
                    }
                } catch (e) {
                    console.error('Error rejecting MIR:', e);
                } finally {
                    this.processing = false;
                }
            },

            openScanModal(line) {
                this.selectedLine = line;
                this.scanForm = {
                    bin_barcode: '',
                    material_barcode: '',
                    quantity: line.remaining_qty
                };
                this.scanError = '';
                this.showScanModal = true;
                // Auto-focus bin barcode input
                setTimeout(() => {
                    const el = document.querySelector('input[placeholder*="Scan or enter bin"]');
                    if (el) el.focus();
                }, 100);
            },

            async submitScan() {
                this.processing = true;
                this.scanError = '';
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}/lines/${this.selectedLine.id}/scan`;
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify(this.scanForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showScanModal = false;
                        await this.loadMIR();
                        if (data.data.all_issued) {
                            alert('All materials issued successfully!');
                        } else {
                            alert('Material issued successfully.');
                        }
                    } else {
                        this.scanError = data.message || 'Scan validation failed';
                    }
                } catch (e) {
                    this.scanError = 'A connection error occurred. Please try again.';
                    console.error('Error scanning:', e);
                } finally {
                    this.processing = false;
                }
            },

            scanStatusClass(status) {
                switch(status) {
                    case 'PENDING': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-100';
                    case 'PARTIAL': return 'bg-orange-50 text-orange-700 ring-1 ring-orange-100';
                    case 'ISSUED': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100';
                    default: return 'bg-slate-50 text-slate-700 ring-1 ring-slate-100';
                }
            }
        }
    }
</script>
@endsection
