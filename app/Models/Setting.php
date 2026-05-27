<?php

namespace App\Models;

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

    public static function write(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
