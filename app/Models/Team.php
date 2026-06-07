<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'short_name', 'color', 'logo', 'base_country',
        'overall_rating', 'engine_rating', 'aero_rating', 'reliability_rating', 'budget',
    ];

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
}
