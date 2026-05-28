<x-filament-panels::page>
    <div class="flex items-center justify-center min-h-[calc(100vh-10rem)]">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8 dark:bg-slate-800 dark:border-slate-700">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-500/20 mb-4">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Pilih Perusahaan</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Pilih perusahaan yang ingin Anda akses</p>
                </div>

                <form wire:submit="submit" class="space-y-6">
                    <div>
                        {{ $this->form }}
                    </div>

                    <x-filament::button type="submit" class="w-full justify-center" size="lg">
                        Masuk
                    </x-filament::button>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
