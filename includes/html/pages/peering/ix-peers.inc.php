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
<div class="tw:max-w-3xl">
    <x-data-table name="peering-peers" :url="route('table.peering.peers')" :params="['ixid' => $ixid]" :sort="['remote_asn' => 'asc']" :column-toggle="false" :title="__('Peers')">
        <x-slot:heading>
            <select class="form-control input-sm tw:w-auto" aria-label="{{ __('Connected') }}" x-on:change="config.params.status = $event.target.value; changed(true)">
                <option value="all">{{ __('All') }}</option>
                <option value="connected">{{ __('Connected') }}</option>
                <option value="unconnected">{{ __('Not connected') }}</option>
            </select>
        </x-slot:heading>
        <x-data-table.column field="remote_asn" :label="__('ASN')" />
        <x-data-table.column field="remote_ipaddr4" :label="__('IP')" />
        <x-data-table.column field="peer" :label="__('Peer')" :sortable="false" />
        <x-data-table.column field="connected" :label="__('Connected')" :sortable="false">
            <i class="fa fa-2x" :class="row.connected ? 'fa-check text-success' : 'fa-times text-default'"></i>
        </x-data-table.column>
        <x-data-table.column field="links" :sortable="false">
            <a :href="row.pdb_url" target="_blank" rel="noopener"><i class="fa fa-database"></i></a>
        </x-data-table.column>
    </x-data-table>
</div>
BLADE, ['ixid' => (int) ($vars['ixid'] ?? 0)]);
