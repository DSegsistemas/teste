<script>
    var ProposalServerParams1;
    $(function(){
        'use strict';

        ProposalServerParams1 = {
            "appointment_type_id": "[name='appointment_type_id']",
            "inspection_form_detail_id": "[name='inspection_form_detail_id']",

        };

    });
    function add_part(inspection_form_detail_id){
        "use strict";
        $('input[name="inspection_form_detail_id"]').val(inspection_form_detail_id);

        $('#part_detail').modal('show');

        $('.table-part_table').DataTable().destroy();
        var part_table = $('table.table-part_table');
        var _table_api = initDataTable(part_table, admin_url+'workshop/part_table', true, '', ProposalServerParams1);

        // hidden column ID; Option for Repair Job
        var hidden_columns = [0,8];
        $('.table-part_table').DataTable().columns(hidden_columns).visible(false, false);
    }

    function add_part_to_table(row, part_id, inspection_form_detail_id) {
        "use strict";
        var data = part_get_item_preview_values(row, part_id, inspection_form_detail_id);

        if (data.quantity == ""  ) {
            alert_float('warning', '<?php echo _l('wshop_please_select_quantity') ?>');
            return;
        }

        var table_row = '';
        console.log('lastAddedPartItemKey', lastAddedPartItemKey);
        var item_key = lastAddedPartItemKey ? lastAddedPartItemKey += 1 : $("body").find('.part-items-table tbody .item').length + 1;
        lastAddedPartItemKey = item_key;
        console.log('lastAddedPartItemKey2', lastAddedPartItemKey);
        $("box-loading").append('<div class="dt-loader"></div>');
      //show box loading
        var html = '';
        html += '<div class="Box">';
        html += '<span>';
        html += '<span></span>';
        html += '</span>';
        html += '</div>';
        $('#box-loading').html(html);

        part_get_item_row_template('newpartitems[' + item_key + ']', 
            data.part_id, data.inspection_id,data.inspection_form_id, data.inspection_form_detail_id, data.quantity, item_key).done(function(output){
                table_row += output;
                $('.part-item table.part-items-table.items tbody').prepend(table_row);

                setTimeout(function () {
                    part_calculate_total();
                }, 15);
                init_selectpicker();
                init_datepicker();
                part_reorder_items('.part-item');
                $('body').find('#items-warning').remove();
                $("body").find('.dt-loader').remove();
                alert_float('success', '<?php echo _l('wshop_select_parts'); ?>');
                $(row).parents('tr').remove();
                $('#box-loading').html('');

                return true;
            });
            return false;

        }
    

</script>