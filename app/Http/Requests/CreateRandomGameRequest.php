<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRandomGameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'team1' => 'required|array',
            'team1.name' => 'required|string|max:255',
            'team1.players_number' => 'required|integer|min:1',
            'team1.avatar_id' => 'nullable|exists:player_avatars,id',

            'team2' => 'required|array',
            'team2.name' => 'required|string|max:255',
            'team2.players_number' => 'required|integer|min:1',
            'team2.avatar_id' => 'nullable|exists:player_avatars,id',

            'countries' => 'required|array|min:1',
            'countries.*' => 'required|exists:countries,id',
        ];
    }
}
