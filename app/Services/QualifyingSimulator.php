<?php

namespace App\Services;

use App\Models\CarSetup;
use App\Models\Driver;
use App\Models\QualifyingResult;
use App\Models\RaceWeekend;

class QualifyingSimulator
{
    private const BASE_LAP_TIMES = [
        'soft'   => 0.0,
        'medium' => 0.6,
        'hard'   => 1.2,
    ];

    private const WEATHER_MODIFIER = [
        'sunny'      => 0.0,
        'cloudy'     => 0.2,
        'rain'       => 3.5,
        'heavy_rain' => 6.0,
    ];

    public function simulate(RaceWeekend $weekend): array
    {
        $drivers = Driver::with('team')->get();
        $results = [];

        foreach ($drivers as $driver) {
            $setup = CarSetup::where('race_weekend_id', $weekend->id)
                ->where('driver_id', $driver->id)
                ->first();

            $baseTime = $this->calculateBaseTime($driver, $weekend, $setup);
            $results[$driver->id] = [
                'driver'   => $driver,
                'q1_time'  => $this->addVariation($baseTime, 0.8),
                'q2_time'  => null,
                'q3_time'  => null,
                'position' => null,
            ];
        }

        // Q1 — eliminate bottom 5
        $q1Sorted = $this->sortByTime($results, 'q1_time');
        $q1Eliminated = array_slice($q1Sorted, 15);
        foreach ($q1Eliminated as $driverId) {
            $results[$driverId]['q1_eliminated'] = true;
        }

        // Q2 — top 15 run again
        $q2Drivers = array_slice($q1Sorted, 0, 15);
        foreach ($q2Drivers as $driverId) {
            $improvement = $this->addVariation($results[$driverId]['q1_time'] - 0.3, 0.5);
            $results[$driverId]['q2_time'] = max($improvement, $results[$driverId]['q1_time'] - 0.8);
        }

        $q2Sorted = $this->sortByTime(
            array_filter($results, fn($r) => isset($r['q2_time'])),
            'q2_time'
        );
        $q2Eliminated = array_slice($q2Sorted, 10);
        foreach ($q2Eliminated as $driverId) {
            $results[$driverId]['q2_eliminated'] = true;
        }

        // Q3 — top 10
        $q3Drivers = array_slice($q2Sorted, 0, 10);
        foreach ($q3Drivers as $driverId) {
            $improvement = $results[$driverId]['q2_time'] - rand(1, 5) / 10;
            $results[$driverId]['q3_time'] = max($improvement, $results[$driverId]['q2_time'] - 1.0);
        }

        // Final positions
        $finalSorted = $this->sortByTime(
            array_filter($results, fn($r) => isset($r['q3_time'])),
            'q3_time'
        );

        $position = 1;
        foreach ($finalSorted as $driverId) {
            $results[$driverId]['position'] = $position++;
        }

        // Positions 11-15 (Q2 eliminated)
        $q2EliminatedSorted = $this->sortByTime(
            array_filter($results, fn($r) => isset($r['q2_eliminated']) && $r['q2_eliminated']),
            'q2_time'
        );
        foreach ($q2EliminatedSorted as $driverId) {
            $results[$driverId]['position'] = $position++;
        }

        // Positions 16-20 (Q1 eliminated)
        $q1EliminatedSorted = $this->sortByTime(
            array_filter($results, fn($r) => isset($r['q1_eliminated']) && $r['q1_eliminated']),
            'q1_time'
        );
        foreach ($q1EliminatedSorted as $driverId) {
            $results[$driverId]['position'] = $position++;
        }

        // Save to DB
        QualifyingResult::where('race_weekend_id', $weekend->id)->delete();
        foreach ($results as $driverId => $data) {
            QualifyingResult::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $driverId,
                'position'        => $data['position'],
                'q1_time'         => $data['q1_time'],
                'q2_time'         => $data['q2_time'] ?? null,
                'q3_time'         => $data['q3_time'] ?? null,
                'q1_eliminated'   => $data['q1_eliminated'] ?? false,
                'q2_eliminated'   => $data['q2_eliminated'] ?? false,
            ]);
        }

        $weekend->update(['status' => 'race']);

        return QualifyingResult::with('driver.team')
            ->where('race_weekend_id', $weekend->id)
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    private function calculateBaseTime(Driver $driver, RaceWeekend $weekend, ?CarSetup $setup): float
    {
        $circuit    = $weekend->circuit;
        $baseTime   = 80.0 + ($circuit->lap_length * 5);

        // Driver skill effect
        $driverScore = ($driver->pace * 0.6 + $driver->consistency * 0.4) / 100;
        $baseTime -= $driverScore * 4.0;

        // Team aero
        $teamAero = $driver->team->aero_rating / 100;
        $baseTime -= $teamAero * 2.5;

        // Setup bonus
        if ($setup) {
            $setupBonus = $this->calculateSetupBonus($setup, $circuit);
            $baseTime -= $setupBonus;
        }

        // Weather
        $baseTime += self::WEATHER_MODIFIER[$weekend->weather] ?? 0;

        return $baseTime;
    }

    private function calculateSetupBonus(CarSetup $setup, $circuit): float
    {
        $bonus = 0.0;
        $optimalDownforce = $circuit->downforce_requirement / 10;
        $actualDownforce  = ($setup->front_wing + $setup->rear_wing) / 2;
        $downforceDiff    = abs($optimalDownforce - $actualDownforce / 1.1);
        $bonus           += max(0, 1.0 - $downforceDiff * 0.2);

        return $bonus;
    }

    private function addVariation(float $base, float $range): float
    {
        return round($base + (mt_rand(-100, 100) / 100) * $range, 3);
    }

    private function sortByTime(array $results, string $timeKey): array
    {
        $ids = array_keys($results);
        usort($ids, function ($a, $b) use ($results, $timeKey) {
            $ta = $results[$a][$timeKey] ?? PHP_INT_MAX;
            $tb = $results[$b][$timeKey] ?? PHP_INT_MAX;
            return $ta <=> $tb;
        });
        return $ids;
    }
}
