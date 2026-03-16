<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTournamentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'size' => 'required|in:4,8,16',
            'teams' => 'required|array',
            'teams.*.name' => 'required|string|max:255',
            'teams.*.avatar_id' => 'nullable|exists:player_avatars,id',
            'teams.*.players_number' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $size = $this->input('size');
            $teams = $this->input('teams', []);

            if (count($teams) !== (int) $size) {
                $validator->errors()->add(
                    'teams',
                    "The number of teams must equal the tournament size ({$size})."
                );
            }
        });
    }
}
