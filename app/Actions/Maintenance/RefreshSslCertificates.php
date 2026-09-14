<?php

/**
 * RefreshSslCertificates.php
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

namespace App\Actions\Maintenance;

use App\Facades\LibrenmsConfig;
use App\Maintenance\TaskResult;
use App\Models\Eventlog;
use App\Models\SslCertificate;
use LibreNMS\Enum\Severity;

class RefreshSslCertificates
{
    private const CONNECT_TIMEOUT = 10;

    /**
     * @param  int|null  $id  refresh one certificate; null refreshes every enabled one
     */
    public function execute(?int $id = null): TaskResult
    {
        $result = TaskResult::make();

        $query = SslCertificate::query()->where('disabled', 0);
        if ($id !== null) {
            $query->where('id', $id);
        }

        $skipHosts = array_map(strtolower(...), (array) LibrenmsConfig::get('ssl_certificates.skip_hosts', []));
        $certificates = $query->get()
            ->reject(fn (SslCertificate $cert) => in_array(strtolower($cert->host), $skipHosts, true));

        if ($certificates->isEmpty()) {
            return $result->warning(trans('commands.maintenance:refresh-ssl-certificates.none'));
        }

        $refreshed = 0;
        $failed = 0;

        foreach ($certificates as $cert) {
            try {
                $cert->updateFromHost(self::CONNECT_TIMEOUT);
            } catch (\Throwable $e) {
                // one host being unreachable is not the task failing, so it is a
                // warning; it used to be printed only at -v, now it is always kept
                $result->warning("$cert->host:$cert->port – " . $e->getMessage());
                $failed++;

                continue;
            }

            $changes = $cert->getTrackedChanges();
            $cert->save();

            if ($changes !== '') {
                $refreshed++;
                Eventlog::log("SSL certificate refreshed: {$cert->host}:{$cert->port} – {$changes}", $cert->device_id, 'ssl-certificate', Severity::Info, $cert->id);
            }
        }

        return $result->info(trans('commands.maintenance:refresh-ssl-certificates.summary', [
            'refreshed' => $refreshed,
            'failed' => $failed,
        ]));
    }
}
