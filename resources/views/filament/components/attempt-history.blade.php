@php
    /** @var \App\Models\Prospect|null $prospect */
    $attempts = $prospect ? $prospect->attempts()->get() : collect();
@endphp

@if ($prospect)
    <div style="display:flex;flex-direction:column;gap:.6rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <span style="font-weight:700;font-size:.95rem;">{{ $prospect->proposal?->customer?->name ?? 'Prospecção' }}</span>
            <span style="display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:9999px;font-size:.75rem;font-weight:700;background:{{ $attempts->count() >= \App\Models\Prospect::MAX_ATTEMPTS ? '#fee2e2' : 'rgba(120,120,120,.15)' }};color:{{ $attempts->count() >= \App\Models\Prospect::MAX_ATTEMPTS ? '#991b1b' : 'inherit' }};">
                {{ $attempts->count() }}/{{ \App\Models\Prospect::MAX_ATTEMPTS }} {{ \Illuminate\Support\Str::plural('tentativa', $attempts->count()) }}
            </span>
        </div>

        @if ($attempts->count() >= \App\Models\Prospect::MAX_ATTEMPTS)
            <div style="display:flex;align-items:center;gap:.5rem;padding:.6rem .75rem;border-radius:.6rem;background:#fee2e2;color:#991b1b;font-size:.85rem;font-weight:600;border:1px solid #fecaca;">
                ⚠️ Limite de {{ \App\Models\Prospect::MAX_ATTEMPTS }} tentativas atingido — hora de decidir se continua ou para de prospectar.
            </div>
        @endif

        @forelse ($attempts as $attempt)
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;padding:.55rem .75rem;border-radius:.6rem;background:rgba(120,120,120,.08);">
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:1.6rem;height:1.6rem;padding:0 .5rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:#f59e0b;color:#1f2937;">
                    {{ $loop->iteration }}ª
                </span>
                <span style="font-weight:600;">📣 {{ $attempt->channelLabel() }}</span>
                <span style="opacity:.85;">📅 {{ $attempt->attempted_at?->format('d/m/Y') }}</span>
                @if ($attempt->notes)
                    <span style="flex-basis:100%;font-size:.8rem;opacity:.7;padding-left:2.1rem;">— {{ $attempt->notes }}</span>
                @endif
            </div>
        @empty
            <div style="opacity:.7;padding:.5rem .25rem;">
                Nenhuma tentativa registrada ainda. Use o formulário abaixo para registrar a primeira.
            </div>
        @endforelse
    </div>
@else
    <div style="padding:1rem;opacity:.7;">Prospecção não encontrada.</div>
@endif
