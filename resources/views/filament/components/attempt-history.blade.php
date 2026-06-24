@php
    /** @var \App\Models\Prospect|null $prospect */
    $attempts = $prospect ? $prospect->attempts()->get() : collect();
@endphp

@if ($prospect)
    <div style="display:flex;flex-direction:column;gap:.6rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <span style="font-weight:700;font-size:.95rem;">{{ $prospect->proposal?->customer?->name ?? 'Prospecção' }}</span>
            <span style="display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:9999px;font-size:.75rem;font-weight:700;background:rgba(120,120,120,.15);">
                {{ $attempts->count() }} {{ \Illuminate\Support\Str::plural('tentativa', $attempts->count()) }}
            </span>
        </div>

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
