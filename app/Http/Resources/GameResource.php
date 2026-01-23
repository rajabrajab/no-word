<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teams = $this->teams;
        
        return [
            'id' => $this->id,
            'status' => $this->status,
            'team1' => $teams->count() > 0 ? new TeamResource($teams->first()) : null,
            'team2' => $teams->count() > 1 ? new TeamResource($teams->skip(1)->first()) : null,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
        ];
    }
}

