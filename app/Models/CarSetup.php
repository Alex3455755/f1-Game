<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_weekend_id', 'driver_id',
        'front_wing', 'rear_wing', 'suspension_stiffness', 'ride_height',
        'brake_bias', 'tire_pressure_front', 'tire_pressure_rear', 'differential',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function getSetupRatingAttribute(): int
    {
        return (int) round(
            ($this->front_wing / 11 + $this->rear_wing / 11) * 30 +
            ($this->suspension_stiffness / 10) * 20 +
            (abs($this->brake_bias - 55) / 10) * -5 + 50
        );
    }
}
