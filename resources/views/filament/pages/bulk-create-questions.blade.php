<x-filament-panels::page>
    <x-filament::section
        class="mb-6"
        collapsible
        :collapsed="true"
        collapse-id="bulk-questions-excel"
        :heading="__('panel.bulk_excel_section_title')"
        :description="__('panel.bulk_excel_section_description')"
    >
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::actions
                :actions="array_values(array_filter([
                    $this->getAction('downloadCategoryTemplate'),
                    $this->getAction('importCategoryExcel'),
                ]))"
            />
        </div>
    </x-filament::section>

    <x-filament::section
        collapsible
        :collapsed="true"
        collapse-id="bulk-questions-manual"
        :heading="__('panel.bulk_manual_section_title')"
        :description="__('panel.bulk_manual_section_description')"
    >
        {{ $this->form }}
    </x-filament::section>

    <div
        class="fi-form-actions mt-8 flex flex-wrap items-center gap-3 justify-end border-t border-gray-200 pt-6 dark:border-white/10"
    >
        <x-filament::button
            color="gray"
            tag="a"
            :href="\App\Filament\Resources\Categories\CategoryResource::getUrl('index')"
        >
            {{ __('panel.cancel') }}
        </x-filament::button>
        <x-filament::button
            type="button"
            wire:click="save"
            wire:target="save"
        >
            {{ __('panel.save') }}
        </x-filament::button>
    </div>
</x-filament-panels::page>


