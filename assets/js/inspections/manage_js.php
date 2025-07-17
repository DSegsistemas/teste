<script type="text/javascript">
    $(function(){
        'use strict';

        var inspection_params = {
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "client_filter": "[name='client_filter']",
            "inspection_type_filter": "[name='inspection_type_filter']",
            "device_filter": "[name='device_filter']",
            "status_filter": "[name='status_filter']",
            "repair_job_filter": "[name='repair_job_filter']",
        };

        var inspection_table = $('table.table-inspection_table');
        var _table_api = initDataTable(inspection_table, admin_url+'workshop/inspection_table', [0], [0], inspection_params, ['0', 'desc']);
        var hidden_columns = [0];
        $('.table-inspection_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(inspection_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-inspection_table').DataTable().ajax.reload();
            });
        });

        $('input[name="from_date_filter"]').on('change', function() {  
            $('.table-inspection_table').DataTable().ajax.reload();
        });
        $('input[name="to_date_filter"]').on('change', function() {  
            $('.table-inspection_table').DataTable().ajax.reload();
        });

    });

    function inspection_modal(inspection_id, repair_job_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_inspection_modal'); ?>", {
          inspection_id: inspection_id,
          repair_job_id: repair_job_id,
      }, function() {
          $("body").find('#inspectionModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_inspection(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_inspection/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-inspection_table').DataTable().ajax.reload();

                    var inspection_detail = $('input[name="inspection_detail"]').val();
                    if(inspection_detail == 1){
                        var repair_job_id = $('input[name="_repair_job_id"]').val();
                        window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=inspection');
                    }
                }
            });
        }
    }

    function delete_inspection_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_workshop_attachment/' +attachment_id + '/INSPECTION_FOLDER', function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.pdf_attachment').remove();

                    var totalAttachmentsIndicator = $('.pdf_attachment'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_file_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_file_failed') ?>");
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

    function inspection_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'workshop/inspection_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-inspection_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);

                var inspection_detail = $('input[name="inspection_detail"]').val();
                if(inspection_detail == 1){
                    window.location.assign('<?php echo admin_url('workshop/inspection_detail/') ?>'+id+'?tab=detail');
                }

            }
        });
    }

    function inspection_send_mail_client(){
        "use strict";
        
        $('#mail_modal').modal({show: true,backdrop: 'static'});
        appValidateForm($('#mail_client-form'), {
           content: 'required', subject:'required',email:'required'});
    }

</script>