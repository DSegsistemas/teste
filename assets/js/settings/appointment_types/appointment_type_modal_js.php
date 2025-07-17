<script type="text/javascript">
    $(function(){
        'use strict';

            $('#add_edit_appointment_type').appFormValidator({
                rules: {
                    code: 'required',
                    'item_id[]': 'required',
                    estimated_hours: 'required',
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/appointment_type_exists",
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
                onSubmit: SubmitHandler,
                messages: {
                    name: '<?php echo _l("wshop_appointment_type_name_already_exists"); ?>',
                },
            });

    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_appointment_type');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.appointment_type_submit_button').attr( "disabled", "disabled" );

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
            
            $("#appointment_typeModal").modal("hide");
            $('.table-appointment_type_table').DataTable().ajax.reload();

        });
        return false;
    }

</script>