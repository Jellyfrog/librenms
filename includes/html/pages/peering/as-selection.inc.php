<?php
/**
 * LibreNMS PeeringDB Integration
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
 *
 * @copyright  2017 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */
$cache_date = dbFetchCell('SELECT FROM_UNIXTIME(`timestamp`) FROM `pdb_ix` ORDER BY `timestamp` ASC LIMIT 1');

echo "<div class='alert alert-info' role='alert'>Cached date: $cache_date</div>";

?>

<?php
echo Blade::render(<<<'BLADE'
<div class="tw:max-w-xl">
    <x-data-table name="peering-asn" :url="route('table.peering.asns')" :sort="['bgpLocalAs' => 'asc']" :column-toggle="false" :title="__('ASN')">
        <x-data-table.column field="bgpLocalAs" :label="__('ASN')" />
        <x-data-table.column field="asname" :label="__('AS Name')" :sortable="false" />
        <x-data-table.column field="action" :sortable="false">
            <a class="btn btn-sm btn-primary" :href="row.url" role="button">{{ __('Show connected IXes') }}</a>
        </x-data-table.column>
    </x-data-table>
</div>
BLADE);
