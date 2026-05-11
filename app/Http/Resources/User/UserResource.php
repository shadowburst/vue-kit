<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Http\Resources\Team\TeamResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

/**
 * @mixin User
 */
#[TypeScript]
#[TypeScriptType([
    'id'                => 'number',
    'name'              => 'string',
    'email'             => 'string',
    'email_verified_at' => 'string | null',
    'current_team_id'   => 'number | null',
    'permissions'       => 'string[]',
    'is_owner'          => 'boolean | null',
])]
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'current_team_id'   => $this->current_team_id,
            'permissions'       => $this->getAllPermissions()->pluck('name')->sort()->values()->all(),
            'is_owner'          => $this->whenLoaded(
                'currentTeam',
                fn (): bool => $this->currentTeam?->owner_id === $this->id,
            ),
            'teams'             => $this->whenLoaded('teams', fn () => TeamResource::collection($this->teams)),
        ];
    }
}
