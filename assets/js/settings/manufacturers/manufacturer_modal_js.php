<script type="text/javascript">
    $(function(){
        'use strict';

            $('#add_edit_manufacturer').appFormValidator({
                rules: {
                    name: {
                        required: true,
                        remote: {
                            url: admin_url + "workshop/manufacturer_exists",
                            type: 'post',
                            data: {
                                name: function() {
                                    return $('textarea[name="name"]').val();
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
                    days_off: '<?php echo _l("wshop_manufacturer_already_exists"); ?>',
                },
            });


        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_manufacturer');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.manufacturer_submit_button').attr( "disabled", "disabled" );

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
            
            $("#manufacturerModal").modal("hide");
            $('.table-manufacturer_table').DataTable().ajax.reload();

        });
        return false;
    }

    function readURL(input) {
        "use strict";

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#manufacturerModal #wizardPicturePreview').attr('src', e.target.result).fadeIn('slow');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function delete_manufacturer_image(id, row) {
      "use strict";
      if (confirm_delete()) {

        requestGet('workshop/delete_manufacturer_image/' + id).done(function(success) {
          if (success || success == true) {
            $('#add_edit_manufacturer').find('div.article_image .new_remove_file').remove();
         }
     }).fail(function(error) {
        alert_float('danger', error.responseText);
    });
 }
}

</script>