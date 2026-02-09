<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'package_id' => 'required|exists:packages,id',
            "coupon_id" => "nullable|string|exists:coupons,id",
        ];
    }
}
