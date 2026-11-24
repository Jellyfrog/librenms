<x-alert-log-table :name="$filterName"
                   :filter-name="$filterName"
                   :filter-fields="$filterFields"
                   :filter="$filter"
                   :params="['device_id' => $deviceId ?? null]"
                   :title="$title ?? null" />

@include('alerts.modals.details')
