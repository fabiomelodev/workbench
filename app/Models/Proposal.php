<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Proposal extends Model
{
    protected $guarded = ['id'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->slug = Str::slug($model->name);
        });

        static::updating(function ($model) {
            $model->slug = Str::slug($model->name);
        });

        // Trocou ou removeu o PDF: apaga o arquivo antigo do disco.
        static::updated(function (Proposal $proposal) {
            $old = $proposal->getOriginal('document');

            if ($proposal->wasChanged('document') && filled($old)) {
                Storage::disk(self::DOCUMENT_DISK)->delete($old);
            }
        });

        static::deleting(fn (Proposal $proposal) => $proposal->purgeFiles());
    }

    /** Disco privado: o PDF só abre pela rota proposals.document, logado no painel. */
    public const DOCUMENT_DISK = 'local';

    public function hasDocument(): bool
    {
        return filled($this->document) && Storage::disk(self::DOCUMENT_DISK)->exists($this->document);
    }

    public function documentUrl(): ?string
    {
        return filled($this->document) ? route('proposals.document', $this) : null;
    }

    /**
     * Apaga do disco o PDF da proposta e os comprovantes das parcelas das
     * cobranças dela (que saem por cascade no banco, sem eventos de model).
     */
    public function purgeFiles(): void
    {
        if (filled($this->document)) {
            Storage::disk(self::DOCUMENT_DISK)->delete($this->document);
        }

        Payment::query()
            ->whereIn('transaction_id', $this->transactions()->select('id'))
            ->get(['id', 'receipts'])
            ->each(fn (Payment $payment) => Payment::deleteReceiptFiles($payment->receipts ?? []));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    /** Cobranças da proposta (normalmente uma; mais de uma só se houver cancelada). */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Cobrança vigente (não cancelada), se houver. */
    public function activeTransaction(): ?Transaction
    {
        return $this->transactions()->notCanceled()->latest('id')->first();
    }

    public function isSubscription(): bool
    {
        return $this->type === 'signature';
    }

    /** Foi contratada quando alguma prospecção vinculada está como "Contratado". */
    public function isHired(): bool
    {
        // Usa o withExists('... as is_hired') quando disponível para evitar query extra.
        if (isset($this->is_hired)) {
            return (bool) $this->is_hired;
        }

        return $this->prospects()->where('status', Prospect::HIRED)->exists();
    }
}
