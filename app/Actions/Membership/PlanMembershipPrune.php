<?php

declare(strict_types=1);

namespace App\Actions\Membership;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\QueueableAction\QueueableAction;

final class PlanMembershipPrune
{
    use QueueableAction;

    /**
     * Returns the most-recently-added non-owner members to detach to reach $newCap.
     * Returns empty when the team is already at-or-under cap. Excludes the team owner.
     *
     * @return Collection<int, User>
     */
    public function execute(Team $team, int $newCap): Collection
    {
        /** @var Collection<int, int> $nonOwnerIds */
        $nonOwnerIds = DB::table('model_has_roles')
            ->select('model_id')
            ->selectRaw('MAX(created_at) as joined_at')
            ->where('model_type', (new User)->getMorphClass())
            ->where('team_id', $team->id)
            ->where('model_id', '!=', $team->owner_id)
            ->groupBy('model_id')
            ->orderByDesc('joined_at')
            ->pluck('model_id');

        $toRemoveCount = max(0, $nonOwnerIds->count() - $newCap);

        if ($toRemoveCount === 0) {
            /** @mago-expect analysis:less-specific-return-statement */
            return collect();
        }

        /** @var list<int> $idsToRemove */
        $idsToRemove = $nonOwnerIds->take($toRemoveCount)->values()->all();

        // Fetch users then reorder to match the most-recently-added-first ordering from the query.
        $indexMap = array_flip($idsToRemove);

        /** @mago-expect analysis:less-specific-return-statement */
        return User::query()
            ->findMany($idsToRemove)
            ->sortBy(fn (User $user): int => $indexMap[$user->id] ?? PHP_INT_MAX)
            ->values();
    }
}
