<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
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
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
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
