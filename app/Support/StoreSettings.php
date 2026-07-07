<?php

namespace App\Support;

use App\Models\Setting;

class StoreSettings
{
    public const DEFAULTS = [
        'shipping_fee' => 2500,
        'free_shipping_threshold' => 50000,
        'tax_rate' => 0.1,
        'currency' => 'FCFA',
        'store_name' => 'BE-Market',
        'store_email' => 'contact@be-market.tg',
        'store_phone' => '',
    ];

    private const KEY_ALIASES = [
        'site_name' => 'store_name',
        'support_email' => 'store_email',
        'shipping_cost' => 'shipping_fee',
    ];

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    public static function resolve(): array
    {
        $stored = Setting::all()
            ->pluck('value', 'key')
            ->map(fn ($value) => is_string($value) ? json_decode($value, true) ?? $value : $value)
            ->all();

        foreach (self::KEY_ALIASES as $legacyKey => $canonicalKey) {
            if (array_key_exists($legacyKey, $stored) && ! array_key_exists($canonicalKey, $stored)) {
                $stored[$canonicalKey] = $stored[$legacyKey];
            }
        }

        return array_merge(self::DEFAULTS, $stored);
    }
}
