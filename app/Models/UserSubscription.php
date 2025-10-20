<?php

namespace App\Models;

class UserSubscription extends BaseModel
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    protected static function booted()
    {
        static::created(function (UserSubscription $sub) {
            $sub->package()->increment('subscription_count');
        });

        static::deleted(function (UserSubscription $sub) {
            if ($sub->package_id) {
                Package::whereKey($sub->package_id)->decrement('subscription_count');
            }
        });

        static::updating(function (UserSubscription $sub) {
            if ($sub->isDirty('package_id')) {
                $original = $sub->getOriginal('package_id');
                if ($original) {
                    Package::whereKey($original)->decrement('subscription_count');
                }
            }
        });

        static::updated(function (UserSubscription $sub) {
            if ($sub->wasChanged('package_id')) {
                $sub->package()->increment('subscription_count');
            }
        });
    }
}
