{{--
    Row behaviour of x-alerts-table and x-alert-log-table: incident details, ack, notes and details buttons.
    Delegated handlers, registered once, so they work for rows rendered later and for tables in dashboard widgets.
--}}
<style>
    .alert-table .incident-toggle { visibility: hidden; cursor: pointer; }
    .alert-table tr:hover .incident-toggle, .alert-table .incident-toggle.fa-minus { visibility: visible; }
    .alert-table[data-expand-on-row-click] tbody tr { cursor: pointer; }
</style>
<script>
    (function () {
        window.LibreNMS = window.LibreNMS || {};
        if (window.LibreNMS.alertTableHandlers) {
            return;
        }
        window.LibreNMS.alertTableHandlers = true;

        function toggleIncident($toggle) {
            $($toggle.data('target')).collapse('toggle');
            $toggle.toggleClass('fa-plus fa-minus');
        }

        $(document)
            .on('click', '.alert-table .incident-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation(); // bootstrap's collapse data-api would toggle it a second time
                toggleIncident($(this));
            })
            .on('click', '.alert-table[data-expand-on-row-click] tbody tr', function (e) {
                if ($(e.target).closest('a, button, .incident-toggle').length) {
                    return;
                }
                const $toggle = $(this).find('.incident-toggle.fa-plus');
                if ($toggle.length) {
                    toggleIncident($toggle);
                }
            })
            .on('click', '.alert-table .command-ack-alert', function (e) {
                e.preventDefault();
                $('#ack_alert_id').val($(this).data('alert_id'));
                $('#ack_alert_state').val($(this).data('alert_state'));
                $('#ack_msg').val('');
                $('#alert_ack_modal').modal('show');
            })
            .on('click', '.alert-table .command-alert-note', function (e) {
                e.preventDefault();
                $('#alert_id').val($(this).data('alert_id'));
                $('#alert_notes_modal').modal('show');
            })
            .on('click', '.alert-table .command-alert-details', function (e) {
                e.preventDefault();
                e.stopPropagation(); // don't also expand the row
                $('#alert_log_id').val($(this).data('alert_log_id'));
                $('#alert_details_modal').modal('show');
            });
    })();
</script>
