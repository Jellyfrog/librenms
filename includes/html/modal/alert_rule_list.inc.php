<?php
/**
 * alert_rule_list.inc.php
 *
 * LibreNMS alert_rule_list modal
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
 * @copyright  2020 Thomas Berberich
 * @author     Thomas Berberich <sourcehhdoctor@gmail.com>
 */

use LibreNMS\Alerting\QueryBuilderParser;

?>

<div class="modal fade" id="search_alert_rule_modal" tabindex="-1" role="dialog" aria-labelledby="search_alert_rule_list" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h5 class="modal-title" id="search_alert_rule_list">Running Alert rules</h5>
            </div>
            <div class="modal-body">
                <?php
                $rows = [];
                foreach (\App\Models\AlertRule::orderBy('name')->get() as $rule) {
                    $rows[] = [
                        'id' => $rule->id,
                        'alert_name' => strip_tags((string) $rule->name),
                        'alert_rule' => strip_tags((string) QueryBuilderParser::fromJson($rule->builder)->toSql(false)),
                        'alert_severity' => $rule->severity,
                    ];
                }
                echo Blade::render(<<<'BLADE'
                <x-data-table name="alert-rule-list" mode="local" :rows="$rows" :sort="['alert_name' => 'asc']" :column-toggle="false" :panel="false">
                    <x-data-table.column field="alert_name" :label="__('Name')" width="200px" />
                    <x-data-table.column field="alert_rule" :label="__('Rule')" class="tw:italic" />
                    <x-data-table.column field="alert_severity" :label="__('Severity')" />
                    <x-data-table.column field="alert_action" :sortable="false" :searchable="false">
                        <button type="button" class="btn btn-sm btn-primary" x-on:click="selectAlertRuleFromList(row.id)">{{ __('Select') }}</button>
                    </x-data-table.column>
                </x-data-table>
                BLADE, ['rows' => $rows]);
                ?>
                <script>
                    function selectAlertRuleFromList(alert_rule_id) {
                        $.ajax({
                            type: "GET",
                            url: route('alert-rule-template.rule', alert_rule_id),
                            dataType: "json",
                            success: function (data) {
                                if (data.status == 'ok') {
                                    $("#search_alert_rule_modal").one('hidden.bs.modal', function(event) {
                                        $('#create-alert').modal('show');
                                        loadRule(data);
                                    });
                                    $("#search_alert_rule_modal").modal('hide');
                                } else {
                                    toastr.error(data.message);
                                }
                            },
                            error: function () {
                                toastr.error('Failed to process template');
                            }
                        });
                    }
                </script>
            </div>
        </div>
    </div>
</div>

<script>
    $("#search_alert_rule_modal").on('hidden.bs.modal', function(e) {
        $("#alert_rule_id").val('');
        $("#rule_suggest").val('');
        $("#rule_display").html('');

        var $parent = $('#create-alert');
        if ($parent.hasClass('in')) {
            $('body').addClass('modal-open');
            $parent.modal('handleUpdate');
        }
    });
</script>
