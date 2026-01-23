<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Builders\BaseBuilder;

class Category extends BaseModel
{
    use SoftDeletes;

    public function newEloquentBuilder($query): BaseBuilder
    {
        return new BaseBuilder($query);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function scopeByCountry($query, $countryId = null)
    {
        return $query->when($countryId, function ($q) use ($countryId) {
            $q->where('country_id', $countryId);
        });
    }

}
