<?php

namespace App\Http\Resources;

use App\Models\HelpingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameBoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allHelpingMethods = HelpingMethod::ordered()->get();

        $teams = $this->teams->map(function ($team) use ($allHelpingMethods) {
            $usedHelpingMethods = $team->usedHelpingMethods->keyBy('id');

            $helpingMethods = $allHelpingMethods->map(function ($helpingMethod) use ($usedHelpingMethods) {
                return [
                    'id' => $helpingMethod->id,
                    'key' => $helpingMethod->key,
                    'icon' => $helpingMethod->icon ? asset('storage/'.$helpingMethod->icon) : null,
                    'name' => $helpingMethod->name,
                    'is_used' => $usedHelpingMethods->has($helpingMethod->id),
                ];
            });

            return [
                'id' => $team->id,
                'team_name' => $team->name,
                'avatar' => $team->avatar ? asset('storage/'.$team->avatar->avatar_path) : null,
                'score' => $team->score,
                'helping_methods' => $helpingMethods,
            ];
        });

        $questionsByCategory = $this->questions->groupBy('category_id');

        $categories = $questionsByCategory->map(function ($categoryQuestions, $categoryId) {
            $category = $categoryQuestions->first()->category;

            if (! $category) {
                return null;
            }

            return [
                'category' => new CategoryResource($category),
                'questions' => QuestionResource::collection($categoryQuestions),
            ];
        })->filter()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_tournament_game' => (bool) $this->tournament_game,
            'teams' => $teams->values(),
            'categories' => $categories,
        ];
    }
}
