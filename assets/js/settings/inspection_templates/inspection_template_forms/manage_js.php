<script type="text/javascript">
    var inspection_template_id;
    $(function(){
        'use strict';
        inspection_template_id = $('input[name="inspection_template_id"]').val();
        wshop_init_items_sortable();
        wshop_init_questions_sortable();

        var active_form_id = $("#form_tab ul li").find('a').data('id');
        get_inspection_template_form_details(active_form_id);

        $('body').on('click', '#form_tab .nav-link', function() {
            "use strict";
            var form_id = $(this).parent('.nav-item').find('input[name="order"]').data("form_id");

            get_inspection_template_form_details(form_id);
        });

        $('body').on('click', '.tab-content input[type="checkbox"]', function() {
            "use strict";

            var question_id = $(this).parent().find('input[type="checkbox"]').data("id");
            if($(this).is(':checked')){
                var question_required = 1;
            }else{
                var question_required = 0;
            }

            $.get(admin_url + 'workshop/update_question_required/' + question_id +'/'+question_required, function (response) {
                if (response.status === true || response.status == "true") {
                   alert_float('success', response.message)
               }

           }, 'json');

        });
        

    });

    function get_inspection_template_form_details(form_id) {
        "use strict";
        
        $.get(admin_url + 'workshop/get_inspection_template_form_details/' + form_id, function (response) {
            if (response.status === true || response.status == "true") {
                $('#form_detail_'+form_id).html(response.inspection_template_form_details);
                init_datepicker();
                init_selectpicker();
            }

        }, 'json');
    }

    function wshop_init_items_sortable(preview_table) {
        'use strict';

        var _items_sortable = $("#sortable");

        if (_items_sortable.length === 0) {
            return;
        }

        _items_sortable.sortable({
            helper: fixHelperTableHelperSortable,
            placeholder: "ui-placeholder",
            itemPath: "> ul",
            itemSelector: "li.dragger",
            items: "li.dragger",
            update: function () {
                if (typeof preview_table == "undefined") {
                    inspection_template_form_title_reorder();
                } else {
        // If passed from the admin preview there is other function for re-ordering
                    form_save_ei_items_order();
                }
            },
            sort: function (event, ui) {

      // Firefox fixer when dragging
                var $target = $(event.target);
                if (!/html|body/i.test($target.offsetParent()[0].tagName)) {
                    var top =
                    event.pageY -
                    $target.offsetParent().offset().top -
                    ui.helper.outerHeight(true) / 2;
                    ui.helper.css({
                      top: top + "px",
                  });
                }
            },
        });
    }

    // Reoder the items in table edit for estimate and invoices
    function inspection_template_form_title_reorder() {
        'use strict';

        var rows = $("#form_tab ul li");
        var i = 1,
        order = [];
        $.each(rows, function () {
          $(this).find('input[name="order"]').val(i);
          order.push([$(this).find('input[name="order"]').data("form_id"), i]);

          i++;
      });

        setTimeout(function () {
            $.post(admin_url + "workshop/update_inspection_template_form_order", {
              data: order,
          });
        }, 200);
    }

    
    function inspection_template_form_modal(form_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_inspection_template_form_modal'); ?>", {
          form_id: form_id,
          inspection_template_id: inspection_template_id,
      }, function() {
          $("body").find('#inspection_template_formModal').modal({ show: true, backdrop: 'static' });
          $('.selectpicker').selectpicker("refresh");
      });

    }

    function delete_inspection_template_form(wrapper, id, rel_type) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_inspection_template_form/" + id +"/"+ rel_type).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    window.location.reload(true);
                }
            });
        }
    }

    function inspection_template_form_detail_modal(form_detail_id, inspection_template_form_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_inspection_template_form_detail_modal'); ?>", {
          form_detail_id: form_detail_id,
          inspection_template_form_id: inspection_template_form_id,
      }, function() {
          $("body").find('#inspection_template_form_detailModal').modal({ show: true, backdrop: 'static' });
          $('.selectpicker').selectpicker("refresh");
      });

    }

    function delete_inspection_template_form_detail(wrapper, id, rel_type) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_inspection_template_form_detail/" + id +"/"+ rel_type).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $(wrapper).parents(".form-question").remove();
                }
            });
        }
    }

    function wshop_init_questions_sortable(preview_table) {
        'use strict';

        var _items_sortable = $(".tab-content");

        if (_items_sortable.length === 0) {
            return;
        }

        _items_sortable.sortable({
            helper: fixHelperTableHelperSortable,
            placeholder: "ui-placeholder",
            itemPath: "> div",
            itemSelector: "div.form-question.dragger",
            items: "div.form-question.dragger",
            update: function () {
                if (typeof preview_table == "undefined") {
                    inspection_template_form_question_reorder();
                } else {
        // If passed from the admin preview there is other function for re-ordering
                }
            },
            sort: function (event, ui) {

      // Firefox fixer when dragging
                var $target = $(event.target);
                if (!/html|body/i.test($target.offsetParent()[0].tagName)) {
                    var top =
                    event.pageY -
                    $target.offsetParent().offset().top -
                    ui.helper.outerHeight(true) / 2;
                    ui.helper.css({
                      top: top + "px",
                  });
                }
            },
        });
    }

    // Reoder the items in table edit for estimate and invoices
    function inspection_template_form_question_reorder() {
        'use strict';

        var rows = $(".tab-content .tab-pane.active .form-question");
        var i = 1,
        order = [];
        $.each(rows, function () {
            $(this).find('input[name="field_order"]').val(i);
            order.push([$(this).find('input[name="field_order"]').data("question_id"), i]);

            i++;
        });

        setTimeout(function () {
            $.post(admin_url + "workshop/update_inspection_template_form_question_order", {
              data: order,
          });
        }, 200);
    }

</script>