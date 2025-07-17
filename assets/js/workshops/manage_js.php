<script type="text/javascript">
    $(function(){
        'use strict';

        var device_params = {
            "repair_job_filter": "[name='repair_job_filter']",
            "report_type_filter": "[name='report_type_filter']",
            "report_status_filter": "[name='report_status_filter']",
        };
        var workshop_table = $('table.table-workshop_table');
        var _table_api = initDataTable(workshop_table, admin_url+'workshop/workshop_table', [0], [0], device_params, ['0', 'asc']);
        var hidden_columns = [0];
        $('.table-workshop_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(device_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-workshop_table').DataTable().ajax.reload();
            });
        });

    });

    function workshop_modal(workshop_id, repair_job_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_workshop_modal'); ?>", {
          workshop_id: workshop_id,
          repair_job_id: repair_job_id,
      }, function() {
          $("body").find('#workshopModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_workshop(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_workshop/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-workshop_table').DataTable().ajax.reload();

                    var workshop_detail = $('input[name="workshop_detail"]').val();
                    if(workshop_detail == 1){
                        var repair_job_id = $('input[name="_repair_job_id"]').val();
                        window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=workshop');
                    }
                }
            });
        }
    }

    function delete_workshop_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_workshop_attachment/' +attachment_id + '/WORKSHOP_FOLDER', function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.pdf_attachment').remove();

                    var totalAttachmentsIndicator = $('.pdf_attachment'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_workshop_file_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_workshop_file_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }

    function preview_file(invoker){
        'use strict';

        var id = $(invoker).attr('id');
        var rel_id = $(invoker).attr('rel_id');
        view_file(id, rel_id);
    }

    function view_file(id, rel_id) {   
        'use strict';

        $('#pdf_file_data').empty();
        $("#pdf_file_data").load(admin_url + 'workshop/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
            if (status == "error") {
                alert_float('danger', xhr.statusText);
            }
        });
    }

</script>