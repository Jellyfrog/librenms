<?php

use App\Models\AlertRule;
use App\Models\AlertTemplate;
use App\Models\AlertTemplateMap;
use Illuminate\Support\Facades\Gate as Gate;

$no_refresh = true;

include 'includes/html/modal/alert_template.inc.php';
include 'includes/html/modal/delete_alert_template.inc.php';
?>
<?php
$rows = [];
$default_tplid = 0;
foreach (AlertTemplate::with('alert_rules')->select(['id', 'name', 'template'])->get() as $template) {
    // the default template is used by the rules without a template, it is shown as #0
    $default = $template->name == 'Default Alert Template';
    if ($default) {
        $default_tplid = $template->id;
    }
    $rules = $default
        ? AlertRule::whereNotIn('id', AlertTemplateMap::pluck('alert_rule_id'))->select('id', 'name')->orderBy('name')->get()
        : $template->alert_rules;

    $rows[] = [
        'id' => $default ? 0 : $template->id,
        'templatename' => $template->name,
        'alert_rules' => $rules->map(fn ($rule) => ['id' => $rule->id, 'name' => $rule->name])->values(),
        'old_template' => str_contains((string) $template->template, '{/if}'),
    ];
}

echo Blade::render(<<<'BLADE'
<x-data-table name="alert-templates" mode="local" :rows="$rows" :sort="['id' => 'asc']" :panel="false"
              x-on:alert-template:saved.window="let row = allRows().find((item) => item.id == $event.detail.id); row ? Object.assign(row, $event.detail) : addRow($event.detail)"
              x-on:alert-template:deleted.window="removeRow($event.detail)">
    @can('create', \App\Models\AlertTemplate::class)
        <x-slot:heading>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#alert-template" data-template_id="">{{ __('Create new alert template') }}</button>
        </x-slot:heading>
    @endcan
    <x-data-table.column field="id" label="#" :searchable="false" />
    <x-data-table.column field="templatename" :label="__('Name')" />
    <x-data-table.column field="alert_rules" :label="__('Alert Rules')" :sortable="false" :searchable="false">
        <template x-for="rule in row.alert_rules" :key="rule.id"><div x-text="rule.name"></div></template>
    </x-data-table.column>
    <x-data-table.column field="actions" :label="__('Action')" :sortable="false" :searchable="false" class="tw:whitespace-nowrap">
        {{-- legacy templates need converting: edit the template, click convert, then save --}}
        <button x-show="row.old_template" type="button" class="btn btn-xs btn-warning" title="{{ __('This is a legacy template and needs converting, please edit this template and click convert then save') }}"><i class="fa fa-exclamation-triangle"></i></button>
        <button type="button" class="btn btn-xs btn-primary" name="edit-alert-template"
                x-on:click="$('#default_template').val(row.id == 0 ? '1' : '0'); $('#template_id').val(row.id == 0 ? @js($defaultTemplateId) : row.id); $('#alert-template').modal('show')">
            <i class="fa fa-pencil" aria-hidden="true"></i>
        </button>
        <button type="button" class="btn btn-xs btn-danger" name="delete-alert-template" :disabled="row.id == 0"
                x-on:click="$('#template_id').val(row.id); $('#confirm-delete-alert-template').modal('show')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
        </button>
    </x-data-table.column>
</x-data-table>
BLADE, ['rows' => $rows, 'defaultTemplateId' => $default_tplid]);
