@extends('layouts.warehouse')

@section('title', 'Review MIR - ' . $organization->org_name)
@section('page-title', 'Review & Issue Material')

@section('content')
<div x-data="mirShowData()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/org/{{ $organization->org_slug }}/warehouse/mir" class="p-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-gray-600">arrow_back</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900" x-text="'MIR: ' + (mir?.mir_no || '...')"></h2>
                <p class="text-sm text-gray-500">Review request and scan materials for production</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="mir?.status === 'PENDING'">
                <div class="flex gap-2">
                    <button @click="approveMIR()" :disabled="processing"
                        class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition flex items-center gap-2 disabled:opacity-50">
                        <span class="material-symbols-outlined">check_circle</span>
                        Approve MIR
                    </button>
                    <button @click="openRejectModal()" :disabled="processing"
                        class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition flex items-center gap-2 disabled:opacity-50">
                        <span class="material-symbols-outlined">cancel</span>
                        Reject
                    </button>
                </div>
            </template>
            <template x-if="mir?.status === 'APPROVED'">
                <span class="px-3 py-1.5 bg-green-100 text-green-700 border border-green-200 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Approved & Ready to Issue
                </span>
            </template>
            <template x-if="mir?.status === 'REJECTED'">
                <span class="px-3 py-1.5 bg-red-100 text-red-700 border border-red-200 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">cancel</span>
                    Rejected
                </span>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details (Left/Center) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Production Order Info Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">precision_manufacturing</span>
                        Production Order Details
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Order Number</p>
                        <p class="text-sm font-bold text-primary" x-text="mir?.order_no || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Target Product</p>
                        <p class="text-sm font-bold text-gray-900" x-text="mir?.product_name || '—'"></p>
                        <p class="text-xs text-gray-500" x-text="mir?.product_code || ''"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Target Qty</p>
                        <p class="text-sm font-bold text-gray-900">
                            <span x-text="mir?.target_qty || '0'"></span>
                            <span class="text-xs text-gray-500 font-medium" x-text="mir?.uom"></span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Requested Date</p>
                        <p class="text-sm text-gray-700 font-medium" x-text="mir?.created_at || '—'"></p>
                    </div>
                    <div x-show="mir?.rejection_reason">
                        <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Rejection Reason</p>
                        <p class="text-sm text-red-600 font-medium" x-text="mir?.rejection_reason"></p>
                    </div>
                </div>
            </div>

            <!-- Material Lines Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">inventory_2</span>
                        Requested Raw Materials
                    </h3>
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-[10px] font-bold uppercase" x-text="mir?.lines?.length + ' Materials'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Material</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Required</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Issued</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="line in mir?.lines" :key="line.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-gray-900" x-text="line.material_name"></p>
                                        <p class="text-xs text-gray-500 font-mono" x-text="line.material_code"></p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <p class="text-sm font-bold text-gray-700">
                                            <span x-text="line.required_qty"></span>
                                            <span class="text-[10px] text-gray-400" x-text="line.uom"></span>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <p class="text-sm font-bold text-green-600">
                                            <span x-text="line.issued_qty"></span>
                                            <span class="text-[10px] text-gray-400" x-text="line.uom"></span>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                            :class="scanStatusClass(line.scan_status)" x-text="line.scan_status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button x-show="mir?.status === 'APPROVED' && line.scan_status !== 'ISSUED'"
                                            @click="openScanModal(line)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-white text-[10px] font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                            <span class="material-symbols-outlined text-sm">qr_code_scanner</span>
                                            SCAN & ISSUE
                                        </button>
                                        <div x-show="line.scan_status === 'ISSUED'" class="flex flex-col items-end">
                                            <p class="text-[10px] text-gray-400 font-medium italic" x-text="'Bin: ' + line.bin_barcode"></p>
                                            <p class="text-[10px] text-gray-400 font-medium italic" x-text="line.scanned_at"></p>
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
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showScanModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Scan & Issue Material</h3>
                        <p class="text-xs text-gray-500" x-text="selectedLine?.material_name"></p>
                    </div>
                    <button @click="showScanModal = false" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Progress Info -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-blue-500 uppercase mb-1">Requested</p>
                            <p class="text-sm font-bold text-blue-900" x-text="selectedLine?.required_qty + ' ' + selectedLine?.uom"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-blue-500 uppercase mb-1">Remaining</p>
                            <p class="text-sm font-bold text-blue-900" x-text="selectedLine?.remaining_qty + ' ' + selectedLine?.uom"></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitScan()" class="space-y-4">
                        <!-- Bin Barcode -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">shelves</span>
                                Step 1: Scan Bin Barcode *
                            </label>
                            <input type="text" x-model="scanForm.bin_barcode" required autofocus
                                placeholder="Scan or enter bin code..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-mono">
                        </div>

                        <!-- Material Barcode -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">barcode_scanner</span>
                                Step 2: Scan Material Barcode *
                            </label>
                            <input type="text" x-model="scanForm.material_barcode" required
                                placeholder="Scan or enter material code..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-mono">
                        </div>

                        <!-- Issue Quantity -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">numbers</span>
                                Issue Quantity
                            </label>
                            <div class="relative">
                                <input type="number" x-model="scanForm.quantity" step="0.001" required
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-bold">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-gray-400" x-text="selectedLine?.uom"></span>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-500 italic">Enter full remaining qty or partial amount.</p>
                        </div>

                        <!-- Error Message -->
                        <div x-show="scanError" x-text="scanError" class="p-3 bg-red-50 text-red-600 text-xs rounded-lg font-medium border border-red-100"></div>

                        <div class="pt-4 border-t border-gray-100 flex gap-3">
                            <button type="button" @click="showScanModal = false"
                                class="flex-1 py-3 px-4 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="processing"
                                class="flex-[2] py-3 px-4 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
                                <span x-show="!processing" class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg">check_circle</span>
                                    CONFIRM ISSUE
                                </span>
                                <span x-show="processing" class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    Processing...
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
                    case 'PENDING': return 'bg-amber-100 text-amber-700';
                    case 'PARTIAL': return 'bg-orange-100 text-orange-700';
                    case 'ISSUED': return 'bg-green-100 text-green-700';
                    default: return 'bg-gray-100 text-gray-700';
                }
            }
        }
    }
</script>
@endsection
