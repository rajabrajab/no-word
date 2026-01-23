<?php

namespace App\Http\Resources;

use App\Models\HelpingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameBoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allHelpingMethods = HelpingMethod::orderBy('id')->limit(3)->get();

        $teams = $this->teams->map(function ($team) use ($allHelpingMethods) {
            $usedHelpingMethods = $team->usedHelpingMethods->keyBy('id');

            $helpingMethods = $allHelpingMethods->map(function ($helpingMethod) use ($usedHelpingMethods) {
                return [
                    'icon' => $helpingMethod->icon ? asset('storage/' . $helpingMethod->icon) : null,
                    'name' => $helpingMethod->name,
                    'is_used' => $usedHelpingMethods->has($helpingMethod->id),
                ];
            });

            return [
                'team_name' => $team->name,
                'score' => $team->score,
                'helping_methods' => $helpingMethods,
            ];
        });

        $questionsByCategory = $this->questions->groupBy('category_id');

        $categories = $questionsByCategory->map(function ($categoryQuestions, $categoryId) {
            $category = $categoryQuestions->first()->category;

            if (!$category) {
                return null;
            }

            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'questions' => $categoryQuestions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'score' => $question->score,
                    ];
                })->values(),
            ];
        })->filter()->values();

        return [
            'teams' => $teams->values(),
            'categories' => $categories,
        ];
    }
}

