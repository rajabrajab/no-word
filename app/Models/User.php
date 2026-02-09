<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TomatoPHP\FilamentLanguageSwitcher\Traits\InteractsWithLanguages;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, InteractsWithLanguages, SoftDeletes, HasApiTokens;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function phone()
    {
        return [
            'number' => $this->number,
            'country_code' => $this->country_code,
            'iso_code' => $this->iso_code,
        ];
    }

    public function profileImage()
    {
        if (!$this->profile_image) {


            return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
        }

        return asset('storage/'.$this->profile_image);
    }

    public function subscription()
    {
        return $this->hasOne(UserSubscription::class);
    }

    public function activeSubscription()
    {
        $subscription = $this->subscription;
        return ($subscription && $subscription->status === 'active') ? $subscription : null;
    }

    public function hasRemainingGames(): bool
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return false;
        }

        return $subscription->games_remaining > 0;
    }

    public function getRemainingGamesCount(): int
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return 0;
        }

        return $subscription->games_remaining;
    }

    public function decrementGamesRemaining(): bool
    {
        $subscription = $this->activeSubscription();

        if (!$subscription || $subscription->games_remaining <= 0) {
            return false;
        }

        $subscription->decrement('games_remaining');

        return true;
    }
}
