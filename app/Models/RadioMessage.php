<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadioMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_weekend_id', 'driver_id', 'lap',
        'direction', 'message_type', 'message', 'is_player_sent',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
