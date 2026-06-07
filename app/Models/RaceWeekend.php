<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaceWeekend extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id', 'circuit_id', 'round_number', 'race_name',
        'race_date', 'weather', 'status', 'is_sprint',
    ];

    protected $casts = [
        'race_date' => 'date',
        'is_sprint' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function circuit()
    {
        return $this->belongsTo(Circuit::class);
    }

    public function raceResults()
    {
        return $this->hasMany(RaceResult::class)->orderBy('finish_position');
    }

    public function qualifyingResults()
    {
        return $this->hasMany(QualifyingResult::class)->orderBy('position');
    }

    public function carSetups()
    {
        return $this->hasMany(CarSetup::class);
    }

    public function pitStops()
    {
        return $this->hasMany(PitStop::class);
    }

    public function radioMessages()
    {
        return $this->hasMany(RadioMessage::class)->orderBy('created_at');
    }
}
