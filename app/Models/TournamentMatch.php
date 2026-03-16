<?php

namespace App\Models;

class TournamentMatch extends BaseModel
{
    protected $casts = [
        'position' => 'integer',
        'team1_id' => 'integer',
        'team2_id' => 'integer',
        'winner_id' => 'integer',
        'game_id' => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function round()
    {
        return $this->belongsTo(TournamentRound::class, 'round_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function team1()
    {
        return $this->belongsTo(TournamentTeam::class, 'team1_id');
    }

    public function team2()
    {
        return $this->belongsTo(TournamentTeam::class, 'team2_id');
    }

    public function winner()
    {
        return $this->belongsTo(TournamentTeam::class, 'winner_id');
    }
}
