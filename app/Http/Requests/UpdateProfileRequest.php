<?php

namespace App\Http\Requests;

use App\Helpers\PhoneHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'country_code' => 'sometimes|string|max:5',
            'iso_code' => 'sometimes|string|max:5',

            'number' => ['sometimes', function ($attribute, $value, $fail) {
                $normalized = PhoneHelper::normalize($value);
                if (!$normalized) {
                    $fail('The phone number is invalid or not supported.');
                }
            }],
        ];
    }
}
