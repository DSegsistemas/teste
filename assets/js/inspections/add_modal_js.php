<script type="text/javascript">
    var expenseDropzone,repair_job_id;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            init_inspection_currency()
            init_ajax_search("customer", "#client_id.ajax-search");
            form_init_editor('.tinymce', {height:150, auto_focus: true});

        $('#add_edit_inspection').appFormValidator({
            rules: {
                number: 'required',
                device_id: 'required',
                inspection_template_id: 'required',
                inspection_type_id: 'required',
                client_id: 'required',
                person_in_charge: 'required',
                start_date: 'required',
                end_date: 'required',
                
            },
            onSubmit: SubmitHandler,
            messages: {
                name: '<?php echo _l("wshop_device_already_exists"); ?>',
            },
        });


        $('#wizard-picture').on('change', function() {
            "use strict";

            readURL(this);
        });

        if($('#dropzoneDragArea').length > 0){
            expenseDropzone = new Dropzone("#add_edit_inspection", appCreateDropzoneOptions({
                autoProcessQueue: false,
                clickable: '#dropzoneDragArea',
                previewsContainer: '.dropzone-previews',
                addRemoveLinks: true,
                maxFiles: 20,

                success:function(file,response){
                    response = JSON.parse(response);
                    if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);
                        }
                        $("#inspectionModal").modal("hide");
                        $('.table-inspection_table').DataTable().ajax.reload();

                        var workshop_detail = $('input[name="workshop_detail"]').val();
                        if(workshop_detail == 1){
                            window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=detail');
                        }

                    }else{
                        expenseDropzone.processQueue();
                    }
                },
            }));
        }

        $("body").on("change", ".rj_client_id #client_id", function () {
            "use strict";

            var val = $(this).val();
            if(val == ''){
                return;
            }

            clear_billing_and_shipping_details();

            var currentInvoiceID = $("body")
            .find('input[name="merge_current_invoice"]')
            .val();
            currentInvoiceID =
            typeof currentInvoiceID == "undefined" ? "" : currentInvoiceID;
            var device_id = $("body")
            .find('#inspectionModal select[name="device_id"]')
            .val();
            device_id =
            typeof device_id == "undefined" ? "" : device_id;

            console.log('device_id', device_id);
            requestGetJSON( "workshop/client_change_data/" + val ).done(function (response) {

                for (var f in billingAndShippingFields) {
                    if (billingAndShippingFields[f].indexOf("billing") > -1) {
                        if (billingAndShippingFields[f].indexOf("country") > -1) {
                            $(
                                'select[name="' + billingAndShippingFields[f] + '"]'
                                ).selectpicker(
                                "val",
                                response["billing_shipping"][0][billingAndShippingFields[f]]
                                );
                            } else {
                                if (billingAndShippingFields[f].indexOf("billing_street") > -1) {
                                    $('textarea[name="' + billingAndShippingFields[f] + '"]').val(
                                        response["billing_shipping"][0][billingAndShippingFields[f]]
                                        );
                                } else {
                                    $('input[name="' + billingAndShippingFields[f] + '"]').val(
                                        response["billing_shipping"][0][billingAndShippingFields[f]]
                                        );
                                }
                            }
                        }
                    }

                    if (!empty(response["billing_shipping"][0]["shipping_street"])) {
                        $('input[name="include_shipping"]').prop("checked", true).change();
                    }

                    for (var fsd in billingAndShippingFields) {
                        if (billingAndShippingFields[fsd].indexOf("shipping") > -1) {
                            if (billingAndShippingFields[fsd].indexOf("country") > -1) {
                                $(
                                  'select[name="' + billingAndShippingFields[fsd] + '"]'
                                  ).selectpicker(
                                  "val",
                                  response["billing_shipping"][0][billingAndShippingFields[fsd]]
                                  );
                              } else {
                                if (billingAndShippingFields[fsd].indexOf("shipping_street") > -1) {
                                  $('textarea[name="' + billingAndShippingFields[fsd] + '"]').val(
                                    response["billing_shipping"][0][billingAndShippingFields[fsd]]
                                    );
                              } else {
                                  $('input[name="' + billingAndShippingFields[fsd] + '"]').val(
                                    response["billing_shipping"][0][billingAndShippingFields[fsd]]
                                    );
                              }
                          }
                      }
                  }

                  $('input[name="phonenumber"]').val(response.phonenumber);
                  $('input[name="contact_name"]').val(response.contact_name);
                  $('input[name="contact_email"]').val(response.contact_email);

                  $('.client_phonenumber').html(response.phonenumber);
                  $('.contact_name').html(response.contact_name);
                  $('.contact_email').html(response.contact_email);

                  if(device_id == '' || device_id == null){
                      $('#inspectionModal select[name="device_id"]').html(response.device_html).selectpicker("refresh");
                  }

                  init_billing_and_shipping_details();

                  init_inspection_currency();
              });
        });

        $("body").on("change", "select[name='interval_id']", function () {
            calculate_next_inspection_date();
        });
        $("body").on("change", "input[name='start_date']", function () {
            calculate_next_inspection_date();
        });
        $("body").on("change", "select[name='repair_job_id']", function () {
            var repair_job_id = $("select[name='repair_job_id']").val();
            $.get(admin_url + 'workshop/get_repair_job_infor/' +repair_job_id, function (response) {
                if (response.success == true) {
                    $('select[name="client_id"]').html('');
                    $('select[name="client_id"]').html(response.client_html);
                    $('select[name="client_id"]').selectpicker("refresh");
                    $('select[name="client_id"]').val(response.client_id).change();
                    setTimeout(function () {
                        $('select[name="device_id"]').val(response.device_id).change();
                    }, 1000);
                }
            }, 'json');
        });
        <?php if(isset($customer_id)){ ?>
            $('select[name="client_id"]').change();
        <?php } ?>
        

        });


    });

