<?php

/**
 * SqliteMysqlFunctions.php
 *
 * Adds the MySQL functions LibreNMS queries use to SQLite test connections
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

namespace LibreNMS\Tests;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use PDO;

class SqliteMysqlFunctions
{
    private const DATE_FORMAT = [
        '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n', '%d' => 'd', '%e' => 'j',
        '%H' => 'H', '%k' => 'G', '%h' => 'h', '%I' => 'h', '%l' => 'g', '%i' => 'i',
        '%s' => 's', '%S' => 's', '%p' => 'A', '%M' => 'F', '%b' => 'M', '%W' => 'l',
        '%a' => 'D', '%T' => 'H:i:s', '%%' => '%',
    ];

    /**
     * Register the functions on every SQLite connection created from now on
     */
    public static function register(): void
    {
        Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
            $pdo = $pdo instanceof Closure
                ? fn () => self::addFunctions($pdo())
                : self::addFunctions($pdo);

            return new SQLiteConnection($pdo, $database, $prefix, $config);
        });
    }

    public static function addFunctions(PDO $pdo): PDO
    {
        $functions = [
            'IF' => [fn ($condition, $true, $false) => $condition ? $true : $false, 3],
            'UNIX_TIMESTAMP' => [fn ($date = null) => match (true) {
                func_num_args() === 0 => time(),
                $date === null => null,
                default => strtotime((string) $date) ?: null,
            }, -1],
            'DATE_FORMAT' => [fn ($date, $format) => $date === null || $format === null ? null : date(self::phpDateFormat((string) $format), (int) strtotime((string) $date)), 2],
            'INET6_ATON' => [fn ($ip) => $ip === null ? null : (@inet_pton((string) $ip) ?: null), 1],
            'INET6_NTOA' => [fn ($ip) => $ip === null ? null : (@inet_ntop((string) $ip) ?: null), 1],
        ];

        foreach ($functions as $name => [$callback, $args]) {
            if ($pdo instanceof \Pdo\Sqlite) {
                $pdo->createFunction($name, $callback, $args);
            } else {
                $pdo->sqliteCreateFunction($name, $callback, $args);
            }
        }

        return $pdo;
    }

    /**
     * Convert a MySQL DATE_FORMAT() format to a date() format
     */
    private static function phpDateFormat(string $format): string
    {
        return (string) preg_replace_callback('/%(.)|[a-zA-Z\\\\]/', fn (array $match) => isset($match[1])
            ? (self::DATE_FORMAT['%' . $match[1]] ?? '\\' . $match[1])
            : '\\' . $match[0], $format);
    }
}
