<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyTournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => (int) $this->size,
            'is_completed' => (bool) $this->is_completed,
            'completion_percentage' => (float) $this->completion_percentage,
            'current_round' => (int) $this->current_round,
        ];
    }
}

