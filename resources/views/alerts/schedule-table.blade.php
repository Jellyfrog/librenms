{{-- alert schedules (maintenance), the edit and delete modals are includes/html/modal/alert_schedule.inc.php and remove_alert_schedule.inc.php --}}
<x-data-table name="alert-schedule"
              :url="route('table.alert-schedule')"
              :sort="['start' => 'desc']"
              :title="__('Alert Schedule')">
    @can('create', \App\Models\AlertSchedule::class)
        <x-slot:heading>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#schedule-maintenance">{{ __('Schedule maintenance') }}</button>
        </x-slot:heading>
    @endcan
    <x-data-table.column field="title" :label="__('Title')" :hideable="false" />
    <x-data-table.column field="behavior" :label="__('Behavior')" />
    <x-data-table.column field="recurring" :label="__('Recurring')" />
    <x-data-table.column field="start" :label="__('Start (no recurring)')" />
    <x-data-table.column field="end" :label="__('End (no recurring)')" />
    <x-data-table.column field="start_recurring_dt" :label="__('Start recurring dt')" />
    <x-data-table.column field="end_recurring_dt" :label="__('End recurring dt')" />
    <x-data-table.column field="start_recurring_hr" :label="__('Start recurring hr')" />
    <x-data-table.column field="end_recurring_hr" :label="__('End recurring hr')" />
    <x-data-table.column field="recurring_day" :label="__('Recurring on days')" :sortable="false" />
    <x-data-table.column field="actions" :label="__('Actions')" :sortable="false" class="tw:whitespace-nowrap">
        @can('update', \App\Models\AlertSchedule::class)
            <button type="button" class="btn btn-xs btn-primary" x-on:click="$('#schedule_id').val(row.id); $('#schedule-maintenance').modal('show')"><span class="fa fa-pencil"></span></button>
        @endcan
        @can('delete', \App\Models\AlertSchedule::class)
            <button type="button" class="btn btn-xs btn-danger" x-on:click="$('#del_schedule_id').val(row.id); $('#delete-maintenance').modal('show')"><span class="fa fa-trash-o"></span></button>
        @endcan
    </x-data-table.column>
    <x-data-table.column field="status" :label="__('Status')">
        <span class="label" :class="{1: 'label-danger', 2: 'label-success', 0: 'label-warning'}[row.status]"
              x-text="{1: @js(__('Lapsed')), 2: @js(__('Current')), 0: @js(__('Set'))}[row.status]"></span>
    </x-data-table.column>
</x-data-table>
