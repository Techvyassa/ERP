@extends('layouts.quality')

@section('title', 'Quality Control Center - Nexus ERP')

@section('content')
<div class="max-w-[1400px] mx-auto space-y-8">
    <div class="flex items-end justify-between">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Quality Assurance Control</h2>
            <p class="text-slate-500 font-medium">Lab data analysis and material usage decisions.</p>
        </div>
        <div class="flex gap-4">
             <div class="flex -space-x-2">
                <div class="size-10 rounded-full border-2 border-white bg-slate-200"></div>
                <div class="size-10 rounded-full border-2 border-white bg-slate-300"></div>
                <div class="size-10 rounded-full border-2 border-white bg-qc text-white flex items-center justify-center text-xs font-bold">+3</div>
             </div>
             <button class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform">
                Generate Weekly QA Report
             </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="size-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500">
                <span class="material-symbols-outlined text-3xl">biotech</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Tests</p>
                <h4 class="text-2xl font-black text-slate-800">42 Lots</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="size-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500">
                <span class="material-symbols-outlined text-3xl">verified</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Passed (24h)</p>
                <h4 class="text-2xl font-black text-slate-800">128 Lots</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="size-14 bg-red-50 rounded-2xl flex items-center justify-center text-red-500">
                <span class="material-symbols-outlined text-3xl">dangerous</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rejection Rate</p>
                <h4 class="text-2xl font-black text-slate-800">2.4%</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="size-14 bg-qc/10 rounded-2xl flex items-center justify-center text-qc">
                <span class="material-symbols-outlined text-3xl">timer</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Avg TAT</p>
                <h4 class="text-2xl font-black text-slate-800">3.2 Hrs</h4>
            </div>
        </div>
    </div>

    <!-- Active Inspections -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xl font-bold">Priority Lab Inspections</h3>
            <div class="flex gap-2">
                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">3 CRITICAL</span>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">12 NORMAL</span>
            </div>
        </div>
        <div class="p-0">
             <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="px-8 py-4">Lot ID</th>
                        <th class="px-8 py-4">Material Name</th>
                        <th class="px-8 py-4">Sample Type</th>
                        <th class="px-8 py-4">Lab Analyst</th>
                        <th class="px-8 py-4 text-right">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr class="group hover:bg-slate-50/80 transition-colors">
                        <td class="px-8 py-6">
                            <p class="text-sm font-bold text-slate-900">LT-A00451</p>
                            <p class="text-[10px] text-slate-400">GRN: #88210</p>
                        </td>
                        <td class="px-8 py-6 text-sm font-medium text-slate-600">Stainless Steel Billets 304L</td>
                        <td class="px-8 py-6"><span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold">CHEMICAL</span></td>
                        <td class="px-8 py-6 text-sm text-slate-500">Dr. Sarah Jenkins</td>
                        <td class="px-8 py-6 text-right">
                            <div class="inline-flex flex-col items-end gap-1.5 w-32">
                                <span class="text-[10px] font-bold text-qc">65% COMPLETED</span>
                                <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="w-[65%] h-full bg-qc rounded-full"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
