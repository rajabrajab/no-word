<?php

namespace App\Http\Resources;

use App\Models\HelpingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allHelpingMethods = HelpingMethod::ordered()->get();

        $usedHelpingMethods = $this->relationLoaded('usedHelpingMethods')
            ? $this->usedHelpingMethods->keyBy('id')
            : $this->usedHelpingMethods()->get()->keyBy('id');

        $helpingMethods = $allHelpingMethods->map(function ($helpingMethod) use ($usedHelpingMethods) {
            $used = $usedHelpingMethods->has($helpingMethod->id);

            return [
                'id' => $helpingMethod->id,
                'key' => $helpingMethod->key,
                'name' => $helpingMethod->name,
                'description' => $helpingMethod->description,
                'icon' => $helpingMethod->icon ? asset('storage/'.$helpingMethod->icon) : null,
                'used' => $used,
                'used_at' => $used ? $usedHelpingMethods->get($helpingMethod->id)->pivot->used_at : null,
            ];
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'players_number' => $this->players_number,
            'score' => $this->score,
            'avatar' => $this->whenLoaded('avatar', function () {
                return new PlayerAvatarResource($this->avatar);
            }),
            'helping_methods' => $helpingMethods,
        ];
    }
}
