
<script>
    $(function(){
        'use strict';
        var StaffServerParams = {
            "role": "[name='mechanic_role[]']",
            "deparment": "[name='deparment']",
        };
        var table_staff = $('table.table-table_staff');
        initDataTable(table_staff,admin_url + 'workshop/mechanic_table', [0],[0], StaffServerParams, [1, 'desc']);

        //hide first column
        var hidden_columns = [0];
        $('.table-table_staff').DataTable().columns(hidden_columns).visible(false, false);

        $.each(StaffServerParams, function() {
            $('#deparment').on('change', function() {
                table_staff.DataTable().ajax.reload();
            });
        
            $('#mechanic_role').on('change', function() {
                table_staff.DataTable().ajax.reload();
            });
        });        

    })
//staff role end  
    function delete_staff_member(id){
        'use strict';

        $('#delete_staff').modal('show');
        $('#transfer_data_to').find('option').prop('disabled',false);
        $('#transfer_data_to').find('option[value="'+id+'"]').prop('disabled',true);
        $('#delete_staff .delete_id input').val(id);
        $('#transfer_data_to').selectpicker('refresh');
    }

    function staff_bulk_actions(){
        'use strict';

        $('#table_staff_bulk_actions').modal('show');
    }

    function staff_delete_bulk_action(event) {
        'use strict';
        
        if (confirm_delete()) {
            var mass_delete = $('#mass_delete').prop('checked');

            if(mass_delete == true){
                var ids = [];
                var data = {};
                data.mass_delete = true;
                data.rel_type = 'hrm_staff';

                var rows = $('#table-table_staff').find('tbody tr');
                $.each(rows, function() {
                    var checkbox = $($(this).find('td').eq(0)).find('input');
                    if (checkbox.prop('checked') === true) {
                        ids.push(checkbox.val());
                    }
                });
                data.ids = ids;
                $(event).addClass('disabled');
                
                setTimeout(function() {
                    $.post(admin_url + 'hr_profile/hrm_delete_bulk_action', data).done(function() {
                        window.location.reload();
                    }).fail(function(data) {
                        $('#table_contract_bulk_actions').modal('hide');
                        alert_float('danger', data.responseText);
                    });
                }, 200);

            }else{
                window.location.reload();
            }
        }
    }

    function hr_profile_add_staff(staff_id, role_id, add_new) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('hr_profile/hr_profile/member_modal'); ?>", {
            slug: 'create',
            staff_id: staff_id,
            role_id: role_id,
            add_new: add_new
        }, function() {
            if ($('.modal-backdrop.fade').hasClass('in')) {
                $('.modal-backdrop.fade').remove();
            }
            if ($('#appointmentModal').is(':hidden')) {
                $('#appointmentModal').modal({
                    show: true
                });
            }
        });

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');
    }


    function update_mechanic(staff_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/workshop/mechanic_modal'); ?>", {
            slug: 'update',
            staff_id: staff_id,
            manage_staff: 'manage_staff'
        }, function() {
            if ($('.modal-backdrop.fade').hasClass('in')) {
                $('.modal-backdrop.fade').remove();
            }
            if ($('#appointmentModal').is(':hidden')) {
                $('#appointmentModal').modal({
                    show: true
                });
            }
        });

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');
    }


</script>