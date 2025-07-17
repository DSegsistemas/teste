<script type="text/javascript">
    $(function(){
        'use strict';
        // for repair job
        var repair_job_params = {
            "client_filter": "[name='client_filter']",
            "appointment_type_filter": "[name='appointment_type_filter']",
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "device_filter": "[name='device_filter']",
        };
        var repair_job_table = $('table.table-repair_job_table');
        var _table_api = initDataTable(repair_job_table, admin_url+'workshop/repair_job_table', [0], [0], repair_job_params, ['0', 'desc']);
        var hidden_columns = [0];
        $('.table-repair_job_table').DataTable().columns(hidden_columns).visible(false, false);

        // for inspection
        var inspection_params = {
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "client_filter": "[name='client_filter']",
            "inspection_type_filter": "[name='inspection_type_filter']",
            "device_filter": "[name='device_filter']",
            "status_filter": "[name='status_filter']",
            "repair_job_filter": "[name='repair_job_filter']",
            "device_filter": "[name='device_filter']",
        };

        var inspection_table = $('table.table-inspection_table');
        var _table_api = initDataTable(inspection_table, admin_url+'workshop/inspection_table', [0], [0], inspection_params, ['0', 'desc']);
        var hidden_columns = [0];
        $('.table-inspection_table').DataTable().columns(hidden_columns).visible(false, false);

        // for workshop
         var device_params = {
            "repair_job_filter": "[name='repair_job_filter']",
            "report_type_filter": "[name='report_type_filter']",
            "report_status_filter": "[name='report_status_filter']",
            "device_filter": "[name='device_filter']",
        };
        var workshop_table = $('table.table-workshop_table');
        var _table_api = initDataTable(workshop_table, admin_url+'workshop/workshop_table', [0], [0], device_params, ['0', 'asc']);
        var hidden_columns = [0];
        $('.table-workshop_table').DataTable().columns(hidden_columns).visible(false, false);

    });

    function transfer_ownership_modal(device_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_transfer_ownership_modal'); ?>", {
            device_id: device_id,
        }, function() {
            $("body").find('#transfer_ownershipModal').modal({ show: true, backdrop: 'static' });
            init_selectpicker();
        });

    }
</script>