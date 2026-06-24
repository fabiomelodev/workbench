<?php
namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Icons\Heroicon;
use Wezlo\FilamentKanban\Contracts\KanbanStatusEnum;

enum LeadStatus: string implements HasIcon, KanbanStatusEnum
{
    case ON_HOLD = 'on_hold';
    case NEW = 'new';
    case INTOUCH = 'in_touch';
    case AWAITING_RETURN = 'awaiting_return';
    case PASSED_DEPARTMENT = 'passed_department';
    case CLOSED = 'closed';
    case SCHEDULED_MEETING = 'scheduled_meeting';
    case NO_RESPONSE = 'no_response';
    case HIRED = 'hired';

    // Required by HasLabel (via KanbanStatusEnum)
    public function getLabel(): string
    {
        return match ($this) {
            self::ON_HOLD => 'Em espera',
            self::NEW => 'Novo',
            self::INTOUCH => 'Em contato',
            self::AWAITING_RETURN => 'Aguardando retorno',
            self::PASSED_DEPARTMENT => 'Repassado para outro departamento',
            self::CLOSED => 'Encerrado',
            self::SCHEDULED_MEETING => 'Reunião marcada',
            self::NO_RESPONSE => 'Sem resposta',
            self::HIRED => 'Contratado',
        };
    }

    // Required by HasColor (via KanbanStatusEnum)
    public function getColor(): string
    {
        return match ($this) {
            self::ON_HOLD => 'warning',
            self::NEW => 'success',
            self::INTOUCH => 'success',
            self::AWAITING_RETURN => 'success',
            self::PASSED_DEPARTMENT => 'success',
            self::CLOSED => 'info',
            self::SCHEDULED_MEETING => 'info',
            self::NO_RESPONSE => 'info',
            self::HIRED => 'info'
        };
    }

    // Optional: HasIcon
    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::NEW => Heroicon::Sparkles,
            self::ON_HOLD => Heroicon::Sparkles,
            default => Heroicon::Sparkles
        // ...
        };
    }

    // Define which statuses each status can transition to.
    // Return null to allow all transitions (arrastar para qualquer coluna).
    public function getAllowedTransitions(): ?array
    {
        return null;
    }

    // Set max cards per column. Return null for unlimited (sem limite de cartões).
    public function getWipLimit(): ?int
    {
        return null;
    }
}