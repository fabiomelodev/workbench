@php
    /** @var \App\Models\Proposal|null $proposal */
    $url = $proposal?->hasDocument() ? $proposal->documentUrl() : null;
@endphp

@if ($url)
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <x-filament::link :href="$url" target="_blank" icon="heroicon-o-arrow-top-right-on-square">
                Abrir em nova aba
            </x-filament::link>
            <x-filament::link :href="$url . '?download=1'" icon="heroicon-o-arrow-down-tray" color="gray">
                Baixar
            </x-filament::link>
        </div>

        {{-- Imagem (fallback) ou PDF no visualizador nativo do navegador. --}}
        @if (in_array(strtolower(pathinfo($proposal->document, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']))
            <img src="{{ $url }}" alt="Proposta" class="max-w-full rounded-lg border border-gray-200 dark:border-white/10">
        @else
            <iframe
                src="{{ $url }}#view=FitH"
                title="Proposta enviada"
                class="w-full rounded-lg border border-gray-200 dark:border-white/10"
                style="height: 80vh; min-height: 520px;"
            ></iframe>
        @endif
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Nenhuma proposta anexada ainda. Envie o PDF no campo "Proposta enviada (PDF)" e salve.
    </p>
@endif
