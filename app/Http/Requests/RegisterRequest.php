<?php

namespace App\Http\Requests;

use App\Helpers\PhoneHelper;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|string|min:8',
            'country_code' => 'nullable|string|max:5',
            'iso_code' => 'nullable|string|max:5',
            'number' => ['required', function ($attribute, $value, $fail) {
                $normalized = PhoneHelper::normalize($value);
                if (!$normalized) {
                    $fail('The phone number is invalid or not supported.');
                }
            }],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (!empty($data['number'])) {
            $data['normalized'] = PhoneHelper::normalize($data['number']);
        }

        if (!empty($data['profile_image'])) {
            $data['profile_image'] = $data['profile_image']->store('profile_images', 'public');
        }

        return $data;
    }
}
