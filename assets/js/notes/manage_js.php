<script type="text/javascript">
    $(function(){
        'use strict';

        var device_params = {
            
        };
        var return_table = $('table.table-return_table');
        var _table_api = initDataTable(return_table, admin_url+'workshop/return_table', [0], [0], device_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-return_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(device_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-return_table').DataTable().ajax.reload();
            });
        });

    });

    function note_modal(note_id, repair_job_id, return_delivery_id, transaction_type) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_note_modal'); ?>", {
          note_id: note_id,
          repair_job_id: repair_job_id,
          return_delivery_id: return_delivery_id,
          transaction_type: transaction_type,
      }, function() {
          $("body").find('#noteModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
          init_datepicker();

      });

    }

    function delete_note(id, transaction_type) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_note/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-return_table').DataTable().ajax.reload();

                    if(transaction_type == 'return'){
                        window.location.assign('<?php echo admin_url('workshop/repair_job_detail/'.$repair_job->id.'?tab=return'); ?>');
                    }else{
                        window.location.assign('<?php echo admin_url('workshop/repair_job_detail/'.$repair_job->id.'?tab=delivery'); ?>');
                    }
                }
            });
        }
    }

    function delete_note_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_workshop_attachment/' +attachment_id + '/NOTE_FOLDER', function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.pdf_attachment').remove();

                    var totalAttachmentsIndicator = $('.pdf_attachment'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_note_file_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_note_file_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }


</script>