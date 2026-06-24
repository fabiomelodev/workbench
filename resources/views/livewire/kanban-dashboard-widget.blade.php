{{-- <x-filament-widgets::widget>
    <x-filament::section>
        Widget content
    </x-filament::section>
</x-filament-widgets::widget> --}}

<x-filament-widgets::widget>
    <x-filament::card>
        <div class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">
            Meu Funil de Prospecção Diária
        </div>

        @livewire(\App\Filament\Resources\Prospects\Pages\ListProspects::class)

    </x-filament::card>
</x-filament-widgets::widget>