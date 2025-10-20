<?php

namespace App\Models;

class Package extends BaseModel
{
    protected $casts = [
        'price' => 'decimal:2',
        'games_count' => 'decimal:2',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }
}
