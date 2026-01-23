<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends BaseModel
{
    use SoftDeletes;

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function avatar()
    {
        return $this->belongsTo(PlayerAvatar::class, 'avatar_id');
    }

    public function usedHelpingMethods()
    {
        return $this->belongsToMany(HelpingMethod::class, 'team_helping_methods')
            ->withTimestamps();
    }
}

