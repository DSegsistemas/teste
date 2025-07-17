<script type="text/javascript">

    $(function(){
        'use strict';

        var material_params = {
            "labour_product_id": "[name='labour_product_id']",
        };
        var material_table = $('table.table-material_table');
        var _table_api = initDataTable(material_table, admin_url+'workshop/material_table', [0], [0], material_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-material_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(material_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-material_table').DataTable().ajax.reload();
            });
        });

    });

    function material_modal(material_id, labour_product_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_material_modal'); ?>", {
          material_id: material_id,
          labour_product_id: labour_product_id,
      }, function() {
          $("body").find('#materialModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_material(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_material/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-material_table').DataTable().ajax.reload();
                }
            });
        }
    }
</script>