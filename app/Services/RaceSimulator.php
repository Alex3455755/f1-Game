<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\PitStop;
use App\Models\RadioMessage;
use App\Models\RaceResult;
use App\Models\RaceWeekend;

class RaceSimulator
{
    // Degradation rate per lap (seconds added)
    private const TIRE_DEGRADATION = [
        'soft'         => 0.065,
        'medium'       => 0.032,
        'hard'         => 0.016,
        'intermediate' => 0.045,
        'wet'          => 0.055,
    ];

    // Pace offset vs medium (seconds per lap, negative = faster)
    private const TIRE_PACE = [
        'soft'         => -0.85,
        'medium'       =>  0.00,
        'hard'         =>  0.55,
        'intermediate' =>  1.20,
        'wet'          =>  1.80,
    ];

    // Optimal stint length (laps) before pace cliff
    private const TIRE_CLIFF = [
        'soft'         => 15,
        'medium'       => 24,
        'hard'         => 35,
        'intermediate' => 18,
        'wet'          => 20,
    ];

    private const POINTS = [25, 18, 15, 12, 10, 8, 6, 4, 2, 1];

    // ─────────────────────────────────────────────────────────────────────────

    public function initRace(RaceWeekend $weekend): array
    {
        $qualResults = $weekend->qualifyingResults()->with('driver.team')->orderBy('position')->get();
        RaceResult::where('race_weekend_id', $weekend->id)->delete();

        $strategies = [
            ['soft',   25, 'medium'],
            ['medium', 28, 'hard'],
            ['soft',   18, 'hard'],
            ['medium', 22, 'medium'],
            ['hard',   35, 'medium'],
        ];

        foreach ($qualResults as $qr) {
            $strategy     = $strategies[array_rand($strategies)];
            $startCompound = $strategy[0];

            // Initial gap: 0.4s per grid slot (P1=0, P2=0.4s, P20=7.6s)
            $gridGap = ($qr->position - 1) * 0.4;

            RaceResult::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $qr->driver_id,
                'grid_position'   => $qr->position,
                'finish_position' => $qr->position,
                'status'          => 'running',
                'tire_compound'   => $startCompound,
                'current_lap'     => 0,
                'pit_stop_count'  => 0,
                'gap_to_leader'   => $gridGap,
                'last_pit_lap'    => 0,
                'points_scored'   => 0,
                'fastest_lap'     => false,
            ]);
        }

        return $this->getRaceState($weekend);
    }

    // ─────────────────────────────────────────────────────────────────────────

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

            // Player pit command
            if (isset($playerActions['pit_driver_' . $result->driver_id])) {
                $newCompound = $playerActions['pit_driver_' . $result->driver_id];
                $this->executePitStop($weekend, $result, $lap, $newCompound, true);
            }
            // AI pit decision
            elseif ($this->shouldAIPit($result, $lap, $circuit)) {
                $newCompound = $this->chooseAICompound($result->tire_compound, $circuit, $weekend->weather);
                $this->executePitStop($weekend, $result, $lap, $newCompound, false);
            }

            $lapTime = $this->calculateLapTime($result, $circuit, $weekend->weather, $lap);
            $lapTimes[$result->id] = $lapTime;

            $result->current_lap = $lap;
            $result->save();
        }

        // Safety car: ~1.5% chance per lap (VSC bunches the field)
        if (mt_rand(1, 1000) <= 15 && $lap > 3 && $lap < $circuit->lap_count - 3) {
            $this->triggerSafetyCar($weekend, $lap);
        }

        $this->updatePositions($weekend, $lapTimes);

        if ($lap % 5 === 0 || $lap === 1 || $lap === $circuit->lap_count) {
            $this->generateAIRadioMessages($weekend, $lap);
        }

        if ($lap >= $circuit->lap_count) {
            $this->finalizeRace($weekend);
        }

        return $this->getRaceState($weekend);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function calculateLapTime(RaceResult $result, $circuit, string $weather, int $lap): float
    {
        $driver = $result->driver;

        // ── Base lap time from physics ──────────────────────────────────────
        // avgSpeed in km/h: faster at low downforce, slower at street/high-df
        $avgSpeed = 240.0
            + (50 - $circuit->downforce_requirement) * 1.2
            - ($circuit->circuit_type === 'street' ? 25.0 : 0.0);
        $avgSpeed = max(140.0, min(280.0, $avgSpeed));
        $baseTime = ($circuit->lap_length / $avgSpeed) * 3600.0;

        // ── Driver skill ─────────────────────────────────────────────────────
        // Best driver saves ~1.8s, worst saves ~0.6s → 1.2s spread
        $skillScore = ($driver->pace * 0.50
                     + $driver->consistency * 0.30
                     + $driver->tire_management * 0.20) / 100.0;
        $baseTime -= 0.6 + $skillScore * 1.2;

        // ── Engine power (±0.6s) ─────────────────────────────────────────────
        $baseTime -= ($driver->team->engine_rating - 80.0) / 100.0 * 0.6;

        // ── Tire compound pace ───────────────────────────────────────────────
        $baseTime += self::TIRE_PACE[$result->tire_compound] ?? 0.0;

        // ── Tire degradation model ───────────────────────────────────────────
        $lapsSincePit = max(0, $lap - ($result->last_pit_lap ?? 0));
        $cliffLap     = self::TIRE_CLIFF[$result->tire_compound] ?? 22;
        $degradRate   = self::TIRE_DEGRADATION[$result->tire_compound] ?? 0.032;
        $wearFactor   = $circuit->tire_wear_rate / 100.0;

        // Linear degradation
        $baseTime += $lapsSincePit * $degradRate * $wearFactor * 10.0;

        // After cliff: exponential degradation (tire drop-off)
        if ($lapsSincePit > $cliffLap) {
            $over = $lapsSincePit - $cliffLap;
            $baseTime += $over * $over * $degradRate * $wearFactor * 3.0;
        }

        // ── Weather impact ───────────────────────────────────────────────────
        if (in_array($weather, ['rain', 'heavy_rain'])) {
            $wetPenalty = (100.0 - $driver->wet_skill) / 100.0
                        * ($weather === 'heavy_rain' ? 6.0 : 3.5);
            $baseTime += $wetPenalty;
        } elseif ($weather === 'cloudy') {
            $baseTime += 0.15; // slightly slower grip
        }

        // ── DRS / slipstream (2% chance per lap at the front)  ───────────────
        if ($result->finish_position <= 3 && mt_rand(1, 100) <= 2) {
            $baseTime -= 0.3; // gap opened
        }

        // ── Random variation based on consistency ────────────────────────────
        $maxVar = 0.08 + (1.0 - $skillScore) * 0.35;
        $baseTime += (mt_rand(-1000, 1000) / 1000.0) * $maxVar;

        // ── DNF: probability based on team reliability ───────────────────────
        $dnfProbability = max(0, (102.0 - $driver->team->reliability_rating) * 0.6);
        if (mt_rand(1, 10000) <= $dnfProbability) {
            $result->update(['status' => 'dnf']);
            return 999.0;
        }

        return round(max($baseTime, 60.0), 3);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function updatePositions(RaceWeekend $weekend, array $lapTimes): void
    {
        $running = RaceResult::where('race_weekend_id', $weekend->id)
            ->where('status', 'running')
            ->get();

        // Find leader's lap time (minimum valid time)
        $validTimes = array_filter($lapTimes, fn($t) => $t < 900.0);
        if (empty($validTimes)) return;

        $leaderTime = min($validTimes);

        // Accumulate real gap deltas: delta = driver_time - leader_time
        foreach ($running as $result) {
            $lapTime = $lapTimes[$result->id] ?? ($leaderTime + 2.0);
            if ($lapTime >= 900.0) continue;

            $delta = $lapTime - $leaderTime;
            $result->gap_to_leader = max(0.0, round(($result->gap_to_leader ?? 0.0) + $delta, 3));
            $result->save();
        }

        // Sort: DNF to back, then by gap
        $allResults = RaceResult::where('race_weekend_id', $weekend->id)
            ->orderByRaw("CASE status WHEN 'dnf' THEN 1 ELSE 0 END")
            ->orderBy('gap_to_leader')
            ->get();

        foreach ($allResults as $i => $result) {
            $result->finish_position = $i + 1;
            $result->save();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function shouldAIPit(RaceResult $result, int $lap, $circuit): bool
    {
        $lapsSincePit = $lap - ($result->last_pit_lap ?? 0);
        $totalLaps    = $circuit->lap_count;

        // Never pit in last 5 laps
        if ($lap > $totalLaps - 5) return false;
        // Never more than 2 stops
        if ($result->pit_stop_count >= 2) return false;

        $cliff  = self::TIRE_CLIFF[$result->tire_compound] ?? 22;
        $wearMod = $circuit->tire_wear_rate / 100.0; // wear adjusts the window

        // Pit when degradation cliff is reached, adjusted by circuit wear
        $pitWindow = (int) round($cliff / $wearMod);

        // Mandatory: pit if 4 laps past cliff (tires destroyed)
        if ($lapsSincePit >= $pitWindow + 4) return true;

        // Strategic: 70% chance to pit at optimal window
        if ($lapsSincePit >= $pitWindow) {
            return mt_rand(1, 10) <= 7;
        }

        return false;
    }

    private function chooseAICompound(string $current, $circuit, string $weather): string
    {
        if (in_array($weather, ['rain', 'heavy_rain'])) {
            return $weather === 'heavy_rain' ? 'wet' : 'intermediate';
        }

        // High wear circuit → prefer harder compound for second stint
        $wearHigh = $circuit->tire_wear_rate >= 70;

        return match ($current) {
            'soft'   => $wearHigh ? 'hard' : 'medium',
            'medium' => $wearHigh ? 'hard' : (mt_rand(0, 1) ? 'hard' : 'medium'),
            default  => 'medium',
        };
    }

    private function executePitStop(RaceWeekend $weekend, RaceResult $result, int $lap, string $newCompound, bool $isPlayer): void
    {
        // Pit loss: 20-26s (stationary 2.2-2.8s + pit lane delta ~18-22s)
        $stationary = round(mt_rand(220, 280) / 100, 3);
        $pitLaneDelta = mt_rand(18, 22);
        $totalPitLoss = $stationary + $pitLaneDelta;

        PitStop::create([
            'race_weekend_id'    => $weekend->id,
            'driver_id'          => $result->driver_id,
            'lap'                => $lap,
            'compound_in'        => $result->tire_compound,
            'compound_out'       => $newCompound,
            'duration'           => $stationary,
            'is_player_decision' => $isPlayer,
        ]);

        $result->update([
            'tire_compound'  => $newCompound,
            'pit_stop_count' => $result->pit_stop_count + 1,
            'last_pit_lap'   => $lap,
            'gap_to_leader'  => round(($result->gap_to_leader ?? 0) + $totalPitLoss, 3),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function triggerSafetyCar(RaceWeekend $weekend, int $lap): void
    {
        // VSC / Safety car: compress the field by 60-80%
        $results = RaceResult::where('race_weekend_id', $weekend->id)
            ->where('status', 'running')
            ->orderBy('gap_to_leader')
            ->get();

        $compression = mt_rand(55, 75) / 100.0; // compress gaps by this factor

        foreach ($results as $result) {
            $result->gap_to_leader = round($result->gap_to_leader * $compression, 3);
            $result->save();
        }

        // Radio message about safety car
        $playerTeamId = $weekend->season->player_team_id;
        $playerDrivers = Driver::where('team_id', $playerTeamId)->get();
        foreach ($playerDrivers as $driver) {
            RadioMessage::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $driver->id,
                'lap'             => $lap,
                'direction'       => 'engineer_to_driver',
                'message_type'    => 'safety_car',
                'message'         => '🟡 Safety car out! Stay out or come in? Это шанс для бесплатного пит-стопа!',
                'is_player_sent'  => false,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function finalizeRace(RaceWeekend $weekend): void
    {
        // Make sure positions are correct one last time
        $results = RaceResult::where('race_weekend_id', $weekend->id)
            ->orderByRaw("CASE status WHEN 'dnf' THEN 1 ELSE 0 END")
            ->orderBy('gap_to_leader')
            ->get();

        foreach ($results as $i => $result) {
            $result->finish_position = $i + 1;
            if ($result->status === 'running') {
                $result->status = 'finished';
                $result->points_scored = self::POINTS[$i] ?? 0;
            }
            // DNF = 0 points (default)
            $result->save();
        }

        // Fastest lap bonus point: random among top 10 finishers
        $topTen = $results->filter(fn($r) => $r->status === 'finished' && $r->finish_position <= 10);
        if ($topTen->isNotEmpty()) {
            $fl = $topTen->random();
            $fl->update(['fastest_lap' => true, 'points_scored' => $fl->points_scored + 1]);
        }

        $weekend->update(['status' => 'completed']);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generateAIRadioMessages(RaceWeekend $weekend, int $lap): void
    {
        $playerTeamId  = $weekend->season->player_team_id;
        $playerDrivers = Driver::where('team_id', $playerTeamId)->get();
        $totalLaps     = $weekend->circuit->lap_count;

        foreach ($playerDrivers as $driver) {
            $result = RaceResult::where('race_weekend_id', $weekend->id)
                ->where('driver_id', $driver->id)
                ->first();
            if (!$result) continue;

            $gap       = number_format($result->gap_to_leader, 3);
            $pos       = $result->finish_position ?? '?';
            $tire      = $result->tire_compound ?? 'medium';
            $lapsSince = $lap - ($result->last_pit_lap ?? 0);

            // Context-aware message selection
            if ($lap === 1) {
                $msg = "Старт хороший, P{$pos} сейчас. Управляй резиной.";
            } elseif ($lapsSince > (self::TIRE_CLIFF[$tire] ?? 22) * 0.9) {
                $msg = "⚠️ Резина на пределе! Деградация критическая, +{$gap}s от лидера.";
            } elseif ($result->gap_to_leader < 1.0 && $pos > 1) {
                $msg = "DRS-зона! Мы в одной секунде от P" . ($pos - 1) . ". Атакуй!";
            } elseif ($lap > $totalLaps - 5) {
                $msg = "Финальный спурт! P{$pos}, +{$gap}s. Всё в этих последних кругах!";
            } elseif ($result->pit_stop_count === 0 && $lap > $totalLaps * 0.35) {
                $msg = "📻 Инженер: Нужно думать о пит-стопе. Резина уже {$lapsSince} кругов.";
            } else {
                $messages = [
                    "P{$pos}, разрыв +{$gap}s. Продолжаем в том же темпе.",
                    "Резина ещё есть. {$lapsSince} кругов на {$tire}.",
                    "Соперник сзади в DRS. Держи траекторию!",
                    "Мы быстрее на этом отрезке. Пушим!",
                    "Топливо в норме. Темп хороший.",
                ];
                $msg = $messages[array_rand($messages)];
            }

            RadioMessage::create([
                'race_weekend_id' => $weekend->id,
                'driver_id'       => $driver->id,
                'lap'             => $lap,
                'direction'       => 'driver_to_engineer',
                'message_type'    => 'update',
                'message'         => $msg,
                'is_player_sent'  => false,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function sendRadioMessage(RaceWeekend $weekend, int $driverId, string $type, int $lap): string
    {
        $engineerMsgs = [
            'push'    => 'Push now, push now! Максимальный темп, убираем разрыв!',
            'save'    => 'Fuel save mode, protect the tires. Снижаем темп.',
            'defend'  => 'Watch your mirrors, defend P' . '—' . '. Закрывай внутреннюю!',
            'attack'  => 'You have DRS, attack on the straight. GO GO GO!',
            'pit_in'  => 'Box this lap, box this lap. Резина готова.',
            'box_box' => 'Box box box! Немедленно в боксы!',
            'report'  => 'Give me a status report. Как машина?',
        ];

        $driverResponses = [
            'push'    => 'Понял! Атакую, даю всё!',
            'save'    => 'Понял, переключаюсь на экономный режим.',
            'defend'  => 'Понял, закрываю внутреннюю траекторию.',
            'attack'  => 'Понял, попробую на следующем торможении!',
            'pit_in'  => 'Понял, захожу в боксы через круг.',
            'box_box' => 'Бокс, бокс, бокс! Иду!',
            'report'  => 'Машина отлично, резина ещё есть. Пушим.',
        ];

        RadioMessage::create([
            'race_weekend_id' => $weekend->id,
            'driver_id'       => $driverId,
            'lap'             => $lap,
            'direction'       => 'engineer_to_driver',
            'message_type'    => $type,
            'message'         => $engineerMsgs[$type] ?? 'Understood.',
            'is_player_sent'  => true,
        ]);

        $response = $driverResponses[$type] ?? 'Понял!';
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

    // ─────────────────────────────────────────────────────────────────────────

    public function getRaceState(RaceWeekend $weekend): array
    {
        $results = RaceResult::with('driver.team')
            ->where('race_weekend_id', $weekend->id)
            ->orderByRaw("CASE status WHEN 'dnf' THEN 1 ELSE 0 END")
            ->orderBy('gap_to_leader')
            ->get();

        $pitStops = PitStop::where('race_weekend_id', $weekend->id)->get();
        $radio    = RadioMessage::with('driver')
            ->where('race_weekend_id', $weekend->id)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return [
            'results'   => $results->toArray(),
            'pit_stops' => $pitStops->toArray(),
            'radio'     => $radio->toArray(),
            'weekend'   => $weekend->load('circuit')->toArray(),
        ];
    }
}
