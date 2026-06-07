<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RaceWeekend;
use App\Services\QualifyingSimulator;

class QualifyingController extends Controller
{
    public function __construct(private QualifyingSimulator $simulator) {}

    public function simulate(string $weekendId)
    {
        $weekend = RaceWeekend::with(['circuit', 'season'])->findOrFail($weekendId);

        if (!in_array($weekend->status, ['setup', 'qualifying'])) {
            return response()->json(['error' => 'Квалификация недоступна в данный момент'], 422);
        }

        $weekend->update(['status' => 'qualifying']);
        $results = $this->simulator->simulate($weekend);

        return response()->json(['results' => $results]);
    }

    public function results(string $weekendId)
    {
        $weekend = RaceWeekend::findOrFail($weekendId);
        return response()->json(
            $weekend->qualifyingResults()->with('driver.team')->orderBy('position')->get()
        );
    }
}
