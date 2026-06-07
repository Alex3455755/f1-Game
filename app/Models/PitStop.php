<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_weekend_id', 'driver_id', 'lap',
        'compound_in', 'compound_out', 'duration', 'is_player_decision',
    ];
}
