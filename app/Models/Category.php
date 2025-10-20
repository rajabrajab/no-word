<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends BaseModel
{
    use SoftDeletes;

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
