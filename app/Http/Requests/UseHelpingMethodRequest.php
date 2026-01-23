<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseHelpingMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'helping_method_id' => 'required|exists:helping_methods,id',
        ];
    }
}

