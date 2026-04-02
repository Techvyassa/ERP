@extends('layouts.maintenance')

@section('title', 'Work Orders - ' . $organization->org_name)
@section('page-title', 'Work Orders')

@section('content')
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-900">All Work Orders</h2>
    <p class="text-sm text-gray-500">Full list of work orders across all statuses</p>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-3 px-4 font-semibold">Work Order</th>
                    <th class="py-3 px-4 font-semibold">Request</th>
                    <th class="py-3 px-4 font-semibold">Asset</th>
                    <th class="py-3 px-4 font-semibold">Technician</th>
                    <th class="py-3 px-4 font-semibold">Priority</th>
                    <th class="py-3 px-4 font-semibold">Due Date</th>
                    <th class="py-3 px-4 font-semibold">Assigned On</th>
                    <th class="py-3 px-4 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workOrders as $wo)
                    <tr class="border-b last:border-b-0 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold text-gray-900">{{ $wo['wo'] }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $wo['mr_id'] ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $wo['asset'] }}</td>
                        <td class="py-3 px-4">{{ $wo['technician'] }}</td>
                        <td class="py-3 px-4">
                            @php
                                $pc = ['High' => 'bg-red-100 text-red-700', 'Medium' => 'bg-yellow-100 text-yellow-700', 'Low' => 'bg-green-100 text-green-700'][$wo['priority'] ?? 'Medium'] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $pc }}">{{ $wo['priority'] ?? 'Medium' }}</span>
                        </td>
                        <td class="py-3 px-4 {{ isset($wo['due']) && $wo['due'] < date('Y-m-d') && $wo['status'] !== 'Closed' ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ $wo['due'] ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-gray-600">{{ $wo['assigned_on'] ?? '-' }}</td>
                        <td class="py-3 px-4">
                            @php
                                $sc = ['Assigned' => 'bg-blue-100 text-blue-700', 'In Progress' => 'bg-amber-100 text-amber-700', 'Completed' => 'bg-green-100 text-green-700', 'Closed' => 'bg-gray-100 text-gray-600'][$wo['status']] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $sc }}">{{ $wo['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-12 text-center text-gray-400" colspan="8">
                            <span class="material-symbols-outlined text-4xl block mb-2">handyman</span>
                            No work orders yet. Assign approved requests to create work orders.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
