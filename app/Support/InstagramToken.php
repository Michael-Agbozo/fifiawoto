<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Resolves the Instagram Graph API access token.
 *
 * A token refreshed by `instagram:refresh-token` is persisted (encrypted) in the
 * settings table and takes precedence over the INSTAGRAM_ACCESS_TOKEN env value,
 * which only seeds the very first token.
 */
class InstagramToken
{
    public const SETTING_KEY = 'instagram.access_token';

    public static function current(): ?string
    {
        $stored = Setting::read(self::SETTING_KEY);

        if (is_string($stored) && $stored !== '') {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                // APP_KEY rotated — fall back to the env token.
            }
        }

        $configured = config('services.instagram.access_token');

        return filled($configured) ? (string) $configured : null;
    }

    public static function store(string $token): void
    {
        Setting::write(self::SETTING_KEY, Crypt::encryptString($token));
    }

    /**
     * Strip the token from text (e.g. exception messages that echo the request URL).
     */
    public static function redact(string $text, ?string $token): string
    {
        return filled($token) ? str_replace($token, '[redacted]', $text) : $text;
    }
}
