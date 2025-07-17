<script type="text/javascript">

    $(function(){
        'use strict';
        $('#reassign_mechanic').appFormValidator({
            rules: {
                assign_mechanic: 'required',
            },
            onSubmit: reassign_mechanic_handler,
            messages: {
            },
        });
    });


    function cancel_repair_job(){
        "use strict";
        $('#cancel_repair_job').modal('show');
    }
    
    function print_label_preview(){
        "use strict";
        $('#print_label_preview').modal('show');
    }

    function print_report_preview(){
        "use strict";
        $('#print_report_preview').modal('show');
    }

    function assign_mechanic(){
        "use strict";
        $('#assign_mechanic').modal('show');
    }

    function repair_job_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'workshop/repair_job_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {
                $('#cancel_repair_job').modal('hide');

                location.reload();
                alert_float('success', response.message);
            }
        });
    }

    function reassign_mechanic_handler() {
        "use strict"; 
        var mechanic_id = $('select[name="assign_mechanic"]').val();
        var repair_job_id = '<?php echo html_entity_decode($repair_job->id) ?>';
        var url = 'workshop/reassign_mechanic/' + repair_job_id + '/' + mechanic_id ;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {
                $('#assign_mechanic').modal('hide');

                location.reload();
                alert_float('success', response.message);
            }
        });
    }

    function repair_job_send_mail_client(){
        "use strict";
        $('#mail_modal').modal({show: true,backdrop: 'static'});
        appValidateForm($('#mail_client-form'), {
         content: 'required', subject:'required',email:'required'});
    }

</script>