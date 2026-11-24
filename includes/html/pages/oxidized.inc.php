<?php
/*
 * LibreNMS
 *
 * Copyright (c) 2018 Søren Friis Rosiak <sorenrosiak@gmail.com>
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$pagetitle[] = 'Oxidized';
$no_refresh = true;
?>
<div class="col-xs-12">
    <h2>Oxidized</h2>
    <div class="panel-heading">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#list" data-toggle="tab">Node List</a></li>
            <li><a href="#search" data-toggle="tab">Config Search</a></li>
        </ul>
    </div>
    <div class="panel with-nav-tabs panel-default">
        <div class="panel-body">
            <div class="tab-content">
                <div class="tab-pane fade in active" id="list">
<?php
echo Blade::render(<<<'BLADE'
<x-data-table name="oxidized-nodes" mode="local" :rows="$rows" :sort="['hostname' => 'asc']" :panel="false">
    <x-slot:heading>
        <button type="button" class="btn btn-success btn-sm" name="btn-reload-nodes" title="{{ __('Update Oxidized\'s node list from LibreNMS data') }}">
            <i class="fa fa-refresh"></i> {{ __('Reload node list') }}
        </button>
    </x-slot:heading>
    <x-data-table.column field="id" :label="__('ID')" :visible="false" />
    <x-data-table.column field="hostname" :label="__('Hostname')" :hideable="false">
        <template x-if="row.id"><a :href="@js(url('device')) + '/' + encodeURIComponent(row.id)" x-text="row.hostname"></a></template>
        <template x-if="! row.id"><span x-text="row.hostname"></span></template>
    </x-data-table.column>
    <x-data-table.column field="sysname" :label="__('SysName')" :visible="false" />
    <x-data-table.column field="last_status" :label="__('Last Status')">
        <i class="fa fa-square" :class="row.last_status == 'success' ? 'text-success' : 'text-danger'" :title="row.last_status"></i>
    </x-data-table.column>
    <x-data-table.column field="last_update" :label="__('Last Update')" />
    <x-data-table.column field="model" :label="__('Model')" />
    <x-data-table.column field="group" :label="__('Group')" />
    <x-data-table.column field="actions" :label="__('Actions')" :sortable="false" :searchable="false">
        <template x-if="row.id">
            <span>
                <button type="button" class="btn btn-default btn-sm" title="{{ __('Refetch config') }}" x-on:click="refresh_oxidized_node(row.hostname)"><i class="fa fa-refresh"></i></button>
                <a :href="@js(url('device')) + '/' + encodeURIComponent(row.id) + '/config'" title="{{ __('View config') }}"><i class="fa fa-align-justify fa-lg icon-theme"></i></a>
            </span>
        </template>
    </x-data-table.column>
</x-data-table>
BLADE, ['rows' => get_oxidized_nodes()]);
?>
                </div>
                <div class="tab-pane fade" id="search">
                    <form class="form-horizontal" action="" method="post">
                        <?php echo csrf_field() ?>
                        <br/>
                        <div class="input-group">
                            <input type="text" class="form-control" id="input-parameter"
                                   placeholder="service password-encryption etc. (Regex)">
                            <span class="input-group-btn">
                                <button type="submit" name="btn-search" id="btn-search" class="btn btn-primary">Search</button>
                            </span>
                        </div>
                    </form>
                    <br/>
                    <div id="search-output" class="alert alert-success" style="display: none;"></div>
                    <br/>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $("[name='btn-search']").on('click', function (event) {
        event.preventDefault();
        var $this = $(this);
        var search_in_conf_textbox = $("#input-parameter").val();
        $.ajax({
            type: 'POST',
            url: 'ajax_form.php',
            data: {
                type: "search-oxidized-config",
                search_in_conf_textbox: search_in_conf_textbox
            },
            dataType: "json",
            success: function (data) {
                $('#search-output').empty();
                $("#search-output").show();
                if (data.output) {
                    $('#search-output').append('<p>Config appears on the following device(s):</p>');
                    $.each(data.output, function (row, value) {
                        if (value['dev_id']) {
                            $('#search-output').append('<p><a href="<?= url('device') ?>/' + value['dev_id'] + '/config">' + value['full_name'] + '</p>');
                        } else {
                            $('#search-output').append('<p>' + value['full_name'] + '</p>');
                        }
                        });
                }
            },
            error: function () {
                toastr.error('Error');
            }
        });
    });
    $(document).on('click', "[name='btn-reload-nodes']", function (event) {
        $.ajax({
            type: 'POST',
            url: 'ajax_form.php',
            data: { type: "reload-oxidized-nodes-list" },
            dataType: "json",
            success: function (data) {
                if(data['status'] == 'ok') {
                    toastr.success(data['message']);
                } else {
                    toastr.error(data['message']);
                }
            },
            error:function(){
                toastr.error('An error occured while reloading the Oxidized nodes list');
            }
        });
    });
</script>
