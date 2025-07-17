<script type="text/javascript">
    $(function(){
        'use strict';

        $( document ).ready(function() {

            $('#add_edit_material').appFormValidator({
                rules: {
                    item_id: 'required',
                    quantity: 'required',
                },
                onSubmit: SubmitHandler,
                messages: {
                },
            });
        });

    });
    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_material');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.material_submit_button').attr( "disabled", "disabled" );

        $.ajax({
            type: $(form).attr("method"),
            data: formData,
            mimeType: $(form).attr("enctype"),
            contentType: false,
            cache: false,
            processData: false,
            url: formURL,
        }).done(function(response) {
            var response = JSON.parse(response);
            $('#box-loading').addClass('hide');

            if(response.success == true || response.success == 'true'){
                alert_float('success', response.message);
            }
            
            $("#materialModal").modal("hide");
            $('.table-material_table').DataTable().ajax.reload();

        });
        return false;
    }
</script>