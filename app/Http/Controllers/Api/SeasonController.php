<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Circuit;
use App\Models\RaceWeekend;
use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        return response()->json(
            Season::with('playerTeam')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate(['team_id' => 'required|exists:teams,id']);

        // End any active season
        Season::where('status', 'active')->update(['status' => 'completed']);

        $season = Season::create([
            'year'           => now()->year,
            'player_team_id' => $request->team_id,
            'status'         => 'active',
            'current_round'  => 1,
        ]);

        // Create race calendar
        $circuits   = Circuit::inRandomOrder()->get();
        $raceNames  = [
            'Гран-При Бахрейна', 'Гран-При Саудовской Аравии', 'Гран-При Австралии',
            'Гран-При Японии', 'Гран-При Китая', 'Гран-При Майами',
            'Гран-При Эмилии-Романьи', 'Гран-При Монако', 'Гран-При Канады',
            'Гран-При Испании', 'Гран-При Австрии', 'Гран-При Великобритании',
            'Гран-При Венгрии', 'Гран-При Бельгии', 'Гран-При Нидерландов',
            'Гран-При Италии', 'Гран-При Азербайджана', 'Гран-При Сингапура',
            'Гран-При США', 'Гран-При Мексики', 'Гран-При Бразилии',
            'Гран-При Лас-Вегаса', 'Гран-При Катара', 'Гран-При Абу-Даби',
        ];

        $weathers   = ['sunny', 'sunny', 'sunny', 'cloudy', 'cloudy', 'rain'];
        $startDate  = now()->startOfYear()->addMonths(2);

        foreach ($circuits as $i => $circuit) {
            if ($i >= count($raceNames)) break;
            RaceWeekend::create([
                'season_id'    => $season->id,
                'circuit_id'   => $circuit->id,
                'round_number' => $i + 1,
                'race_name'    => $raceNames[$i],
                'race_date'    => $startDate->copy()->addWeeks($i * 2),
                'weather'      => $weathers[array_rand($weathers)],
                'status'       => $i === 0 ? 'setup' : 'upcoming',
            ]);
        }

        return response()->json($season->load('playerTeam', 'raceWeekends.circuit'), 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Season::with(['playerTeam.drivers', 'raceWeekends.circuit'])->findOrFail($id)
        );
    }

    public function current()
    {
        $season = Season::with(['playerTeam.drivers', 'raceWeekends.circuit'])
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$season) {
            return response()->json(null);
        }

        return response()->json($season);
    }

    public function standings(string $id)
    {
        $season = Season::findOrFail($id);

        $results = \App\Models\RaceResult::with('driver.team')
            ->whereHas('raceWeekend', fn($q) => $q->where('season_id', $id))
            ->where('status', 'finished')
            ->get()
            ->groupBy('driver_id')
            ->map(function ($driverResults) {
                $driver = $driverResults->first()->driver;
                return [
                    'driver'          => $driver,
                    'team'            => $driver->team,
                    'points'          => $driverResults->sum('points_scored'),
                    'wins'            => $driverResults->where('finish_position', 1)->count(),
                    'podiums'         => $driverResults->where('finish_position', '<=', 3)->count(),
                    'fastest_laps'    => $driverResults->where('fastest_lap', true)->count(),
                ];
            })
            ->sortByDesc('points')
            ->values();

        return response()->json($results);
    }
}
