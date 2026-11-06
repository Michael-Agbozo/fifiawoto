<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Once;

/**
 * Website details staff can edit from the admin. Anything not yet saved falls
 * back to the defaults below, so the site looks right on a fresh install.
 */
class SiteSettings
{
    public const SETTING_KEY = 'site_settings';

    /** @return array<string, string|int> */
    public static function defaults(): array
    {
        return [
            'summary' => 'The Fifiawoto Foundation works to empower communities through compassion, service, and sustainable development initiatives across the United States, Ghana, Togo, and Benin.',
            'contact_email' => (string) config('notifications.admin_email'),
            'media_email' => 'media@fifiawoto.org',
            'phone' => '+233 (0) 00 000 0000',
            'phone_hours' => 'Monday to Friday, 9 am – 5 pm GMT.',
            'mobile_money_number' => '',
            'instagram_url' => config('social.instagram') ?: 'https://instagram.com',
            'facebook_url' => config('social.facebook') ?: 'https://facebook.com',
            'youtube_url' => config('social.youtube') ?: 'https://youtube.com',
            'hero_text' => 'Transforming lives, one step at a time, through compassion, service and community empowerment across the United States, Ghana, Togo and Benin.',
            'hero_image_main' => 'images/foundation/school-donation.jpg',
            'hero_image_left' => 'images/foundation/outreach-2.webp',
            'hero_image_right' => 'images/foundation/outreach-3.webp',
            'baseline_children' => 520,
            'baseline_communities' => 18,
            'baseline_countries' => 4,
            'baseline_volunteer_hours' => 0,
        ];
    }

    /** @return array<string, string|int> */
    public static function all(): array
    {
        return once(fn () => array_merge(
            self::defaults(),
            array_intersect_key((array) Setting::read(self::SETTING_KEY, []), self::defaults()),
        ));
    }

    public static function get(string $key): string|int|null
    {
        return self::all()[$key] ?? null;
    }

    /** @param array<string, string|int> $values */
    public static function save(array $values): void
    {
        Setting::write(self::SETTING_KEY, array_intersect_key($values, self::defaults()));
        Once::flush();
    }

    /** The phone number as a tel: link, without display formatting. */
    public static function phoneHref(): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', str_replace('(0)', '', (string) self::get('phone')));
    }
}
