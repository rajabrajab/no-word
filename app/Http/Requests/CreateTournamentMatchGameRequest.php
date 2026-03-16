<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTournamentMatchGameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tournament_id' => 'required|integer|exists:tournaments,id',
            'teams' => 'required|array|size:2',
            'teams.*' => 'required|integer|exists:teams,id',
            'categories' => 'required|array|min:1|max:6',
            'categories.*' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'categories.max' => 'Maximum of 6 categories allowed.',
            'teams.size' => 'Exactly 2 teams are required.',
        ];
    }
}
