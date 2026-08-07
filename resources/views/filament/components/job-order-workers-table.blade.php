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
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                type="text" 
                x-model="search" 
                placeholder="Cari NIP, Nama, Departemen, atau Posisi..." 
                class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-xl border border-gray-300 dark:border-gray-700 shadow-2xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition font-medium"
            />
        </div>
        <div class="px-3.5 py-2 bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/20 rounded-xl text-xs text-amber-900 dark:text-amber-300 font-semibold flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
            <svg style="width: 16px; height: 16px; flex-shrink: 0;" class="text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Terpilih: <strong class="font-bold font-mono text-amber-600 dark:text-amber-400" x-text="selected ? selected.length : 0"></strong> orang</span>
        </div>
    </div>

    <!-- Native Filament Styled Table -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-xs bg-white dark:bg-gray-900">
        <div class="max-h-80 overflow-y-auto">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-gray-800 border-collapse">
                <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider sticky top-0 z-10 backdrop-blur-md">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center border-b border-gray-200 dark:border-gray-800">
                            <input 
                                type="checkbox" 
                                @change="toggleAll($event)" 
                                class="rounded border-gray-300 dark:border-gray-700 text-amber-600 shadow-xs focus:ring-amber-500 cursor-pointer"
                            />
                        </th>
                        <th class="px-4 py-3 font-bold border-b border-gray-200 dark:border-gray-800">NIP</th>
                        <th class="px-4 py-3 font-bold border-b border-gray-200 dark:border-gray-800">Nama Karyawan</th>
                        <th class="px-4 py-3 font-bold border-b border-gray-200 dark:border-gray-800">Departemen</th>
                        <th class="px-4 py-3 font-bold border-b border-gray-200 dark:border-gray-800">Posisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    <template x-for="worker in filteredWorkers()" :key="worker.name">
                        <tr class="hover:bg-amber-500/5 dark:hover:bg-amber-500/10 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input 
                                    type="checkbox" 
                                    :value="worker.name" 
                                    x-model="selected" 
                                    class="rounded border-gray-300 dark:border-gray-700 text-amber-600 shadow-xs focus:ring-amber-500 cursor-pointer"
                                />
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400 font-semibold" x-text="worker.number"></td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white" x-text="worker.name"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700" x-text="worker.dept"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-medium" x-text="worker.pos"></td>
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

