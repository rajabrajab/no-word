<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get subscribed_package_id from request
        $subscribedPackageId = $request->get('subscribed_package_id');
        $isSubscribed = $subscribedPackageId === $this->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'games_count' => $this->games_count,
            'price' => $this->price,
            'is_subscribed' => $isSubscribed,
        ];
    }
}
