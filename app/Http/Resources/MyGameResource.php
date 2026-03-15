<?php

namespace App\Http\Resources;

use App\Models\HelpingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                    'description' => $category->description,
                    'image' => $category->image ? asset('storage/' . $category->image) : null,
                    'country' => $category->country ? [
                        'id' => $category->country->id,
                        'name' => $category->country->name,
                        'image' => $category->country->image ? asset('storage/' . $category->country->image) : null,
                    ] : null,
                ]
            ];
        })->filter()->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'playing_times' => $this->playing_times,
            'categories' => $categories,
        ];
    }
}

