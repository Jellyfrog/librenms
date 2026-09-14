<?php

/**
 * FetchOuis.php
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LibreNMS\Util\Http;

class FetchOuis
{
    private const MANUF_URL = 'https://www.wireshark.org/download/automated/data/manuf';

    private const MIN_REFRESH_DAYS = 6;

    private const UPSERT_CHUNK_SIZE = 1000;

    /**
     * @param  bool  $force  ignore the mac_oui.enabled setting and the refresh interval
     */
    public function execute(bool $force = false): TaskResult
    {
        $result = TaskResult::make();

        if (LibrenmsConfig::get('mac_oui.enabled') !== true && ! $force) {
            return $result->warning(trans('commands.maintenance:fetch-ouis.disabled', ['setting' => 'mac_oui.enabled']));
        }

        // This lock is a rate limiter, not a mutex. It is deliberately left
        // held on success so its TTL means "no re-download for six days", and
        // released only on failure so the next run tries again. The overlap
        // middleware on the job guards something different and does not
        // replace it.
        $lock = Cache::lock('vendor_oui_db_refresh', 86400 * self::MIN_REFRESH_DAYS);
        if (! $lock->get() && ! $force) {
            return $result->warning(trans('commands.maintenance:fetch-ouis.recently_fetched'));
        }

        try {
            $ouis = self::parseManuf(Http::client()->get(self::MANUF_URL)->body());

            $count = 0;
            foreach (array_chunk($ouis, self::UPSERT_CHUNK_SIZE) as $chunk) {
                $count += DB::table('vendor_ouis')->upsert($chunk, 'oui');
            }

            return $result->info(trans_choice('commands.maintenance:fetch-ouis.success', $count, ['count' => $count]));
        } catch (\Throwable $e) {
            $lock->release(); // we did not succeed, so try again next time

            return $result->error(trans('commands.maintenance:fetch-ouis.error') . ' ' . $e::class . ': ' . $e->getMessage());
        }
    }

    /**
     * Turn Wireshark's manuf file into rows for the vendor_ouis table.
     *
     * Each line is "oui<TAB>short<TAB>long". Prefixes longer than /24 are
     * written as e.g. 00:1B:C5:00:00:00/36 and are cut to the hex digits the
     * prefix covers, four bits per digit.
     *
     * @return array<int, array{vendor: string, oui: string}>
     */
    public static function parseManuf(string $csv): array
    {
        $ouis = [];

        foreach (explode("\n", rtrim($csv)) as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            [$oui, , $vendor] = str_getcsv($line, "\t"); // index 1 is the short vendor name

            $oui = strtolower(str_replace(':', '', (string) $oui));
            $slash = strpos($oui, '/');

            if ($slash !== false) {
                $prefixLength = (int) substr($oui, $slash + 1);
                $oui = substr($oui, 0, (int) floor($prefixLength / 4));
            }

            $ouis[] = [
                'vendor' => trim((string) $vendor),
                'oui' => trim($oui),
            ];
        }

        return $ouis;
    }
}
