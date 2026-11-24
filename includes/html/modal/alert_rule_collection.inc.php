<?php
/**
 * search_rule_collection.inc.php
 *
 * LibreNMS search_rule_collection modal
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */

use LibreNMS\Alerting\QueryBuilderParser;

?>

<div class="modal fade" id="search_rule_modal" tabindex="-1" role="dialog" aria-labelledby="search_rule" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h5 class="modal-title" id="search_rule">Alert rule collection</h5>
            </div>
            <div class="modal-body">
                <?php
                // the collection is a list, its position is the template id
                $rows = [];
                foreach (array_values(get_rules_from_json()) as $index => $rule) {
                    $rows[] = [
                        'id' => $index,
                        'name' => $rule['name'],
                        'rule' => QueryBuilderParser::fromJson($rule['builder'])->toSql(false),
                    ];
                }
                echo Blade::render(<<<'BLADE'
                <x-data-table name="alert-rule-collection" mode="local" :rows="$rows" :sort="['name' => 'asc']" :column-toggle="false" :panel="false">
                    <x-data-table.column field="name" :label="__('Name')" width="200px" />
                    <x-data-table.column field="rule" :label="__('Rule')" />
                    <x-data-table.column field="action" :sortable="false" :searchable="false">
                        <button type="button" class="btn btn-sm btn-primary" x-on:click="selectRuleFromCollection(row.id)">{{ __('Select') }}</button>
                    </x-data-table.column>
                </x-data-table>
                BLADE, ['rows' => $rows]);
                ?>
                <script>
                    function selectRuleFromCollection(template_rule_id) {
                        $.ajax({
                            type: "GET",
                            url: route('alert-rule-template', template_rule_id),
                            dataType: "json",
                            success: function (data) {
                                if (data.status == 'ok') {
                                    $("#search_rule_modal").one('hidden.bs.modal', function(event) {
                                        $('#create-alert').modal('show');
                                        loadRule(data);
                                    });
                                    $("#search_rule_modal").modal('hide');
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
    $("#search_rule_modal").on('hidden.bs.modal', function(e) {
        $("#template_rule_id").val('');
        $("#rule_suggest").val('');
        $("#rule_display").html('');

        var $parent = $('#create-alert');
        if ($parent.hasClass('in')) {
            $('body').addClass('modal-open');
            $parent.modal('handleUpdate');
        }
    });
</script>
