<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectAttempt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'attempted_at' => 'date',
    ];

    public const OUTCOME_NO_ANSWER = 'no_answer';
    public const OUTCOME_RESPONDED = 'responded';
    public const OUTCOME_CALLBACK = 'callback';
    public const OUTCOME_MEETING = 'meeting';
    public const OUTCOME_NOT_INTERESTED = 'not_interested';
    public const OUTCOME_CLOSED = 'closed';
    public const OUTCOME_WRONG_NUMBER = 'wrong_number';

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** Rótulo legível do canal (E-mail, Instagram, Whatsapp...). */
    public function channelLabel(): string
    {
        return Prospect::getTypeChannels()[$this->channel] ?? '—';
    }

    /** Desfechos possíveis de uma tentativa. */
    public static function getOutcomes(): array
    {
        return [
            self::OUTCOME_NO_ANSWER => 'Não atendeu / sem retorno',
            self::OUTCOME_RESPONDED => 'Respondeu',
            self::OUTCOME_CALLBACK => 'Pediu retorno',
            self::OUTCOME_MEETING => 'Reunião marcada',
            self::OUTCOME_NOT_INTERESTED => 'Sem interesse',
            self::OUTCOME_CLOSED => 'Fechou negócio',
            self::OUTCOME_WRONG_NUMBER => 'Número errado',
        ];
    }

    /** Desfechos que contam como "houve resposta" (engajamento). */
    public static function responseOutcomes(): array
    {
        return [
            self::OUTCOME_RESPONDED,
            self::OUTCOME_CALLBACK,
            self::OUTCOME_MEETING,
            self::OUTCOME_NOT_INTERESTED,
            self::OUTCOME_CLOSED,
        ];
    }

    /** Desfechos inválidos, excluídos do denominador da taxa de resposta. */
    public static function invalidOutcomes(): array
    {
        return [self::OUTCOME_WRONG_NUMBER];
    }

    public function outcomeLabel(): string
    {
        return self::getOutcomes()[$this->outcome] ?? 'Não informado';
    }

    public function outcomeColor(): string
    {
        return match ($this->outcome) {
            self::OUTCOME_CLOSED, self::OUTCOME_MEETING => 'success',
            self::OUTCOME_RESPONDED, self::OUTCOME_CALLBACK => 'info',
            self::OUTCOME_NOT_INTERESTED => 'warning',
            self::OUTCOME_WRONG_NUMBER => 'danger',
            self::OUTCOME_NO_ANSWER => 'gray',
            default => 'gray',
        };
    }
}
