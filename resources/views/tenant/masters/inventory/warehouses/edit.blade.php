@extends('tenant.layouts.inventory')

@section('title', 'Edit Warehouse')
@section('page-title', 'Configure Storage Center')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@20..48,100..700,0..1" rel="stylesheet">
<style>
    [x-cloak] { display: none !important; }
    .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
    .animate-in { animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('content')
<div x-data="warehouseEditForm()" x-init="initialize()" class="max-w-5xl mx-auto space-y-6">
    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-3xl p-8 flex flex-col items-center gap-4 shadow-2xl animate-in">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent shadow-md shadow-indigo-100"></div>
            <p class="text-gray-900 font-black text-sm tracking-widest uppercase">Synchronizing Master Data...</p>
        </div>
    </div>

    <!-- Header Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg shadow-indigo-200">
                    <span class="material-symbols-outlined text-white text-3xl">edit_square</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Warehouse</h1>
                    <p class="text-gray-500 text-sm font-medium">Update master configuration for storage center <span class="text-indigo-600 font-mono font-black" x-text="form.warehouse_code"></span></p>
                </div>
            </div>

            <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
               class="inline-flex items-center gap-2 text-gray-400 hover:text-indigo-600 px-4 py-2 hover:bg-indigo-50 rounded-xl transition-all font-bold text-sm">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
                Back to Master
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <!-- Left Side: Config -->
        <div class="md:col-span-8 space-y-8">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <h3 class="text-xs font-black text-indigo-600 flex items-center gap-2 mb-8 uppercase tracking-[0.2em] border-b border-gray-50 pb-4">
                    <span class="material-symbols-outlined text-lg">factory</span>
                    Facility Details
                </h3>

                <div class="space-y-8">
                    <!-- Warehouse Name -->
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Official Warehouse Name *</label>
                        <input type="text" x-model="form.warehouse_name" required maxlength="100"
                               placeholder="e.g. Regional Distribution Center - North"
                               class="w-full px-6 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-lg bg-gray-50/50">
                    </div>

                    <!-- Type and Code Logic -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Facility Classification *</label>
                            <select x-model="form.warehouse_type" required
                                    class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold text-sm bg-gray-50/50">
                                <option value="RM">Raw Materials</option>
                                <option value="FG">Finished Goods</option>
                                <option value="PKG">Packaging & Labels</option>
                                <option value="REJECTION">Quality Rejections</option>
                                <option value="WIP">Work In Progress</option>
                            </select>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest">Facility Code (Immutable)</label>
                            <div class="relative">
                                <input type="text" x-model="form.warehouse_code" readonly
                                       class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-black tracking-widest text-lg text-gray-400 cursor-not-allowed">
                                <span class="material-symbols-outlined absolute right-4 top-4 text-gray-300">lock</span>
                            </div>
                            <p class="text-[10px] text-gray-400 italic">Facility codes are unique identifiers and cannot be changed after creation.</p>
                        </div>
                    </div>

                    <!-- Address Matrix -->
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Logical / Physical Address</label>
                        <textarea x-model="form.address" rows="4" 
                                  placeholder="Full street address, building number, and floor details..."
                                  class="w-full px-6 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-sm bg-gray-50/50 resize-none"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Sidebar -->
        <div class="md:col-span-4 space-y-8">
            <!-- Operations Control Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 space-y-8">
                <h3 class="text-xs font-black text-indigo-600 flex items-center gap-2 uppercase tracking-widest border-b border-gray-50 pb-4">
                    <span class="material-symbols-outlined text-lg">supervisor_account</span>
                    Governance
                </h3>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-3 tracking-widest">Facility Incharge</label>
                    <select x-model="form.incharge_user_id"
                            class="w-full px-5 py-3.5 border border-gray-100 bg-gray-50 rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                        <option value="">Assign Personnel</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-2xl border border-indigo-100/50">
                    <div>
                        <p class="text-[10px] font-black text-indigo-900 uppercase tracking-widest mb-1">Operational Status</p>
                        <p x-text="form.is_active ? 'ENABLED' : 'DISABLED'" 
                           :class="form.is_active ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50'"
                           class="inline-block px-3 py-1 rounded-full text-[10px] font-black border border-current"></p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 shadow-inner"></div>
                    </label>
                </div>

                <div class="pt-4 flex flex-col gap-3">
                    <button type="submit" 
                            class="w-full py-4 bg-gray-900 border border-gray-800 text-white hover:bg-indigo-600 hover:border-indigo-500 rounded-2xl font-black text-sm shadow-xl shadow-gray-200 transition-all flex items-center justify-center gap-2 leading-none group">
                        <span class="material-symbols-outlined text-lg group-hover:scale-110 transition-transform">save</span>
                        Commit Version
                    </button>
                    <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}" 
                       class="w-full py-3.5 text-center text-sm font-black text-gray-500 hover:bg-gray-50 rounded-2xl transition-colors">
                        Discard
                    </a>
                </div>
            </div>

            <!-- Hint Card -->
            <div class="bg-indigo-900 rounded-3xl shadow-xl p-8 text-white relative overflow-hidden group">
                <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-9xl opacity-10 rotate-12 group-hover:rotate-45 transition-transform duration-700">inventory_2</span>
                <p class="text-xs leading-relaxed opacity-80 relative z-10 italic">
                    "Modification of facility type or incharge will be reflected in all future Material Receipt and Issue workflows. Historic ledger entries will maintain their original facility metadata."
                </p>
            </div>
        </div>
    </form>
