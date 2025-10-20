<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends BaseModel
{
    use SoftDeletes;

    public function categories(){
        return  $this->hasMany(Category::class);
    }

}
