<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaceResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_weekend_id', 'driver_id', 'grid_position', 'finish_position',
        'status', 'tire_compound', 'current_lap', 'pit_stop_count',
        'gap_to_leader', 'points_scored', 'fastest_lap', 'last_pit_lap',
    ];

    protected $casts = [
        'fastest_lap' => 'boolean',
        'gap_to_leader' => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class)->with('team');
    }

    public function raceWeekend()
    {
        return $this->belongsTo(RaceWeekend::class);
    }
}
