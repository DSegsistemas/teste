<script type="text/javascript">
    $(function(){
        'use strict';

        var model_params = {
        };
        var model_table = $('table.table-model_table');
        var _table_api = initDataTable(model_table, admin_url+'workshop/model_table', [0], [0], model_params, ['1', 'asc']);
        var hidden_columns = [0];
        $('.table-model_table').DataTable().columns(hidden_columns).visible(false, false);
        $( document ).ready(function() {

            $('#add_edit_model').appFormValidator({
                rules: {
                    name: 'required',
                    manufacturer_id: 'required',
                    category_id: 'required',
                },
                onSubmit: manage_model,
                messages: {
                },
            });

          });

        $('#model').on('hidden.bs.modal', function(event) {
            $('#model input[name="name"]').val('');
            $('#model input[name="model_no"]').val('');
            $('#model select[name="manufacturer_id"]').val('').change();
            $('#model select[name="category_id"]').val('').change();
            $('#model select[name="fieldset_id"]').val('').change();
            $('#model textarea[name="description"]').val('');

            $('#model #model_additional').html('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

    });

    function manage_model(form) {
        'use strict';

        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if (response.success) {
                $('.table-model_table').DataTable().ajax.reload();
                alert_float('success', response.message);
            }

            $(form).trigger('reinitialize.areYouSure');
            $('#model').modal('hide');
        });
        return false;
    }

    function new_model(){
        'use strict';

        $('#model').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_model(invoker,id){
        'use strict';

        var name = $(invoker).data('name');
        var manufacturer_id = $(invoker).data('manufacturer_id');
        var category_id = $(invoker).data('category_id');
        var model_no = $(invoker).data('model_no');
        var fieldset_id = $(invoker).data('fieldset_id');
        var description = $(invoker).data('description');

        $('#model_additional').append(hidden_input('id',id));
        $('#model input[name="name"]').val(name);
        $('#model input[name="model_no"]').val(model_no);
        $('#model select[name="manufacturer_id"]').val(manufacturer_id).change();
        $('#model select[name="category_id"]').val(category_id).change();
        $('#model select[name="fieldset_id"]').val(fieldset_id).change();
        $('#model textarea[name="description"]').val(description);

        $('#model').modal('show');
        $('.add-title').addClass('hide');
    }
</script>