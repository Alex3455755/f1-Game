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

        // Race name derived directly from each circuit's data
        $circuitGPNames = [
            'Bahrain International Circuit'    => 'Гран-При Бахрейна',
            'Jeddah Corniche Circuit'          => 'Гран-При Саудовской Аравии',
            'Albert Park Circuit'              => 'Гран-При Австралии',
            'Suzuka Circuit'                   => 'Гран-При Японии',
            'Shanghai International Circuit'   => 'Гран-При Китая',
            'Miami International Autodrome'    => 'Гран-При Майами',
            'Autodromo Enzo e Dino Ferrari'    => 'Гран-При Эмилии-Романьи',
            'Circuit de Monaco'                => 'Гран-При Монако',
            'Circuit Gilles Villeneuve'        => 'Гран-При Канады',
            'Circuit de Barcelona-Catalunya'   => 'Гран-При Испании',
            'Red Bull Ring'                    => 'Гран-При Австрии',
            'Silverstone Circuit'              => 'Гран-При Великобритании',
            'Hungaroring'                      => 'Гран-При Венгрии',
            'Circuit de Spa-Francorchamps'     => 'Гран-При Бельгии',
            'Circuit Zandvoort'                => 'Гран-При Нидерландов',
            'Autodromo Nazionale Monza'        => 'Гран-При Италии',
            'Baku City Circuit'                => 'Гран-При Азербайджана',
            'Marina Bay Street Circuit'        => 'Гран-При Сингапура',
            'Circuit of the Americas'          => 'Гран-При США (Остин)',
            'Autodromo Hermanos Rodriguez'     => 'Гран-При Мексики',
            'Autodromo Jose Carlos Pace'       => 'Гран-При Бразилии',
            'Las Vegas Strip Circuit'          => 'Гран-При Лас-Вегаса',
            'Losail International Circuit'     => 'Гран-При Катара',
            'Yas Marina Circuit'               => 'Гран-При Абу-Даби',
        ];

        // Typical weather per circuit city (rough approximation)
        $circuitWeather = [
            'Bahrain International Circuit'    => ['sunny','sunny','sunny','cloudy'],
            'Jeddah Corniche Circuit'          => ['sunny','sunny','sunny'],
            'Albert Park Circuit'              => ['sunny','cloudy','cloudy','rain'],
            'Suzuka Circuit'                   => ['sunny','cloudy','rain','rain'],
            'Miami International Autodrome'    => ['sunny','sunny','sunny','rain'],
            'Autodromo Enzo e Dino Ferrari'    => ['cloudy','sunny','rain'],
            'Circuit de Monaco'                => ['sunny','cloudy','rain'],
            'Circuit de Spa-Francorchamps'     => ['cloudy','rain','rain','heavy_rain'],
            'default'                          => ['sunny','sunny','cloudy','cloudy','rain'],
        ];

        // Get circuits in calendar order (seeded order = real F1 schedule)
        $circuits  = Circuit::orderBy('id')->get();
        $startDate = now()->startOfYear()->addMonths(2);

        foreach ($circuits as $i => $circuit) {
            $raceName = $circuitGPNames[$circuit->name]
                ?? 'Гран-При ' . $circuit->country;

            $weatherPool = $circuitWeather[$circuit->name]
                ?? $circuitWeather['default'];

            RaceWeekend::create([
                'season_id'    => $season->id,
                'circuit_id'   => $circuit->id,
                'round_number' => $i + 1,
                'race_name'    => $raceName,
                'race_date'    => $startDate->copy()->addWeeks($i * 2),
                'weather'      => $weatherPool[array_rand($weatherPool)],
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
