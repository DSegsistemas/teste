<script type="text/javascript">
    $(function(){
        'use strict';

        var appointment_type_params = {
        };
        var appointment_type_table = $('table.table-appointment_type_table');
        var _table_api = initDataTable(appointment_type_table, admin_url+'workshop/appointment_type_table', [0], [0], appointment_type_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-appointment_type_table').DataTable().columns(hidden_columns).visible(false, false);
    });

    function appointment_type_modal(appointment_type_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_appointment_type_modal'); ?>", {
          appointment_type_id: appointment_type_id,
      }, function() {
          $("body").find('#appointment_typeModal').modal({ show: true, backdrop: 'static' });
          $('.selectpicker').selectpicker("refresh");
      });

    }
</script>