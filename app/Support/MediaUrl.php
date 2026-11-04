<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resolves a stored image/media path to a public URL.
 *
 *  - Absolute http(s) URLs (CDN, Instagram) are returned untouched.
 *  - `images/...` paths are assets bundled in public/ (seed data, fallbacks).
 *  - Anything else is an admin upload on the `public` disk, resolved through
 *    Storage so it keeps working if that disk moves to S3 or another driver.
 */
class MediaUrl
{
    public static function for(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk('public')->url(preg_replace('#^storage/#', '', $path));
    }
}
