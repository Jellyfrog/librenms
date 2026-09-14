<?php

/*
 * FetchOuisTest.php
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

use App\Actions\Maintenance\FetchOuis;
use App\Facades\LibrenmsConfig;
use Illuminate\Support\Facades\Cache;
use LibreNMS\Tests\TestCase;

/**
 * Covers the decisions made before anything is downloaded, plus the parser,
 * which is the part that used to be tied to the console.
 */
final class FetchOuisTest extends TestCase
{
    public function testTheManufFileIsParsedIntoRows(): void
    {
        $manuf = implode("\n", [
            '# a comment line',
            "00:00:0C\tCisco\tCisco Systems, Inc",
            "00:1B:C5:00:00:00/36\tShort\tSome Vendor With A /36",
            '',
        ]);

        $this->assertSame([
            ['vendor' => 'Cisco Systems, Inc', 'oui' => '00000c'],
            // 36 bits is nine hex digits
            ['vendor' => 'Some Vendor With A /36', 'oui' => '001bc5000'],
        ], FetchOuis::parseManuf($manuf));
    }

    public function testNothingIsFetchedWhenTheFeatureIsOff(): void
    {
        LibrenmsConfig::set('mac_oui.enabled', false);

        $result = (new FetchOuis)->execute();

        $this->assertFalse($result->failed(), 'being switched off is not a failure');
        $this->assertStringContainsString(
            trans('commands.maintenance:fetch-ouis.disabled', ['setting' => 'mac_oui.enabled']),
            $result->summary()
        );
    }

    /**
     * The refresh interval is a lock that is held on purpose. The overlap
     * middleware on the job does not stand in for it, so it must still be
     * honoured by the task itself.
     */
    public function testARecentFetchIsNotRepeated(): void
    {
        LibrenmsConfig::set('mac_oui.enabled', true);
        $this->assertTrue(Cache::lock('vendor_oui_db_refresh', 60)->get(), 'test could not take the lock');

        $result = (new FetchOuis)->execute();

        $this->assertFalse($result->failed());
        $this->assertStringContainsString(
            trans('commands.maintenance:fetch-ouis.recently_fetched'),
            $result->summary()
        );
    }

    /**
     * The offer to enable the feature is the command's, not the task's: a
     * declined prompt ends the run without the task ever being asked.
     */
    public function testTheCommandOffersToEnableTheFeatureAndTakesNoForAnAnswer(): void
    {
        LibrenmsConfig::set('mac_oui.enabled', false);

        $this->artisan('maintenance:fetch-ouis')
            ->expectsOutputToContain(trans('commands.maintenance:fetch-ouis.disabled', ['setting' => 'mac_oui.enabled']))
            ->expectsConfirmation(trans('commands.maintenance:fetch-ouis.enable_question'), 'no')
            ->assertExitCode(0);

        $this->assertNotTrue(LibrenmsConfig::get('mac_oui.enabled'), 'declining must not switch the feature on');
    }
}
