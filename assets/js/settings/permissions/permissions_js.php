
<script>

	$(function() {

		"use strict";
		initDataTable('.table-hr-profile-permission', admin_url + 'workshop/workshop_permission_table');
	});

	function workshop_permissions_update(staff_id, role_id, add_new) {
	"use strict";

	  $("#modal_wrapper").load("<?php echo admin_url('workshop/workshop/permission_modal'); ?>", {
	       slug: 'update',
	       staff_id: staff_id,
	       role_id: role_id,
	       add_new: add_new
	  }, function() {
	       if ($('.modal-backdrop.fade').hasClass('in')) {
	            $('.modal-backdrop.fade').remove();
	       }
	       if ($('#appointmentModal').is(':hidden')) {
	            $('#appointmentModal').modal({
	                 show: true
	            });
	       }
	  });

	  init_selectpicker();
	  $(".selectpicker").selectpicker('refresh');
	}

</script>
