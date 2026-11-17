<?php

/**
 * AlertOperationTimingTest.php
 *
 * Tests for the independent per-segment alert operation scheduler.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Alert\AlertUtil;

/**
 * Run the per-segment scheduler over a simulated timeline, anchored at t=0.
 *
 * @param  array<int, array{id:int, escalation_step_from:int, escalation_step_to:int|null, start_in_seconds:int, step_duration_seconds:int}>  $segments
 * @return array<int, array<int, int>> fire timestamps keyed by segment id
 */
function simulate(array $segments, int $pollInterval, int $duration, int $tolerance = 0, int $defaultStep = 300): array
{
    $state = [];
    $fired = [];

    for ($now = 0; $now <= $duration; $now += $pollInterval) {
        [$due, $state] = AlertUtil::evaluateSegmentTimers($segments, $state, 0, $now, $tolerance, $defaultStep);
        foreach ($due as $id) {
            $fired[$id][] = $now;
        }
    }

    return $fired;
}

test('single segment', function (): void {
    $segments = [
        ['id' => 1, 'escalation_step_from' => 1, 'escalation_step_to' => null, 'start_in_seconds' => 300, 'step_duration_seconds' => 600],
    ];

    $fired = simulate($segments, pollInterval: 60, duration: 1800);

    expect($fired[1] ?? [])->toBe([300, 900, 1500]);
});

test('two segments independent different times', function (): void {
    $segments = [
        ['id' => 1, 'escalation_step_from' => 1, 'escalation_step_to' => null, 'start_in_seconds' => 180, 'step_duration_seconds' => 900],
        ['id' => 2, 'escalation_step_from' => 2, 'escalation_step_to' => null, 'start_in_seconds' => 900, 'step_duration_seconds' => 1800],
    ];

    $fired = simulate($segments, pollInterval: 60, duration: 3600);

    // segment 1 keeps its own 900s beat from 180s; segment 2 runs independently every 1800s from 900s
    expect($fired[1] ?? [])->toBe([180, 1080, 1980, 2880]);
    expect($fired[2] ?? [])->toBe([900, 2700]);
});

test('two segments different steps same timing', function (): void {
    $segments = [
        ['id' => 1, 'escalation_step_from' => 1, 'escalation_step_to' => null, 'start_in_seconds' => 300, 'step_duration_seconds' => 600],
        ['id' => 2, 'escalation_step_from' => 2, 'escalation_step_to' => null, 'start_in_seconds' => 300, 'step_duration_seconds' => 600],
    ];

    $fired = simulate($segments, pollInterval: 60, duration: 1800);

    expect($fired[1] ?? [])->toBe([300, 900, 1500]);
    expect($fired[2])->toBe($fired[1], 'Segments with identical timing should fire at the same times');
});

test('escalation step to caps fire count', function (): void {
    $segments = [
        ['id' => 1, 'escalation_step_from' => 1, 'escalation_step_to' => 3, 'start_in_seconds' => 0, 'step_duration_seconds' => 600],
    ];

    $fired = simulate($segments, pollInterval: 60, duration: 3600);

    // from=1, to=3 -> exactly 3 fires: at 0, 600, 1200, then it stops
    expect($fired[1] ?? [])->toBe([0, 600, 1200]);
});
