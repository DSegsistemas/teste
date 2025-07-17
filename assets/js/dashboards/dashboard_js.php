<script type="text/javascript">
	$(function(){
        'use strict';

        var inspection_params = {
            "from_date_filter": "[name='from_date_filter']",
            "to_date_filter": "[name='to_date_filter']",
            "client_filter": "[name='client_filter']",
            "inspection_type_filter": "[name='inspection_type_filter']",
            "device_filter": "[name='device_filter']",
            "status_filter": "[name='inspection_status_filter']",
            "repair_job_filter": "[name='repair_job_filter']",
        };

        var inspection_table = $('table.table-inspection_table');
        var _table_api = initDataTable(inspection_table, admin_url+'workshop/inspection_table', [0], [0], inspection_params, ['0', 'desc']);
        var hidden_columns = [0,6,9,10,11,13];
        $('.table-inspection_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(inspection_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-inspection_table').DataTable().ajax.reload();
            });
        });

        $('input[name="from_date_filter"]').on('change', function() {  
            $('.table-inspection_table').DataTable().ajax.reload();
        });
        $('input[name="to_date_filter"]').on('change', function() {  
            $('.table-inspection_table').DataTable().ajax.reload();
        });

    });

	function inspection_status_filter(status){
        'use strict';
		
		$('input[name="inspection_status_filter"]').val(status);
		$('.table-inspection_table').DataTable().ajax.reload();
	}

	report_by_repair_job_weekly('report_by_repair_job_weekly', '', '');
	report_by_repair_job_month('report_by_repair_job_month', '', '');
	report_by_mechanic_performance('report_by_mechanic_performance', '', '');

	function report_by_mechanic_performance(id, value, title_c){
		'use strict';

		var months_report = $('select[name="mo_months-report"]').val(); 
		var report_from = $('input[name="mo_report-from"]').val();
		var report_to = $('input[name="mo_report-to"]').val();

		requestGetJSON('workshop/report_by_mechanic_performance?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			/*get data for hightchart*/

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo _l('wshop_report_by_mechanic_performance'); ?>'
				},
				credits: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: '<?php echo _l('wshop_hours'); ?>'
					}
				},
				
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '<?php echo _l('wshop_hours'); ?>',
					data: response.estimated_hours

				}
				]
			});


		});
	}

	function report_by_repair_job_month(id, value, title_c){
		'use strict';

		var months_report = $('select[name="mo_months-report"]').val(); 
		var report_from = $('input[name="mo_report-from"]').val();
		var report_to = $('input[name="mo_report-to"]').val();

		requestGetJSON('workshop/report_by_repair_job_month?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			/*get data for hightchart*/

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo _l('wshop_monthly'); ?>'
				},
				credits: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: [{
					min: 0,
					title: {
						text: '<?php echo _l('wshop_amount'); ?>'
					}
				}, { // Secondary axis
					className: 'highcharts-color-1',
					opposite: true,
					title: {
						text: '<?php echo _l('wshop_hours'); ?>'
					}
				}],
				
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '<?php echo _l('wshop_repair_job'); ?>',
					data: response.total

				},{
					name: '<?php echo _l('wshop_Labour_Product'); ?>',
					data: response.labour_total

				},{
					name: '<?php echo _l('wshop_hours'); ?>',
					data: response.estimated_hours,
					yAxis: 1

				}
				]
			});


		});
	}

	function report_by_repair_job_weekly(id, value, title_c){
		'use strict';

		var months_report = $('select[name="mo_months-report"]').val(); 
		var report_from = $('input[name="mo_report-from"]').val();
		var report_to = $('input[name="mo_report-to"]').val();

		requestGetJSON('workshop/report_by_repair_job_weekly?months_report='+months_report+'&report_from='+report_from+'&report_to='+report_to).done(function (response) {

			/*get data for hightchart*/

			Highcharts.setOptions({
				chart: {
					style: {
						fontFamily: 'inherit !important',
						fill: 'black'
					}
				},
				colors: [ '#119EFA','#15f34f','#ef370dc7','#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263','#6AF9C4','#50B432','#0d91efc7','#ED561B']
			});
			Highcharts.chart(id, {
				chart: {
					type: 'column'
				},
				title: {
					text: '<?php echo _l('wshop_weekly'); ?>'
				},
				credits: {
					enabled: false
				},
				xAxis: {
					categories: response.categories,
					crosshair: true
				},
				yAxis: [{
					min: 0,
					title: {
						text: '<?php echo _l('wshop_amount'); ?>'
					}
				}, { // Secondary axis
					className: 'highcharts-color-1',
					opposite: true,
					title: {
						text: '<?php echo _l('wshop_hours'); ?>'
					}
				}],
				
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: '<?php echo _l('wshop_repair_job'); ?>',
					data: response.total

				},{
					name: '<?php echo _l('wshop_Labour_Product'); ?>',
					data: response.labour_total

				},{
					name: '<?php echo _l('wshop_hours'); ?>',
					data: response.estimated_hours,
					yAxis: 1

				}
				]
			});


		});
	}


	var mo_report_from = $('input[name="mo_report-from"]');
	var mo_report_to = $('input[name="mo_report-to"]');
	var mo_date_range = $('#mo_date-range');

	$('select[name="mo_months-report"]').on('change', function() {
		'use strict';

		var val = $(this).val();
		mo_report_to.attr('disabled', true);
		mo_report_to.val('');
		mo_report_from.val('');
		if (val == 'custom') {
			mo_date_range.addClass('fadeIn').removeClass('hide');
			return;
		} else {
			if (!mo_date_range.hasClass('hide')) {
				mo_date_range.removeClass('fadeIn').addClass('hide');
			}
		}
		 repair_job_by_month_gen_reports();
		 mechanic_performance_gen_reports();
	});

	mo_report_from.on('change', function() {
		'use strict';

		var val = $(this).val();
		var report_to_val = mo_report_to.val();
		if (val != '') {
			mo_report_to.attr('disabled', false);
			if (report_to_val != '') {
				 repair_job_by_month_gen_reports();
				 mechanic_performance_gen_reports();
				 
			}
		} else {
			mo_report_to.attr('disabled', true);
		}
	});

	mo_report_to.on('change', function() {
		'use strict';

		var val = $(this).val();
		if (val != '') {
			 repair_job_by_month_gen_reports();
			 mechanic_performance_gen_reports();

		}
	});

	function  mechanic_performance_gen_reports() {
		'use strict';
		report_by_mechanic_performance('report_by_mechanic_performance', '', '');
	}

	function  repair_job_by_month_gen_reports() {
		'use strict';
		report_by_repair_job_month('report_by_repair_job_month', '', '');
	}
	

	function  repair_job_by_weekly_gen_reports() {
		'use strict';
		report_by_repair_job_weekly('report_by_repair_job_weekly', '', '');
	}


</script>