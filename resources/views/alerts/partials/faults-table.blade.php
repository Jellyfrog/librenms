<x-alerts-table :name="$filterName"
                :filter-name="$filterName"
                :filter-fields="$filterFields"
                :filter="$filter"
                :params="['alert_id' => $alertId ?? null, 'device_id' => $deviceId ?? null]"
                :location="! isset($deviceId)"
                :title="$title ?? null" />

@include('alerts.modals.details')
@include('alerts.modals.notes')
@include('alerts.modals.ack')
