<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $package = $this->package;

        $isActive = $this->status === 'active' && $this->games_remaining > 0;

        return [
            'id' => $this->id,
            'games_remaining' => $this->games_remaining,
            'subscribed_at' => $this->created_at,
            'is_active' => $isActive,
            'package' => $package ? [
                'id' => $package->id,
                'name' => $package->name,
                'image' => $package->image ? asset('storage/' . $package->image) : null,
                'games_count' => $package->games_count,
                'price' => $package->price,
            ] : null,
        ];
    }
}
