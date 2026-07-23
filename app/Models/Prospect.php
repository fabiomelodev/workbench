<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_action' => 'datetime',
        'next_action' => 'datetime'
    ];

    public const ON_HOLD = 'on_hold';
    public const NEW = 'new';
    public const INTOUCH = 'in_touch';
    public const AWAITING_RETURN = 'awaiting_return';
    public const PASSED_DEPARTMENT = 'passed_department';
    public const CLOSED = 'closed';
    public const SCHEDULED_MEETING = 'scheduled_meeting';
    public const NO_RESPONSE = 'no_response';
    public const HIRED = 'hired';

    /** Quantidade máxima de tentativas antes de decidir continuar ou parar. */
    public const MAX_ATTEMPTS = 3;

    public static function getTypeChannels(): array
    {
        return [
            'email' => 'E-mail',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'phone' => 'Telefone',
            'whatsapp' => 'Whatsapp'
        ];
    }

    public static function getChannel(string $value): string
    {
        return static::getTypeChannels()[$value];
    }

    public static function getTypeStatus(): array
    {
        return [
            static::NEW => 'Novo',
            static::ON_HOLD => 'Em espera',
            static::INTOUCH => 'Em contato',
            static::AWAITING_RETURN => 'Aguardando retorno',
            static::PASSED_DEPARTMENT => 'Repassado para outro departamento',
            static::SCHEDULED_MEETING => 'Reunião marcada',
            static::HIRED => 'Contratado',
            static::CLOSED => 'Encerrado',
            static::NO_RESPONSE => 'Sem resposta',
        ];
    }

    public static function getTypeStatusProgress(): array
    {
        return [
            static::INTOUCH,
            static::AWAITING_RETURN,
            static::PASSED_DEPARTMENT,
        ];
    }

    public static function getStatus(string $value): string
    {
        return static::getTypeStatus()[$value];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** Histórico de tentativas de contato (canal + data), em ordem cronológica. */
    public function attempts(): HasMany
    {
        return $this->hasMany(ProspectAttempt::class)
            ->orderBy('attempted_at')
            ->orderBy('id');
    }

    /** Usa o withCount quando disponível para evitar query extra. */
    public function attemptsCount(): int
    {
        return $this->attempts_count ?? $this->attempts()->count();
    }

    public function attemptsReachedLimit(): bool
    {
        return $this->attemptsCount() >= self::MAX_ATTEMPTS;
    }
}
