<x-filament-widgets::widget>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem;">
        @foreach ($this->tabs() as $key => $label)
            @php
                $active = $this->activeTab === $key;
                $count = $this->countFor($key);
                $badgeBg = ($key === 'overdue' && $count > 0) ? '#dc2626' : ($active ? 'rgba(255,255,255,.25)' : 'rgba(120,120,120,.18)');
                $badgeColor = ($key === 'overdue' && $count > 0) ? '#fff' : 'inherit';
            @endphp
            <button
                type="button"
                wire:click="setTab('{{ $key }}')"
                @style([
                    'display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .9rem;border-radius:.6rem;font-size:.85rem;font-weight:600;border:1px solid transparent;transition:all .12s;cursor:pointer;',
                    'background:#f59e0b;color:#1f2937;box-shadow:0 1px 2px rgba(0,0,0,.12);' => $active,
                    'background:rgba(120,120,120,.10);' => ! $active,
                ])
            >
                {{ $label }}
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:1.25rem;height:1.25rem;padding:0 .35rem;border-radius:9999px;font-size:.7rem;font-weight:700;background:{{ $badgeBg }};color:{{ $badgeColor }};">
                    {{ $count }}
                </span>
            </button>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-widgets::widget>
