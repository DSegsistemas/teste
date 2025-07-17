<script type="text/javascript">
    $(function(){
        'use strict';

        var fieldset_params = {
        };
        var fieldset_table = $('table.table-fieldset_table');
        var _table_api = initDataTable(fieldset_table, admin_url+'workshop/fieldset_table', [0], [0], fieldset_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-fieldset_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_fieldset').appFormValidator({
                rules: {
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/fieldset_exists",
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
                onSubmit: manage_fieldset,
                messages: {
                    name: '<?php echo _l("wshop_fieldset_already_exists"); ?>',
                },
            });

          });

        $('#fieldset').on('hidden.bs.modal', function(event) {
            $('#fieldset input[name="name"]').val('');
            $('#fieldset textarea[name="description"]').val('');
            $('#fieldset #fieldset_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_fieldset(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-fieldset_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#fieldset').modal('hide');
        });
        return false;
    }

    function new_fieldset(){
        'use strict';

        $('#fieldset').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_fieldset(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#fieldset_additional').append(hidden_input('id',id));
        $('#fieldset input[name="name"]').val(name);
        $('#fieldset textarea[name="description"]').val(description);
        $('#fieldset').modal('show');
        $('.add-title').addClass('hide');
    }
</script>