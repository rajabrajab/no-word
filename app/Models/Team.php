<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'score' => 'integer',
        'players_number' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
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

    public function isGameTeam(): bool
    {
        return $this->game_id !== null;
    }

    public function isTournamentTeam(): bool
    {
        return $this->tournament_id !== null;
    }
}

