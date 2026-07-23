<?php
namespace App\Enums;

use App\Models\Prospect;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Icons\Heroicon;
use Wezlo\FilamentKanban\Contracts\KanbanStatusEnum;

enum LeadStatus: string implements HasIcon, KanbanStatusEnum
{
    case NEW = 'new';
    case ON_HOLD = 'on_hold';
    case INTOUCH = 'in_touch';
    case AWAITING_RETURN = 'awaiting_return';
    case PASSED_DEPARTMENT = 'passed_department';
    case SCHEDULED_MEETING = 'scheduled_meeting';
    case HIRED = 'hired';
    case CLOSED = 'closed';
    case NO_RESPONSE = 'no_response';

    // Required by HasLabel (via KanbanStatusEnum)
    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::ON_HOLD => 'Em espera',
            self::INTOUCH => 'Em contato',
            self::AWAITING_RETURN => 'Aguardando retorno',
            self::PASSED_DEPARTMENT => 'Repassado para outro departamento',
            self::SCHEDULED_MEETING => 'Reunião marcada',
            self::HIRED => 'Contratado',
            self::CLOSED => 'Encerrado',
            self::NO_RESPONSE => 'Sem resposta',
        };
    }

    // Required by HasColor (via KanbanStatusEnum)
    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'warning',
            self::ON_HOLD => 'success',
            self::INTOUCH => 'success',
            self::AWAITING_RETURN => 'success',
            self::PASSED_DEPARTMENT => 'success',
            self::SCHEDULED_MEETING => 'info',
            self::HIRED => 'info',
            self::CLOSED => 'danger',
            self::NO_RESPONSE => 'danger',
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