<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSessions
{
    /**
     * Sign a user out everywhere except the given session: their stored sessions are
     * deleted (database driver only) and their "remember me" token is rotated, so a
     * browser holding the old cookie cannot silently sign back in.
     */
    public static function endOthers(User $user, ?string $keepSessionId = null): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->getKey())
            ->when($keepSessionId, fn ($sessions) => $sessions->where('id', '!=', $keepSessionId))
            ->delete();
    }
}
