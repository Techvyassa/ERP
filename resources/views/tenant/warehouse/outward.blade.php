@extends('layouts.warehouse')

@section('title', 'Outward — ' . $organization->org_name)
@section('page-title', 'Outward')

@section('content')
<div x-data="outwardApp()" x-init="init()">

    <!-- ── Start Picking Modal ─────────────────────────────────────────── -->
    <div x-show="pickingModal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.45)">
        <div @click.outside="closePickingModal()"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-[90vh]">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Start Picking</h2>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="'SO: ' + (pickingModal.so?.so_number ?? '—')"></p>
                </div>
                <button @click="closePickingModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                <!-- Picklist loading -->
                <div x-show="pickingModal.loadingPicklist" class="flex items-center gap-2 text-sm text-gray-400 py-2">
                    <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                    Loading picklist…
                </div>

                <template x-if="!pickingModal.loadingPicklist">
                <div class="space-y-5">

                <!-- Step 1: Pallet No -->
                <div x-show="!pickingModal.palletLocked">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pallet No <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input x-model="pickingModal.palletInput"
                               @keydown.enter="lockPallet()"
                               type="text"
                               placeholder="Enter or scan pallet number…"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
                        <button @click="lockPallet()"
                                :disabled="!pickingModal.palletInput.trim()"
                                class="bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            Set Pallet
                        </button>
                    </div>
                </div>

                <!-- Active Pallet badge + scan row -->
                <div x-show="pickingModal.palletLocked" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            <span class="material-symbols-outlined text-amber-600 text-base">pallet</span>
                            <span class="text-sm font-bold text-amber-700" x-text="'Pallet: ' + pickingModal.currentPallet"></span>
                        </div>
                        <button @click="pickingModal.palletLocked = false; pickingModal.palletInput = ''"
                                class="text-xs text-gray-400 hover:text-gray-600 underline">Change</button>
                    </div>

                    <!-- Scan / Select row -->
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <!-- Bin -->
                        <div class="col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Bin</label>
                            <div class="relative">
                                <input x-model="pickingModal.binSearch"
                                       @input="searchBins()"
                                       @focus="pickingModal.showBinDrop = true"
                                       @keydown.escape="pickingModal.showBinDrop = false"
                                       type="text"
                                       placeholder="Scan / type bin…"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
                                <ul x-show="pickingModal.showBinDrop && pickingModal.binOptions.length > 0"
                                    @click.outside="pickingModal.showBinDrop = false"
                                    class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto text-sm">
                                    <template x-for="b in pickingModal.binOptions" :key="b.bin_code">
                                        <li @click="selectBin(b)"
                                            class="px-3 py-2 hover:bg-amber-50 cursor-pointer flex justify-between">
                                            <span x-text="b.bin_code"></span>
                                            <span class="text-gray-400 text-xs" x-text="b.location_name ?? ''"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <!-- Item (picklist only — scan barcode or pick from dropdown) -->
                        <div class="col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Item</label>
                            <div class="relative">
                                <!-- Scan input: matches product_code from picklist -->
                                <input x-model="pickingModal.itemScan"
                                       @input="filterPicklistItems()"
                                       @focus="pickingModal.filteredPicklist = pickingModal.picklistItems; pickingModal.showItemDrop = true"
                                       @keydown.escape="pickingModal.showItemDrop = false"
                                       @keydown.enter="autoSelectIfOne()"
                                       :readonly="!!pickingModal.selectedItem"
                                       type="text"
                                       placeholder="Click to select or scan barcode…"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 pr-8"
                                       :class="pickingModal.selectedItem ? 'border-teal-400 bg-teal-50 cursor-default' : ''" />
                                <!-- Clear button when item selected -->
                                <button x-show="pickingModal.selectedItem"
                                        @click.prevent="pickingModal.selectedItem = null; pickingModal.itemScan = ''; pickingModal.qty = null; pickingModal.filteredPicklist = pickingModal.picklistItems; $nextTick(() => $el.previousElementSibling.focus())"
                                        class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 hover:text-red-500">
                                    <span class="material-symbols-outlined text-base">close</span>
                                </button>
                                <!-- Dropdown: full picklist on focus, filtered while typing -->
                                <ul x-show="pickingModal.showItemDrop && !pickingModal.selectedItem && pickingModal.filteredPicklist.length > 0"
                                    @click.outside="pickingModal.showItemDrop = false"
                                    class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-52 overflow-y-auto text-sm">
                                    <template x-for="it in pickingModal.filteredPicklist" :key="it.product_id">
                                        <li @mousedown.prevent="selectItem(it)"
                                            class="px-3 py-2.5 hover:bg-amber-50 cursor-pointer border-b border-gray-50 last:border-0">
                                            <div class="font-medium text-gray-800" x-text="it.product_name"></div>
                                            <div class="flex items-center gap-3 mt-0.5">
                                                <span class="text-xs text-gray-400 font-mono" x-text="it.product_code"></span>
                                                <span class="text-xs font-semibold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded"
                                                      x-text="'Qty: ' + it.ordered_qty"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                                <!-- No match state -->
                                <div x-show="pickingModal.showItemDrop && !pickingModal.selectedItem && pickingModal.filteredPicklist.length === 0 && pickingModal.itemScan.length > 0"
                                     class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 px-3 py-3 text-xs text-gray-400 text-center">
                                    No matching item in picklist
                                </div>
                            </div>
                        </div>

                        <!-- Qty -->
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Qty</label>
                            <input x-model.number="pickingModal.qty"
                                   @keydown.enter="addPickLine()"
                                   type="number" min="1"
                                   placeholder="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
                        </div>

                        <!-- Add btn -->
                        <div class="col-span-2">
                            <button @click="addPickLine()"
                                    :disabled="!pickingModal.selectedBin || !pickingModal.selectedItem || !pickingModal.qty"
                                    class="w-full bg-teal-600 hover:bg-teal-700 disabled:opacity-40 text-white text-sm font-semibold px-3 py-2 rounded-lg flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-base">add</span> Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pick Lines Table -->
                <div x-show="pickingModal.lines.length > 0">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Picked Items</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Pallet</th>
                                    <th class="px-3 py-2 text-left">Bin</th>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                    <th class="px-3 py-2 text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(line, idx) in pickingModal.lines" :key="idx">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 font-semibold text-amber-700" x-text="line.pallet_no"></td>
                                        <td class="px-3 py-2 text-gray-700" x-text="line.bin_code"></td>
                                        <td class="px-3 py-2 text-gray-800">
                                            <div x-text="line.item_name"></div>
                                            <div class="text-xs text-gray-400" x-text="line.item_code"></div>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="line.qty"></td>
                                        <td class="px-3 py-2 text-center">
                                            <button @click="pickingModal.lines.splice(idx, 1)"
                                                    class="text-red-400 hover:text-red-600">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                </div>
                </template>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-3">
                <p class="text-xs text-gray-400" x-text="pickingModal.lines.length + ' line(s) added'"></p>
                <div class="flex gap-3">
                    <button @click="closePickingModal()"
                            class="text-sm text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button @click="submitPicking()"
                            :disabled="pickingModal.lines.length === 0 || pickingModal.submitting"
                            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white text-sm font-semibold px-5 py-2 rounded-lg flex items-center gap-2">
                        <span x-show="pickingModal.submitting" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                        <span class="material-symbols-outlined text-base" x-show="!pickingModal.submitting">task_alt</span>
                        Mark Picking Complete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Start Packing Modal ─────────────────────────────────────────── -->
    <div x-show="packingModal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.45)">
        <div @click.outside="closePackingModal()"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-[90vh]">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Start Packing</h2>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="'SO: ' + (packingModal.so?.so_number ?? '—')"></p>
                </div>
                <button @click="closePackingModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                <!-- Picklist loading -->
                <div x-show="packingModal.loadingPicklist" class="flex items-center gap-2 text-sm text-gray-400 py-2">
                    <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                    Loading picklist…
                </div>

                <template x-if="!packingModal.loadingPicklist">
                <div class="space-y-5">

                <!-- Step 1: Box No -->
                <div x-show="!packingModal.boxLocked">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Box No <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input x-model="packingModal.boxInput"
                               @keydown.enter="lockBox()"
                               type="text"
                               placeholder="Enter or scan box number…"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        <button @click="lockBox()"
                                :disabled="!packingModal.boxInput.trim()"
                                class="bg-indigo-500 hover:bg-indigo-600 disabled:opacity-40 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            Set Box
                        </button>
                    </div>
                </div>

                <!-- Active Box badge + scan row -->
                <div x-show="packingModal.boxLocked" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-2">
                            <span class="material-symbols-outlined text-indigo-600 text-base">package_2</span>
                            <span class="text-sm font-bold text-indigo-700" x-text="'Box: ' + packingModal.currentBox"></span>
                        </div>
                        <button @click="packingModal.boxLocked = false; packingModal.boxInput = ''"
                                class="text-xs text-gray-400 hover:text-gray-600 underline">Change</button>
                    </div>

                    <!-- Item + Qty row -->
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <!-- Item (picklist only) -->
                        <div class="col-span-7">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Item</label>
                            <div class="relative">
                                <input x-model="packingModal.itemScan"
                                       @input="filterPackingItems()"
                                       @focus="packingModal.filteredPicklist = packingModal.picklistItems; packingModal.showItemDrop = true"
                                       @keydown.escape="packingModal.showItemDrop = false"
                                       @keydown.enter="packingAutoSelectIfOne()"
                                       :readonly="!!packingModal.selectedItem"
                                       type="text"
                                       placeholder="Click to select or scan barcode…"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 pr-8"
                                       :class="packingModal.selectedItem ? 'border-teal-400 bg-teal-50 cursor-default' : ''" />
                                <button x-show="packingModal.selectedItem"
                                        @click.prevent="packingModal.selectedItem = null; packingModal.itemScan = ''; packingModal.qty = null; packingModal.filteredPicklist = packingModal.picklistItems"
                                        class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 hover:text-red-500">
                                    <span class="material-symbols-outlined text-base">close</span>
                                </button>
                                <ul x-show="packingModal.showItemDrop && !packingModal.selectedItem && packingModal.filteredPicklist.length > 0"
                                    @click.outside="packingModal.showItemDrop = false"
                                    class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-52 overflow-y-auto text-sm">
                                    <template x-for="it in packingModal.filteredPicklist" :key="it.product_id">
                                        <li @mousedown.prevent="selectPackingItem(it)"
                                            class="px-3 py-2.5 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0">
                                            <div class="font-medium text-gray-800" x-text="it.product_name"></div>
                                            <div class="flex items-center gap-3 mt-0.5">
                                                <span class="text-xs text-gray-400 font-mono" x-text="it.product_code"></span>
                                                <span class="text-xs font-semibold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded"
                                                      x-text="'Qty: ' + it.ordered_qty"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                                <div x-show="packingModal.showItemDrop && !packingModal.selectedItem && packingModal.filteredPicklist.length === 0 && packingModal.itemScan.length > 0"
                                     class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 px-3 py-3 text-xs text-gray-400 text-center">
                                    No matching item in picklist
                                </div>
                            </div>
                        </div>

                        <!-- Qty -->
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Qty</label>
                            <input x-model.number="packingModal.qty"
                                   @keydown.enter="addBoxLine()"
                                   type="number" min="1"
                                   placeholder="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        </div>

                        <!-- Add btn -->
                        <div class="col-span-2">
                            <button @click="addBoxLine()"
                                    :disabled="!packingModal.selectedItem || !packingModal.qty"
                                    class="w-full bg-teal-600 hover:bg-teal-700 disabled:opacity-40 text-white text-sm font-semibold px-3 py-2 rounded-lg flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-base">add</span> Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Box Lines Table -->
                <div x-show="packingModal.lines.length > 0">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Packed Items</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Box</th>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                    <th class="px-3 py-2 text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(line, idx) in packingModal.lines" :key="idx">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 font-semibold text-indigo-700" x-text="line.box_no"></td>
                                        <td class="px-3 py-2 text-gray-800">
                                            <div x-text="line.item_name"></div>
                                            <div class="text-xs text-gray-400" x-text="line.item_code"></div>
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold" x-text="line.qty"></td>
                                        <td class="px-3 py-2 text-center">
                                            <button @click="packingModal.lines.splice(idx, 1)"
                                                    class="text-red-400 hover:text-red-600">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-3">
                <p class="text-xs text-gray-400" x-text="packingModal.lines.length + ' line(s) added'"></p>
                <div class="flex gap-3">
                    <button @click="closePackingModal()"
                            class="text-sm text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button @click="submitPacking()"
                            :disabled="packingModal.lines.length === 0 || packingModal.submitting"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white text-sm font-semibold px-5 py-2 rounded-lg flex items-center gap-2">
                        <span x-show="packingModal.submitting" class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                        <span class="material-symbols-outlined text-base" x-show="!packingModal.submitting">task_alt</span>
                        Mark Packing Complete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-amber-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-amber-600 text-xl">send_to_mobile</span>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded">HHT</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.picking">0</p>
            <p class="text-sm text-gray-500 mt-1">In Picking</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-purple-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-xl">inventory_2</span>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">Ready</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.packed">0</p>
            <p class="text-sm text-gray-500 mt-1">Packed</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="bg-teal-100 p-2.5 rounded-lg">
                    <span class="material-symbols-outlined text-teal-600 text-xl">local_shipping</span>
                </div>
                <span class="text-xs font-semibold text-teal-600 bg-teal-50 px-2 py-1 rounded">Today</span>
            </div>
            <p class="text-3xl font-bold text-gray-900" x-text="stats.dispatched_today">0</p>
            <p class="text-sm text-gray-500 mt-1">Dispatched Today</p>
        </div>
    </div>

    <!-- Tab Panel -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 border-b border-gray-200">
            <nav class="flex gap-6 -mb-px">
                <button @click="tab = 'picking'"
                    :class="tab === 'picking' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">send_to_mobile</span>
                    Picking
                    <span class="bg-amber-100 text-amber-700 text-xs font-bold px-1.5 py-0.5 rounded-full" x-text="stats.picking"></span>
                </button>
                <button @click="tab = 'packed'"
                    :class="tab === 'packed' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">inventory_2</span>
                    Packed
                    <span class="bg-purple-100 text-purple-700 text-xs font-bold px-1.5 py-0.5 rounded-full" x-text="stats.packed"></span>
                </button>
                <button @click="tab = 'dispatched'"
                    :class="tab === 'dispatched' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">local_shipping</span>
                    Dispatched
                </button>
            </nav>
            <button @click="load()" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined text-xl">refresh</span>
            </button>
        </div>

        <div class="p-6">
            <div x-show="loading" class="flex justify-center py-12">
                <span class="material-symbols-outlined animate-spin text-3xl text-teal-500">progress_activity</span>
            </div>

            <div x-show="!loading">
                <!-- Picking -->
                <template x-if="tab === 'picking'">
                    <div>
                        <div x-show="picking.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">send_to_mobile</span>
                            No orders currently in picking.
                        </div>
                        <div x-show="picking.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Delivery Date</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-left">Items</th>
                                        <th class="px-4 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in picking" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3">
                                                <span :class="isOverdue(so.required_delivery_date) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                                      x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-gray-500 text-xs" x-text="(so.line_items?.length ?? so.items_count ?? '—') + ' item(s)'"></td>
                                            <td class="px-4 py-3">
                                                <button @click="openPickingModal(so)"
                                                    class="text-xs bg-amber-500 text-white hover:bg-amber-600 px-3 py-1.5 rounded font-semibold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-sm">send_to_mobile</span> Start Picking
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Packed -->
                <template x-if="tab === 'packed'">
                    <div>
                        <div x-show="packed.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">inventory_2</span>
                            No packed orders awaiting dispatch.
                        </div>
                        <div x-show="packed.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Delivery Date</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in packed" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3">
                                                <span :class="isOverdue(so.required_delivery_date) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                                      x-text="so.required_delivery_date ? new Date(so.required_delivery_date).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3">
                                                <template x-if="so.packing_data && so.packing_data.length > 0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs bg-teal-100 text-teal-700 px-2.5 py-1 rounded font-semibold flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-sm">check_circle</span>
                                                            Packing Done
                                                        </span>
                                                        <button @click="openPackingModal(so)"
                                                            class="text-xs text-gray-400 hover:text-indigo-600 underline">Re-pack</button>
                                                    </div>
                                                </template>
                                                <template x-if="!so.packing_data || so.packing_data.length === 0">
                                                    <button @click="openPackingModal(so)"
                                                        class="text-xs bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-1.5 rounded font-semibold flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-sm">inventory_2</span> Start Packing
                                                    </button>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Dispatched -->
                <template x-if="tab === 'dispatched'">
                    <div>
                        <div x-show="dispatched.length === 0" class="text-center py-12 text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2 block">local_shipping</span>
                            No dispatched orders yet.
                        </div>
                        <div x-show="dispatched.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">SO Number</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Dispatched On</th>
                                        <th class="px-4 py-3 text-right">Grand Total</th>
                                        <th class="px-4 py-3 text-left">Vehicle / Driver</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="so in dispatched" :key="so.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-semibold text-teal-700" x-text="so.so_number"></td>
                                            <td class="px-4 py-3 text-gray-800" x-text="so.customer?.customer_name ?? '—'"></td>
                                            <td class="px-4 py-3 text-gray-600"
                                                x-text="so.dispatched_at ? new Date(so.dispatched_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true}) : '—'"></td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800"
                                                x-text="'₹' + parseFloat(so.grand_total ?? 0).toLocaleString('en-IN',{minimumFractionDigits:2})"></td>
                                            <td class="px-4 py-3 text-gray-600 text-xs"
                                                x-text="(so.vehicle_number ?? '—') + ' / ' + (so.driver_name ?? '—')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Dispatch Modal removed — dispatch is handled by Security portal -->

