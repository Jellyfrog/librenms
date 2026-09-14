<?php

/**
 * DiscoverSslCertificates.php
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
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\SslCertificate;
use LibreNMS\Enum\Severity;

class DiscoverSslCertificates
{
    private const PORT = 443;

    private const CONNECT_TIMEOUT = 10;

    /**
     * @param  string  $deviceSpec  device_id, hostname, or all
     * @param  bool  $force  ignore the ssl_certificates.auto_discover setting
     */
    public function execute(string $deviceSpec = 'all', bool $force = false): TaskResult
    {
        $result = TaskResult::make();

        // The gate is here, not in the command, so a queued run respects it.
        // It was once a when() on the schedule entry, which meant a manual run
        // scanned every device with discovery switched off.
        if (! LibrenmsConfig::get('ssl_certificates.auto_discover', false) && ! $force) {
            return $result->warning(trans('commands.maintenance:discover-ssl-certificates.disabled'));
        }

        $query = Device::query()->where('disabled', 0);
        if ($deviceSpec !== 'all') {
            $query->whereDeviceSpec($deviceSpec);
        }
        $devices = $query->get();

        if ($devices->isEmpty()) {
            return $result->warning(trans('commands.maintenance:discover-ssl-certificates.no_devices'));
        }

        $skipHosts = array_map(strtolower(...), (array) LibrenmsConfig::get('ssl_certificates.skip_hosts', []));
        $created = 0;
        $updated = 0;
        $failed = 0;

        /** @var Device $device */
        foreach ($devices as $device) {
            $host = $device->pollerTarget();
            if (empty($host) || in_array(strtolower($host), $skipHosts, true)) {
                continue;
            }

            $cert = SslCertificate::firstOrNew([
                'device_id' => $device->device_id,
                'host' => $host,
                'port' => self::PORT,
            ], [
                'disabled' => false,
            ]);

            try {
                $cert->updateFromHost(self::CONNECT_TIMEOUT);
            } catch (\Throwable $e) {
                // one host being unreachable is not the task failing
                $result->warning("$host:" . self::PORT . ' – ' . $e->getMessage());
                $failed++;

                continue;
            }

            if ($cert->exists) {
                $changes = $cert->getTrackedChanges();
                $cert->save();
                if ($changes !== '') {
                    $updated++;
                    Eventlog::log("SSL certificate updated: {$host}:" . self::PORT . " – {$changes}", $device->device_id, 'ssl-certificate', Severity::Info, $cert->id);
                }
            } else {
                $cert->save();
                $created++;
                $msg = "SSL certificate discovered: {$host}:" . self::PORT . " – Subject: {$cert->subject}, Issuer: {$cert->issuer}, Valid until: " . ($cert->valid_to?->format('Y-m-d H:i:s') ?? 'N/A') . ', Days until expiry: ' . ($cert->days_until_expiry ?? 'N/A');
                Eventlog::log($msg, $device->device_id, 'ssl-certificate', Severity::Info, $cert->id);
            }
        }

        return $result->info(trans('commands.maintenance:discover-ssl-certificates.summary', [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
        ]));
    }
}
