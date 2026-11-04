<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Setting;

/**
 * The public-site popup, configured from Admin → Popup settings and stored as one
 * JSON row (`site.popup`) in the settings table.
 */
class SitePopup
{
    public const SETTING_KEY = 'site.popup';

    public const TYPES = [
        'newsletter' => 'Newsletter signup',
        'donate' => 'Donate',
        'volunteer' => 'Volunteer',
        'event' => 'Upcoming event',
        'announcement' => 'Announcement',
    ];

    public const TRIGGERS = [
        'delay' => 'After a delay',
        'scroll' => 'Scroll depth',
        'exit_intent' => 'Exit intent',
    ];

    public const FREQUENCIES = [
        'always' => 'Every page load',
        'session' => 'Once per browser session',
        'once' => 'Once ever per browser',
    ];

    public const PAGES = [
        'all' => 'All pages',
        'home' => 'Homepage only',
        'events' => 'Events pages',
        'donate' => 'Donate page',
        'volunteer' => 'Volunteer page',
    ];

    /**
     * @return array{enabled: bool, type: string, trigger: string, trigger_delay_seconds: int, trigger_scroll_percent: int, frequency: string, pages: string, event_id: int|null, announcement_heading: string, announcement_body: string, announcement_cta_label: string, announcement_cta_url: string}
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'type' => 'newsletter',
            'trigger' => 'delay',
            'trigger_delay_seconds' => 5,
            'trigger_scroll_percent' => 40,
            'frequency' => 'session',
            'pages' => 'all',
            'event_id' => null,
            'announcement_heading' => '',
            'announcement_body' => '',
            'announcement_cta_label' => '',
            'announcement_cta_url' => '',
        ];
    }

    /**
     * Stored config merged over the defaults, so newly added keys never come back missing.
     *
     * @return array{enabled: bool, type: string, trigger: string, trigger_delay_seconds: int, trigger_scroll_percent: int, frequency: string, pages: string, event_id: int|null, announcement_heading: string, announcement_body: string, announcement_cta_label: string, announcement_cta_url: string}
     */
    public static function config(): array
    {
        $stored = Setting::read(self::SETTING_KEY, []);

        return array_merge(self::defaults(), is_array($stored) ? array_intersect_key($stored, self::defaults()) : []);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function save(array $config): void
    {
        Setting::write(self::SETTING_KEY, array_merge(self::defaults(), array_intersect_key($config, self::defaults())));
    }

    /**
     * Which PAGES bucket the current request belongs to, or null for pages with no bucket.
     */
    public static function currentPage(): ?string
    {
        return match (true) {
            request()->routeIs('home') => 'home',
            request()->routeIs('events.*') => 'events',
            request()->routeIs('donate') => 'donate',
            request()->routeIs('volunteer') => 'volunteer',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function shouldShow(array $config, ?string $page): bool
    {
        if (! $config['enabled'] || ! array_key_exists($config['type'], self::TYPES)) {
            return false;
        }

        if ($config['type'] === 'event' && ! self::event($config)) {
            return false;
        }

        if ($config['type'] === 'announcement' && blank($config['announcement_heading'])) {
            return false;
        }

        return $config['pages'] === 'all' || $config['pages'] === $page;
    }

    /**
     * The promoted event — only while it is still published and upcoming.
     *
     * @param  array<string, mixed>  $config
     */
    public static function event(array $config): ?Event
    {
        if (blank($config['event_id'])) {
            return null;
        }

        return Event::query()->published()->upcoming()->find($config['event_id']);
    }

    /**
     * Storage key for "already seen". Derived from the config, so editing the popup
     * shows the new version to visitors who dismissed the old one.
     *
     * @param  array<string, mixed>  $config
     */
    public static function dismissalKey(array $config): string
    {
        return 'fifiawoto_popup_'.substr(md5((string) json_encode($config)), 0, 10);
    }
}
