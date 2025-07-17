<script type="text/javascript">
    $(function(){
        'use strict';

        var interval_params = {
        };
        var interval_table = $('table.table-interval_table');
        var _table_api = initDataTable(interval_table, admin_url+'workshop/interval_table', [0], [0], interval_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-interval_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_interval').appFormValidator({
                rules: {
                    
                    name: 'required',
                    value: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/interval_exists",
                            type: 'post',
                            data: {
                                value: function() {
                                    return $('input[name="value"]').val();
                                },
                                type: function() {
                                    return $('select[name="type"]').val();
                                },
                                
                                id: function() {
                                    return $('input[name="id"]').val();
                                }
                            }
                        }
                    },
                    type: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/interval_exists",
                            type: 'post',
                            data: {
                                value: function() {
                                    return $('input[name="value"]').val();
                                },
                                type: function() {
                                    return $('select[name="type"]').val();
                                },
                                
                                id: function() {
                                    return $('input[name="id"]').val();
                                }
                            }
                        }
                    }
                },
                onSubmit: manage_interval,
                messages: {
                    value: '<?php echo _l("wshop_interval_already_exists"); ?>',
                    type: '<?php echo _l("wshop_interval_already_exists"); ?>',
                    
                },
            });

          });

        $('#interval').on('hidden.bs.modal', function(event) {
            $('#interval input[name="name"]').val('');
            $('#interval input[name="value"]').val('');
            $('#interval select[name="type"]').val('').change();
            $('#interval textarea[name="description"]').val('');
            $('#interval #interval_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_interval(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-interval_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#interval').modal('hide');
        });
        return false;
    }

    function new_interval(){
        'use strict';

        $('#interval').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_interval(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var value = $(invoker).data('value');
        var type = $(invoker).data('type');
        var description = $(invoker).data('description');

        $('#interval_additional').append(hidden_input('id',id));
        $('#interval input[name="name"]').val(name);
        $('#interval input[name="value"]').val(value);
        $('#interval select[name="type"]').val(type).change();
        $('#interval textarea[name="description"]').val(description);

        $('#interval').modal('show');
        $('.add-title').addClass('hide');
    }
</script>