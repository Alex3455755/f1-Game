<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'player_team_id', 'status', 'current_round'];

    public function playerTeam()
    {
        return $this->belongsTo(Team::class, 'player_team_id');
    }

    public function raceWeekends()
    {
        return $this->hasMany(RaceWeekend::class)->orderBy('round_number');
    }

    public function currentWeekend()
    {
        return $this->raceWeekends()->where('round_number', $this->current_round)->first();
    }
}
