<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the audit trail of who did what to sensitive records.
 *
 * Logging must never get in the way of the action being logged, so any
 * failure is reported and swallowed. Secrets never reach the log: keys that
 * look like passwords, tokens or 2FA material are stripped before saving, and
 * Audit::changes() only copies values for fields the caller lists as safe.
 */
class Audit
{
    /** Property keys whose values are never stored, at any depth. */
    protected const SECRET_KEY_PATTERN = '/password|token|secret|recovery_codes|remember/i';

    /** Attributes that change on every save and say nothing about what was done. */
    protected const IGNORED_FIELDS = ['updated_at', 'created_at'];

    /**
     * Record one entry. Returns null (after reporting the error) if it couldn't be saved.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
    ): ?AuditLog {
        try {
            $actor ??= auth()->user();
            $properties = self::scrub($properties);

            // Keeps the name readable after the account itself is deleted (user_id is then nulled).
            if ($actor instanceof User) {
                $properties['actor_name'] = $actor->name;
            }

            // A savepoint when called inside a transaction, so a failed insert can't poison it.
            return DB::transaction(fn (): AuditLog => AuditLog::query()->create([
                'user_id' => $actor?->getKey(),
                'action' => Str::limit($action, 80, ''),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description, 500),
                'properties' => $properties ?: null,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]));
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Describe the last save of a model: which fields changed and, for the fields
     * listed in $withValues only, what they changed from and to. Everything else
     * (case notes, stories, contact details, file paths) is recorded by name only.
     *
     * @param  array<int, string>  $withValues
     * @return array{fields?: array<int, string>, before?: array<string, mixed>, after?: array<string, mixed>}
     */
    public static function changes(Model $model, array $withValues = []): array
    {
        $after = array_diff_key($model->getChanges(), array_flip(self::IGNORED_FIELDS));

        if ($after === []) {
            return [];
        }

        $before = $model->getPrevious();
        $shown = array_values(array_filter(
            array_keys($after),
            fn (string $field): bool => in_array($field, $withValues, true) && ! preg_match(self::SECRET_KEY_PATTERN, $field),
        ));

        return array_filter([
            'fields' => array_keys($after),
            'before' => array_intersect_key($before, array_flip($shown)),
            'after' => array_intersect_key($after, array_flip($shown)),
        ], fn (array $part): bool => $part !== []);
    }

    /**
     * Drop secret-looking keys (recursively) and stringify anything that isn't plain data.
     *
     * @param  array<array-key, mixed>  $properties
     * @return array<array-key, mixed>
     */
    public static function scrub(array $properties): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET_KEY_PATTERN, $key)) {
                continue;
            }

            $clean[$key] = match (true) {
                is_array($value) => self::scrub($value),
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                is_object($value) => method_exists($value, '__toString') ? (string) $value : $value::class,
                default => $value,
            };
        }

        return $clean;
    }
}
