<?php

/*
 * CleanupNetworksTest.php
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

use App\Actions\Maintenance\CleanupNetworks;
use App\Facades\LibrenmsConfig;
use App\Models\Ipv4Network;
use LibreNMS\Tests\InMemoryDbTestCase;

final class CleanupNetworksTest extends InMemoryDbTestCase
{
    /**
     * The gate belongs to the task, not the command, so that a queued run
     * respects the setting the same way a manual one does.
     */
    public function testNothingIsDeletedWhenThePurgeSettingIsOff(): void
    {
        LibrenmsConfig::set('networks_purge', false);
        Ipv4Network::create(['ipv4_network' => '10.0.0.0/8']);

        $result = (new CleanupNetworks)->execute();

        $this->assertFalse($result->failed(), 'being switched off is not a failure');
        $this->assertStringContainsString(
            trans('commands.maintenance:cleanup-networks.disabled'),
            $result->summary()
        );
        $this->assertSame(1, Ipv4Network::count(), 'the network should have been left alone');
    }

    public function testForceOverridesThePurgeSetting(): void
    {
        LibrenmsConfig::set('networks_purge', false);
        Ipv4Network::create(['ipv4_network' => '10.0.0.0/8']);

        $result = (new CleanupNetworks)->execute(force: true);

        $this->assertFalse($result->failed());
        $this->assertSame(0, Ipv4Network::count(), 'the unused network should have been deleted');
    }

    public function testUnusedNetworksAreDeletedWhenEnabled(): void
    {
        LibrenmsConfig::set('networks_purge', true);
        Ipv4Network::create(['ipv4_network' => '10.0.0.0/8']);

        $result = (new CleanupNetworks)->execute();

        $this->assertSame(0, Ipv4Network::count());
        $this->assertStringContainsString(
            trans('commands.maintenance:cleanup-networks.delete', ['count' => 1]),
            $result->summary()
        );
    }
}
