<?php

// the table sorts, searches and paginates the components in the browser, the checkboxes edit the rows and Save posts them all
$components = (new LibreNMS\Component())->getComponents($device['device_id'])[$device['device_id']] ?? [];
$rows = [];
foreach ($components as $id => $component) {
    $rows[] = [
        'id' => $id,
        'type' => $component['type'],
        'label' => $component['label'],
        'status' => (int) $component['status'],
        'disabled' => (bool) $component['disabled'],
        'ignore' => (bool) $component['ignore'],
    ];
}

echo Blade::render(<<<'BLADE'
<x-data-table name="components" mode="local" :rows="$rows" :sort="['id' => 'asc']" :panel="false"
              :config="['device' => $deviceId, 'formUrl' => url('ajax_form.php')]">
    <x-slot:heading>
        {{-- the bulk buttons change every component, not only this page --}}
        <button type="button" class="btn btn-success btn-sm" :disabled="isPending('save')" title="{{ __('Save current component disable/ignore settings') }}"
                x-on:click="pending.save = true; $.post(config.formUrl, {
                    _token: document.querySelector('meta[name=csrf-token]').content, type: 'component', component: 'yes', device: config.device,
                    ...Object.fromEntries(allRows().flatMap((row) => [...(row.disabled ? [['dis_' + row.id, 'on']] : []), ...(row.ignore ? [['ign_' + row.id, 'on']] : [])])),
                }, null, 'json').done((data) => data.status == 'ok' ? toastr.success(data.message) : toastr.error(data.message))
                  .fail(() => toastr.error(@js(__('Failed to save')))).always(() => delete pending.save)">{{ __('Save') }}</button>
        <button type="button" class="btn btn-danger btn-sm" title="{{ __('Reset to the saved settings') }}" x-on:click="window.location.reload()">{{ __('Reset') }}</button>
        <span class="tw:ml-2">{{ __('Ignore alert tag') }}:</span>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Disable alerting on all currently warning components') }}" x-on:click="allRows().filter((row) => row.status == 1).forEach((row) => row.ignore = ! row.ignore)">{{ __('Warning') }}</button>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Disable alerting on all currently critical components') }}" x-on:click="allRows().filter((row) => row.status == 2).forEach((row) => row.ignore = ! row.ignore)">{{ __('Critical') }}</button>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Toggle alert tag for all components') }}" x-on:click="allRows().forEach((row) => row.ignore = ! row.ignore)">{{ __('Toggle') }}</button>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Disable alert tag on all components') }}" x-on:click="allRows().forEach((row) => row.ignore = true)">{{ __('Select All') }}</button>
        <span class="tw:ml-2">{{ __('Disable') }}:</span>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Toggle polling for all components') }}" x-on:click="allRows().forEach((row) => row.disabled = ! row.disabled)">{{ __('Toggle') }}</button>
        <button type="button" class="btn btn-default btn-sm" title="{{ __('Disable polling on all components') }}" x-on:click="allRows().forEach((row) => row.disabled = true)">{{ __('Select All') }}</button>
    </x-slot:heading>
    <x-data-table.column field="id" :label="__('ID')" />
    <x-data-table.column field="type" :label="__('Type')" />
    <x-data-table.column field="label" :label="__('Label')" />
    <x-data-table.column field="status" :label="__('Status')">
        <span :class="{0: 'green', 1: 'grey'}[row.status] ?? 'red'" x-text="{0: @js(__('Ok')), 1: @js(__('Warning'))}[row.status] ?? @js(__('Critical'))"></span>
    </x-data-table.column>
    <x-data-table.column field="disabled" :label="__('Disable')" :sortable="false">
        <input type="checkbox" x-model="row.disabled" :aria-label="@js(__('Disable')) + ' ' + row.label">
    </x-data-table.column>
    <x-data-table.column field="ignore" :label="__('Ignore alert tag')" :sortable="false">
        <input type="checkbox" x-model="row.ignore" :aria-label="@js(__('Ignore alert tag')) + ' ' + row.label">
    </x-data-table.column>
</x-data-table>
BLADE, ['rows' => $rows, 'deviceId' => $device['device_id']]);
