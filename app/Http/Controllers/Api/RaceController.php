<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RaceWeekend;
use App\Models\Season;
use App\Services\RaceSimulator;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    public function __construct(private RaceSimulator $simulator) {}

    public function init(string $weekendId)
    {
        $weekend = RaceWeekend::with(['circuit', 'season'])->findOrFail($weekendId);

        if ($weekend->status !== 'race') {
            return response()->json(['error' => 'Сначала проведите квалификацию'], 422);
        }

        $state = $this->simulator->initRace($weekend);
        return response()->json($state);
    }

    public function lap(Request $request, string $weekendId)
    {
        $request->validate(['lap' => 'required|integer|min:1']);

        $weekend = RaceWeekend::with(['circuit', 'season'])->findOrFail($weekendId);

        if (!in_array($weekend->status, ['race', 'completed'])) {
            return response()->json(['error' => 'Гонка не начата'], 422);
        }

        $playerActions = $request->get('player_actions', []);
        $state         = $this->simulator->simulateLap($weekend, $request->lap, $playerActions);

        return response()->json($state);
    }

    public function radio(Request $request, string $weekendId, string $driverId)
    {
        $request->validate(['type' => 'required|string', 'lap' => 'required|integer']);

        $weekend  = RaceWeekend::findOrFail($weekendId);
        $response = $this->simulator->sendRadioMessage($weekend, $driverId, $request->type, $request->lap);

        return response()->json(['response' => $response]);
    }

    public function pit(Request $request, string $weekendId, string $driverId)
    {
        $request->validate([
            'compound' => 'required|in:soft,medium,hard,intermediate,wet',
            'lap'      => 'required|integer',
        ]);

        $weekend = RaceWeekend::with(['circuit', 'season'])->findOrFail($weekendId);
        $actions = ['pit_driver_' . $driverId => $request->compound];
        $state   = $this->simulator->simulateLap($weekend, $request->lap, $actions);

        return response()->json($state);
    }

    public function state(string $weekendId)
    {
        $weekend = RaceWeekend::with(['circuit', 'season'])->findOrFail($weekendId);
        return response()->json($this->simulator->getRaceState($weekend));
    }

    public function nextRound(string $weekendId)
    {
        $weekend = RaceWeekend::findOrFail($weekendId);

        if ($weekend->status !== 'completed') {
            return response()->json(['error' => 'Гонка не завершена'], 422);
        }

        $season = $weekend->season;
        $nextRound = $season->current_round + 1;
        $season->update(['current_round' => $nextRound]);

        $nextWeekend = RaceWeekend::where('season_id', $season->id)
            ->where('round_number', $nextRound)
            ->first();

        if ($nextWeekend) {
            $nextWeekend->update(['status' => 'setup']);
            return response()->json(['next_weekend' => $nextWeekend->load('circuit')]);
        }

        $season->update(['status' => 'completed']);
        return response()->json(['season_completed' => true]);
    }
}
