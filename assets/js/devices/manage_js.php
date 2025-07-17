<script type="text/javascript">
    $(function(){
        'use strict';

        var device_params = {
            "client_filter": "[name='client_filter']",
            "devices_filter": "[name='devices_filter']",
            "model_filter": "[name='model_filter']",
            "warranty_status_filter": "[name='warranty_status_filter']",
        };
        var device_table = $('table.table-device_table');
        var _table_api = initDataTable(device_table, admin_url+'workshop/device_table', [0], [0], device_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-device_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(device_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-device_table').DataTable().ajax.reload();
            });
        });

    });

    function device_modal(device_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_device_modal'); ?>", {
          device_id: device_id,
      }, function() {
          $("body").find('#deviceModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_device(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_device/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-device_table').DataTable().ajax.reload();
                }
            });
        }
    }

    function delete_device_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_device_attachment/' +attachment_id, function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.dz-preview').remove();

                    var totalAttachmentsIndicator = $('.dz-preview'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_device_image_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_device_image_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }

</script>