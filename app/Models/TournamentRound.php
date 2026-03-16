<?php

namespace App\Models;

class TournamentRound extends BaseModel
{
    protected $casts = [
        'round_number' => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class, 'round_id');
    }
}
