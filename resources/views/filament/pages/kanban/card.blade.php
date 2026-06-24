@php
    $customer = $record->proposal?->customer;

    $phoneColors = match ($customer?->phone_type) {
        'mobile'   => ['#dcfce7', '#166534'],
        'landline' => ['#fef9c3', '#854d0e'],
        'invalid'  => ['#fee2e2', '#991b1b'],
        default    => ['#f3f4f6', '#6b7280'],
    };

    $chip = 'display:inline-flex;align-items:center;justify-content:center;gap:.25rem;padding:.3rem .5rem;'
        . 'border-radius:.45rem;font-size:.7rem;font-weight:700;color:#fff;text-decoration:none;line-height:1;';
@endphp

<div
    class="p-4 bg-white rounded-lg shadow space-y-3 border border-gray-100 hover:border-primary-500 transition-all duration-200">
    <div class="font-bold text-gray-900 text-sm flex justify-between items-start">
        <div>
            {{ $customer?->name ?? 'Cliente Sem Nome' }}
            <span class="block text-xs text-gray-500 font-normal mt-0.5">
                {{ $customer?->niche?->name ?? 'Sem segmento' }}
            </span>
        </div>
        @if ($customer)
            <span style="display:inline-flex;align-items:center;gap:.2rem;padding:.15rem .45rem;border-radius:9999px;font-size:.65rem;font-weight:700;background:{{ $phoneColors[0] }};color:{{ $phoneColors[1] }};white-space:nowrap;">
                {{ $customer->phoneTypeEmoji() }} {{ $customer->phoneTypeLabel() }}
            </span>
        @endif
    </div>

    <div class="text-xs text-gray-600 space-y-1 pt-1 border-t border-gray-50">
        <div>
            <span class="font-semibold text-gray-400">Proposta:</span>
            <span class="text-gray-900 font-medium">{{ $record->proposal?->name ?? 'Sem Proposta' }}</span>
        </div>
        <div>
            <span class="font-semibold text-gray-400">Orçamento:</span>
            <span class="text-success-600 font-semibold">R$
                {{ number_format($record->proposal?->amount ?? 0, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- Ações de 1 clique --}}
    @if ($customer)
        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-50">
            @if ($customer->whatsappUrl())
                <a href="{{ $customer->whatsappUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#25D366;">💬 Whats</a>
            @endif

            @if ($customer->instagramUrl())
                <a href="{{ $customer->instagramUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#E1306C;">📷 Insta</a>
            @else
                <a href="{{ $customer->instagramSearchUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#9333ea;">🔎 Insta</a>
            @endif

            @if ($customer->websiteUrl())
                <a href="{{ $customer->websiteUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#2563eb;">🌐 Site</a>
            @else
                <a href="{{ $customer->googleSearchUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#0ea5e9;">🔎 Site</a>
            @endif

            <a href="{{ $customer->googleMapsUrl() }}" target="_blank" rel="noopener" style="{{ $chip }}background:#ea4335;">📍 Maps</a>
        </div>
    @endif

    <div class="flex items-center justify-between pt-2 border-t border-gray-50 text-[11px]">
        <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-mono font-bold uppercase">
            {{ $record->channel ?? '—' }}
        </span>

        <a href="{{ route('filament.admin.resources.prospects.edit', $record) }}" class="text-primary-600 hover:text-primary-700 font-medium">
            Editar
        </a>
    </div>
</div>
