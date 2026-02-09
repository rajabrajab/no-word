<?php

namespace App\Models;

class Package extends BaseModel
{
    protected $casts = [
        'price' => 'integer',
        'games_count' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }
}
