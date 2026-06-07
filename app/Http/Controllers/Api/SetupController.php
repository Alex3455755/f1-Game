<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarSetup;
use App\Models\Driver;
use App\Models\RaceWeekend;
use App\Models\Season;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function show(string $weekendId)
    {
        $weekend = RaceWeekend::with('circuit')->findOrFail($weekendId);
        $season  = Season::where('status', 'active')->latest()->first();

        if (!$season) {
            return response()->json(['error' => 'No active season'], 404);
        }

        $drivers = Driver::where('team_id', $season->player_team_id)->get();
        $setups  = [];

        foreach ($drivers as $driver) {
            $setup = CarSetup::firstOrCreate(
                ['race_weekend_id' => $weekendId, 'driver_id' => $driver->id],
                [
                    'front_wing'           => 6,
                    'rear_wing'            => 6,
                    'suspension_stiffness' => 5,
                    'ride_height'          => 5,
                    'brake_bias'           => 55,
                    'tire_pressure_front'  => 23.0,
                    'tire_pressure_rear'   => 21.5,
                    'differential'         => 50,
                ]
            );
            $setups[] = array_merge($setup->toArray(), ['driver' => $driver]);
        }

        return response()->json([
            'weekend' => $weekend,
            'setups'  => $setups,
        ]);
    }

    public function update(Request $request, string $weekendId, string $driverId)
    {
        $request->validate([
            'front_wing'           => 'integer|min:1|max:11',
            'rear_wing'            => 'integer|min:1|max:11',
            'suspension_stiffness' => 'integer|min:1|max:10',
            'ride_height'          => 'integer|min:1|max:10',
            'brake_bias'           => 'integer|min:45|max:65',
            'tire_pressure_front'  => 'numeric|min:20|max:26',
            'tire_pressure_rear'   => 'numeric|min:19|max:25',
            'differential'         => 'integer|min:30|max:70',
        ]);

        $setup = CarSetup::updateOrCreate(
            ['race_weekend_id' => $weekendId, 'driver_id' => $driverId],
            $request->only([
                'front_wing', 'rear_wing', 'suspension_stiffness', 'ride_height',
                'brake_bias', 'tire_pressure_front', 'tire_pressure_rear', 'differential',
            ])
        );

        return response()->json($setup);
    }

    public function hint(string $weekendId, string $driverId)
    {
        $weekend = RaceWeekend::with('circuit')->findOrFail($weekendId);
        $circuit = $weekend->circuit;

        $hints = [];

        // Downforce hint
        $df = $circuit->downforce_requirement;
        if ($df >= 70) {
            $hints[] = ['type' => 'wings', 'text' => 'Высокая прижимная сила нужна — ставь крылья 9-10.'];
        } elseif ($df <= 30) {
            $hints[] = ['type' => 'wings', 'text' => 'Трасса скоростная — минимальные крылья 3-5 для скорости.'];
        } else {
            $hints[] = ['type' => 'wings', 'text' => 'Средняя прижимная сила — крылья 6-7 оптимально.'];
        }

        // Tire wear hint
        if ($circuit->tire_wear_rate >= 70) {
            $hints[] = ['type' => 'tires', 'text' => 'Высокий износ резины — рассмотри стратегию Medium-Hard.'];
        } else {
            $hints[] = ['type' => 'tires', 'text' => 'Мягкий износ — Soft-Medium может принести преимущество.'];
        }

        // Weather hint
        if (in_array($weekend->weather, ['rain', 'heavy_rain'])) {
            $hints[] = ['type' => 'weather', 'text' => 'Дождь! Увеличь дорожный просвет и смягчи подвеску.'];
        }

        return response()->json($hints);
    }
}
