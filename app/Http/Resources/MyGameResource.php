<?php

namespace App\Http\Resources;

use App\Models\HelpingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allHelpingMethods = HelpingMethod::orderBy('id')->limit(3)->get();

        $teams = $this->teams->map(function ($team) use ($allHelpingMethods) {
            $usedHelpingMethods = $team->usedHelpingMethods->keyBy('id');

            $helpingMethods = $allHelpingMethods->map(function ($helpingMethod) use ($usedHelpingMethods) {
                return [
                    'id' => $helpingMethod->id,
                    'icon' => $helpingMethod->icon ? asset('storage/' . $helpingMethod->icon) : null,
                    'name' => $helpingMethod->name,
                    'is_used' => $usedHelpingMethods->has($helpingMethod->id),
                ];
            });

            return [
                'id' => $team->id,
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
                        'question' => $question->question,
                        'answer' => $question->answer,
                        'hint' => $question->hint,
                        'media' => $question->media ? asset('storage/' . $question->media) : null,
                        'media_type' => $question->media_type,
                    ];
                })->values(),
            ];
        })->filter()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'playing_times' => $this->playing_times,
            'teams' => $teams->values(),
            'categories' => $categories,
        ];
    }
}

