<script type="text/javascript">
    $(function(){
        'use strict';

            $('#add_edit_branch').appFormValidator({
                rules: {
                    email: 'required',
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/branch_exists",
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
                    name: '<?php echo _l("wshop_branch_already_exists"); ?>',
                },
            });


        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_branch');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.branch_submit_button').attr( "disabled", "disabled" );

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
            
            $("#branchModal").modal("hide");
            $('.table-branch_table').DataTable().ajax.reload();

        });
        return false;
    }


</script>