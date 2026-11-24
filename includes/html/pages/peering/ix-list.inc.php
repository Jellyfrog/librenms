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
echo Blade::render(<<<'BLADE'
<div class="tw:max-w-xl">
    <x-data-table name="peering-ix" :url="route('table.peering.exchanges')" :params="['asn' => $asn]" :sort="['exchange' => 'asc']" :column-toggle="false" :title="__('Exchanges')">
        <x-data-table.column field="exchange" :label="__('Exchange')" />
        <x-data-table.column field="action" :sortable="false">
            <a class="btn btn-sm btn-primary" :href="row.peers_url" role="button">{{ __('Show Peers') }}</a>
        </x-data-table.column>
        <x-data-table.column field="links" :sortable="false">
            <a :href="row.pdb_url" target="_blank" rel="noopener"><i class="fa fa-database"></i></a>
        </x-data-table.column>
    </x-data-table>
</div>
BLADE, ['asn' => (int) ($vars['bgpLocalAs'] ?? 0)]);
