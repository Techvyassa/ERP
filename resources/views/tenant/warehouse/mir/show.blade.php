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
                    <span class="text-[10px] font-bold text-slate-400 font-mono" x-text="mir?.created_at ? new Date(mir.created_at).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '...'"></span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight" x-text="(mir?.mir_no || '...')"></h2>
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
                        Approve All & Start Picking
                    </button>
                </div>
            </template>
            <template x-if="mir?.status === 'APPROVED'">
                <span class="px-4 py-2 bg-blue-50 text-blue-700 ring-1 ring-blue-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">thumb_up</span>
                    Approved — Ready to Pick
                </span>
            </template>
            <template x-if="mir?.status === 'PARTIALLY_ISSUED'">
                <span class="px-4 py-2 bg-orange-50 text-orange-700 ring-1 ring-orange-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">hourglass_top</span>
                    Partially Issued
                </span>
            </template>
            <template x-if="mir?.status === 'FULLY_ISSUED'">
                <span class="px-4 py-2 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    Fully Issued
                </span>
            </template>
            <template x-if="mir?.status === 'CLOSED'">
                <span class="px-4 py-2 bg-slate-100 text-slate-600 ring-1 ring-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-sm">lock</span>
                    Closed
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
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Reference No</p>
                        <p class="text-sm font-black text-slate-900 font-mono" x-text="mir?.request_no || mir?.order_no || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Final Product</p>
                        <p class="text-sm font-bold text-slate-800" x-text="mir?.product_name || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Batch Target</p>
                        <p class="text-sm font-black text-slate-900">
                            <span x-text="mir?.target_qty ?? '—'"></span>
                            <span class="text-[10px] text-slate-400 uppercase ml-1" x-text="mir?.uom_name || mir?.uom || ''"></span>
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
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Materials Lists</h3>
                    </div>
                    <span class="px-2 py-1 bg-slate-200 text-slate-600 rounded text-[10px] font-black uppercase tracking-widest"
                        x-text="(mir?.lines?.length ?? 0) + ' Components'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/20 border-b border-gray-50">
                                <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Component</th>
                                <th class="px-3 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Required</th>
                                <th class="px-3 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Available</th>
                                <th class="px-3 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Issued</th>
                                <th class="px-3 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-3 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="line in mir?.lines" :key="line.id">
                                <tr class="hover:bg-slate-50/30 transition-all group">
                                    <td class="px-4 py-3 leading-none">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800" x-text="line.material_name"></span>
                                            <span class="text-[10px] text-slate-400 font-mono" x-text="line.material_code"></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center leading-none">
                                        <span class="text-sm font-black text-slate-700" x-text="parseFloat(line.required_qty).toFixed(3)"></span>
                                        <span class="text-[9px] font-medium text-slate-400 uppercase ml-1" x-text="line.uom_name || line.uom"></span>
                                    </td>
                                    <td class="px-3 py-3 text-center leading-none">
                                        <template x-if="getStockForMaterial(line.material_id)">
                                            <div class="flex flex-col items-center">
                                                <span class="px-2 py-1 rounded-lg text-xs font-black"
                                                    :class="parseFloat(getStockForMaterial(line.material_id).total_available) >= parseFloat(line.required_qty) 
                                                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' 
                                                        : 'bg-red-100 text-red-700 border border-red-200'"
                                                    x-text="parseFloat(getStockForMaterial(line.material_id).total_available || 0).toFixed(3)"></span>
                                                <span class="text-[9px] text-slate-400 mt-1" x-text="line.uom_name || line.uom"></span>
                                            </div>
                                        </template>
                                        <template x-if="!getStockForMaterial(line.material_id)">
                                            <span class="text-[10px] text-slate-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-3 text-center leading-none">
                                        <span class="text-sm font-black text-emerald-600" x-text="parseFloat(line.issued_qty || 0).toFixed(3)"></span>
                                        <span class="text-[9px] font-medium text-slate-400 uppercase ml-1" x-text="line.uom_name || line.uom"></span>
                                    </td>
                                    <td class="px-3 py-3 text-center leading-none">
                                        <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                            :class="lineStatusClass(line.status)" x-text="lineStatusLabel(line.status)"></span>
                                    </td>
                                    <td class="px-3 py-3 text-right leading-none">
                                        <!-- APPROVED or PARTIALLY_PICKED: Issue -->
                                        <div x-show="['APPROVED','PARTIALLY_PICKED'].includes(line.status) && ['APPROVED','PARTIALLY_ISSUED','FULLY_ISSUED'].includes(mir?.status)" x-cloak>
                                            <button @click="openScanModal(line)"
                                                class="inline-flex items-center gap-1 px-2 py-1.5 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-slate-800 transition-all shadow-md active:scale-95">
                                                <span class="material-symbols-outlined text-xs">outbox</span>
                                                Issue
                                            </button>
                                        </div>
                                        <!-- PENDING: waiting for MIR approval -->
                                        <div x-show="line.status === 'PENDING'" x-cloak>
                                            <span class="text-[9px] text-slate-400 font-semibold">Awaiting</span>
                                        </div>
                                        <!-- FULLY_PICKED -->
                                        <div x-show="line.status === 'FULLY_PICKED'" x-cloak>
                                            <div class="flex items-center gap-1 text-emerald-600">
                                                <span class="material-symbols-outlined text-xs">check_circle</span>
                                                <span class="text-[9px] font-bold">Done</span>
                                            </div>
                                        </div>
                                        <!-- REJECTED -->
                                        <div x-show="line.status === 'REJECTED'" x-cloak>
                                            <span class="text-[9px] text-red-600 font-bold">Rejected</span>
                                        </div>
                                            </div>
                                        </div>
                                        <!-- PARTIALLY_PICKED: show issued bins + Issue more button -->
                                        <div x-show="line.status === 'PARTIALLY_PICKED'" x-cloak class="flex flex-col items-end gap-2">
                                            <template x-if="line.transactions && line.transactions.length > 0">
                                                <div class="flex flex-col items-end gap-1">
                                                    <template x-for="txn in line.transactions" :key="txn.id">
                                                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-orange-50 border border-orange-100 rounded-lg">
                                                            <span class="material-symbols-outlined text-xs text-orange-500">shelves</span>
                                                            <span class="text-[10px] font-black text-orange-800 font-mono" x-text="txn.bin_code || '—'"></span>
                                                            <span class="text-[9px] text-orange-600 font-semibold" x-text="parseFloat(txn.issued_qty).toFixed(3)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                        <!-- REJECTED -->
                                        <div x-show="line.status === 'REJECTED'" x-cloak class="flex flex-col items-end gap-1">
                                            <span class="text-[10px] text-red-600 font-black">Rejected</span>
                                            <span class="text-[9px] text-slate-400" x-text="line.rejected_reason || ''"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Summary & Help -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Summary Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-5">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 mb-4 text-xs uppercase tracking-widest">
                    <span class="material-symbols-outlined text-amber-600">summarize</span>
                    Summary
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-black text-slate-800" x-text="mir?.lines?.length || 0"></p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase">Total Items</p>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-black text-emerald-600" x-text="mir?.summary?.fully_picked_lines || 0"></p>
                        <p class="text-[9px] font-bold text-emerald-400 uppercase">Issued</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-black text-amber-600" x-text="mir?.summary?.pending_lines || 0"></p>
                        <p class="text-[9px] font-bold text-amber-400 uppercase">Pending</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-black text-orange-600" x-text="mir?.summary?.partially_picked_lines || 0"></p>
                        <p class="text-[9px] font-bold text-orange-400 uppercase">Partial</p>
                    </div>
                </div>
            </div>

            <!-- Guidelines -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-5">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 mb-3 text-xs uppercase tracking-widest">
                    <span class="material-symbols-outlined text-blue-600">info</span>
                    Guidelines
                </h3>
                <ul class="space-y-2">
                    <li class="flex gap-2 text-[10px] text-gray-600">
                        <span class="text-blue-500 font-black">•</span>
                        <span>Review availability before approving</span>
                    </li>
                    <li class="flex gap-2 text-[10px] text-gray-600">
                        <span class="text-blue-500 font-black">•</span>
                        <span>Scan bin & material barcode for each issue</span>
                    </li>
                    <li class="flex gap-2 text-[10px] text-gray-600">
                        <span class="text-blue-500 font-black">•</span>
                        <span>Partial issuance is allowed</span>
                    </li>
                </ul>
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
                                <h3 class="text-lg font-black text-white tracking-tight">Issue Materials</h3>
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
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Required</p>
                            <p class="text-base font-black text-slate-900" x-text="selectedLine?.required_qty + ' ' + (selectedLine?.uom_name || selectedLine?.uom || '')"></p>
                        </div>
                        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100">
                            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Remaining</p>
                            <p class="text-base font-black text-amber-700" x-text="selectedLine?.remaining_qty + ' ' + (selectedLine?.uom_name || selectedLine?.uom || '')"></p>
                        </div>
                    </div>

                    <form @submit.prevent="submitScan()" class="space-y-5">
                        <div class="space-y-4">
                            <!-- Bin Barcode with suggestions -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">shelves</span>
                                    Storage Bin
                                </label>

                                <!-- Loading bins -->
                                <template x-if="binsLoading">
                                    <div class="flex items-center gap-2 py-2 text-slate-400 text-xs">
                                        <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                                        Loading available bins...
                                    </div>
                                </template>

                                <!-- Bin suggestions (when stock found) -->
                                <template x-if="!binsLoading && availableBins.length > 0">
                                    <div class="mb-2 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <p class="text-[10px] text-slate-400 font-semibold">All bin locations:</p>
                                            <p class="text-[10px] text-slate-400">
                                                Total available:
                                                <span class="font-black text-emerald-600"
                                                    x-text="availableBins.reduce((s,b) => s + parseFloat(b.qty_available||0), 0).toFixed(3)">
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="bin in availableBins" :key="bin.bin_code">
                                                <button type="button"
                                                    @click="parseFloat(bin.qty_available) > 0 && selectBin(bin)"
                                                    :disabled="parseFloat(bin.qty_available) <= 0"
                                                    :class="{
                                                        'bg-slate-900 text-white border-slate-900': scanForm.bin_barcode === bin.bin_code,
                                                        'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100': parseFloat(bin.qty_available) > 0 && scanForm.bin_barcode !== bin.bin_code,
                                                        'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed': parseFloat(bin.qty_available) <= 0,
                                                    }"
                                                    class="px-3 py-1.5 rounded-lg text-[10px] font-black border transition-all flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-xs">shelves</span>
                                                    <span x-text="bin.bin_code"></span>
                                                    <span class="font-semibold opacity-70"
                                                        :class="parseFloat(bin.qty_available) > 0 ? 'text-emerald-600' : 'text-gray-300'"
                                                        x-text="parseFloat(bin.qty_available) > 0 ? '(' + parseFloat(bin.qty_available).toFixed(3) + ')' : '(empty)'">
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- No bins at all -->
                                <template x-if="!binsLoading && availableBins.length === 0 && selectedLine">
                                    <div class="mb-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2">
                                        <span class="material-symbols-outlined text-amber-500 text-sm">warning</span>
                                        <span class="text-[10px] text-amber-700 font-bold">No stock records found for this material. Enter bin manually.</span>
                                    </div>
                                </template>

                                <input type="text" x-model="scanForm.bin_barcode" required
                                    placeholder="Select above or enter bin code..."
                                    class="w-full px-4 py-3.5 bg-gray-50 border-none rounded-2xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all font-mono placeholder:text-slate-300">
                            </div>

                            <!-- Material Code — auto-filled, editable -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">barcode_scanner</span>
                                    Material Code
                                    <span class="text-emerald-500 font-semibold normal-case tracking-normal">(auto-filled)</span>
                                </label>
                                <input type="text" x-model="scanForm.material_barcode" required
                                    placeholder="Material code..."
                                    class="w-full px-4 py-3.5 bg-gray-50 border-none rounded-2xl text-sm font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 transition-all font-mono placeholder:text-slate-300">
                            </div>

                            <!-- Issue Quantity — read-only, pre-filled with remaining qty -->
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs">numbers</span>
                                    Issue Quantity
                                    <span class="text-slate-400 font-semibold normal-case tracking-normal ml-auto"
                                        x-text="'max: ' + selectedLine?.remaining_qty + ' ' + (selectedLine?.uom_name || selectedLine?.uom || '')"></span>
                                </label>
                                <div class="relative">
                                    <input type="number"
                                        :value="scanForm.quantity"
                                        readonly
                                        class="w-full px-4 py-3.5 bg-slate-100 border-none rounded-2xl text-sm font-black text-slate-500 cursor-not-allowed select-none">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase" x-text="selectedLine?.uom_name || selectedLine?.uom || ''"></span>
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
            availableBins: [],
            binsLoading: false,
            scanForm: {
                bin_barcode: '',
                material_barcode: '',
                quantity: 0
            },
            scanError: '',
            rejectionReason: '',
            stockLoading: false,
            stockData: [],

            async init() {
                await this.loadMIR();
            },

            async loadMIR() {
                this.loading = true;
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}`;
                    const res = await fetch(apiUrl, { headers: headers() });
                    const data = await res.json();
                    if (data.success) {
                        // API returns data.data as the MIR object directly (not data.data.mir)
                        const raw = data.data;
                        // Flatten nested material fields for template access
                        raw.lines = (raw.lines || []).map(line => ({
                            ...line,
                            material_name: line.material?.name,
                            material_code: line.material?.code,
                            material_id: line.material_id || line.material?.id,
                            transactions: line.transactions || [],
                        }));
                        this.mir = raw;
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: data.message || 'Failed to load MIR',
                                type: 'error'
                            }
                        }));
                        setTimeout(() => {
                            window.location.href = `/org/${orgSlug}/warehouse/mir`;
                        }, 1500);
                    }
                    // Load stock data after MIR is loaded
                    this.loadStockData();
                } catch (e) {
                    console.error('Error loading MIR:', e);
                } finally {
                    this.loading = false;
                }
            },

            async loadStockData() {
                if (!this.mir || !this.mir.lines || this.mir.lines.length === 0) return;
                
                this.stockLoading = true;
                this.stockData = [];
                
                try {
                    // Get unique material IDs from MIR lines
                    const materialIds = [...new Set(this.mir.lines.map(line => line.material_id).filter(Boolean))];
                    
                    // Fetch stock for each material
                    const stockPromises = materialIds.map(materialId => 
                        fetch(`${window.location.origin}/api/v1/lookup/material-bins?material_id=${materialId}`, { headers: headers() })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success && data.data) {
                                    const line = this.mir.lines.find(l => l.material_id == materialId);
                                    return {
                                        material_id: materialId,
                                        material_code: line?.material_code || '',
                                        material_name: line?.material_name || '',
                                        uom_code: line?.uom_name || line?.uom || '',
                                        total_available: data.data.reduce((sum, bin) => sum + parseFloat(bin.qty_available || 0), 0),
                                        bins: data.data
                                    };
                                }
                                return null;
                            })
                            .catch(err => {
                                console.error('Error fetching stock for material', materialId, err);
                                return null;
                            })
                    );
                    
                    const results = await Promise.all(stockPromises);
                    this.stockData = results.filter(r => r !== null);
                } catch (e) {
                    console.error('Error loading stock data:', e);
                } finally {
                    this.stockLoading = false;
                }
            },

            approveMIR() {
                window.dispatchEvent(new CustomEvent('open-confirm', {
                    detail: {
                        title: 'Approve MIR',
                        message: 'Approve this MIR? All materials will be marked as approved and the store can start issuing.',
                        confirmText: 'Approve All',
                        confirmColor: 'blue',
                        onConfirm: () => this.executeApproval()
                    }
                }));
            },

            async executeApproval() {
                this.processing = true;
                try {
                    const apiUrl = `${window.location.origin}/api/v1/material-issue-requests/${mirId}/approve`;
                    const res = await fetch(apiUrl, {
                        method: 'PATCH',
                        headers: headers()
                    });
                    const data = await res.json();
                    if (data.success) {
                        await this.loadMIR();
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: 'MIR approved successfully',
                                type: 'success'
                            }
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: data.message || 'Failed to approve MIR',
                                type: 'error'
                            }
                        }));
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
                        method: 'PATCH',
                        headers: headers(),
                        body: JSON.stringify({ rejection_reason: this.rejectionReason })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showRejectModal = false;
                        await this.loadMIR();
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: 'MIR rejected successfully',
                                type: 'success'
                            }
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: data.message || 'Failed to reject MIR',
                                type: 'error'
                            }
                        }));
                    }
                } catch (e) {
                    console.error('Error rejecting MIR:', e);
                } finally {
                    this.processing = false;
                }
            },

            openScanModal(line) {
                this.selectedLine = line;
                this.availableBins = [];   // reset bins before fetching
                this.binsLoading = false;
                this.scanError = '';
                this.scanForm = {
                    bin_barcode: '',
                    material_barcode: line.material_code || line.material?.code || '',
                    quantity: parseFloat(line.remaining_qty ?? 0),
                };
                this.showScanModal = true;
                this.fetchBins(line);
            },

            async fetchBins(line) {
                // Resolve material_id from either the flattened field or nested object
                const materialId = line.material_id || line.material?.id;
                if (!materialId) return;
                this.binsLoading = true;
                try {
                    const res = await fetch(
                        `${window.location.origin}/api/v1/lookup/material-bins?material_id=${materialId}`,
                        { headers: headers() }
                    );
                    const data = await res.json();
                    // Show ALL bins (including zero-stock), sorted by available qty desc
                    this.availableBins = data.success ? (data.data || []) : [];
                    // Auto-select first bin that has available stock
                    const firstAvailable = this.availableBins.find(b => parseFloat(b.qty_available) > 0);
                    if (firstAvailable && !this.scanForm.bin_barcode) {
                        this.selectBin(firstAvailable);
                    }
                } catch (e) {
                    console.error('fetchBins error', e);
                    this.availableBins = [];
                } finally {
                    this.binsLoading = false;
                }
            },

            selectBin(bin) {
                this.scanForm.bin_barcode = bin.bin_code;
            },

            async submitScan() {
                this.processing = true;
                this.scanError = '';
                const qty = parseFloat(this.scanForm.quantity);
                const remaining = parseFloat(this.selectedLine?.remaining_qty ?? 0);
                if (!qty || qty <= 0) {
                    this.scanError = 'Issue quantity must be greater than 0.';
                    this.processing = false;
                    return;
                }
                if (qty > remaining + 0.001) {
                    this.scanError = `Cannot issue ${qty}. Remaining is ${remaining}.`;
                    this.processing = false;
                    return;
                }
                try {
                    // Use the new MIR line issue endpoint
                    const apiUrl = `${window.location.origin}/api/v1/mir-lines/${this.selectedLine.id}/issue`;
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: headers(),
                        body: JSON.stringify({
                            issued_qty: parseFloat(this.scanForm.quantity),
                            notes: [this.scanForm.bin_barcode, this.scanForm.material_barcode].filter(Boolean).join(' | ')
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showScanModal = false;
                        await this.loadMIR();
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                message: data.data?.line?.status === 'FULLY_PICKED'
                                    ? 'Material fully issued!'
                                    : 'Partial issue recorded.',
                                type: 'success'
                            }
                        }));
                    } else {
                        this.scanError = data.message || 'Issue failed. Please try again.';
                    }
                } catch (e) {
                    this.scanError = 'A connection error occurred. Please try again.';
                    console.error('Error issuing material:', e);
                } finally {
                    this.processing = false;
                }
            },

            lineStatusLabel(status) {
                const map = {
                    'PENDING': 'Pending',
                    'APPROVED': 'Approved',
                    'PARTIALLY_PICKED': 'Partial',
                    'FULLY_PICKED': 'Issued',
                    'REJECTED': 'Rejected',
                };
                return map[status] || status || '—';
            },

            getStockForMaterial(materialId) {
                return this.stockData.find(s => s.material_id == materialId);
            },

            lineStatusClass(status) {
                switch (status) {
                    case 'PENDING':          return 'bg-amber-50 text-amber-700 ring-1 ring-amber-100';
                    case 'APPROVED':         return 'bg-blue-50 text-blue-700 ring-1 ring-blue-100';
                    case 'PARTIALLY_PICKED': return 'bg-orange-50 text-orange-700 ring-1 ring-orange-100';
                    case 'FULLY_PICKED':     return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100';
                    case 'REJECTED':         return 'bg-red-50 text-red-700 ring-1 ring-red-100';
                    default:                 return 'bg-slate-50 text-slate-700 ring-1 ring-slate-100';
                }
            },
        }
    }
</script>
@endsection