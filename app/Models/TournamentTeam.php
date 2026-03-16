<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class TournamentTeam extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'score' => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function avatar()
    {
        return $this->belongsTo(PlayerAvatar::class, 'avatar_id');
    }

    public function matchesAsTeam1()
    {
        return $this->hasMany(TournamentMatch::class, 'team1_id');
    }

    public function matchesAsTeam2()
    {
        return $this->hasMany(TournamentMatch::class, 'team2_id');
    }

    public function matchesAsWinner()
    {
        return $this->hasMany(TournamentMatch::class, 'winner_id');
    }
}
