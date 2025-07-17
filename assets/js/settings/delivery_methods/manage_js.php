<script type="text/javascript">
    $(function(){
        'use strict';

        var delivery_method_params = {
        };
        var delivery_method_table = $('table.table-delivery_method_table');
        var _table_api = initDataTable(delivery_method_table, admin_url+'workshop/delivery_method_table', [0], [0], delivery_method_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-delivery_method_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_delivery_method').appFormValidator({
                rules: {
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/delivery_method_exists",
                            type: 'post',
                            data: {
                                name: function() {
                                    return $('input[name="name"]').val();
                                },
                                id: function() {
                                    return $('input[name="id"]').val();
                                }
                            }
                        }
                    }
                },
                onSubmit: manage_delivery_method,
                messages: {
                    name: '<?php echo _l("wshop_delivery_method_already_exists"); ?>',
                },
            });

          });

        $('#delivery_method').on('hidden.bs.modal', function(event) {
            'use strict';
            
            $('#delivery_method input[name="name"]').val('');
            $('#delivery_method textarea[name="description"]').val('');
            $('#delivery_method #delivery_method_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_delivery_method(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-delivery_method_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#delivery_method').modal('hide');
        });
        return false;
    }

    function new_delivery_method(){
        'use strict';

        $('#delivery_method').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_delivery_method(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#delivery_method_additional').append(hidden_input('id',id));
        $('#delivery_method input[name="name"]').val(name);
        $('#delivery_method textarea[name="description"]').val(description);
        $('#delivery_method').modal('show');
        $('.add-title').addClass('hide');
    }
</script>