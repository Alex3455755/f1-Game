<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Circuit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'country', 'city', 'lap_count', 'lap_length', 'circuit_type',
        'downforce_requirement', 'tire_wear_rate', 'fuel_consumption', 'overtaking_difficulty',
    ];
}
