<script type="text/javascript">
    $(function(){
        'use strict';

        var manufacturer_params = {
        };
        var manufacturer_table = $('table.table-manufacturer_table');
        var _table_api = initDataTable(manufacturer_table, admin_url+'workshop/manufacturer_table', [0], [0], manufacturer_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-manufacturer_table').DataTable().columns(hidden_columns).visible(false, false);
    });

    function manufacturer_modal(manufacturer_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_manufacturer_modal'); ?>", {
          manufacturer_id: manufacturer_id,
      }, function() {
          $("body").find('#manufacturerModal').modal({ show: true, backdrop: 'static' });
      });

    }
</script>