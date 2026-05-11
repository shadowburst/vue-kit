<?php

declare(strict_types=1);

namespace App\Http\Resources\Team;

use App\Http\Resources\User\UserResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

/**
 * @mixin Team
 */
#[TypeScript]
#[TypeScriptType([
    'id'       => 'number',
    'name'     => 'string',
    'slug'     => 'string',
    'tier'     => 'App.Enums.Subscription.SubscriptionTier',
    'features' => 'Record<string, unknown>',
])]
class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'tier'        => $this->tier(),
            'features'    => $this->features,
            'owner'       => $this->whenLoaded('owner', fn () => UserResource::make($this->owner)),
            'memberships' => $this->whenLoaded(
                'members',
                fn () => UserResource::collection($this->members),
            ),
        ];
    }
}
