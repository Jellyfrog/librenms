<?php

/*
 * DiscoverSslCertificatesTest.php
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
 */

namespace LibreNMS\Tests\Feature\Maintenance;

use App\Actions\Maintenance\DiscoverSslCertificates;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\SslCertificate;
use LibreNMS\Tests\InMemoryDbTestCase;

/**
 * Covers everything up to the point a host would be contacted. The connection
 * itself needs a network, so every device here is one the task must decide
 * not to touch.
 */
final class DiscoverSslCertificatesTest extends InMemoryDbTestCase
{
    /**
     * The guard once lived on the schedule entry, so a manual run scanned
     * every device with discovery switched off, and moving the task into the
     * registry would have switched it on everywhere. It has to be in the task.
     */
    public function testDiscoveryStaysOffWhenAutoDiscoverIsDisabled(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', false);
        Device::factory()->create();

        $result = (new DiscoverSslCertificates)->execute();

        $this->assertFalse($result->failed(), 'being switched off is not a failure');
        $this->assertStringContainsString(
            trans('commands.maintenance:discover-ssl-certificates.disabled'),
            $result->summary()
        );
        $this->assertSame(0, SslCertificate::count(), 'nothing should have been contacted');
    }

    public function testForceOverridesTheSetting(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', false);

        // no devices, so this proves only that the guard was bypassed
        $result = (new DiscoverSslCertificates)->execute(force: true);

        $this->assertStringContainsString(
            trans('commands.maintenance:discover-ssl-certificates.no_devices'),
            $result->summary()
        );
    }

    public function testDisabledDevicesAreNotContacted(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', true);
        Device::factory()->create(['disabled' => 1]);

        $result = (new DiscoverSslCertificates)->execute();

        $this->assertStringContainsString(
            trans('commands.maintenance:discover-ssl-certificates.no_devices'),
            $result->summary()
        );
    }

    public function testSkippedHostsAreNotContacted(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', true);
        LibrenmsConfig::set('ssl_certificates.skip_hosts', ['Skip.TEST']);
        Device::factory()->create(['hostname' => 'skip.test']);

        $result = (new DiscoverSslCertificates)->execute();

        $this->assertFalse($result->failed());
        $this->assertStringContainsString(
            trans('commands.maintenance:discover-ssl-certificates.summary', ['created' => 0, 'updated' => 0, 'failed' => 0]),
            $result->summary(),
            'a skipped host should not count as created, updated, or failed'
        );
        $this->assertSame(0, SslCertificate::count());
    }

    public function testTheCommandPassesForceThrough(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', false);

        $this->artisan('maintenance:discover-ssl-certificates', ['--force' => true])
            ->expectsOutputToContain(trans('commands.maintenance:discover-ssl-certificates.no_devices'))
            ->assertExitCode(0);
    }
}
