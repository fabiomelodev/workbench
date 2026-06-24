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
            static::ON_HOLD => 'Em espera',
            static::NEW => 'Novo',
            static::INTOUCH => 'Em contato',
            static::AWAITING_RETURN => 'Aguardando retorno',
            static::PASSED_DEPARTMENT => 'Repassado para outro departamento',
            static::CLOSED => 'Encerrado',
            static::SCHEDULED_MEETING => 'Reunião marcada',
            static::NO_RESPONSE => 'Sem resposta',
            static::HIRED => 'Contratado',
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
}
