<x-filament-panels::page>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-left" wire:click="previousMonth">
                Anterior
            </x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="goToday">
                Hoje
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-right" wire:click="nextMonth">
                Próximo
            </x-filament::button>
        </div>

        <div style="font-size:1.15rem;font-weight:600;">{{ $this->monthLabel() }}</div>

        <div style="font-size:.85rem;opacity:.7;">{{ $this->followUpCount() }} follow-up(s) no mês</div>
    </div>

    @php $dows = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']; @endphp

    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;">
        @foreach ($dows as $dow)
            <div style="text-align:center;font-size:.72rem;font-weight:700;opacity:.55;padding:.25rem 0;">{{ $dow }}</div>
        @endforeach

        @foreach ($this->weeks() as $week)
            @foreach ($week as $cell)
                @php
                    $cellBg = $cell['isToday'] ? 'rgba(245,158,11,.12)' : 'rgba(120,120,120,.05)';
                    $cellBorder = $cell['isToday'] ? '#f59e0b' : 'rgba(120,120,120,.18)';
                @endphp
                <div style="min-height:106px;border:1px solid {{ $cellBorder }};border-radius:.5rem;padding:.35rem;background:{{ $cellBg }};{{ $cell['inMonth'] ? '' : 'opacity:.4;' }}display:flex;flex-direction:column;gap:.2rem;">
                    <div style="font-size:.74rem;font-weight:700;text-align:right;{{ $cell['isToday'] ? 'color:#b45309;' : 'opacity:.65;' }}">
                        {{ $cell['day'] }}
                    </div>

                    @foreach ($cell['prospects']->take(4) as $prospect)
                        @php
                            $name = $prospect->proposal?->customer?->name ?? 'Sem cliente';
                            $chipBg = $cell['isPast'] ? '#fee2e2' : '#e0e7ff';
                            $chipColor = $cell['isPast'] ? '#991b1b' : '#3730a3';
                        @endphp
                        <a href="{{ route('filament.admin.resources.prospects.edit', $prospect) }}"
                           title="{{ $name }}"
                           style="display:block;font-size:.7rem;font-weight:600;padding:.16rem .35rem;border-radius:.3rem;background:{{ $chipBg }};color:{{ $chipColor }};text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ \Illuminate\Support\Str::limit($name, 16) }}
                        </a>
                    @endforeach

                    @if ($cell['prospects']->count() > 4)
                        <div style="font-size:.65rem;opacity:.6;padding-left:.2rem;">
                            +{{ $cell['prospects']->count() - 4 }} mais
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>
