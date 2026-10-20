<?php

namespace App\Support;

use App\Rules\MalaysianMobile;

class Phone
{
    /** The placeholder shown on phone inputs, in the local dialling format. */
    public const PLACEHOLDER = '012-345 6789';

    /**
     * A Malaysian mobile number, normalised to wa.me digits, is 601 followed by
     * 7–9 digits (e.g. 60123456789). Accepts local (01x…), bare (1x…) and
     * international (+60…) input via toWhatsApp() normalisation.
     */
    public static function isValidMobile(?string $raw): bool
    {
        $wa = self::toWhatsApp($raw);

        return $wa !== null && preg_match('/^601\d{7,9}$/', $wa) === 1;
    }

    /**
     * The validation rule for a (optional) Malaysian mobile field. A rule object,
     * not a bare closure, so Filament's ->rule() accepts it directly.
     */
    public static function rule(): MalaysianMobile
    {
        return new MalaysianMobile;
    }

    /**
     * The subscriber ("national") part shown after a +60 prefix — the digits
     * with any leading country code (60) or trunk 0 removed. '' for blank.
     * e.g. '0123456789' → '123456789', '60123456789' → '123456789'.
     */
    public static function national(?string $raw, string $cc = '60'): string
    {
        $d = self::digits($raw);

        if ($d === '') {
            return '';
        }

        if (str_starts_with($d, $cc)) {
            return substr($d, strlen($cc));
        }

        if (str_starts_with($d, '0')) {
            return substr($d, 1);
        }

        return $d;
    }

    /** Strip everything except digits; '' for blank/null. */
    public static function digits(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    /** Normalize a MY mobile to intl wa.me digits (e.g. 60123456789); null if blank. */
    public static function toWhatsApp(?string $raw, string $cc = '60'): ?string
    {
        $d = self::digits($raw);
        if ($d === '') {
            return null;
        }

        if (str_starts_with($d, '0')) {
            $d = $cc.substr($d, 1);           // 012… → 6012…
        } elseif (! str_starts_with($d, $cc)) {
            $d = $cc.$d;                       // bare 1x… → 601x…
        }

        return $d;                            // already 60… kept
    }

    /** E.164-ish tel: form (+60123456789); null if blank. */
    public static function toTel(?string $raw): ?string
    {
        $d = self::toWhatsApp($raw);

        return $d ? '+'.$d : null;
    }
}
