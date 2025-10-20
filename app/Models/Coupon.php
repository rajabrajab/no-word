<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'discount_value' => 'float'
    ];

   public function calculateDiscount($originalPrice)
    {
        return $this->discount_type === 'percentage'
            ? round($originalPrice * $this->discount_value / 100, 1)
            : round($this->discount_value, 1);
    }
}
