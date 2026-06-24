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

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** Rótulo legível do canal (E-mail, Instagram, Whatsapp...). */
    public function channelLabel(): string
    {
        return Prospect::getTypeChannels()[$this->channel] ?? '—';
    }
}
