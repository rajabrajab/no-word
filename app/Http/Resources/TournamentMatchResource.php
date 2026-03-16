<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'position' => $this->position,
            'status' => $this->status,
            'team1' => $this->when($this->team1_id, function () {
                return new TournamentTeamResource($this->whenLoaded('team1') ? $this->team1 : $this->team1()->with('avatar')->first());
            }),
            'team2' => $this->when($this->team2_id, function () {
                return new TournamentTeamResource($this->whenLoaded('team2') ? $this->team2 : $this->team2()->with('avatar')->first());
            }),
            'winner' => $this->when($this->winner_id, function () {
                return new TournamentTeamResource($this->whenLoaded('winner') ? $this->winner : $this->winner()->with('avatar')->first());
            }),
        ];
    }
}
