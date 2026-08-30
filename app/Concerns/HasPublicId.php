<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gives a model a ULID public identifier used in URLs and API payloads, while
 * keeping a bigint primary key for join performance and index locality.
 *
 * Sequential ids in a multi-tenant product leak growth metrics and invite
 * enumeration; ULIDs avoid the index fragmentation that random UUIDs cause.
 * Retrofitting this once URLs exist in the wild is close to impossible, so it
 * belongs on models from the start.
 *
 * @phpstan-require-extends Model
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * Bind route parameters by public id rather than primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
