<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rounds = $this->whenLoaded('rounds')
            ? $this->rounds->sortBy('round_number')
            : $this->rounds()->orderBy('round_number')->get();

        return [
            'id' => $this->id,
            'size' => $this->size,
            'is_completed' => $this->is_completed,
            'completion_percentage' => (float) $this->completion_percentage,
            'current_round' => $this->current_round,
            'champion' => $this->when($this->champion_id, function () {
                return new TournamentTeamResource($this->whenLoaded('champion') ? $this->champion : $this->champion()->with('avatar')->first());
            }),
            'teams' => TournamentTeamResource::collection(
                $this->whenLoaded('teams')
                    ? $this->teams
                    : $this->teams()->with('avatar')->get()
            ),
            'rounds' => $rounds->map(function ($round) {
                $matches = $round->relationLoaded('matches')
                    ? $round->matches->sortBy('position')
                    : $round->matches()->orderBy('position')->get();

                return [
                    'round_number' => $round->round_number,
                    'name' => $round->name,
                    'matches' => TournamentMatchResource::collection($matches),
                ];
            })->values(),
        ];
    }
}
