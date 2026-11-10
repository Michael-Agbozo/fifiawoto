<?php

namespace App\Models;

use App\Support\Audit;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        $row = self::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    /**
     * Settings whose changes go in the audit log, with the label used there.
     * Anything else (e.g. the encrypted Instagram token) is never logged.
     *
     * @var array<string, string>
     */
    public const AUDITED_KEYS = [
        'site_settings' => 'site settings',
        'site_popup' => 'website popup',
    ];

    public static function write(string $key, mixed $value): void
    {
        $before = isset(self::AUDITED_KEYS[$key]) ? self::read($key) : null;

        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if (isset(self::AUDITED_KEYS[$key])) {
            self::recordChange($key, (array) $before, (array) $value);
        }
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected static function recordChange(string $key, array $before, array $after): void
    {
        $changed = array_values(array_filter(
            array_unique([...array_keys($before), ...array_keys($after)]),
            fn (string|int $field): bool => ($before[$field] ?? null) != ($after[$field] ?? null),
        ));

        if ($changed === []) {
            return;
        }

        $label = self::AUDITED_KEYS[$key];

        Audit::record("settings.{$key}_updated", "Changed the {$label} (".implode(', ', $changed).').', properties: [
            'fields' => $changed,
            'before' => array_intersect_key($before, array_flip($changed)),
            'after' => array_intersect_key($after, array_flip($changed)),
        ]);
    }
}
