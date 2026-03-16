<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PO {{ $po->po_number }} — {{ $orgName }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<div x-data="vendorPoView()" x-init="init()" class="max-w-4xl mx-auto py-8 px-4 space-y-6">

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-start justify-between flex-wrap gap-3">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-1">Purchase Order</p>
                <h1 class="text-2xl font-bold text-gray-900">{{ $po->po_number }}</h1>
                <p class="text-gray-500 mt-1 text-sm">From <span class="font-semibold text-gray-700">{{ $orgName }}</span></p>
            </div>
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                @if(in_array($po->status, ['OPEN','PARTIAL'])) bg-green-100 text-green-700
                @elseif($po->status === 'CANCELLED') bg-red-100 text-red-700
                @elseif($po->status === 'CLOSED') bg-gray-200 text-gray-600
                @else bg-blue-100 text-blue-700 @endif">
                {{ $po->status }}
            </span>
        </div>
    </div>

    <!-- PO Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Order Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs mb-0.5">PO Date</p>
                <p class="font-semibold text-gray-900">{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Expected Delivery</p>
                <p class="font-semibold text-gray-900">{{ $po->expected_delivery ? \Carbon\Carbon::parse($po->expected_delivery)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Payment Terms</p>
                <p class="font-semibold text-gray-900">{{ $po->payment_terms ?: '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Credit Days</p>
                <p class="font-semibold text-gray-900">{{ $po->credit_days ?? '—' }}</p>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Line Items</h2>
            <button @click="downloadTemplate()"
                    class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1 font-semibold">
                ↓ Download ASN Template CSV
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Line</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Material</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Ordered Qty</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                        <th class="text-right py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($po->lineItems as $item)
                    <tr>
                        <td class="py-3 px-5">
                            <p class="text-xs font-mono text-gray-500">Line {{ $item->line_number }}</p>
                            <p class="text-xs text-gray-300">id: {{ $item->id }}</p>
                        </td>
                        <td class="py-3 px-5">
                            <p class="font-medium text-gray-900">{{ $item->material ? $item->material->material_name : '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $item->material ? $item->material->material_code : '' }}</p>
                        </td>
                        <td class="py-3 px-5 text-gray-700">
                            {{ $item->ordered_qty }} {{ $item->uom ? $item->uom->uom_code : '' }}
                        </td>
                        <td class="py-3 px-5 text-gray-700">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-5 text-right font-semibold text-gray-900">₹{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No line items</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 text-right space-y-1 text-sm">
            @if($po->discount_amount > 0)
            <p class="text-gray-500">Discount: <span class="font-semibold">−₹{{ number_format($po->discount_amount, 2) }}</span></p>
            @endif
            @if($po->tax_amount > 0)
            <p class="text-gray-500">Tax: <span class="font-semibold">₹{{ number_format($po->tax_amount, 2) }}</span></p>
            @endif
            @if($po->freight_charges > 0)
            <p class="text-gray-500">Freight: <span class="font-semibold">₹{{ number_format($po->freight_charges, 2) }}</span></p>
            @endif
            <p class="text-base font-bold text-gray-900">Grand Total: ₹{{ number_format($po->grand_total, 2) }}</p>
        </div>
    </div>


    {{-- ===== STEP 1: VENDOR APPROVE / REJECT ===== --}}
    @if($vendorRejected)
    {{-- Rejected state --}}
    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <div class="text-4xl mb-3">✗</div>
        <h3 class="text-lg font-bold text-red-700 mb-1">PO Rejected</h3>
        <p class="text-sm text-red-600">You have rejected this purchase order. It has been marked as cancelled.</p>
    </div>

    @elseif(!$vendorApproved)
    {{-- Step 1: Pending vendor decision --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold">1</span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest">Review &amp; Respond to PO</h2>
        </div>
        <p class="text-sm text-gray-500 mb-5">Please review the purchase order above and confirm whether you accept or reject it.</p>

        <template x-if="step1Error">
            <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700" x-text="step1Error"></div>
        </template>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Remarks (optional)</label>
            <textarea x-model="step1Remark" rows="2"
                      placeholder="Add any notes or conditions..."
                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 resize-none"></textarea>
        </div>

        <div class="flex items-center gap-3">
            <button @click="approvePO()"
                    :disabled="step1Loading"
                    class="px-6 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <svg x-show="step1Loading && step1Action==='approve'" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                ✓ Approve PO
            </button>
            <button @click="showRejectModal = true"
                    :disabled="step1Loading"
                    class="px-6 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                ✗ Reject PO
            </button>
        </div>
    </div>

    {{-- Reject confirmation modal --}}
    <div x-show="showRejectModal" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-base font-bold text-gray-900 mb-2">Reject Purchase Order?</h3>
            <p class="text-sm text-gray-500 mb-4">This will cancel the PO. Please provide a reason (optional).</p>
            <textarea x-model="rejectReason" rows="3"
                      placeholder="Reason for rejection..."
                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400 resize-none mb-4"></textarea>
            <div class="flex gap-3">
                <button @click="rejectPO()"
                        :disabled="step1Loading"
                        class="flex-1 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                    <svg x-show="step1Loading && step1Action==='reject'" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Confirm Reject
                </button>
                <button @click="showRejectModal = false"
                        class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    @else
    {{-- Step 1 complete — vendor approved --}}
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-green-600 text-white text-xs font-bold shrink-0">✓</span>
        <div>
            <p class="text-sm font-semibold text-green-800">PO Approved by Vendor</p>
            <p class="text-xs text-green-600">You have accepted this purchase order. Please proceed to upload your ASN below.</p>
        </div>
    </div>
    @endif

    {{-- ===== STEP 2: ASN UPLOAD (only after vendor approves) ===== --}}
    @if($vendorApproved && !$vendorRejected)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-1">
            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold">2</span>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-widest">Upload Advance Shipping Notice (ASN)</h2>
        </div>
        <p class="text-sm text-gray-500 mb-5 ml-10">Fill in shipment details and upload your CSV. Use the template button above to get the pre-filled CSV.</p>

        <template x-if="asnSuccess">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm font-medium">
                ✓ ASN <strong x-text="asnNumber"></strong> submitted successfully. The buyer has been notified.
            </div>
        </template>

        <template x-if="!asnSuccess">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Ship Date <span class="text-red-500">*</span></label>
                        <input type="date" x-model="asn.ship_date"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">ETA <span class="text-red-500">*</span></label>
                        <input type="date" x-model="asn.eta"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Delivery Warehouse <span class="text-red-500">*</span></label>
                        <select x-model="asn.warehouse_id"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                            <option value="">Select warehouse</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Carrier</label>
                        <input type="text" x-model="asn.carrier_name" placeholder="e.g. FedEx, DTDC"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tracking Number</label>
                        <input type="text" x-model="asn.tracking_number"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Vehicle Number</label>
                        <input type="text" x-model="asn.vehicle_number" placeholder="e.g. GJ01AB1234"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Remarks</label>
                    <input type="text" x-model="asn.remarks" placeholder="Any notes for the buyer..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">CSV File <span class="text-red-500">*</span></label>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-5 text-center hover:border-blue-300 transition-colors">
                        <input type="file" accept=".csv,.txt" @change="onFileSelect($event)" class="hidden" id="csvFile">
                        <label for="csvFile" class="cursor-pointer block">
                            <p class="text-sm text-gray-500 font-medium" x-text="asn.fileName || 'Click to select CSV file'"></p>
                            <p class="text-xs text-gray-400 mt-1">Required columns: po_line_id, material_id, shipped_qty, uom_id</p>
                        </label>
                    </div>
                </div>
                <template x-if="asnError">
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700" x-text="asnError"></div>
                </template>
                <div class="flex items-center gap-3 pt-1">
                    <button @click="submitASN()"
                            :disabled="asnUploading || !asn.csvFile || !asn.ship_date || !asn.eta || !asn.warehouse_id"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg x-show="asnUploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span x-text="asnUploading ? 'Uploading...' : 'Submit ASN'"></span>
                    </button>
                    <p class="text-xs text-gray-400">Buyer will be notified once submitted</p>
                </div>
            </div>
        </template>
    </div>
    @endif

    {{-- Acknowledgement / Remarks --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Acknowledgement / Remarks</h2>
        <template x-if="ackSubmitted">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm font-medium">
                ✓ Your acknowledgement has been sent. Thank you.
            </div>
        </template>
        <template x-if="!ackSubmitted">
            <div>
                <textarea x-model="remark" rows="3"
                          placeholder="e.g. We confirm receipt of this PO. Expected dispatch by 20 Mar 2026..."
                          class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 resize-none"></textarea>
                <div class="flex items-center gap-3 mt-3">
                    <button @click="acknowledge()"
                            :disabled="ackSending || !remark.trim()"
                            class="px-5 py-2 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg x-show="ackSending" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span x-text="ackSending ? 'Sending...' : 'Send Acknowledgement'"></span>
                    </button>
                </div>
                <template x-if="ackError">
                    <p class="mt-2 text-sm text-red-600" x-text="ackError"></p>
                </template>
            </div>
        </template>
    </div>

</div>

<script>
function vendorPoView() {
    const lineItems = {!! $lineItemsData !!};

    return {
        // Step 1
        step1Loading: false, step1Action: '', step1Error: null,
        step1Remark: '', showRejectModal: false, rejectReason: '',

        // Ack
        remark: '', ackSending: false, ackSubmitted: false, ackError: null,

        // ASN
        asn: { ship_date: '', eta: '', warehouse_id: '', carrier_name: '', tracking_number: '', vehicle_number: '', remarks: '', csvFile: null, fileName: '' },
        asnUploading: false, asnSuccess: false, asnNumber: '', asnError: null,

        init() {
            const eta = '{{ $po->expected_delivery ? \Carbon\Carbon::parse($po->expected_delivery)->format("Y-m-d") : "" }}';
            if (eta) this.asn.eta = eta;
            this.asn.ship_date = new Date().toISOString().split('T')[0];
        },

        async approvePO() {
            this.step1Loading = true; this.step1Action = 'approve'; this.step1Error = null;
            try {
                const res  = await fetch('{{ route("vendor.po.vendor-approve", ["token" => $token]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ remark: this.step1Remark })
                });
                const data = await res.json();
                if (data.success) { window.location.reload(); }
                else { this.step1Error = data.message || 'Failed to approve'; }
            } catch (e) { this.step1Error = 'Network error.'; }
            finally { this.step1Loading = false; }
        },

        async rejectPO() {
            this.step1Loading = true; this.step1Action = 'reject'; this.step1Error = null;
            try {
                const res  = await fetch('{{ route("vendor.po.vendor-reject", ["token" => $token]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ reason: this.rejectReason })
                });
                const data = await res.json();
                if (data.success) { window.location.reload(); }
                else { this.step1Error = data.message || 'Failed to reject'; this.showRejectModal = false; }
            } catch (e) { this.step1Error = 'Network error.'; this.showRejectModal = false; }
            finally { this.step1Loading = false; }
        },

        async acknowledge() {
            if (!this.remark.trim()) return;
            this.ackSending = true; this.ackError = null;
            try {
                const res  = await fetch('{{ route("vendor.po.acknowledge", ["token" => $token]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ remark: this.remark })
                });
                const data = await res.json();
                if (data.success) { this.ackSubmitted = true; }
                else { this.ackError = data.message || 'Failed to send'; }
            } catch (e) { this.ackError = 'Network error.'; }
            finally { this.ackSending = false; }
        },

        onFileSelect(e) {
            const f = e.target.files[0];
            if (f) { this.asn.csvFile = f; this.asn.fileName = f.name; }
        },

        downloadTemplate() {
            const headers = 'po_line_id,material_id,shipped_qty,uom_id,batch_number,lot_number,manufacturing_date,expiry_date,pallet_id,sscc,gross_weight,net_weight';
            const rows    = lineItems.map(i => `${i.id},${i.material_id},${i.ordered_qty},${i.uom_id || ''},,,,,,,,`);
            const blob    = new Blob([headers + '\n' + rows.join('\n')], { type: 'text/csv' });
            const url     = URL.createObjectURL(blob);
            const a       = document.createElement('a');
            a.href = url; a.download = 'asn_{{ $po->po_number }}.csv'; a.click();
            URL.revokeObjectURL(url);
        },

        async submitASN() {
            if (!this.asn.csvFile || !this.asn.ship_date || !this.asn.eta || !this.asn.warehouse_id) return;
            this.asnUploading = true; this.asnError = null;
            try {
                const fd = new FormData();
                fd.append('file',         this.asn.csvFile);
                fd.append('warehouse_id', this.asn.warehouse_id);
                fd.append('ship_date',    this.asn.ship_date);
                fd.append('eta',          this.asn.eta);
                if (this.asn.carrier_name)    fd.append('carrier_name',    this.asn.carrier_name);
                if (this.asn.tracking_number) fd.append('tracking_number', this.asn.tracking_number);
                if (this.asn.vehicle_number)  fd.append('vehicle_number',  this.asn.vehicle_number);
                if (this.asn.remarks)         fd.append('remarks',         this.asn.remarks);
                fd.append('_token', '{{ csrf_token() }}');

                const res  = await fetch('{{ route("vendor.po.upload-asn", ["token" => $token]) }}', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) { this.asnSuccess = true; this.asnNumber = data.data.asn_number; }
                else { this.asnError = data.message || 'Upload failed'; }
            } catch (e) { this.asnError = 'Network error. Please try again.'; }
            finally { this.asnUploading = false; }
        }
    };
}
</script>
</body>
</html>
