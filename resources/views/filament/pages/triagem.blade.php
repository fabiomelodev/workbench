<x-filament-panels::page>
    @php $customer = $this->currentCustomer(); @endphp

    @if (! $customer)
        <x-filament::section>
            <div style="text-align:center;padding:2.5rem 1rem;">
                <div style="font-size:2.25rem;">🎉</div>
                <div style="font-weight:700;font-size:1.15rem;margin-top:.5rem;">Tudo limpo por aqui!</div>
                <div style="opacity:.7;margin-top:.35rem;">Nenhum lead pendente de enriquecimento nesta sessão.</div>
                <div style="margin-top:1.25rem;">
                    <x-filament::button color="gray" wire:click="restart" icon="heroicon-m-arrow-path">
                        Recomeçar do início
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @else
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.25rem;">
            <div style="font-weight:600;opacity:.85;">
                Faltam <strong>{{ $this->remainingCount() }}</strong> lead(s) para enriquecer nesta sessão
            </div>
            <x-filament::button size="sm" color="gray" wire:click="restart" icon="heroicon-m-arrow-path">
                Recomeçar
            </x-filament::button>
        </div>

        {{-- Botões de 1 clique + status do telefone + completude (reuso da Central de Contato) --}}
        <x-filament::section>
            @include('filament.components.contact-actions', ['customer' => $customer])
        </x-filament::section>

        {{-- Preencher o que encontrou --}}
        <x-filament::section heading="Preencher dados encontrados">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                <div>
                    <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">Telefone / WhatsApp</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="phone" placeholder="(11) 99999-9999" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">Instagram</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="instagram" placeholder="@perfil ou link" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">Site</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="website" placeholder="https://..." />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div style="font-size:.8rem;font-weight:600;opacity:.8;margin-bottom:.35rem;">E-mail</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="email" wire:model="email" placeholder="contato@empresa.com" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:1.25rem;flex-wrap:wrap;">
                <x-filament::button wire:click="save" icon="heroicon-m-check" wire:loading.attr="disabled">
                    Salvar e próximo
                </x-filament::button>
                <x-filament::button color="gray" wire:click="skip" icon="heroicon-m-arrow-right" wire:loading.attr="disabled">
                    Pular
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
