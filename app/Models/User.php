<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StaffRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * There is no password column: every account signs in with an emailed
 * one-time code, or by linking a Google/GitHub identity. See
 * App\Actions\Auth\* and App\Http\Controllers\Auth\*.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Gender|null $gender
 * @property Carbon|null $email_verified_at
 * @property string|null $google_id
 * @property string|null $github_id
 * @property string|null $avatar
 * @property string|null $avatar_path
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $avatar_url
 * @property-read bool $google_linked
 * @property-read bool $github_linked
 */
#[Fillable(['name', 'phone', 'gender', 'avatar_path'])]
#[Hidden(['remember_token', 'google_id', 'github_id'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Appended to every array/JSON representation — including the `auth.user`
     * shared with the frontend — since the raw provider ids and file path
     * aren't what the UI needs.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_url', 'google_linked', 'github_linked'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'gender' => Gender::class,
        ];
    }

    /**
     * The URL to show as this user's avatar: a locally uploaded file takes
     * precedence over one pulled from a social provider.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path !== null
            ? Storage::disk('public')->url($this->avatar_path)
            : $this->avatar;
    }

    public function getGoogleLinkedAttribute(): bool
    {
        return $this->google_id !== null;
    }

    public function getGithubLinkedAttribute(): bool
    {
        return $this->github_id !== null;
    }

    /**
     * There is no password column. Overridden because the base
     * Authenticatable trait reads `$this->password` — which strict-attribute
     * mode turns into a hard error rather than null — and Filament's
     * AuthenticateSession middleware calls this on every admin-panel request.
     */
    public function getAuthPassword(): string
    {
        return '';
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
     * Whether the user holds any staff role.
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
        return $this->roles()->pluck('name');
    }

    /**
     * Audit identity changes. Credentials are never logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
