<?php

namespace App\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Classifica e normaliza números de telefone usando a libphonenumber.
 *
 * Resolve a dor de "esse número é WhatsApp válido ou um telefone fixo?":
 * a partir do formato do número (offline, sem custo) descobrimos se é
 * celular (provável WhatsApp), fixo, outro tipo válido ou inválido.
 */
class PhoneNumberService
{
    public const MOBILE = 'mobile';
    public const LANDLINE = 'landline';
    public const OTHER = 'other';
    public const INVALID = 'invalid';

    /**
     * Recebe qualquer entrada (URL wa.me, dígitos locais, número formatado)
     * e devolve a análise normalizada.
     *
     * @return array{e164: ?string, national: ?string, digits: ?string, type: ?string, valid: bool}
     */
    public function parse(?string $raw, string $region = 'BR'): array
    {
        $empty = ['e164' => null, 'national' => null, 'digits' => null, 'type' => null, 'valid' => false];

        if (blank($raw)) {
            return $empty;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '' || $digits === null) {
            return $empty;
        }

        $util = PhoneNumberUtil::getInstance();

        // Se já vier com o DDI 55 (ex.: links wa.me têm 12+ dígitos), tratamos
        // como número internacional. Números locais com DDD 55 (RS) têm no
        // máximo 11 dígitos e caem no parse por região, sem falso positivo.
        $candidate = (str_starts_with($digits, '55') && strlen($digits) >= 12)
            ? '+' . $digits
            : $digits;

        try {
            $proto = $util->parse($candidate, $region);
        } catch (NumberParseException) {
            return ['e164' => null, 'national' => null, 'digits' => $digits, 'type' => self::INVALID, 'valid' => false];
        }

        if (! $util->isValidNumber($proto)) {
            return ['e164' => null, 'national' => null, 'digits' => $digits, 'type' => self::INVALID, 'valid' => false];
        }

        $type = match ($util->getNumberType($proto)) {
            PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE => self::MOBILE,
            PhoneNumberType::FIXED_LINE => self::LANDLINE,
            default => self::OTHER,
        };

        $e164 = $util->format($proto, PhoneNumberFormat::E164);

        return [
            'e164' => $e164,
            'national' => $util->format($proto, PhoneNumberFormat::NATIONAL),
            'digits' => preg_replace('/\D+/', '', $e164),
            'type' => $type,
            'valid' => true,
        ];
    }

    /**
     * Atalho: devolve apenas o tipo (mobile|landline|other|invalid) ou null.
     */
    public function classify(?string $raw, string $region = 'BR'): ?string
    {
        return $this->parse($raw, $region)['type'];
    }
}
