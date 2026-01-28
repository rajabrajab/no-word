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
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, InteractsWithLanguages, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
        return $this->hasMany(UserSubscription::class);
    }
}
