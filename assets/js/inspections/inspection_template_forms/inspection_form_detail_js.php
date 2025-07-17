<script type="text/javascript">

	$(function(){
		'use strict';

		$( document ).ready(function() {

			$('#inspection_approval_form').appFormValidator({
				rules: {
				},
				onSubmit: SubmitHandler,
				messages: {
					name: '<?php echo _l("wshop_device_already_exists"); ?>',
				},
			});


		});

	});


	function SubmitHandler(form) {
        "use strict";

        form = $('#inspection_approval_form');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);

        $('#box-loading').show();
        $('.inspection_approval_submit_button').attr( "disabled", "disabled" );

        $.ajax({
            type: $(form).attr("method"),
            data: formData,
            mimeType: $(form).attr("enctype"),
            contentType: false,
            cache: false,
            processData: false,
            url: formURL,
        }).done(function(response) {
        	$('#box-loading').addClass('hide');
            var response = JSON.parse(response);

            if(response.success == true || response.success == 'true'){
                alert_float('success', response.message);
            }
            
            $("#inspection_approval_modal").modal("hide");
            $('.inspection_approval_submit_button').removeAttr("disabled");
            var inspection_form_detail_id = $('#inspection_approval_form input[name="inspection_form_detail_id"]').val()
            if(inspection_form_detail_id == 0 || response.update_inspection_status == true){
            	location.reload();
            }else{
            	var inspection_id = $('#inspection_approval_form input[name="inspection_id"]').val()
            	$('.inspection_approval_'+inspection_id+'_'+inspection_form_detail_id).html(response.response_text);
            }
        });
        return false;
    }

	function check_list_reject(inspection_form_detail_id){
        'use strict';

		$('.inspection_approval_submit_button').removeAttr("disabled");

		$('#inspection_approval_form').find('span.add-title').html('<?php echo _l('wshop_inspection_reject'); ?>');
		 $('#inspection_approval_modal').modal('show');
		 $('#inspection_approval_form input[name="approve"]').val('rejected');
		 $('#inspection_approval_form input[name="inspection_form_detail_id"]').val(inspection_form_detail_id);
	}

	function check_list_approve(inspection_form_detail_id){
        'use strict';

		$('.inspection_approval_submit_button').removeAttr("disabled");

		$('#inspection_approval_form').find('span.add-title').html('<?php echo _l('wshop_inspection_approval'); ?>');
		$('#inspection_approval_modal').modal('show');
		$('#inspection_approval_form input[name="approve"]').val('approved');
		$('#inspection_approval_form input[name="inspection_form_detail_id"]').val(inspection_form_detail_id);
		$('#inspection_approval_form textarea[name="approve_comment"]').val('');
	}

	function inspection_reject(inspection_form_detail_id){
        'use strict';

		$('.inspection_approval_submit_button').removeAttr("disabled");

		$('#inspection_approval_form').find('span.add-title').html('<?php echo _l('wshop_inspection_reject'); ?>');
		$('#inspection_approval_modal').modal('show');
		$('#inspection_approval_form input[name="approve"]').val('rejected');
		$('#inspection_approval_form input[name="inspection_form_detail_id"]').val(0);
		$('#inspection_approval_form textarea[name="approve_comment"]').val('');
		
	}

	function inspection_approve(inspection_form_detail_id){
        'use strict';

		$('.inspection_approval_submit_button').removeAttr("disabled");

		$('#inspection_approval_form').find('span.add-title').html('<?php echo _l('wshop_inspection_reject'); ?>');
		$('#inspection_approval_modal').modal('show');
		$('#inspection_approval_form input[name="approve"]').val('approved');
		$('#inspection_approval_form input[name="inspection_form_detail_id"]').val(0);
	}
	

</script>