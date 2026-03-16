<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'size' => 'integer',
        'is_completed' => 'boolean',
        'current_round' => 'integer',
        'completion_percentage' => 'float',
        'champion_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teams()
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function rounds()
    {
        return $this->hasMany(TournamentRound::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function champion()
    {
        return $this->belongsTo(TournamentTeam::class, 'champion_id');
    }
}
