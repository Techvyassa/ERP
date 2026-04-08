<!-- Global Toast Notification System -->
<div x-data="{ 
        toasts: [],
        addToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.removeToast(id);
            }, 3000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }" 
    @notify.window="addToast($event.detail.message, $event.detail.type || 'success')"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 items-end pointer-events-none">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 mb-[-50px] scale-95"
             class="flex items-center px-4 py-3 rounded-lg shadow-lg border text-white min-w-[300px] max-w-md pointer-events-auto"
             :class="{
                 'bg-green-600 border-green-700': toast.type === 'success',
                 'bg-red-600 border-red-700': toast.type === 'error',
                 'bg-yellow-500 border-yellow-600': toast.type === 'warning',
                 'bg-blue-600 border-blue-700': toast.type === 'info'
             }">
            <div class="mr-3 flex items-center">
                <template x-if="toast.type === 'success'">
                    <span class="material-symbols-outlined shrink-0">check_circle</span>
                </template>
                <template x-if="toast.type === 'error'">
                    <span class="material-symbols-outlined shrink-0">error</span>
                </template>
                <template x-if="toast.type === 'warning'">
                    <span class="material-symbols-outlined shrink-0">warning</span>
                </template>
                <template x-if="toast.type === 'info'">
                    <span class="material-symbols-outlined shrink-0">info</span>
                </template>
            </div>
            <div class="flex-1 text-sm font-medium" x-text="toast.message"></div>
            <button @click="removeToast(toast.id)" class="ml-4 flex items-center text-white/80 hover:text-white focus:outline-none transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </template>
</div>
