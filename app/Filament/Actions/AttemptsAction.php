<?php

namespace App\Filament\Actions;

use App\Models\Prospect;
use Filament\Actions\Action;
use Filament\Forms\Components\{DatePicker, Select, Textarea};
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Histórico de tentativas de uma prospecção: mostra quantas vezes a empresa já
 * foi prospectada (canal + data) e permite registrar uma nova tentativa em 1
 * clique. Serve para decidir se vale continuar ou parar de prospectar.
 */
class AttemptsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attempts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (Model $record): string => 'Tentativas (' . static::countFor($record) . '/' . Prospect::MAX_ATTEMPTS . ')')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color(fn (Model $record): string => static::countFor($record) >= Prospect::MAX_ATTEMPTS ? 'danger' : 'gray')
            ->slideOver()
            ->modalHeading('Histórico de tentativas')
            ->modalDescription('Cada contato registrado entra no histórico para você acompanhar quantas tentativas já fez.')
            ->modalSubmitActionLabel('Registrar tentativa')
            ->modalContent(fn (Model $record) => view('filament.components.attempt-history', [
                'prospect' => static::resolveProspect($record),
            ]))
            ->fillForm(fn (Model $record): array => [
                'channel' => static::resolveProspect($record)?->channel,
                'attempted_at' => now(),
            ])
            ->schema([
                Section::make('Registrar nova tentativa')
                    ->columns(2)
                    ->schema([
                        Select::make('channel')
                            ->label('Meio de canal')
                            ->options(Prospect::getTypeChannels())
                            ->required(),
                        DatePicker::make('attempted_at')
                            ->label('Data')
                            ->default(now())
                            ->required(),
                        DatePicker::make('next_action')
                            ->label('Agendar próxima tentativa (opcional)'),
                        Textarea::make('notes')
                            ->label('Observação (opcional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (Model $record, array $data) {
                $prospect = static::resolveProspect($record);

                if (! $prospect) {
                    Notification::make()->title('Prospecção não encontrada.')->danger()->send();

                    return;
                }

                $prospect->attempts()->create([
                    'channel' => $data['channel'],
                    'attempted_at' => $data['attempted_at'],
                    'notes' => $data['notes'] ?? null,
                ]);

                // Mantém os campos "atalho" da prospecção em sincronia.
                $prospect->update([
                    'channel' => $data['channel'],
                    'last_action' => $data['attempted_at'],
                    'next_action' => filled($data['next_action'] ?? null) ? $data['next_action'] : $prospect->next_action,
                ]);

                Notification::make()->title('Tentativa registrada!')->success()->send();
            });
    }

    public static function resolveProspect(Model $record): ?Prospect
    {
        return $record instanceof Prospect ? $record : null;
    }

    protected static function countFor(Model $record): int
    {
        $prospect = static::resolveProspect($record);

        if (! $prospect) {
            return 0;
        }

        return $prospect->attempts_count ?? $prospect->attempts()->count();
    }
}
