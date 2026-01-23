<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends BaseModel
{
    use SoftDeletes;

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'game_categories');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'game_questions');
    }
}

