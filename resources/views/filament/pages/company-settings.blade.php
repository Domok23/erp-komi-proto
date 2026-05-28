<x-filament-panels::page>
    <div class="max-w-3xl mx-auto">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            {{ $this->getHeaderActions() }}
        </div>
    </div>
</x-filament-panels::page>
