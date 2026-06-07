<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\PitStop;
use App\Models\RadioMessage;
use App\Models\RaceResult;
use App\Models\RaceWeekend;

class RaceSimulator
{
    private const TIRE_DEGRADATION = [
        'soft'         => 2.5,
        'medium'       => 1.4,
        'hard'         => 0.8,
        'intermediate' => 1.8,
        'wet'          => 2.2,
    ];

    private const TIRE_PACE = [
        'soft'         => -0.8,
        'medium'       =>  0.0,
        'hard'         =>  0.6,
        'intermediate' =>  1.2,
        'wet'          =>  1.8,
    ];

    private const POINTS = [25, 18, 15, 12, 10, 8, 6, 4, 2, 1];

    private const AI_PIT_STRATEGY = [
        'soft-medium'   => ['soft', 30, 'medium'],
        'medium-hard'   => ['medium', 28, 'hard'],
        'soft-hard'     => ['soft', 20, 'hard'],
        'medium-medium' => ['medium', 25, 'medium'],
    ];

    public function initRace(RaceWeekend $weekend): array
    {
        $qualResults = $weekend->qualifyingResults()->with('driver.team')->get();
        RaceResult::where('race_weekend_id', $weekend->id)->delete();

        $compounds = ['soft', 'medium', 'medium', 'hard', 'hard'];
        $strategies = array_keys(self::AI_PIT_STRATEGY);

        foreach ($qualResults as $qr) {
            $strategy = $strategies[array_rand($strategies)];
            $startCompound = self::AI_PIT_STRATEGY[$strategy][0];
            RaceResult::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $qr->driver_id,
                'grid_position'   => $qr->position,
                'finish_position' => null,
                'status'          => 'running',
                'tire_compound'   => $startCompound,
                'current_lap'     => 0,
                'pit_stop_count'  => 0,
                'gap_to_leader'   => 0,
                'points_scored'   => 0,
                'fastest_lap'     => false,
            ]);
        }

        return $this->getRaceState($weekend);
    }

    public function simulateLap(RaceWeekend $weekend, int $lap, array $playerActions = []): array
    {
        $circuit = $weekend->circuit;
        $results = RaceResult::with('driver.team')
            ->where('race_weekend_id', $weekend->id)
            ->where('status', 'running')
            ->get();

        $lapTimes = [];

        foreach ($results as $result) {
            if ($result->current_lap >= $circuit->lap_count) {
                continue;
            }

            // Handle player pit stop command
            if (isset($playerActions['pit_driver_' . $result->driver_id])) {
                $newCompound = $playerActions['pit_driver_' . $result->driver_id];
                $this->executePitStop($weekend, $result, $lap, $newCompound, true);
            }

            // AI pit logic
            elseif ($this->shouldAIPit($result, $lap, $circuit)) {
                $newCompound = $this->chooseAICompound($result->tire_compound, $circuit, $weekend->weather);
                $this->executePitStop($weekend, $result, $lap, $newCompound, false);
            }

            $lapTime = $this->calculateLapTime($result, $circuit, $weekend->weather, $lap);
            $lapTimes[$result->id] = $lapTime;

            $result->current_lap = $lap;
            $result->save();
        }

        // Sort by accumulated gap (simplified with lap times)
        $this->updatePositions($weekend, $lapTimes);

        // AI radio messages
        if ($lap % 5 === 0 || in_array($lap, [1, $circuit->lap_count])) {
            $this->generateAIRadioMessages($weekend, $lap);
        }

        // Check finish
        if ($lap >= $circuit->lap_count) {
            $this->finalizeRace($weekend);
        }

        return $this->getRaceState($weekend);
    }

    private function calculateLapTime(RaceResult $result, $circuit, string $weather, int $lap): float
    {
        $driver     = $result->driver;
        $baseTime   = 80.0 + ($circuit->lap_length * 5);

        // Driver speed
        $driverScore = ($driver->pace * 0.5 + $driver->consistency * 0.3 + $driver->tire_management * 0.2) / 100;
        $baseTime -= $driverScore * 3.0;

        // Team engine
        $baseTime -= $driver->team->engine_rating / 100 * 1.5;

        // Tire compound pace
        $baseTime += self::TIRE_PACE[$result->tire_compound] ?? 0;

        // Tire degradation (increases with laps since last pit)
        $lapsSincePit = $lap - ($result->last_pit_lap ?? 0);
        $degradation  = self::TIRE_DEGRADATION[$result->tire_compound] ?? 1.5;
        $baseTime += $lapsSincePit * $degradation * 0.02;

        // Wet skill in rain
        if (in_array($weather, ['rain', 'heavy_rain'])) {
            $wetBonus = (100 - $driver->wet_skill) / 100 * 3.0;
            $baseTime += $wetBonus;
        }

        // Random variation
        $variation = (mt_rand(-15, 15) / 100);
        $baseTime += $variation;

        // Reliability issue
        if (mt_rand(1, 1000) < (100 - $driver->team->reliability_rating) / 2) {
            $result->update(['status' => 'dnf']);
            return 999.0;
        }

        return round(max($baseTime, 70.0), 3);
    }

    private function shouldAIPit(RaceResult $result, int $lap, $circuit): bool
    {
        $lapsSincePit = $lap - ($result->last_pit_lap ?? 0);
        $totalLaps    = $circuit->lap_count;

        // Pit window based on compound
        $windows = [
            'soft'   => 18,
            'medium' => 28,
            'hard'   => 38,
        ];

        $window = $windows[$result->tire_compound] ?? 25;

        // Don't pit in last 5 laps unless on destroyed tires
        if ($lap > $totalLaps - 5) {
            return false;
        }

        // Pit if tires worn out or in window
        return $lapsSincePit >= $window && $result->pit_stop_count < 2;
    }

    private function chooseAICompound(string $current, $circuit, string $weather): string
    {
        if (in_array($weather, ['rain', 'heavy_rain'])) {
            return $weather === 'heavy_rain' ? 'wet' : 'intermediate';
        }

        return match ($current) {
            'soft'   => mt_rand(0, 1) ? 'medium' : 'hard',
            'medium' => 'hard',
            default  => 'medium',
        };
    }

    private function executePitStop(RaceWeekend $weekend, RaceResult $result, int $lap, string $newCompound, bool $isPlayerDecision): void
    {
        $duration = round(mt_rand(220, 280) / 100, 3);

        PitStop::create([
            'race_weekend_id'   => $weekend->id,
            'driver_id'         => $result->driver_id,
            'lap'               => $lap,
            'compound_in'       => $result->tire_compound,
            'compound_out'      => $newCompound,
            'duration'          => $duration,
            'is_player_decision' => $isPlayerDecision,
        ]);

        $result->update([
            'tire_compound'  => $newCompound,
            'pit_stop_count' => $result->pit_stop_count + 1,
            'last_pit_lap'   => $lap,
            'gap_to_leader'  => $result->gap_to_leader + $duration + 20,
        ]);
    }

    private function updatePositions(RaceWeekend $weekend, array $lapTimes): void
    {
        $results = RaceResult::where('race_weekend_id', $weekend->id)
            ->where('status', 'running')
            ->get();

        foreach ($results as $result) {
            $lapTime = $lapTimes[$result->id] ?? 90.0;
            if ($result->finish_position === null && $lapTime < 900) {
                $result->gap_to_leader = max(0, $result->gap_to_leader + (mt_rand(-3, 3) / 10));
                $result->save();
            }
        }

        // Re-sort by accumulated gap
        $sorted = RaceResult::where('race_weekend_id', $weekend->id)
            ->where('status', 'running')
            ->orderBy('gap_to_leader')
            ->get();

        foreach ($sorted as $i => $result) {
            $result->finish_position = $i + 1;
            $result->save();
        }
    }

    private function finalizeRace(RaceWeekend $weekend): void
    {
        $results = RaceResult::where('race_weekend_id', $weekend->id)
            ->orderBy('finish_position')
            ->get();

        $fastestTime = PHP_INT_MAX;
        $fastestId   = null;

        foreach ($results as $i => $result) {
            if ($result->status === 'running') {
                $result->status = 'finished';
            }
            $points = self::POINTS[$i] ?? 0;
            $result->points_scored = $points;
            $result->save();
        }

        // Award fastest lap bonus point (top 10)
        $topTen = $results->filter(fn($r) => $r->finish_position <= 10)->first();
        if ($topTen) {
            $topTen->update(['fastest_lap' => true, 'points_scored' => $topTen->points_scored + 1]);
        }

        $weekend->update(['status' => 'completed']);
    }

    private function generateAIRadioMessages(RaceWeekend $weekend, int $lap): void
    {
        $playerTeamId = $weekend->season->player_team_id;
        $playerDrivers = Driver::where('team_id', $playerTeamId)->get();

        $messages = [
            'tires' => [
                'Тирес в норме, продолжаем.',
                'Чувствую деградацию на задней оси.',
                'Передние резины перегреваются!',
                'Резина ещё держится, пушим.',
            ],
            'position' => [
                'Позади DRS-окно, можем атаковать.',
                'Отрыв {gap} секунд от лидера.',
                'Соперник сзади в трёх десятых!',
            ],
            'fuel' => [
                'Мы на плане по топливу.',
                'Можем добавить пуш +2.',
                'Экономим топливо, снизить темп.',
            ],
        ];

        foreach ($playerDrivers as $driver) {
            $category = array_rand($messages);
            $msg = $messages[$category][array_rand($messages[$category])];

            $result = RaceResult::where('race_weekend_id', $weekend->id)
                ->where('driver_id', $driver->id)
                ->first();

            if ($result) {
                $gap = number_format($result->gap_to_leader, 1);
                $msg = str_replace('{gap}', $gap, $msg);
            }

            RadioMessage::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $driver->id,
                'lap'             => $lap,
                'direction'       => 'driver_to_engineer',
                'message_type'    => $category,
                'message'         => $msg,
                'is_player_sent'  => false,
            ]);
        }
    }

    public function sendRadioMessage(RaceWeekend $weekend, int $driverId, string $type, int $lap): string
    {
        $responses = [
            'push'     => 'Понял, даю всё что есть! Атакую!',
            'save'     => 'Понял, переключаюсь на экономный режим.',
            'defend'   => 'Понял, закрываю внутреннюю траекторию.',
            'attack'   => 'Понял, попробую пройти на следующем торможении.',
            'pit_in'   => 'Понял, захожу на пит-стоп через круг.',
            'box_box'  => 'Бокс, бокс, бокс! Иду в боксы!',
            'report'   => 'Машина отлично, резина ещё есть, пушим.',
        ];

        $response = $responses[$type] ?? 'Понял!';

        RadioMessage::create([
            'race_weekend_id' => $weekend->id,
            'driver_id'       => $driverId,
            'lap'             => $lap,
            'direction'       => 'engineer_to_driver',
            'message_type'    => $type,
            'message'         => $this->getEngineerMessage($type),
            'is_player_sent'  => true,
        ]);

        RadioMessage::create([
            'race_weekend_id' => $weekend->id,
            'driver_id'       => $driverId,
            'lap'             => $lap,
            'direction'       => 'driver_to_engineer',
            'message_type'    => 'response',
            'message'         => $response,
            'is_player_sent'  => false,
        ]);

        return $response;
    }

    private function getEngineerMessage(string $type): string
    {
        return match($type) {
            'push'    => 'Push now, push now! Дай максимальный темп!',
            'save'    => 'Fuel save mode, manage the tires.',
            'defend'  => 'Watch your mirrors, defend the position.',
            'attack'  => 'You have DRS, attack on the straight.',
            'pit_in'  => 'Box this lap, box this lap. Мягкие готовы.',
            'box_box' => 'Box box box! Немедленно в боксы!',
            'report'  => 'Give me a status report.',
            default   => 'Understood.',
        };
    }

    public function getRaceState(RaceWeekend $weekend): array
    {
        $results = RaceResult::with('driver.team')
            ->where('race_weekend_id', $weekend->id)
            ->orderBy('finish_position')
            ->orderBy('gap_to_leader')
            ->get();

        $pitStops = PitStop::where('race_weekend_id', $weekend->id)->get();
        $radio    = RadioMessage::with('driver')
            ->where('race_weekend_id', $weekend->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return [
            'results'   => $results->toArray(),
            'pit_stops' => $pitStops->toArray(),
            'radio'     => $radio->toArray(),
            'weekend'   => $weekend->load('circuit')->toArray(),
        ];
    }
}
