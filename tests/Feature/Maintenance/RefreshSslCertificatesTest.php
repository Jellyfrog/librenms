<?php

/*
 * RefreshSslCertificatesTest.php
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

use App\Actions\Maintenance\RefreshSslCertificates;
use App\Facades\LibrenmsConfig;
use App\Models\SslCertificate;
use LibreNMS\Tests\InMemoryDbTestCase;

/**
 * Covers everything up to the point a certificate would be fetched from its
 * host. The fetch itself needs a network, so the cases here are the ones that
 * decide whether a certificate is looked at at all.
 */
final class RefreshSslCertificatesTest extends InMemoryDbTestCase
{
    public function testNothingToDoIsNotAFailure(): void
    {
        $result = (new RefreshSslCertificates)->execute();

        $this->assertFalse($result->failed());
        $this->assertStringContainsString(
            trans('commands.maintenance:refresh-ssl-certificates.none'),
            $result->summary()
        );
    }

    public function testDisabledCertificatesAreLeftAlone(): void
    {
        SslCertificate::create(['host' => 'example.test', 'disabled' => true]);

        $result = (new RefreshSslCertificates)->execute();

        $this->assertStringContainsString(
            trans('commands.maintenance:refresh-ssl-certificates.none'),
            $result->summary(),
            'a disabled certificate should not count as one to refresh'
        );
    }

    public function testSkippedHostsAreLeftAlone(): void
    {
        LibrenmsConfig::set('ssl_certificates.skip_hosts', ['Example.TEST']);
        SslCertificate::create(['host' => 'example.test']);

        $result = (new RefreshSslCertificates)->execute();

        $this->assertStringContainsString(
            trans('commands.maintenance:refresh-ssl-certificates.none'),
            $result->summary(),
            'skip_hosts should match regardless of case'
        );
    }

    public function testAnUnknownIdRefreshesNothing(): void
    {
        SslCertificate::create(['host' => 'example.test']);

        $result = (new RefreshSslCertificates)->execute(id: 999);

        $this->assertStringContainsString(
            trans('commands.maintenance:refresh-ssl-certificates.none'),
            $result->summary()
        );
    }
}
