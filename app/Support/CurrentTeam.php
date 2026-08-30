<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the team the current request is acting within.
 *
 * Deliberately overridable so console commands, queued jobs and the staff panel
 * can set the context explicitly rather than depending on an authenticated user.
 */
final class CurrentTeam
{
    private static ?int $override = null;

    private static bool $overridden = false;

    public static function id(): ?int
    {
        if (self::$overridden) {
            return self::$override;
        }

        $user = Auth::user();

        return $user?->current_team_id;
    }

    public static function set(Team|int|null $team): void
    {
        self::$override = $team instanceof Team ? $team->id : $team;
        self::$overridden = true;
    }

    /**
     * Run a callback with the team context forced to $team.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function pretend(Team|int|null $team, callable $callback): mixed
    {
        $hadOverride = self::$overridden;
        $previous = self::$override;

        self::set($team);

        try {
            return $callback();
        } finally {
            self::$override = $previous;
            self::$overridden = $hadOverride;
        }
    }

    public static function forget(): void
    {
        self::$override = null;
        self::$overridden = false;
    }
}
