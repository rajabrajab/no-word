<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_id' => 'required|exists:questions,id',
        ];
    }
}
