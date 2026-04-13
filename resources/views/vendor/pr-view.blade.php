<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PR {{ $pr->pr_number }} — {{ $orgName }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
  body { font-family: 'Inter', sans-serif; }
  @media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .print-shadow { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
  }
</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-gray-200 min-h-screen">

<div class="max-w-3xl mx-auto py-10 px-4 space-y-5">

    <!-- Top bar with print button -->
    <div class="flex items-center justify-between no-print">
        <p class="text-xs text-gray-500 font-medium tracking-wide uppercase">Vendor Portal · Purchase Requisition</p>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
            </svg>
            Print
        </button>
    </div>

    <!-- Header Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-7 print-shadow">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-2">Purchase Requisition</p>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $pr->pr_number }}</h1>
                <p class="text-sm text-gray-500 mt-1.5">From <span class="font-semibold text-gray-700">{{ $orgName }}</span></p>
            </div>
            <span class="mt-1 px-4 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase
                @if($pr->status === 'APPROVED') bg-emerald-50 text-emerald-700 border border-emerald-200
                @elseif($pr->status === 'REJECTED') bg-red-50 text-red-700 border border-red-200
                @elseif($pr->status === 'PENDING_APPROVAL') bg-amber-50 text-amber-700 border border-amber-200
                @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                {{ $pr->status === 'PENDING_APPROVAL' ? 'Pending Approval' : $pr->status }}
            </span>
        </div>
    </div>

    <!-- Requisition Details -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-7 print-shadow">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-5">Requisition Details</p>
        <div class="grid grid-cols-3 gap-6 text-sm">
            <div>
                <p class="text-xs text-gray-400 mb-1">PR Date</p>
                <p class="font-semibold text-gray-900">{{ $pr->pr_date ? \Carbon\Carbon::parse($pr->pr_date)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Required Date</p>
                <p class="font-semibold text-gray-900">{{ $pr->required_date ? \Carbon\Carbon::parse($pr->required_date)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Priority</p>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold
                    @if($pr->priority === 'EMERGENCY') bg-red-100 text-red-700
                    @elseif($pr->priority === 'HIGH') bg-orange-100 text-orange-700
                    @elseif($pr->priority === 'MEDIUM') bg-yellow-100 text-yellow-700
                    @else bg-green-100 text-green-700 @endif">
                    {{ $pr->priority ?: '—' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print-shadow">
        <div class="px-7 py-5 border-b border-gray-100 flex items-center justify-between">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Line Items</p>
            <span class="text-xs text-gray-400 font-medium">{{ $pr->lineItems->count() }} item(s)</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left py-3 px-5 text-[11px] font-semibold text-gray-400 uppercase tracking-wide w-12">#</th>
                    <th class="text-left py-3 px-5 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Item</th>
                    <th class="text-left py-3 px-5 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Description</th>
                    <th class="text-right py-3 px-5 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Qty</th>
                    <th class="text-left py-3 px-5 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">UOM</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pr->lineItems as $item)
                <tr class="hover:bg-gray-50/60 transition-colors">
                    <td class="py-4 px-5">
                        <span class="w-7 h-7 inline-flex items-center justify-center bg-gray-100 text-gray-600 text-xs font-bold rounded-full">
                            {{ $item->line_number }}
                        </span>
                    </td>
                    <td class="py-4 px-5">
                        <p class="font-semibold text-gray-900">{{ $item->item_name }}</p>
                        @if($item->material)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item->material->material_code }}</p>
                        @endif
                    </td>
                    <td class="py-4 px-5 text-gray-500 text-xs">{{ $item->description ?: '—' }}</td>
                    <td class="py-4 px-5 text-right font-semibold text-gray-900">{{ $item->quantity }}</td>
                    <td class="py-4 px-5">
                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md">
                            {{ $item->uom ? $item->uom->uom_name : '—' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-10 text-center text-sm text-gray-400">No line items</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pr->justification)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-7 print-shadow">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-3">Justification</p>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $pr->justification }}</p>
    </div>
    @endif

    @if($pr->remarks)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-7 print-shadow">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-3">Remarks</p>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $pr->remarks }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="text-center text-xs text-gray-400 py-4 no-print">
        <p>This is a secure link to view Purchase Requisition <span class="font-medium text-gray-500">{{ $pr->pr_number }}</span></p>
        <p class="mt-1">Please provide your best quotation for the above items.</p>
    </div>

</div>
</body>
</html>
