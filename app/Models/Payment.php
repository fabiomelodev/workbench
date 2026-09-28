<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Parcela de uma cobrança (Transaction). paid_at null = em aberto. */
class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'number' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    protected static function booted(): void
    {
        // Mantém a quitação da cobrança em sincronia com as parcelas.
        static::saved(fn (Payment $payment) => $payment->transaction?->refreshStatus());
        static::deleted(fn (Payment $payment) => $payment->transaction?->refreshStatus());
    }

    public static function getMethods(): array
    {
        return [
            'pix' => 'Pix',
            'boleto' => 'Boleto',
            'credit_card' => 'Cartão de crédito',
            'transfer' => 'Transferência',
            'cash' => 'Dinheiro',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Parcelas em aberto de cobranças não canceladas. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('paid_at')
            ->whereHas('transaction', fn (Builder $query) => $query->notCanceled());
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereNotNull('paid_at');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_date->lt(today());
    }

    public function methodLabel(): ?string
    {
        return static::getMethods()[$this->method] ?? null;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isPaid() => 'Paga',
            $this->isOverdue() => 'Atrasada',
            default => 'Em aberto',
        };
    }

    public function statusColor(): string
    {
        return match (true) {
            $this->isPaid() => 'success',
            $this->isOverdue() => 'danger',
            default => 'warning',
        };
    }
}
