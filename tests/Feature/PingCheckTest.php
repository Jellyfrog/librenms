<?php

namespace LibreNMS\Tests\Feature;

use App\Actions\Alerts\RunAlertRulesAction;
use App\Actions\Device\SetDeviceAvailability;
use App\Jobs\PingCheck;
use App\Models\Device;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Tests\InMemoryDbTestCase;
use Mockery\MockInterface;

final class PingCheckTest extends InMemoryDbTestCase
{
    /** @var list<Device|null> devices alert rules were run for, in order */
    private array $alerted = [];

    protected function setUp(): void
    {
        parent::setUp();

        // every response changes the device status, so alert rules should run for every device
        $this->mock(SetDeviceAvailability::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->andReturnTrue();
        });

        // record which device alert rules are run for instead of running them
        $this->app->bind(RunAlertRulesAction::class, function ($app, array $params): object {
            $this->alerted[] = $params['device'] ?? null;

            return new class
            {
                public function execute(): void
                {
                }
            };
        });
    }

    public function testChildAlertsAreDeferredUntilParentResponds(): void
    {
        [$parent, $child] = $this->createParentAndChild();

        // fping reports alive hosts right away, but unreachable hosts only after the timeout,
        // so the child's response can arrive before its parent's
        $this->fakeBulkPing([
            "$child->hostname is alive",
            "$parent->hostname is unreachable",
        ]);

        $this->runPingCheck();

        $this->assertAlertsRanFor([$parent, $child]);
    }

    public function testChildAlertsRunImmediatelyWhenParentAlreadyResponded(): void
    {
        [$parent, $child] = $this->createParentAndChild();

        $this->fakeBulkPing([
            "$parent->hostname is unreachable",
            "$child->hostname is alive",
        ]);

        $this->runPingCheck();

        $this->assertAlertsRanFor([$parent, $child]);
    }

    /**
     * @return array{Device, Device}
     */
    private function createParentAndChild(): array
    {
        $parent = Device::factory()->create(['hostname' => 'parent.example.com']);
        $child = Device::factory()->create(['hostname' => 'child.example.com']);
        $child->parents()->attach($parent);

        return [$parent, $child];
    }

    /**
     * Fake fping, sending a response for each output line to the callback in the given order
     *
     * @param  string[]  $lines
     */
    private function fakeBulkPing(array $lines): void
    {
        $this->mock(Fping::class, function (MockInterface $mock) use ($lines): void {
            $mock->shouldReceive('bulkPing')->once()->andReturnUsing(function (array $hosts, callable $callback) use ($lines): void {
                foreach ($lines as $line) {
                    $callback(FpingResponse::parseAliveLine($line));
                }
            });
        });
    }

    private function runPingCheck(): void
    {
        $this->expectOutputRegex('/^Pinged 2 devices in /');

        (new PingCheck)->handle();
    }

    /**
     * @param  Device[]  $expected
     */
    private function assertAlertsRanFor(array $expected): void
    {
        $this->assertSame(
            array_map(fn (Device $device) => $device->device_id, $expected),
            array_map(fn (?Device $device) => $device?->device_id, $this->alerted),
            'Alert rules did not run for the expected devices'
        );
    }
}
