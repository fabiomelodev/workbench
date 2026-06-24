<?php

namespace App\Filament\Actions;

use App\Models\Customer;
use App\Models\Prospect;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * "Central de Contato": abre uma única tela (slide-over) com botões de 1 clique
 * (WhatsApp, Instagram, site, Google e Maps já pesquisados) + campos para colar
 * o que encontrar e salvar. Substitui o vai-e-volta de abrir várias janelas.
 *
 * Funciona tanto a partir de um Customer quanto de um Prospect (resolve o
 * cliente pela proposta).
 */
class ContactCenterAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'contactCenter';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Central de Contato')
            ->icon(Heroicon::User)
            ->color('primary')
            ->slideOver()
            ->modalHeading('Central de Contato')
            ->modalDescription('Confira e enriqueça os dados da empresa sem sair desta tela.')
            ->modalSubmitActionLabel('Salvar dados')
            ->modalContent(fn (Model $record) => view(
                'filament.components.contact-actions',
                ['customer' => static::resolveCustomer($record)],
            ))
            ->mountUsing(function (Schema $schema, Model $record) {
                $customer = static::resolveCustomer($record);

                if ($customer) {
                    $schema->fill($customer->only(['phone', 'instagram', 'website', 'email']));
                }
            })
            ->schema([
                Section::make('Editar / colar dados encontrados')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('phone')
                            ->label('Telefone / WhatsApp')
                            ->helperText('Cole o número; o tipo (celular/fixo) é detectado automaticamente.'),
                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->helperText('@perfil ou link completo.'),
                        TextInput::make('website')
                            ->label('Site')
                            ->url(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email(),
                    ]),
            ])
            ->action(function (Model $record, array $data) {
                $customer = static::resolveCustomer($record);

                if (! $customer) {
                    Notification::make()
                        ->title('Cliente não encontrado para este registro.')
                        ->danger()
                        ->send();

                    return;
                }

                $customer->update($data);

                Notification::make()
                    ->title('Dados atualizados com sucesso!')
                    ->body('O telefone foi reclassificado automaticamente.')
                    ->success()
                    ->send();
            });
    }

    /** Resolve o Customer a partir de um Customer ou de um Prospect. */
    public static function resolveCustomer(Model $record): ?Customer
    {
        if ($record instanceof Customer) {
            return $record;
        }

        if ($record instanceof Prospect) {
            return $record->proposal?->customer;
        }

        return null;
    }
}