function init_inspection_currency(id, callback) {
    "use strict";

    var $accountingTemplate = $("body").find(".accounting-template");

    if ($accountingTemplate.length || id) {
      var selectedCurrencyId = !id
      ? $accountingTemplate.find('select[name="currency"]').val()
      : id;

      requestGetJSON("misc/get_currency/" + selectedCurrencyId).done(function (
        currency
        ) {
        // Used for formatting money
        accounting.settings.currency.decimal = currency.decimal_separator;
        accounting.settings.currency.thousand = currency.thousand_separator;
        accounting.settings.currency.symbol = currency.symbol;
        accounting.settings.currency.format =
        currency.placement == "after" ? "%v %s" : "%s%v";

        labour_product_calculate_total();
        part_calculate_total();

        if (callback) {
          callback();
      }
  });
  }
}

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_inspection');
        $('#add_edit_inspection select[name="repair_job_id"]').prop("disabled", false);
        $('#add_edit_inspection select[name="device_id"]').prop("disabled", false);
        
        var inspection_id = 0;
        repair_job_id = $('#add_edit_inspection select[name="repair_job_id"]').val();
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("description", tinyMCE.activeEditor.getContent());

        $('#box-loading').show();
        $('.inspection_submit_button').attr( "disabled", "disabled" );

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

            if (response.inspection_id) {
                inspection_id = response.inspection_id;
                if(typeof(expenseDropzone) !== 'undefined'){
                    if (expenseDropzone.getQueuedFiles().length > 0) {
                        
                        expenseDropzone.options.url = admin_url + 'workshop/add_inspection_attachment/' + response.inspection_id;
                        expenseDropzone.processQueue();

                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);

                            $("#inspectionModal").modal("hide");
                            $('.table-inspection_table').DataTable().ajax.reload();

                            var workshop_detail = $('input[name="workshop_detail"]').val();
                            if(workshop_detail == 1){
                                window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=detail');
                            }
                            
                        }
                    }
                } else {
                    if(response.success == true || response.success == 'true'){
                        alert_float('success', response.message);
                    }
                }
            } else {
                if(response.success == true || response.success == 'true'){
                    alert_float('success', response.message);
                }
            }
            
            if(response.success == true || response.success == 'true'){
                alert_float('success', response.message);
            }
            
        });
        return false;
    }

    function readURL(input) {
        "use strict";

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#wizardPicturePreview').attr('src', e.target.result).fadeIn('slow');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }


    // Function to init the tinymce editor
    function form_init_editor(selector, settings) {

        "use strict";

        if(tinymce.majorVersion + '.' + tinymce.minorVersion == '6.8.3'){
            tinymce.remove(selector);
            initWorkshopEditor(selector);
        }else{

           tinymce.remove(selector);

           selector = typeof(selector) == 'undefined' ? '.tinymce' : selector;
           var _editor_selector_check = $(selector);

           if (_editor_selector_check.length === 0) { return; }

           $.each(_editor_selector_check, function() {
             if ($(this).hasClass('tinymce-manual')) {
                $(this).removeClass('tinymce');
            }
        });

    // Original settings
           var _settings = {
               branding: false,
               selector: selector,
               browser_spellcheck: true,
               height: 400,
               theme: 'modern',
               skin: 'perfex',
               language: app.tinymce_lang,
               relative_urls: false,
               inline_styles: true,
               verify_html: false,
               cleanup: false,
               autoresize_bottom_margin: 25,
               valid_elements: '+*[*]',
               valid_children: "+body[style], +style[type]",
               apply_source_formatting: false,
               remove_script_host: false,
               removed_menuitems: 'newdocument restoredraft',
               forced_root_block: false,
               autosave_restore_when_empty: false,
               fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
               setup: function(ed) {
            // Default fontsize is 12
                ed.on('init', function() {
                   this.getDoc().body.style.fontSize = '12pt';
               });
            },
            table_default_styles: {
            // Default all tables width 100%
                width: '100%',
            },
            plugins: [
                'advlist autoresize autosave lists link image print hr codesample',
                'visualblocks code fullscreen',
                'media save table contextmenu',
                'paste textcolor colorpicker'
                ],
            toolbar1: 'fontselect fontsizeselect | forecolor backcolor | bold italic | alignleft aligncenter alignright alignjustify | image link | bullist numlist | restoredraft',
            file_browser_callback: elFinderBrowser,
        };

    // Add the rtl to the settings if is true
        isRTL == 'true' ? _settings.directionality = 'rtl' : '';
        isRTL == 'true' ? _settings.plugins[0] += ' directionality' : '';

    // Possible settings passed to be overwrited or added
        if (typeof(settings) != 'undefined') {
           for (var key in settings) {
              if (key != 'append_plugins') {
                 _settings[key] = settings[key];
             } else {
                 _settings['plugins'].push(settings[key]);
             }
         }
     }

    // Init the editor
     var editor = tinymce.init(_settings);
     $(document).trigger('app.editor.initialized');

     return editor;
 }
}

function calculate_next_inspection_date(){
    "use strict";
    
    var data = {};
    data.start_date = $('input[name="start_date"]').val();
    data.interval_id = $('select[name="interval_id"]').val();

    $.post(admin_url + 'workshop/calculate_next_inspection_date', data, function (response) {
        if (response.success == true) {
            $('input[name="next_inspection_date"]').val(response.next_inspection_date);
        } 
    }, 'json');
}

</script>