<?php

namespace LibreNMS\Tests\Unit\Models;

use App\Facades\LibrenmsConfig;
use App\Models\DeviceStats;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Enum\FpingExitCode;
use LibreNMS\Tests\TestCase;

final class DeviceStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LibrenmsConfig::set('device_stats_avg_factor', 0.05);
    }

    public function testFirstPingInitialisesStats(): void
    {
        $stats = new DeviceStats(['device_id' => 1]);

        $stats->fillStats($this->pingResponse(transmitted: 3, received: 2, latency: 12.5));

        $this->assertLossStats($stats, prev: 33.333, last: 33.333, avg: 33.333);
        $this->assertRttStats($stats, prev: 12.5, last: 12.5, avg: 12.5);
    }

    public function testPacketLossAfterNoPacketLoss(): void
    {
        $stats = new DeviceStats([
            'device_id' => 1,
            'ping_loss_last' => 0.0,
            'ping_loss_prev' => 0.0,
            'ping_loss_avg' => 0.0,
        ]);

        $stats->fillStats($this->pingResponse(transmitted: 3, received: 1));

        // 0% loss is a previous value, the average moves one step: 0 + (66.667 - 0) * 0.05
        $this->assertLossStats($stats, prev: 0, last: 66.667, avg: 3.333);
    }

    public function testNoPacketLossAfterPacketLoss(): void
    {
        $stats = new DeviceStats([
            'device_id' => 1,
            'ping_loss_last' => 50.0,
            'ping_loss_prev' => 0.0,
            'ping_loss_avg' => 10.0,
        ]);

        $stats->fillStats($this->pingResponse(transmitted: 3, received: 3));

        // 10 + (0 - 10) * 0.05
        $this->assertLossStats($stats, prev: 50, last: 0, avg: 9.5);
    }

    public function testPacketLossMovingAverage(): void
    {
        $stats = new DeviceStats([
            'device_id' => 1,
            'ping_loss_last' => 20.0,
            'ping_loss_prev' => 10.0,
            'ping_loss_avg' => 5.0,
        ]);

        $stats->fillStats($this->pingResponse(transmitted: 5, received: 4));

        // 5 + (20 - 5) * 0.05
        $this->assertLossStats($stats, prev: 20, last: 20, avg: 5.75);
    }

    public function testRttMovingAverage(): void
    {
        $stats = new DeviceStats([
            'device_id' => 1,
            'ping_rtt_last' => 20.0,
            'ping_rtt_prev' => 10.0,
            'ping_rtt_avg' => 10.0,
        ]);

        $stats->fillStats($this->pingResponse(transmitted: 3, received: 3, latency: 30.0));

        // 10 + (30 - 10) * 0.05
        $this->assertRttStats($stats, prev: 20, last: 30, avg: 11);

        // no replies means no latency, so the rtt stats are left alone
        $stats->fillStats($this->pingResponse(transmitted: 3, received: 0, latency: 0.0));

        $this->assertRttStats($stats, prev: 20, last: 30, avg: 11);
    }

    private function pingResponse(int $transmitted, int $received, float $latency = 1.0): FpingResponse
    {
        return new FpingResponse(
            transmitted: $transmitted,
            received: $received,
            loss: intdiv(100 * ($transmitted - $received), $transmitted),
            min_latency: $latency,
            max_latency: $latency,
            avg_latency: $latency,
            duplicates: 0,
            exit_code: $received > 0 ? FpingExitCode::Success : FpingExitCode::Unreachable,
        );
    }

    private function assertLossStats(DeviceStats $stats, float $prev, float $last, float $avg): void
    {
        $this->assertEqualsWithDelta(
            ['prev' => $prev, 'last' => $last, 'avg' => $avg],
            ['prev' => $stats->ping_loss_prev, 'last' => $stats->ping_loss_last, 'avg' => $stats->ping_loss_avg],
            0.001,
            'ping loss stats',
        );
    }

    private function assertRttStats(DeviceStats $stats, float $prev, float $last, float $avg): void
    {
        $this->assertEqualsWithDelta(
            ['prev' => $prev, 'last' => $last, 'avg' => $avg],
            ['prev' => $stats->ping_rtt_prev, 'last' => $stats->ping_rtt_last, 'avg' => $stats->ping_rtt_avg],
            0.001,
            'ping rtt stats',
        );
    }
}
