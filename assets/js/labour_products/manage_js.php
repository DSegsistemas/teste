<script type="text/javascript">
    $(function(){
        'use strict';

        var labour_product_params = {
            "category_filter": "[name='category_filter']",
            "status_filter": "[name='status_filter']",
            "assign_staff_filter": "[name='assign_staff_filter']",
        };
        var labour_product_table = $('table.table-labour_product_table');
        var _table_api = initDataTable(labour_product_table, admin_url+'workshop/labour_product_table', [0], [0], labour_product_params, ['1', 'asc']);
        var hidden_columns = [0, 8];
        $('.table-labour_product_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(labour_product_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-labour_product_table').DataTable().ajax.reload();
            });
        });

    });

    function labour_product_modal(labour_product_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_labour_product_modal'); ?>", {
          labour_product_id: labour_product_id,
      }, function() {
          $("body").find('#labour_productModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_labour_product(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_labour_product/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-labour_product_table').DataTable().ajax.reload();
                }
            });
        }
    }



</script>