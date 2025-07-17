<script type="text/javascript">
    $(function(){
        'use strict';

        var holiday_params = {
        };
        var holiday_table = $('table.table-holiday_table');
        var _table_api = initDataTable(holiday_table, admin_url+'workshop/holiday_table', [0], [0], holiday_params, ['1', 'asc']);
        var hidden_columns = [4];
        $('.table-holiday_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_holiday').appFormValidator({
                rules: {
                    name:'required',
                    days_off: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/holiday_days_off_exists",
                            type: 'post',
                            data: {
                                days_off: function() {
                                    return $('input[name="days_off"]').val();
                                },
                                id: function() {
                                    return $('input[name="id"]').val();
                                }
                            }
                        }
                    }
                },
                onSubmit: manage_holiday,
                messages: {
                    days_off: '<?php echo _l("wshop_holiday_already_exists"); ?>',
                },
            });

          });

        $('#holiday').on('hidden.bs.modal', function(event) {
            $('#holiday input[name="name"]').val('');
            $('#holiday input[name="days_off"]').val('');
            $('#holiday #holiday_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_holiday(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-holiday_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#holiday').modal('hide');
        });
        return false;
    }

    function new_holiday(){
        'use strict';

        $('#holiday').modal('show');
        $('.edit-title').addClass('hide');

        $('input[name="name"]').val('');
        $('#holiday input[name="days_off"]').val('');
    }

    function edit_holiday(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var days_off = $(invoker).data('days_off');
        $('#holiday_additional').append(hidden_input('id',id));
        $('#holiday input[name="name"]').val(name);
        $('#holiday input[name="days_off"]').val(days_off);
        $('#holiday').modal('show');
        $('.add-title').addClass('hide');
    }
</script>