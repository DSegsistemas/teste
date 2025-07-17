<script>
    var ProposalServerParams1;
    $(function(){
        'use strict';

        ProposalServerParams1 = {
            "appointment_type_id": "[name='appointment_type_id']",
            "inspection_form_detail_id": "[name='inspection_form_detail_id']",
        };
    });

    function add_labour_product(inspection_form_detail_id){
        "use strict";

        $('input[name="inspection_form_detail_id"]').val(inspection_form_detail_id);
        $('#show_detail').modal('show');

        $('.table-labour_product_table').DataTable().destroy();
        var labour_product_table = $('table.table-labour_product_table');
        var _table_api = initDataTable(labour_product_table, admin_url+'workshop/labour_product_table', true, '', ProposalServerParams1);

        // hidden column ID; Option for Repair Job
        var hidden_columns = [0,8,9,10,12];
        $('.table-labour_product_table').DataTable().columns(hidden_columns).visible(false, false);
    }

    function add_labour_product_to_table(row, labour_product_id, inspection_form_detail_id) {
        "use strict";
        
        var data = labour_product_get_item_preview_values(row, labour_product_id, inspection_form_detail_id);

        if (data.estimated_hours == ""  ) {
            alert_float('warning', '<?php echo _l('wshop_please_select_estimated_time') ?>');
            return;
        }

        var table_row = '';
        var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.labour_product-items-table tbody .item').length + 1;
        lastAddedItemKey = item_key;

        var part_item_key = lastAddedPartItemKey ? lastAddedPartItemKey += 1 : $("body").find('.part-items-table tbody .item').length + 1;
        lastAddedPartItemKey = part_item_key;
        
        $("box-loading").append('<div class="dt-loader"></div>');
      //show box loading
        var html = '';
        html += '<div class="Box">';
        html += '<span>';
        html += '<span></span>';
        html += '</span>';
        html += '</div>';
        $('#box-loading').html(html);

        labour_product_get_item_row_template('newlabouritems[' + item_key + ']', 
            data.labour_product_id, data.inspection_id,data.inspection_form_id, data.inspection_form_detail_id, data.estimated_hours, item_key, part_item_key).done(function(output){
                output = JSON.parse(output);

                table_row += output.labour_product_row_template;

                $('.labour_product-item table.labour_product-items-table.items tbody').prepend(table_row);
                $('.part-item table.part-items-table.items tbody').prepend(output.part_row_template);
                lastAddedPartItemKey = output.part_item_key

                setTimeout(function () {
                    labour_product_calculate_total();
                    part_calculate_total();

                }, 15);
                init_selectpicker();
                init_datepicker();
                labour_product_reorder_items('.labour_product-item');
                $('body').find('#items-warning').remove();
                $("body").find('.dt-loader').remove();
                alert_float('success', '<?php echo _l('wshop_add_labour_product_success'); ?>');
                $(row).parents('tr').remove();
                $('#box-loading').html('');

                return true;
            });
            return false;

        }

</script>
