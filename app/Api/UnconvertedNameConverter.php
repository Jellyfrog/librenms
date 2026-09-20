<?php

/**
 * UnconvertedNameConverter.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 */

namespace App\Api;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * A name converter that leaves names exactly as they are.
 *
 * config/api-platform.php sets name_converter to null, meaning the database
 * column names are the API field names. api-platform/laravel honours that when
 * serializing, but builds its query parameter metadata with a
 * CamelCaseToSnakeCaseNameConverter regardless, which rewrites a parameter's
 * property to snake_case and then queries that: a filter or sort on a column
 * with a capital in it (sysName, ifOperStatus) would look for sys_name and
 * silently match nothing. ApiPlatformServiceProvider binds this in its place.
 */
class UnconvertedNameConverter extends CamelCaseToSnakeCaseNameConverter
{
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return $propertyName;
    }

    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return $propertyName;
    }
}
