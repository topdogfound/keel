<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Teams are identified publicly by their unique slug, which the
            // product already uses in URLs. Never the primary key: sequential
            // ids in a multi-tenant API leak growth metrics and invite
            // enumeration. Models created by make:tenant-model get a ULID
            // instead, since they have no natural public key.
            'id' => $this->slug,
            'name' => $this->name,
            'is_personal' => $this->is_personal,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
