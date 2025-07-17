<script type="text/javascript">
    var inspection_id, active_form_id, form_id,lastAddedPartItemKey = null;
    $(function(){
        'use strict';
        inspection_id = $('input[name="inspection_id"]').val();

        active_form_id = $("#form_tab ul li.active").find('a').data('id');
        get_inspection_form_details(active_form_id, inspection_id);

        $('body').on('click', '#form_tab .nav-link', function() {
            "use strict";
            active_form_id = $(this).parent('.nav-item').find('input[name="order"]').data("form_id");
            form_id = $(this).parent('.nav-item').find('input[name="order"]').data("form_id");

            setTimeout(function () {
                get_inspection_form_details(form_id, inspection_id);
            }, 200);
        });

    });

    function get_inspection_form_details(form_id, inspection_id) {
        'use strict';

        $.get(admin_url + 'workshop/get_inspection_form_details/' + form_id + '/' + inspection_id, function (response) {
            if (response.status === true || response.status == "true") {
                $('#form_detail_'+form_id).html(response.inspection_form_details);
                init_datepicker();
                init_selectpicker();

                labour_product_calculate_total();
                part_calculate_total();
            }

        }, 'json');
    }

    $(function(){
        'use strict';

        $('.add_edit_inspection_form').appFormValidator({
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

        var active_form_id = $("#form_tab ul li.active").find('a').data('id');
        form = $('#template_form_'+active_form_id+' .add_edit_inspection_form');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();

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
            
            setTimeout(function () {
                get_inspection_form_details(active_form_id, inspection_id);
            }, 200);
        });
        return false;
    }

    function delete_inspection_question_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_workshop_attachment/' +attachment_id + '/INSPECTION_QUESTION_FOLDER', function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.pdf_attachment').remove();

                    var totalAttachmentsIndicator = $('.pdf_attachment'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_file_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_file_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }

    function preview_file(invoker){
        'use strict';

        var id = $(invoker).attr('id');
        var rel_id = $(invoker).attr('rel_id');
        view_file(id, rel_id);
    }

    function view_file(id, rel_id) {   
        'use strict';

        $('#pdf_file_data').empty();
        $("#pdf_file_data").load(admin_url + 'workshop/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
            if (status == "error") {
                alert_float('danger', xhr.statusText);
            }
        });
    }


</script>