</div>

<script>
function warehouseEditForm() {
    return {
        loading: false,
        warehouseId: null,
        users: [],
        form: {
            warehouse_code: '',
            warehouse_name: '',
            warehouse_type: '',
            address: '',
            incharge_user_id: '',
            is_active: true
        },

        async initialize() {
            const urlParts = window.location.pathname.split('/');
            this.warehouseId = urlParts[urlParts.length - 2];
            await this.loadAllData();
        },

        async loadAllData() {
            this.loading = true;
            try {
                const [whRes] = await Promise.all([
                    fetch(`/api/v1/warehouses/${this.warehouseId}`)
                ]);

                if (whRes.ok) {
                    const result = await whRes.json();
                    const w = result.data.warehouse;
                    this.form = {
                        warehouse_code: w.warehouse_code || '',
                        warehouse_name: w.warehouse_name || '',
                        warehouse_type: w.warehouse_type || '',
                        address: w.address || '',
                        incharge_user_id: w.incharge_user_id || '',
                        is_active: w.is_active !== undefined ? !!w.is_active : true
                    };
                }

                // Mock users
                this.users = [
                    { id: 1, name: 'Admin Operations', email: 'ops@erp.com' },
                    { id: 2, name: 'Inventory Manager', email: 'manager@erp.com' }
                ];
            } catch (e) {
                this.showNotification('Data synchronization failed', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/warehouses/${this.warehouseId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (response.ok) {
                    this.showNotification('Facility master updated successfully', 'success');
                    setTimeout(() => window.location.href = "{{ url(request()->get('tenant_type') === 'subdomain' ? '/warehouses' : '/org/' . $organization->org_slug . '/warehouses') }}", 1500);
                } else {
                    this.showNotification(data.message || 'Update failure', 'error');
                }
            } catch (e) {
                this.showNotification('Network communication failure', 'error');
            } finally {
                this.loading = false;
            }
        },

        showNotification(message, type = 'info') {
            const el = document.createElement('div');
            el.className = `fixed top-6 right-6 px-6 py-3 rounded-2xl shadow-2xl z-[100] text-white font-bold text-sm flex items-center gap-3 animate-in ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            el.innerHTML = `<span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span>${message}`;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(() => el.remove(), 500); }, 3000);
        }
    }
}
</script>
@endsection
