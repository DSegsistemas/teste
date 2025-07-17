<script type="text/javascript">
    $(function(){
        'use strict';

        $('#add_edit_inspection_template_form').appFormValidator({
            rules: {
                name: 'required',
            },
            onSubmit: SubmitHandler,
            messages: {
            },
        });

    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_inspection_template_form');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.inspection_template_form_submit_button').attr( "disabled", "disabled" );

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
            
            if(response.status == true || response.status == 'true'){
                alert_float('success', response.message);
            }
            
            $("#inspection_template_formModal").modal("hide");
            $("#sortable").html(response.inspection_template_form_tab_html);
            if(response.is_add == true){
                $(".tab-content.ui-sortable").append(response.inspection_template_form_tab_content);
                var tab_content = $(".tab-content.ui-sortable .tab-pane");
                $.each(tab_content, function() {
                    $(this).removeClass('active');
                });
                $("#template_form_"+response.id).addClass('active');
            }
            setTimeout(function () {
            wshop_init_items_sortable();
            get_inspection_template_form_details(response.id);
        }, 200);
        });
        return false;
    }

</script>
