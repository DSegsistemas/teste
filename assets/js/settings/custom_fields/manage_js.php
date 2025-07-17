<script type="text/javascript">
    var fieldset_id;
    $(function(){
        'use strict';
        fieldset_id = $('input[name="fieldset_id"]').val();
        var customfield_params = {
            "fieldset_id": "input[name='fieldset_id']",
        };
        var customfield_table = $('table.table-customfield_table');
        var _table_api = initDataTable(customfield_table, admin_url+'workshop/custom_field_table', [0], [0], customfield_params, ['5', 'asc']);
        var hidden_columns = [0];
        $('.table-customfield_table').DataTable().columns(hidden_columns).visible(false, false);

    });
    
    function custom_field_modal(custom_field_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_custom_field_modal'); ?>", {
          custom_field_id: custom_field_id,
          fieldset_id: fieldset_id,
      }, function() {
          $("body").find('#custom_fieldModal').modal({ show: true, backdrop: 'static' });
          $('.selectpicker').selectpicker("refresh");
      });

    }

</script>