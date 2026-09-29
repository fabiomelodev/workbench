<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

/**
 * Cobrança de uma proposta fechada. Orçamento fechado tem valor total e nº de
 * parcelas; assinatura (installments = null) tem o valor da mensalidade e
 * parcelas geradas mês a mês pelo comando `transactions:generate-subscriptions`.
 */
class Transaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'installments' => 'integer',
        'paid_at' => 'datetime',
    ];

    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const CANCELED = 'canceled';

    protected static function booted(): void
    {
        // As parcelas saem por cascade no banco (sem eventos), então os
        // comprovantes delas são apagados do disco aqui.
        static::deleting(function (Transaction $transaction) {
            $transaction->payments()->get(['id', 'receipts'])->each(
                fn (Payment $payment) => Payment::deleteReceiptFiles($payment->receipts ?? []),
            );
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('number');
    }

    public function scopeNotCanceled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::CANCELED);
    }

    public function isSubscription(): bool
    {
        return $this->installments === null;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::CANCELED;
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->whereNotNull('paid_at')->sum('amount');
    }

    public function openAmount(): float
    {
        return (float) $this->payments()->whereNull('paid_at')->sum('amount');
    }

    /**
     * Recalcula a quitação a partir das parcelas: orçamento fechado fica "paga"
     * quando todas as parcelas estão pagas. Assinatura nunca quita (é contínua).
     * Chamado automaticamente sempre que uma parcela é salva ou apagada.
     */
    public function refreshStatus(): void
    {
        if ($this->isCanceled()) {
            return;
        }

        $payments = $this->payments()->get(['paid_at']);

        $settled = ! $this->isSubscription()
            && $payments->isNotEmpty()
            && $payments->every(fn (Payment $payment) => $payment->paid_at !== null);

        $this->status = $settled ? self::PAID : self::PENDING;
        $this->paid_at = $settled ? $payments->max('paid_at') : null;
        $this->save();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PAID => 'Quitada',
            self::CANCELED => 'Cancelada',
            default => $this->isSubscription() ? 'Assinatura ativa' : 'Em aberto',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::PAID => 'success',
            self::CANCELED => 'gray',
            default => $this->isSubscription() ? 'info' : 'warning',
        };
    }

    /** Ex.: "2/4 pagas" (orçamento fechado) ou "3 pagas" (assinatura). */
    public function progressLabel(): string
    {
        $paid = $this->payments()->whereNotNull('paid_at')->count();

        return $this->isSubscription()
            ? $paid . ' ' . ($paid === 1 ? 'paga' : 'pagas')
            : $paid . '/' . $this->installments . ' pagas';
    }
}
