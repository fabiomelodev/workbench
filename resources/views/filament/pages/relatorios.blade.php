<x-filament-panels::page>
    {{-- Filtros --}}
    <x-filament::section>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
            <div>
                <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">De</div>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="startDate" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">Até</div>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="endDate" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">Nicho</div>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="nicheId">
                        <option value="">Todos os nichos</option>
                        @foreach ($this->nicheOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    {{-- KPIs --}}
    @php $kpis = $this->kpis(); @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">
        @foreach ([
            ['Tentativas', $kpis['total'], ''],
            ['Respostas', $kpis['responses'], ''],
            ['Taxa de resposta', $kpis['rate'] . '%', ''],
            ['Reuniões marcadas', $kpis['meetings'], ''],
            ['Fechamentos', $kpis['closed'], ''],
        ] as [$label, $value, $_])
            <div style="background:rgba(120,120,120,.07);border-radius:.6rem;padding:.85rem 1rem;">
                <div style="font-size:.78rem;opacity:.7;">{{ $label }}</div>
                <div style="font-size:1.6rem;font-weight:600;margin-top:.15rem;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- Taxa de resposta por canal --}}
    <x-filament::section heading="Taxa de resposta por canal">
        @php $rows = $this->channelStats(); @endphp
        @php $temDados = collect($rows)->contains(fn ($r) => $r['hasData']); @endphp

        @if (! $temDados)
            <div style="opacity:.7;padding:.5rem .25rem;">
                Ainda não há tentativas com desfecho informado neste período. Registre o desfecho ao
                prospectar (na ação "Tentativas") para ver a taxa de resposta por canal.
            </div>
        @else
            @foreach ($rows as $r)
                <div style="margin-bottom:.85rem;">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;font-size:.85rem;margin-bottom:.3rem;">
                        <span style="font-weight:600;">{{ $r['label'] }}</span>
                        <span style="opacity:.75;">
                            @if ($r['hasData'])
                                {{ $r['responses'] }}/{{ $r['valid'] }} respostas · <strong>{{ $r['rate'] }}%</strong>
                            @else
                                sem desfechos
                            @endif
                            <span style="opacity:.6;">({{ $r['total'] }} tent.)</span>
                        </span>
                    </div>
                    <div style="background:rgba(120,120,120,.15);border-radius:9999px;height:10px;overflow:hidden;">
                        <div style="width:{{ $r['hasData'] ? $r['rate'] : 0 }}%;height:100%;background:#2a78d6;border-radius:9999px;transition:width .2s;"></div>
                    </div>
                </div>
            @endforeach
        @endif
    </x-filament::section>

    {{-- Distribuição de desfechos --}}
    <x-filament::section heading="Desfechos das tentativas">
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            @foreach ($this->outcomeDistribution() as $row)
                <span style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .65rem;border-radius:9999px;background:rgba(120,120,120,.1);font-size:.82rem;">
                    {{ $row['label'] }}
                    <strong>{{ $row['count'] }}</strong>
                </span>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