</div>

<script>
function outwardApp() {
    return {
        tab: 'picking',
        loading: false,
        picking: [], packed: [], dispatched: [],
        stats: { picking: 0, packed: 0, dispatched_today: 0 },

        /* ── Picking Modal State ─────────────────────────────────── */
        pickingModal: {
            open: false,
            so: null,
            // picklist items loaded from SO
            picklistItems: [],
            filteredPicklist: [],
            // Step 1 – pallet
            palletInput: '',
            palletLocked: false,
            currentPallet: '',
            // Scan row
            binSearch: '', binOptions: [], showBinDrop: false, selectedBin: null,
            itemScan: '', showItemDrop: false, selectedItem: null,
            qty: null,
            // Accumulated lines
            lines: [],
            submitting: false,
            loadingPicklist: false,
        },

        /* ── Packing Modal State ─────────────────────────────────── */
        packingModal: {
            open: false,
            so: null,
            picklistItems: [],
            filteredPicklist: [],
            boxInput: '',
            boxLocked: false,
            currentBox: '',
            itemScan: '', showItemDrop: false, selectedItem: null,
            qty: null,
            lines: [],
            submitting: false,
            loadingPicklist: false,
        },

        token()   { return localStorage.getItem('access_token') || localStorage.getItem('auth_token') || ''; },
        headers() { return { 'Authorization': 'Bearer ' + this.token(), 'Accept': 'application/json', 'Content-Type': 'application/json' }; },

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            try {
                const h = this.headers();
                const [pRes, pkRes, dRes] = await Promise.all([
                    fetch('/api/v1/sales-orders?per_page=100&status=PICKING', { headers: h }),
                    fetch('/api/v1/sales-orders?per_page=100&status=PACKED',  { headers: h }),
                    fetch('/api/v1/sales-orders?per_page=100&status=DISPATCHED', { headers: h }),
                ]);
                const [pJson, pkJson, dJson] = await Promise.all([pRes.json(), pkRes.json(), dRes.json()]);

                this.picking    = pJson.success  ? (pJson.data.data  ?? pJson.data  ?? []) : [];
                this.packed     = pkJson.success ? (pkJson.data.data ?? pkJson.data ?? []) : [];
                this.dispatched = dJson.success  ? (dJson.data.data  ?? dJson.data  ?? []) : [];

                const today = new Date().toDateString();
                this.stats = {
                    picking:          this.picking.length,
                    packed:           this.packed.length,
                    dispatched_today: this.dispatched.filter(o => o.dispatched_at && new Date(o.dispatched_at).toDateString() === today).length,
                };
            } catch(e) { console.error('Outward load error', e); }
            this.loading = false;
        },

        /* ── Modal helpers ───────────────────────────────────────── */
        async openPickingModal(so) {
            this.pickingModal = {
                open: true, so,
                picklistItems: [], filteredPicklist: [],
                palletInput: '', palletLocked: false, currentPallet: '',
                binSearch: '', binOptions: [], showBinDrop: false, selectedBin: null,
                itemScan: '', showItemDrop: false, selectedItem: null,
                qty: null,
                lines: [],
                submitting: false,
                loadingPicklist: true,
            };
            // Load SO line items as the picklist
            try {
                const res  = await fetch('/api/v1/sales-orders/' + so.id, { headers: this.headers() });
                const json = await res.json();
                if (json.success) {
                    // Laravel serializes lineItems() relation as 'line_items' in JSON
                    const lines = json.data.line_items ?? json.data.lineItems ?? [];
                    this.pickingModal.picklistItems = lines.map(li => ({
                        product_id:   li.product_id,
                        product_name: li.product?.product_name ?? '—',
                        product_code: li.product?.product_code ?? '—',
                        ordered_qty:  parseFloat(li.qty ?? 0),
                    }));
                    this.pickingModal.filteredPicklist = [...this.pickingModal.picklistItems];
                }
            } catch(e) { console.error('Picklist load error', e); }
            this.pickingModal.loadingPicklist = false;
        },

        closePickingModal() {
            this.pickingModal.open = false;
        },

        lockPallet() {
            const p = this.pickingModal.palletInput.trim();
            if (!p) return;
            this.pickingModal.currentPallet = p;
            this.pickingModal.palletLocked  = true;
        },

        /* Bin search */
        async searchBins() {
            const q = this.pickingModal.binSearch.trim();
            this.pickingModal.selectedBin = null;
            if (!q) { this.pickingModal.binOptions = []; return; }
            try {
                const res  = await fetch('/api/v1/bin-locations?search=' + encodeURIComponent(q), { headers: this.headers() });
                const json = await res.json();
                // BinLocationController returns { data: { bin_locations: [...] } }
                const bins = json.data?.bin_locations ?? json.data ?? [];
                this.pickingModal.binOptions  = Array.isArray(bins) ? bins : [];
                this.pickingModal.showBinDrop = true;
            } catch(e) { console.error(e); }
        },

        selectBin(b) {
            this.pickingModal.selectedBin  = b;
            this.pickingModal.binSearch    = b.bin_code;
            this.pickingModal.showBinDrop  = false;
        },

        /* Item — filter picklist by scan input (product_code or product_name) */
        filterPicklistItems() {
            const q = this.pickingModal.itemScan.trim().toLowerCase();
            if (!q) {
                this.pickingModal.filteredPicklist = [...this.pickingModal.picklistItems];
                this.pickingModal.showItemDrop = true;
                return;
            }
            this.pickingModal.filteredPicklist = this.pickingModal.picklistItems.filter(it =>
                it.product_code.toLowerCase().includes(q) ||
                it.product_name.toLowerCase().includes(q)
            );
            this.pickingModal.showItemDrop = true;
            // Exact barcode match → auto-select immediately
            const exact = this.pickingModal.picklistItems.find(
                it => it.product_code.toLowerCase() === q
            );
            if (exact) { this.selectItem(exact); }
        },

        autoSelectIfOne() {
            if (this.pickingModal.filteredPicklist.length === 1) {
                this.selectItem(this.pickingModal.filteredPicklist[0]);
            }
        },

        selectItem(it) {
            this.pickingModal.selectedItem  = it;
            this.pickingModal.itemScan      = it.product_name;
            this.pickingModal.showItemDrop  = false;
            // Auto-fill qty from the picklist ordered qty
            this.pickingModal.qty = it.ordered_qty > 0 ? it.ordered_qty : null;
        },

        /* Add a pick line – pallet stays the same */
        addPickLine() {
            const m = this.pickingModal;
            if (!m.selectedBin || !m.selectedItem || !m.qty || m.qty <= 0) return;

            m.lines.push({
                pallet_no: m.currentPallet,
                bin_code:  m.selectedBin.bin_code,
                bin_id:    m.selectedBin.id,
                item_id:   m.selectedItem.product_id,
                item_code: m.selectedItem.product_code,
                item_name: m.selectedItem.product_name,
                qty:       m.qty,
            });

            // Reset scan row but keep pallet locked
            m.binSearch    = ''; m.selectedBin  = null; m.binOptions  = [];
            m.itemScan     = ''; m.selectedItem = null;
            m.filteredPicklist = [...m.picklistItems];
            m.qty          = null;
        },

        /* Submit → mark packed */
        async submitPicking() {
            const m = this.pickingModal;
            if (m.lines.length === 0 || m.submitting) return;
            m.submitting = true;
            try {
                const res  = await fetch('/api/v1/sales-orders/' + m.so.id + '/mark-packed', {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({ pick_lines: m.lines }),
                });
                const json = await res.json();
                if (json.success) {
                    this.closePickingModal();
                    this.tab = 'packed';
                    await this.load();
                } else {
                    alert(json.message || 'Failed to mark as packed.');
                }
            } catch(e) {
                console.error(e);
                alert('Network error. Please try again.');
            }
            m.submitting = false;
        },

        /* Legacy direct mark-packed (kept for packed tab if needed) */
        async markPacked(id) {
            if (!confirm('Mark this order as PACKED?')) return;
            const res  = await fetch('/api/v1/sales-orders/' + id + '/mark-packed', {
                method: 'PATCH', headers: this.headers()
            });
            const json = await res.json();
            if (json.success) { this.tab = 'packed'; await this.load(); }
            else alert(json.message || 'Failed to mark as packed.');
        },

        /* ── Packing Modal helpers ───────────────────────────────── */
        async openPackingModal(so) {
            this.packingModal = {
                open: true, so,
                picklistItems: [], filteredPicklist: [],
                boxInput: '', boxLocked: false, currentBox: '',
                itemScan: '', showItemDrop: false, selectedItem: null,
                qty: null,
                lines: [],
                submitting: false,
                loadingPicklist: true,
            };
            try {
                const res  = await fetch('/api/v1/sales-orders/' + so.id, { headers: this.headers() });
                const json = await res.json();
                if (json.success) {
                    const lines = json.data.line_items ?? json.data.lineItems ?? [];
                    this.packingModal.picklistItems = lines.map(li => ({
                        product_id:   li.product_id,
                        product_name: li.product?.product_name ?? '—',
                        product_code: li.product?.product_code ?? '—',
                        ordered_qty:  parseFloat(li.qty ?? 0),
                    }));
                    this.packingModal.filteredPicklist = [...this.packingModal.picklistItems];
                }
            } catch(e) { console.error('Packing picklist load error', e); }
            this.packingModal.loadingPicklist = false;
        },

        closePackingModal() { this.packingModal.open = false; },

        lockBox() {
            const b = this.packingModal.boxInput.trim();
            if (!b) return;
            this.packingModal.currentBox = b;
            this.packingModal.boxLocked  = true;
        },

        filterPackingItems() {
            const q = this.packingModal.itemScan.trim().toLowerCase();
            if (!q) {
                this.packingModal.filteredPicklist = [...this.packingModal.picklistItems];
                this.packingModal.showItemDrop = true;
                return;
            }
            this.packingModal.filteredPicklist = this.packingModal.picklistItems.filter(it =>
                it.product_code.toLowerCase().includes(q) ||
                it.product_name.toLowerCase().includes(q)
            );
            this.packingModal.showItemDrop = true;
            const exact = this.packingModal.picklistItems.find(it => it.product_code.toLowerCase() === q);
            if (exact) { this.selectPackingItem(exact); }
        },

        packingAutoSelectIfOne() {
            if (this.packingModal.filteredPicklist.length === 1) {
                this.selectPackingItem(this.packingModal.filteredPicklist[0]);
            }
        },

        selectPackingItem(it) {
            this.packingModal.selectedItem = it;
            this.packingModal.itemScan     = it.product_name;
            this.packingModal.showItemDrop = false;
            this.packingModal.qty = it.ordered_qty > 0 ? it.ordered_qty : null;
        },

        addBoxLine() {
            const m = this.packingModal;
            if (!m.selectedItem || !m.qty || m.qty <= 0) return;
            m.lines.push({
                box_no:    m.currentBox,
                item_id:   m.selectedItem.product_id,
                item_code: m.selectedItem.product_code,
                item_name: m.selectedItem.product_name,
                qty:       m.qty,
            });
            m.itemScan = ''; m.selectedItem = null;
            m.filteredPicklist = [...m.picklistItems];
            m.qty = null;
        },

        async submitPacking() {
            const m = this.packingModal;
            if (m.lines.length === 0 || m.submitting) return;
            m.submitting = true;
            try {
                const payload = { box_lines: m.lines };
                console.log('[Packing] Submitting to SO id=' + m.so.id, payload);

                const res  = await fetch('/api/v1/sales-orders/' + m.so.id + '/mark-packing-complete', {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(payload),
                });

                let json;
                try { json = await res.json(); } catch(e) { json = { success: false, message: 'Invalid server response (HTTP ' + res.status + ')' }; }

                console.log('[Packing] Response:', res.status, json);

                if (json.success) {
                    this.closePackingModal();
                    await this.load();
                } else {
                    alert('Packing failed: ' + (json.message || JSON.stringify(json)));
                }
            } catch(e) {
                console.error('[Packing] Network error:', e);
                alert('Network error: ' + e.message);
            }
            m.submitting = false;
        },

        isOverdue(date) {
            return date && new Date(date) < new Date(new Date().toDateString());
        },
    }
}
</script>
@endsection
