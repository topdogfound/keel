<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * A stand-in tenant-owned model, so the isolation guarantees can be tested
 * without the template shipping an invented domain model.
 */
class TenantRecord extends Model
{
    use BelongsToTeam, HasPublicId;

    protected $table = 'tenant_records';

    protected $guarded = [];
}
