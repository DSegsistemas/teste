<script type="text/javascript">
    $(function(){
        'use strict';

        var branch_params = {
        };
        var branch_table = $('table.table-branch_table');
        var _table_api = initDataTable(branch_table, admin_url+'workshop/branch_table', [0], [0], branch_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-branch_table').DataTable().columns(hidden_columns).visible(false, false);
    });

    function branch_modal(branch_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_branch_modal'); ?>", {
          branch_id: branch_id,
      }, function() {
          $("body").find('#branchModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();

      });

    }

    function send_mail_modal(id, data){
        "use strict";

        var branch_name = $(data).data('name');
        var branch_email = $(data).data('email');

        $('#mail_modal').modal({show: true,backdrop: 'static'});
        appValidateForm($('#mail_branch-form'), {
            email_content: 'required',
            email_subject:'required',
            branch_email:'required'
        });

        $('#mail_branch-form input[name="branch_id"]').val(id);
        $('#mail_branch-form input[name="branch_name"]').val(branch_name);
        $('#mail_branch-form input[name="branch_email"]').val(branch_email);

    }
</script>