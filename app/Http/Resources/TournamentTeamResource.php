<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->whenLoaded('avatar') && $this->avatar && $this->avatar->avatar_path
                ? asset('storage/' . $this->avatar->avatar_path)
                : null,
            'avatar_id' => $this->avatar_id,
            'score' => $this->score,
        ];
    }
}
