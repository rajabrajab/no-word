<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends BaseModel
{
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
        return $this->belongsToMany(Question::class, 'game_questions')
            ->withPivot('is_answered');
    }
}

