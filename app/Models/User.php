<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Enums\StaffRole;
use App\Support\PermissionScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'email', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasTeams, LogsActivity, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable {
        // Both traits define teams(). The application's meaning — the teams a
        // user belongs to — wins, because the whole product relies on it.
        // Spatie's (the teams a user holds roles in) stays reachable under a
        // non-colliding name.
        HasTeams::teams insteadof HasRoles;
        HasRoles::teams as permissionTeams;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Gate access to the Filament staff panel.
     *
     * Without this every registered customer could reach /admin, so it is
     * deliberately explicit rather than inherited from a default.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff();
    }

    /**
     * Whether the user holds any global staff role.
     *
     * Staff roles live under PermissionScope::GLOBAL, so this check is made in
     * that scope regardless of whichever team the request is currently in.
     */
    public function isStaff(): bool
    {
        return $this->staffRoleNames()->isNotEmpty();
    }

    public function hasStaffRole(StaffRole $role): bool
    {
        return $this->staffRoleNames()->contains($role->value);
    }

    /**
     * @return SupportCollection<int, string>
     */
    public function staffRoleNames(): SupportCollection
    {
        $previous = PermissionScope::current();

        PermissionScope::global();

        try {
            /** @var SupportCollection<int, string> $names */
            $names = $this->roles()->pluck('name');

            return $names;
        } finally {
            app(PermissionRegistrar::class)->setPermissionsTeamId($previous);
        }
    }

    /**
     * Audit identity changes. Credentials are never logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'current_team_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
