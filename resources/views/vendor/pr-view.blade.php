<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PR {{ $pr->pr_number }} — {{ $orgName }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<div class="max-w-4xl mx-auto py-8 px-4 space-y-6">

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-start justify-between flex-wrap gap-3">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-1">Purchase Requisition</p>
                <h1 class="text-2xl font-bold text-gray-900">{{ $pr->pr_number }}</h1>
                <p class="text-gray-500 mt-1 text-sm">From <span class="font-semibold text-gray-700">{{ $orgName }}</span></p>
            </div>
            <span class="px-3 py-1.5 rounded-full text-sm font-semibold
                @if($pr->status === 'APPROVED') bg-green-100 text-green-700
                @elseif($pr->status === 'REJECTED') bg-red-100 text-red-700
                @elseif($pr->status === 'PENDING_APPROVAL') bg-yellow-100 text-yellow-700
                @else bg-blue-100 text-blue-700 @endif">
                {{ $pr->status }}
            </span>
        </div>
    </div>

    <!-- PR Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Requisition Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs mb-0.5">PR Date</p>
                <p class="font-semibold text-gray-900">{{ $pr->pr_date ? \Carbon\Carbon::parse($pr->pr_date)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Required Date</p>
                <p class="font-semibold text-gray-900">{{ $pr->required_date ? \Carbon\Carbon::parse($pr->required_date)->format('d M Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Priority</p>
                <p class="font-semibold text-gray-900">{{ $pr->priority ?: '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Department</p>
                <p class="font-semibold text-gray-900">{{ $pr->department ? $pr->department->dept_name : '—' }}</p>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Line Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Line</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Item Name</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">Quantity</th>
                        <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500 uppercase">UOM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pr->lineItems as $item)
                    <tr>
                        <td class="py-3 px-5">
                            <p class="text-xs font-mono text-gray-500">Line {{ $item->line_number }}</p>
                        </td>
                        <td class="py-3 px-5">
                            <p class="font-medium text-gray-900">{{ $item->item_name }}</p>
                            @if($item->material)
                                <p class="text-xs text-gray-400">{{ $item->material->material_code }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-5 text-gray-600 text-xs">
                            {{ $item->description ?: '—' }}
                        </td>
                        <td class="py-3 px-5 text-gray-700">
                            {{ $item->quantity }}
                        </td>
                        <td class="py-3 px-5 text-gray-700">
                            {{ $item->uom ? $item->uom->uom_code : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No line items</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Justification -->
    @if($pr->justification)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Justification</h2>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $pr->justification }}</p>
    </div>
    @endif

    <!-- Remarks -->
    @if($pr->remarks)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Remarks</h2>
        <p class="text-sm text-gray-700 leading-relaxed">{{ $pr->remarks }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="text-center text-xs text-gray-400 py-4">
        <p>This is a secure link to view Purchase Requisition {{ $pr->pr_number }}</p>
        <p class="mt-1">Please provide your best quotation for the above items.</p>
    </div>

</div>
</body>
</html>
