<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Parcela de uma cobrança (Transaction). paid_at null = em aberto. */
class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'number' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'date',
        'receipts' => 'array',
    ];

    /** Disco privado: comprovantes só abrem por URL temporária, logado no painel. */
    public const RECEIPTS_DISK = 'local';

    protected static function booted(): void
    {
        // Mantém a quitação da cobrança em sincronia com as parcelas.
        static::saved(fn (Payment $payment) => $payment->transaction?->refreshStatus());
        static::deleted(fn (Payment $payment) => $payment->transaction?->refreshStatus());

        // Apaga do disco os comprovantes removidos da parcela ou da parcela excluída.
        static::updated(function (Payment $payment) {
            if ($payment->wasChanged('receipts')) {
                $original = json_decode((string) $payment->getRawOriginal('receipts'), true) ?: [];
                static::deleteReceiptFiles(array_diff($original, $payment->receipts ?? []));
            }
        });
        static::deleted(fn (Payment $payment) => static::deleteReceiptFiles($payment->receipts ?? []));
    }

    /** @param  iterable<string>  $paths */
    public static function deleteReceiptFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (filled($path)) {
                Storage::disk(self::RECEIPTS_DISK)->delete($path);
            }
        }
    }

    public function receiptsCount(): int
    {
        return count($this->receipts ?? []);
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
