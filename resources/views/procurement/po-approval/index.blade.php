@extends('layouts.procurement')

@section('title', 'PO Approval - ' . $organization->org_name)
@section('page-title', 'Purchase Order Approval')

@section('content')
<div x-data="poApprovalData()" x-init="init()">
    <!-- Header with Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-2xl">pending_actions</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.pending">0</h3>
            <p class="text-sm text-gray-600">Pending Approval</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.approved">0</h3>
            <p class="text-sm text-gray-600">Approved Today</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-red-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-red-600 text-2xl">cancel</span>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1" x-text="stats.rejected">0</h3>
            <p class="text-sm text-gray-600">Rejected Today</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" x-model="filters.search" @input="loadPendingPOs()" 
                       placeholder="PO Number, Vendor..." 
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select x-model="filters.status" @change="loadPendingPOs()" 
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="PENDING_APPROVAL">Pending Approval</option>
                    <option value="DRAFT">Draft</option>
                    <option value="OPEN">Approved</option>
                    <option value="">All Status</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Range</label>
                <select x-model="filters.amountRange" @change="loadPendingPOs()" 
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">All Amounts</option>
                    <option value="0-50000">₹0 - ₹50,000</option>
                    <option value="50000-200000">₹50,000 - ₹2,00,000</option>
                    <option value="200000-500000">₹2,00,000 - ₹5,00,000</option>
                    <option value="500000+">₹5,00,000+</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()" class="w-full px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Pending POs Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">PO Number</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Vendor</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">PO Date</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Total Amount</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-4 px-6 text-xs font-bold text-gray-500 uppercase">Created By</th>
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
                    
                    <template x-if="!loading && pendingPOs.length === 0">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500">
                                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">check_circle</span>
                                <p>No purchase orders pending approval</p>
                            </td>
                        </tr>
                    </template>
                    
                    <template x-for="po in pendingPOs" :key="po.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-primary cursor-pointer" 
                                      @click="viewDetails(po)" 
                                      x-text="po.po_number"></span>
                            </td>
                            <td class="py-4 px-6 text-gray-900" x-text="po.vendor ? po.vendor.vendor_name : 'N/A'"></td>
                            <td class="py-4 px-6 text-gray-600" x-text="formatDate(po.po_date)"></td>
                            <td class="py-4 px-6">
                                <span class="font-semibold text-gray-900" x-text="formatCurrency(po.grand_total)"></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold" 
                                      :class="getStatusClass(po.status)" 
                                      x-text="po.status"></span>
                            </td>
                            <td class="py-4 px-6 text-gray-600" x-text="po.created_by_user ? po.created_by_user.first_name + ' ' + po.created_by_user.last_name : 'N/A'"></td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewDetails(po)" 
                                            class="px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View Details">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </button>
                                    <button @click="approvePO(po)" 
                                            :disabled="po.status !== 'PENDING_APPROVAL' && po.status !== 'DRAFT'"
                                            class="px-3 py-1 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Approve">
                                        <span class="material-symbols-outlined text-lg">check_circle</span>
                                    </button>
                                    <button @click="rejectPO(po)" 
                                            :disabled="po.status !== 'PENDING_APPROVAL'"
                                            class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Reject">
                                        <span class="material-symbols-outlined text-lg">cancel</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PO Details Modal -->
    <div x-show="showDetailsModal" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showDetailsModal = false">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <template x-if="selectedPO">
                <div>
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-