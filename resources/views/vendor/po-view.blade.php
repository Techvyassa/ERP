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
            {{-- Download ASN Template CSV commented out --}}
            {{-- <button @click="downloadTemplate()"
                    class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1 font-semibold">
                ↓ Download ASN Template CSV
            </button> --}}
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


    {{-- ===== STEP 1: VENDOR APPROVE / REJECT (commented out) ===== --}}
    {{--
    @if($vendorRejected)
    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <div class="text-4xl mb-3">✗</div>
        <h3 class="text-lg font-bold text-red-700 mb-1">PO Rejected</h3>
        <p class="text-sm text-red-600">You have rejected this purchase order. It has been marked as cancelled.</p>
    </div>
    @elseif(!$vendorApproved)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        ... approve / reject / remark UI ...
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        ... vendor approved banner ...
    </div>
    @endif
    --}}

    {{-- ===== STEP 2: ASN UPLOAD (commented out) ===== --}}
    {{--
    @if($vendorApproved && !$vendorRejected)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        ... ASN upload form ...
    </div>
    @endif
    --}}

    {{-- Acknowledgement / Remarks (commented out) --}}
    {{--
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        ... acknowledgement / remarks form ...
    </div>
    --}}

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
