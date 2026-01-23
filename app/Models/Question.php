<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Question extends BaseModel
{
    use SoftDeletes;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_questions');
    }
}
