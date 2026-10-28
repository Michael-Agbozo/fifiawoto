<?php

namespace App\Support;

/**
 * Neutralises spreadsheet formula injection in CSV exports.
 *
 * Any cell beginning with =, +, -, @, tab or carriage return is
 * prefixed with a single quote so Excel / Sheets treat it as text.
 */
class CsvCell
{
    public static function escape(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    public static function row(array $row): array
    {
        return array_map(self::escape(...), $row);
    }
}
