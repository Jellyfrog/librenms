<?php

/*
 * MaintenanceScheduleTest.php
 *
 * -Description-
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2026 LibreNMS
 */

namespace LibreNMS\Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use LibreNMS\Tests\TestCase;

final class MaintenanceScheduleTest extends TestCase
{
    /**
     * Every maintenance command must log to maintenance.log, run on only one server,
     * and record an eventlog entry when it fails.
     */
    public function testMaintenanceCommandsShareCommonSchedulingOptions(): void
    {
        $events = $this->maintenanceEvents();

        $this->assertNotEmpty($events, 'No maintenance commands are scheduled');

        foreach ($events as $event) {
            $name = $this->commandName($event);

            $this->assertTrue($event->onOneServer, "$name is not limited to one server");
            $this->assertStringEndsWith('maintenance.log', (string) $event->output, "$name does not log to maintenance.log");
            $this->assertNotEmpty(
                $this->property($event, 'afterCallbacks'),
                "$name has no onFailure handler"
            );
        }
    }

    /**
     * Pin the frequency of each maintenance command so a refactor cannot silently
     * change when they run.
     */
    public function testMaintenanceCommandFrequencies(): void
    {
        $expressions = $this->maintenanceEvents()
            ->mapWithKeys(fn (Event $event) => [$this->commandName($event) => $event->expression])
            ->all();

        $this->assertSame([
            'maintenance:fetch-ouis' => '43 1 * * 0',
            'maintenance:cleanup-networks' => '5 2 * * 0',
            'maintenance:fetch-rss' => '53 3 * * *',
            'maintenance:cleanup-syslog' => '17 * * * *',
            'maintenance:discover-ssl-certificates' => '14 4 * * *',
            'maintenance:refresh-ssl-certificates' => '55 5 * * *',
        ], $expressions);
    }

    public function testSyslogCleanupDoesNotOverlap(): void
    {
        $syslog = $this->maintenanceEvents()
            ->first(fn (Event $event) => $this->commandName($event) === 'maintenance:cleanup-syslog');

        $this->assertNotNull($syslog);
        $this->assertTrue($syslog->withoutOverlapping, 'hourly syslog cleanup must not overlap');
    }

    public function testSslDiscoveryIsGatedOnConfig(): void
    {
        $discover = $this->maintenanceEvents()
            ->first(fn (Event $event) => $this->commandName($event) === 'maintenance:discover-ssl-certificates');

        $this->assertNotNull($discover);
        $this->assertNotEmpty(
            $this->property($discover, 'filters'),
            'ssl certificate discovery must stay gated on ssl_certificates.auto_discover'
        );
    }

    /** @return Collection<int, Event> */
    private function maintenanceEvents(): Collection
    {
        return (new Collection(app(Schedule::class)->events()))
            ->filter(fn ($event) => $event instanceof Event && str_contains((string) $event->command, 'maintenance:'))
            ->values();
    }

    private function commandName(Event $event): string
    {
        preg_match('/(maintenance:[\w-]+)/', (string) $event->command, $matches);

        return $matches[1] ?? '';
    }

    private function property(object $object, string $name): mixed
    {
        $reflection = new \ReflectionObject($object);

        while (! $reflection->hasProperty($name)) {
            $reflection = $reflection->getParentClass();

            if ($reflection === false) {
                return null;
            }
        }

        $property = $reflection->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
