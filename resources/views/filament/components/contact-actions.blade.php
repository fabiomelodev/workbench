@php
    /** @var \App\Models\Customer|null $customer */

    $btnBase = 'display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.65rem .5rem;'
        . 'border-radius:.6rem;font-size:.85rem;font-weight:600;color:#fff;text-decoration:none;'
        . 'box-shadow:0 1px 2px rgba(0,0,0,.12);line-height:1.1;text-align:center;';

    $disabledBtn = 'display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.65rem .5rem;'
        . 'border-radius:.6rem;font-size:.85rem;font-weight:600;color:#6b7280;background:#e5e7eb;'
        . 'line-height:1.1;text-align:center;cursor:not-allowed;';

    $phoneColors = match ($customer?->phone_type) {
        'mobile'   => ['#dcfce7', '#166534'],
        'landline' => ['#fef9c3', '#854d0e'],
        'invalid'  => ['#fee2e2', '#991b1b'],
        default    => ['#f3f4f6', '#374151'],
    };
@endphp

@if ($customer)
    <div style="display:flex;flex-direction:column;gap:1rem;">
        {{-- Cabeçalho: empresa + status do telefone --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
            <div>
                <div style="font-weight:700;font-size:1.05rem;color:inherit;">{{ $customer->name }}</div>
                <div style="font-size:.8rem;opacity:.7;">
                    {{ $customer->niche?->name ?? 'Sem nicho' }}@if ($customer->cityName()) · {{ $customer->cityName() }}@endif
                </div>
            </div>
            <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;border-radius:9999px;font-size:.78rem;font-weight:600;background:{{ $phoneColors[0] }};color:{{ $phoneColors[1] }};">
                {{ $customer->phoneTypeEmoji() }} {{ $customer->phoneTypeLabel() }}@if ($customer->phoneFormatted()) · {{ $customer->phoneFormatted() }}@endif
            </span>
        </div>

        {{-- Botões de 1 clique --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.5rem;">
            @if ($customer->whatsappUrl())
                <a href="{{ $customer->whatsappUrl() }}" target="_blank" rel="noopener"
                   style="{{ $btnBase }}background:#25D366;">💬 WhatsApp</a>
            @else
                <span style="{{ $disabledBtn }}" title="O número não é um celular válido para WhatsApp">💬 Sem WhatsApp</span>
            @endif

            @if ($customer->instagramUrl())
                <a href="{{ $customer->instagramUrl() }}" target="_blank" rel="noopener"
                   style="{{ $btnBase }}background:#E1306C;">📷 Instagram</a>
            @else
                <a href="{{ $customer->instagramSearchUrl() }}" target="_blank" rel="noopener"
                   style="{{ $btnBase }}background:#9333ea;">🔎 Buscar Instagram</a>
            @endif

            @if ($customer->websiteUrl())
                <a href="{{ $customer->websiteUrl() }}" target="_blank" rel="noopener"
                   style="{{ $btnBase }}background:#2563eb;">🌐 Site</a>
            @else
                <a href="{{ $customer->googleSearchUrl() }}" target="_blank" rel="noopener"
                   style="{{ $btnBase }}background:#0ea5e9;">🔎 Buscar site</a>
            @endif

            <a href="{{ $customer->googleSearchUrl() }}" target="_blank" rel="noopener"
               style="{{ $btnBase }}background:#475569;">🔎 Google</a>

            <a href="{{ $customer->googleMapsUrl() }}" target="_blank" rel="noopener"
               style="{{ $btnBase }}background:#ea4335;">📍 Maps</a>
        </div>

        {{-- Completude dos dados --}}
        <div style="padding:.75rem;border-radius:.6rem;background:rgba(120,120,120,.08);">
            <div style="font-size:.78rem;font-weight:600;opacity:.8;margin-bottom:.5rem;">
                Completude dos dados: {{ $customer->dataScore() }}/{{ $customer->dataTotal() }}
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                @foreach ($customer->dataChecklist() as $label => $ok)
                    <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .55rem;border-radius:9999px;font-size:.75rem;font-weight:600;
                        background:{{ $ok ? '#dcfce7' : '#f3f4f6' }};color:{{ $ok ? '#166534' : '#9ca3af' }};">
                        {{ $ok ? '✓' : '✗' }} {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div style="padding:1rem;opacity:.7;">Nenhum cliente vinculado a este registro.</div>
@endif
