<script type="text/javascript">
    $(function(){
        'use strict';

        var inspection_template_params = {
        };
        var inspection_template_table = $('table.table-inspection_template_table');
        var _table_api = initDataTable(inspection_template_table, admin_url+'workshop/inspection_template_table', [0], [0], inspection_template_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-inspection_template_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_inspection_template').appFormValidator({
                rules: {
                    code: 'required',
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/inspection_template_exists",
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
                onSubmit: manage_inspection_template,
                messages: {
                },
            });

          });

        $('#inspection_template').on('hidden.bs.modal', function(event) {
            $('#inspection_template input[name="code"]').val('');
            $('#inspection_template input[name="name"]').val('');
            $('#inspection_template textarea[name="description"]').val('');
            $('#inspection_template #inspection_template_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_inspection_template(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-inspection_template_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#inspection_template').modal('hide');
        });
        return false;
    }

    function new_inspection_template(){
        'use strict';

        $('#inspection_template').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_inspection_template(invoker,id){
        'use strict';

        var code = $(invoker).data('code');
        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#inspection_template_additional').append(hidden_input('id',id));
        $('#inspection_template input[name="code"]').val(code);
        $('#inspection_template input[name="name"]').val(name);
        $('#inspection_template textarea[name="description"]').val(description);
        $('#inspection_template').modal('show');
        $('.add-title').addClass('hide');
    }
</script>