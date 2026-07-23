<?php

namespace App\Models;

use App\Services\PhoneNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    protected $guarded = ['id'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->slug = Str::slug($model->name);
        });

        static::updated(function ($model) {
            $model->slug = Str::slug($model->name);
        });

        // Classifica o telefone (celular/fixo/inválido) sempre que o registro
        // é salvo, seja por formulário, importação ou comando de backfill.
        static::saving(function (Customer $model) {
            $model->syncPhoneClassification();
        });
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function niche(): BelongsTo
    {
        return $this->belongsTo(Niche::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Telefone (triagem celular x fixo)
    |--------------------------------------------------------------------------
    */

    /**
     * Normaliza e classifica o telefone a partir de `phone` (preferido) ou do
     * `whatsapp` legado (URL wa.me). Mantém o `whatsapp` sincronizado quando o
     * número é um celular válido, para não quebrar telas antigas.
     */
    public function syncPhoneClassification(): void
    {
        $source = filled($this->phone) ? $this->phone : $this->whatsapp;

        if (blank($source)) {
            $this->phone = null;
            $this->phone_type = null;

            return;
        }

        $result = app(PhoneNumberService::class)->parse($source);

        if ($result['valid']) {
            $this->phone = $result['digits'];
            $this->phone_type = $result['type'];

            if ($result['type'] === PhoneNumberService::MOBILE) {
                $this->whatsapp = 'https://wa.me/' . $result['digits'];
            }

            return;
        }

        // Tem algo digitado, mas não é um número válido.
        $this->phone = $result['digits'] ?? preg_replace('/\D+/', '', (string) $source);
        $this->phone_type = PhoneNumberService::INVALID;
    }

    /** Apenas os dígitos do telefone (cai para o whatsapp legado se preciso). */
    public function phoneDigits(): ?string
    {
        if (filled($this->phone)) {
            return preg_replace('/\D+/', '', $this->phone) ?: null;
        }

        if (filled($this->whatsapp)) {
            return preg_replace('/\D+/', '', $this->whatsapp) ?: null;
        }

        return null;
    }

    /** Número formatado de forma legível (ex.: (11) 99999-9999). */
    public function phoneFormatted(): ?string
    {
        $digits = $this->phoneDigits();

        if (!$digits) {
            return null;
        }

        return app(PhoneNumberService::class)->parse($digits)['national'] ?? $digits;
    }

    public function isWhatsappCapable(): bool
    {
        return $this->phone_type === PhoneNumberService::MOBILE && filled($this->phoneDigits());
    }

    public function defaultWhatsappMessage(): string
    {
        return 'Olá! Gostaria de conversar sobre o site da ' . $this->name . '.';
    }

    /** Link wa.me apenas quando o número é um celular válido. */
    public function whatsappUrl(?string $message = null): ?string
    {
        if (!$this->isWhatsappCapable()) {
            return null;
        }

        $url = 'https://wa.me/' . $this->phoneDigits();

        $message ??= $this->defaultWhatsappMessage();

        return $url . '?text=' . rawurlencode($message);
    }

    public function phoneTypeLabel(): string
    {
        return match ($this->phone_type) {
            PhoneNumberService::MOBILE => 'Celular',
            PhoneNumberService::LANDLINE => 'Fixo',
            PhoneNumberService::OTHER => 'Outro',
            PhoneNumberService::INVALID => 'Inválido',
            default => filled($this->phoneDigits()) ? 'Não verificado' : 'Sem número',
        };
    }

    public function phoneTypeColor(): string
    {
        return match ($this->phone_type) {
            PhoneNumberService::MOBILE => 'success',
            PhoneNumberService::LANDLINE => 'warning',
            PhoneNumberService::INVALID => 'danger',
            default => 'gray',
        };
    }

    public function phoneTypeEmoji(): string
    {
        return match ($this->phone_type) {
            PhoneNumberService::MOBILE => '📱',
            PhoneNumberService::LANDLINE => '☎️',
            PhoneNumberService::INVALID => '⚠️',
            default => '',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Links de 1 clique (Central de Contato)
    |--------------------------------------------------------------------------
    */

    public function cityName(): string
    {
        return (string) ($this->city?->name ?? '');
    }

    /** Link direto do Instagram quando há perfil cadastrado. */
    public function instagramUrl(): ?string
    {
        $ig = trim((string) $this->instagram);

        if ($ig === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $ig)) {
            return $ig;
        }

        $handle = ltrim($ig, '@/');

        if (str_contains($handle, 'instagram.com')) {
            return 'https://' . ltrim($handle, '/');
        }

        return 'https://instagram.com/' . $handle;
    }

    /** Busca o Instagram da empresa no Google (quando ainda não cadastrado). */
    public function instagramSearchUrl(): string
    {
        return $this->googleQuery($this->name . ' ' . $this->cityName() . ' instagram');
    }

    /** Site normalizado (garante o esquema https://). */
    public function websiteUrl(): ?string
    {
        $site = trim((string) $this->website);

        if ($site === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $site)) {
            $site = 'https://' . $site;
        }

        return $site;
    }

    public function googleSearchUrl(): string
    {
        return $this->googleQuery(trim($this->name . ' ' . $this->cityName()));
    }

    public function googleMapsUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            . rawurlencode($this->normalizeQuery($this->name . ' ' . $this->cityName()));
    }

    protected function googleQuery(string $query): string
    {
        return 'https://www.google.com/search?q=' . rawurlencode($this->normalizeQuery($query));
    }

    protected function normalizeQuery(string $query): string
    {
        return trim(preg_replace('/\s+/', ' ', $query));
    }

    /*
    |--------------------------------------------------------------------------
    | Completude dos dados (o que falta cadastrar)
    |--------------------------------------------------------------------------
    */

    /** Itens essenciais e se já estão preenchidos. */
    public function dataChecklist(): array
    {
        return [
            'WhatsApp' => $this->isWhatsappCapable(),
            'Instagram' => filled($this->instagram),
            'Site' => filled($this->website),
            'E-mail' => filled($this->email),
        ];
    }

    /** Lista do que ainda falta cadastrar. */
    public function missingData(): array
    {
        return array_keys(array_filter($this->dataChecklist(), fn($filled) => !$filled));
    }

    public function dataScore(): int
    {
        return count(array_filter($this->dataChecklist()));
    }

    public function dataTotal(): int
    {
        return count($this->dataChecklist());
    }
}
