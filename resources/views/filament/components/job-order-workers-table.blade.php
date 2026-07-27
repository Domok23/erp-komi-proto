@php
    $workers = $workers ?? [];
    $statePath = $getStatePath();
@endphp

<div 
    x-data="{
        search: '',
        selected: $wire.entangle('{{ $statePath }}'),
        workers: {{ json_encode($workers) }},
        toggleAll(event) {
            if (event.target.checked) {
                this.selected = this.filteredWorkers().map(w => w.name);
            } else {
                this.selected = [];
            }
        },
        filteredWorkers() {
            if (!this.search) return this.workers;
            const q = this.search.toLowerCase();
            return this.workers.filter(w => 
                (w.number && w.number.toLowerCase().includes(q)) || 
                (w.name && w.name.toLowerCase().includes(q)) || 
                (w.dept && w.dept.toLowerCase().includes(q)) || 
                (w.pos && w.pos.toLowerCase().includes(q))
            );
        }
    }" 
    class="space-y-4"
>
    <!-- Search Bar & Counter Bar -->
    <div class="flex items-center justify-between gap-4">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                type="text" 
                x-model="search" 
                placeholder="Cari berdasarkan NIP, Nama, Departemen, atau Posisi..." 
                class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-lg border border-gray-300 dark:border-gray-700 shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
            />
        </div>
        <div class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-300 font-medium">
            Terpilih: <span class="font-bold text-primary-600 dark:text-primary-400" x-text="selected ? selected.length : 0"></span> orang
        </div>
    </div>

    <!-- Native Filament Styled Table -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-900">
        <div class="max-h-80 overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-gray-800 border-collapse">
                <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider sticky top-0 z-10 backdrop-blur-md">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center border-b border-gray-200 dark:border-gray-800">
                            <input 
                                type="checkbox" 
                                @change="toggleAll($event)" 
                                class="rounded border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500"
                            />
                        </th>
                        <th class="px-4 py-3 font-semibold border-b border-gray-200 dark:border-gray-800">NIP</th>
                        <th class="px-4 py-3 font-semibold border-b border-gray-200 dark:border-gray-800">Nama Karyawan</th>
                        <th class="px-4 py-3 font-semibold border-b border-gray-200 dark:border-gray-800">Departemen</th>
                        <th class="px-4 py-3 font-semibold border-b border-gray-200 dark:border-gray-800">Posisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    <template x-for="worker in filteredWorkers()" :key="worker.name">
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input 
                                    type="checkbox" 
                                    :value="worker.name" 
                                    x-model="selected" 
                                    class="rounded border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500"
                                />
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400" x-text="worker.number"></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="worker.name"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700" x-text="worker.dept"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300" x-text="worker.pos"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredWorkers().length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 italic">
                            Tidak ada data karyawan yang cocok dengan pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
