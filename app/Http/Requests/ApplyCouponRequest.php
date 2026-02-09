<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'coupon_code' => 'required|string|exists:coupons,code',
            'package_id' => 'required|exists:packages,id',
        ];
    }
}
