<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'name', 'code', 'number', 'nationality',
        'pace', 'consistency', 'wet_skill', 'overtaking', 'defending', 'tire_management',
        'is_player_driver',
    ];

    protected $casts = [
        'is_player_driver' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function raceResults()
    {
        return $this->hasMany(RaceResult::class);
    }
}
