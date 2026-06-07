<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualifyingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_weekend_id', 'driver_id', 'position',
        'q1_time', 'q2_time', 'q3_time',
        'q1_eliminated', 'q2_eliminated',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class)->with('team');
    }
}
