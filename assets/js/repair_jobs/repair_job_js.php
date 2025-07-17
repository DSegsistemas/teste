<script type="text/javascript">
    var expenseDropzone,lastAddedPartItemKey = null;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            init_repair_job_currency()
            form_init_editor('.tinymce', {height:150, auto_focus: true});
            init_ajax_search("customer", "#client_id.ajax-search");
            labour_product_calculate_total();
            part_calculate_total();
            
            // Inicializar selectpickers
            if (typeof $.fn.selectpicker !== 'undefined') {
                $('.selectpicker').selectpicker('refresh');
            }
            
            // Sincronizar appointment_date com estimated_completion_date
            syncAppointmentWithEstimatedDate();
        });

        $('#add_edit_repair_job').appFormValidator({
            rules: {
                code: 'required',
                number: 'required',
                appointment_type_id: 'required',
                client_id: 'required',
                device_id: 'required',
                name: 'required',
            },
            // onSubmit removido - submissão tratada por submit_form()
            messages: {
            },
        });

        $("body").on("change", ".rj_client_id #client_id", function () {
            'use strict';
            
            var val = $(this).val();
            if(val == ''){
                return;
            }

            // Clear billing and shipping details if function exists
            if (typeof clear_billing_and_shipping_details === 'function') {
                clear_billing_and_shipping_details();
            }

            var currentInvoiceID = $("body")
            .find('input[name="merge_current_invoice"]')
            .val();
            device_id =
            typeof device_id == "undefined" ? "" : device_id;
            var device_id = $("body")
            .find('select[name="device_id"]')
            .val();
            device_id =
            typeof device_id == "undefined" ? "" : device_id;
            
            requestGetJSON( "workshop/client_change_data/" + val + "/" +device_id ).done(function (response) {

                // Update billing and shipping fields if available
                if (typeof billingAndShippingFields !== 'undefined') {
                    for (var f in billingAndShippingFields) {
                        if (billingAndShippingFields[f].indexOf("billing") > -1) {
                            if (billingAndShippingFields[f].indexOf("country") > -1) {
                                 var countrySelect = $('select[name="' + billingAndShippingFields[f] + '"]');
                                 if (countrySelect.length > 0 && countrySelect.hasClass('selectpicker')) {
                                     countrySelect.selectpicker(
                                         "val",
                                         response["billing_shipping"][0][billingAndShippingFields[f]]
                                     );
                                 }
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

                        if (response["billing_shipping"] && response["billing_shipping"][0] && response["billing_shipping"][0]["shipping_street"] && response["billing_shipping"][0]["shipping_street"].trim() !== '') {
                             $('input[name="include_shipping"]').prop("checked", true).change();
                         }

                        for (var fsd in billingAndShippingFields) {
                            if (billingAndShippingFields[fsd].indexOf("shipping") > -1) {
                                if (billingAndShippingFields[fsd].indexOf("country") > -1) {
                                     var shippingCountrySelect = $('select[name="' + billingAndShippingFields[fsd] + '"]');
                                     if (shippingCountrySelect.length > 0 && shippingCountrySelect.hasClass('selectpicker')) {
                                         shippingCountrySelect.selectpicker(
                                             "val",
                                             response["billing_shipping"][0][billingAndShippingFields[fsd]]
                                         );
                                     }
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
                  }

                  $('input[name="phonenumber"]').val(response.phonenumber);
                  $('input[name="contact_name"]').val(response.contact_name);
                  $('input[name="contact_email"]').val(response.contact_email);

                  $('.client_phonenumber').html(response.phonenumber);
                  $('.contact_name').html(response.contact_name);
                  $('.contact_email').html(response.contact_email);
                 
                  var deviceSelect = $('select[name="device_id"]');
                  if (deviceSelect.length > 0) {
                      deviceSelect.html(response.device_html);
                      if (deviceSelect.hasClass('selectpicker')) {
                          deviceSelect.selectpicker("refresh");
                      }
                  }

                  // Initialize billing and shipping details if function exists
                  if (typeof init_billing_and_shipping_details === 'function') {
                      init_billing_and_shipping_details();
                  }

                  init_repair_job_currency();
              });
        });


        $("body").on( "change", 'td.discount input,input[name="discount_total"]',
            function () {
                if (
                    $('select[name="discount_type"]').val() === "" &&
                    $(this).val() != 0
                    ) {

                    alert("You need to select discount type");
                $("html,body").animate(
                {
                    scrollTop: 0,
                },
                "slow"
                );
                $("#wrapper").highlight($('label[for="discount_type"]').text());
                setTimeout(function () {
                    $("#wrapper").unhighlight();
                }, 3000);
                return false;
            }
            if ($(this).valid() === true) {
                labour_product_calculate_total();
            }
        }
        );
        $("body").on('change', 'select.taxes', function () {
            "use strict";

            labour_product_calculate_total();
            part_calculate_total();
        });
        $("body").on('change', 'select[name="discount_type"]', function () {
            "use strict";

            labour_product_calculate_total();
            part_calculate_total();
        });
        $("body").on('change', 'select[name="discount_option"]', function () {
            "use strict";

            labour_product_calculate_total();
            part_calculate_total();
        });

    });

function init_repair_job_currency(id, callback) {
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

// SubmitHandler removido para evitar duplicação de submissões
// A submissão agora é tratada pela função submit_form()

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

// labour product
function labour_product_calculate_total(){
        "use strict";

        if ($('body').hasClass('no-calculate-total')) {
            return false;
        }

        var calculated_tax,
        taxrate,
        item_taxes,
        row,
        _amount,
        _tax_name,
        taxes = {},
        taxes_rows = [],
        subtotal = 0,
        total = 0,
        total_money = 0,
        total_tax_money = 0,
        quantity = 1,
        total_estimated_hours = 0,
        estimated_hours = 0,
        labour_type = 'fixed',
        total_discount_calculated = 0,
        item_discount_percent = 0,
        item_discount = 0,
        total_tax_calculated = 0,
        item_total_payment,
        rows = $('.table.labour_product-items-table.has-calculations tbody tr.item'),
        subtotal_area = $('#labour_product_subtotal'),
        discount_area = $('#labour_product_discount_area'),
        // discount_percent = $('input[name="discount_percent"]').val(),
        discount_percent = 'before_tax',
        discount_fixed = $('input[name="discount_total"]').val(),
        discount_total_type = 'percent',
        discount_type = $('select[name="discount_type"]').val();

        $('.labour_product-tax-area').remove();

        $.each(rows, function () {

            var item_tax = 0,
            item_amount  = 0,
            item_discount  = 0
            ;

            quantity = $(this).find('td.qty input').val();
            estimated_hours = $(this).find('td.estimated_hours input').val();
            labour_type = $(this).find('td.labour_type input').val();
            if (quantity === '') {
                quantity = 1;
                $(this).find('td.qty input').val(1);
            }
            item_discount_percent = $(this).find('td.discount input').val();

            if (isNaN(item_discount_percent) || item_discount_percent == '') {
                item_discount_percent = 0;
            }

            if(labour_type == 'fixed'){
                _amount = accounting.toFixed($(this).find('td.unit_price input').val() * quantity, app.options.decimal_places);
            }else{
                _amount = accounting.toFixed($(this).find('td.unit_price input').val() * quantity * estimated_hours, app.options.decimal_places);
            }
            total_estimated_hours += parseFloat(estimated_hours);
            item_amount = _amount;
            _amount = parseFloat(_amount);

            subtotal += _amount;
            row = $(this);
            item_taxes = $(this).find('select.taxes').val();

            if (item_taxes) {
                $.each(item_taxes, function (i, taxname) {
                    taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
                    calculated_tax = (_amount / 100 * taxrate);
                    item_tax += calculated_tax;
                    if (!taxes.hasOwnProperty(taxname)) {
                        if (taxrate != 0) {
                            _tax_name = taxname.split('|');
                            var tax_row = '<tr class="labour_product-tax-area"><td>' + _tax_name[0] + '(' + taxrate + '%)</td><td id="labour_tax_id_' + slugify(taxname) + '"></td></tr>';
                            $(subtotal_area).after(tax_row);
                            taxes[taxname] = calculated_tax;
                        }
                    } else {
                                        // Increment total from this tax
                        taxes[taxname] = taxes[taxname] += calculated_tax;
                    }
                });
            }

                // Discount by percent
            if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'before_tax' && discount_total_type == 'percent') {
                item_discount = (item_amount * item_discount_percent) / 100;
            } else if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'before_tax' && discount_total_type == 'fixed_amount') {
                item_discount = item_discount_percent;
            }

            // Discount by percent
            if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'after_tax' && discount_total_type == 'percent') {
                item_discount = ((parseFloat(item_amount) + parseFloat(item_tax) ) * item_discount_percent) / 100;
            } else if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'after_tax' && discount_total_type == 'fixed_amount') {
                item_discount = item_discount_percent;
            }

            //Discount of item
            item_total_payment = parseFloat(item_amount) + parseFloat(item_tax) - parseFloat(item_discount);

            // Append value to item
            total_discount_calculated += parseFloat(item_discount);

            $(this).find('td.label_tax_amount').html(format_money(item_tax));
            $(this).find('td.label_discount_amount').html(format_money(item_discount));
            $(this).find('td.tax_amount input').val(item_tax);
            $(this).find('td.discount_amount input').val(item_discount);
            $(this).find('td.amount').html(format_money(item_amount));
            $(this).find('td.sub_total input').val(parseFloat(item_amount));
        });

        $.each(taxes, function (taxname, total_tax) {
            total += total_tax;
            total_tax_money += total_tax;
            total_tax = format_money(total_tax);
            $('#labour_tax_id_' + slugify(taxname)).html(total_tax);
        });

        total = (total + subtotal);
        total_money = total;

        total = total - total_discount_calculated;

        // Append, format to html and display
        $('.labour_product_discount_area').html(format_money(total_discount_calculated) + hidden_input('estimated_labour_discount_total', accounting.toFixed(total_discount_calculated, app.options.decimal_places)));

        $('.labour_product_subtotal').html(format_money(subtotal) + hidden_input('estimated_labour_subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
        $('.wh-total-tax').html(format_money(total_tax_money));
        $('.labour_product_total').html(format_money(total) + hidden_input('estimated_labour_total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('estimated_labour_total_tax', accounting.toFixed(total_tax_money, app.options.decimal_places))+ hidden_input('estimated_hours', accounting.toFixed(total_estimated_hours, app.options.decimal_places)));
        repair_job_calculate_total();

        $(document).trigger('wshop-labour-product-total-calculated');
    }

    function labour_product_get_item_preview_values(row, labour_product_id) {
        "use strict";

        var response = {};
        response.labour_product_id = labour_product_id;
        response.estimated_hours = $(row).parents('tr').find('td input[name="standard_time"]').val();
        return response;
    }

    function labour_product_reorder_items(parent) {
        "use strict";

        var rows = $(parent + ' .table.labour_product-items-table.has-calculations tbody tr.item');
        var i = 1;
        $.each(rows, function () {
            $(this).find('input.order').val(i);
            i++;
        });
    }

    function labour_product_get_item_row_template(name, labour_product_id, estimated_hours, item_key, part_item_key)  {
        "use strict";

        jQuery.ajaxSetup({
         async: false
     });

        var d = $.post(admin_url + 'workshop/get_labour_product_row_template', {
            name: name,
            labour_product_id: labour_product_id,
            estimated_hours: estimated_hours,
            item_key: item_key,
            part_item_key: part_item_key,
        });
        jQuery.ajaxSetup({
         async: true
     });
        return d;
    }

    function labour_product_delete_item(row, itemid, parent) {
        "use strict";

        $(row).parents('tr').addClass('animated fadeOut', function () {
            setTimeout(function () {
                $(row).parents('tr').remove();
                labour_product_calculate_total();
                calculated_estimated_completion_date();

            }, 50);
        });
        if (itemid && $('input[name="isedit"]').length > 0) {
            $(parent+' #removed-labour-product-items').append(hidden_input('removed_labour_product_items[]', itemid));
        }
    }

    // part
    function part_calculate_total(){
        "use strict";

        if ($('body').hasClass('no-calculate-total')) {
            return false;
        }

        var calculated_tax,
        taxrate,
        item_taxes,
        row,
        _amount,
        _tax_name,
        taxes = {},
        taxes_rows = [],
        subtotal = 0,
        total = 0,
        total_money = 0,
        total_tax_money = 0,
        quantity = 1,
        estimated_hours = 0,
        labour_type = 'fixed',
        total_discount_calculated = 0,
        item_discount_percent = 0,
        item_discount = 0,
        total_tax_calculated = 0,
        item_total_payment,
        rows = $('.table.part-items-table.has-calculations tbody tr.item'),
        subtotal_area = $('#material_subtotal'),
        discount_area = $('#material_discount_area'),
        // discount_percent = $('input[name="discount_percent"]').val(),
        discount_percent = 'before_tax',
        discount_fixed = $('input[name="discount_total"]').val(),
        discount_total_type = 'percent',
        discount_type = $('select[name="discount_type"]').val();

        $('.part-tax-area').remove();

        $.each(rows, function () {

            var item_tax = 0,
            item_amount  = 0,
            item_discount  = 0
            ;

            quantity = $(this).find('td.qty input').val();
            estimated_hours = $(this).find('td.estimated_hours input').val();
            if (quantity === '') {
                quantity = 1;
                $(this).find('td.qty input').val(1);
            }
            item_discount_percent = $(this).find('td.discount input').val();

            if (isNaN(item_discount_percent) || item_discount_percent == '') {
                item_discount_percent = 0;
            }

            _amount = accounting.toFixed($(this).find('td.rate input').val() * quantity, app.options.decimal_places);

            item_amount = _amount;
            _amount = parseFloat(_amount);

            subtotal += _amount;
            row = $(this);
            item_taxes = $(this).find('select.taxes').val();

            if (item_taxes) {
                $.each(item_taxes, function (i, taxname) {
                    taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
                    calculated_tax = (_amount / 100 * taxrate);
                    item_tax += calculated_tax;
                    if (!taxes.hasOwnProperty(taxname)) {
                        if (taxrate != 0) {
                            _tax_name = taxname.split('|');
                            var tax_row = '<tr class="part-tax-area"><td>' + _tax_name[0] + '(' + taxrate + '%)</td><td id="part_tax_id_' + slugify(taxname) + '"></td></tr>';
                            $(subtotal_area).after(tax_row);
                            taxes[taxname] = calculated_tax;
                        }
                    } else {
                                        // Increment total from this tax
                        taxes[taxname] = taxes[taxname] += calculated_tax;
                    }
                });
            }

                // Discount by percent
            if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'before_tax' && discount_total_type == 'percent') {
                item_discount = (item_amount * item_discount_percent) / 100;
            } else if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'before_tax' && discount_total_type == 'fixed_amount') {
                item_discount = item_discount_percent;
            }

            // Discount by percent
            if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'after_tax' && discount_total_type == 'percent') {
                item_discount = ((parseFloat(item_amount) + parseFloat(item_tax) ) * item_discount_percent) / 100;
            } else if ((item_discount_percent !== '' && item_discount_percent != 0) && discount_type == 'after_tax' && discount_total_type == 'fixed_amount') {
                item_discount = item_discount_percent;
            }

            //Discount of item
            item_total_payment = parseFloat(item_amount) + parseFloat(item_tax) - parseFloat(item_discount);

            // Append value to item
            total_discount_calculated += parseFloat(item_discount);

            $(this).find('td.label_tax_amount').html(format_money(item_tax));
            $(this).find('td.label_discount_amount').html(format_money(item_discount));
            $(this).find('td.tax_amount input').val(item_tax);
            $(this).find('td.discount_amount input').val(item_discount);
            $(this).find('td.amount').html(format_money(item_amount));
            $(this).find('td.sub_total input').val(parseFloat(item_amount));
        });

        $.each(taxes, function (taxname, total_tax) {
            total += total_tax;
            total_tax_money += total_tax;
            total_tax = format_money(total_tax);
            $('#part_tax_id_' + slugify(taxname)).html(total_tax);
        });

        total = (total + subtotal);
        total_money = total;

        total = total - total_discount_calculated;

        // Append, format to html and display
        $('.material_discount_area').html(format_money(total_discount_calculated) + hidden_input('estimated_material_discount_total', accounting.toFixed(total_discount_calculated, app.options.decimal_places)));

        $('.material_subtotal').html(format_money(subtotal) + hidden_input('estimated_material_subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
        $('.wh-total-tax').html(format_money(total_tax_money));
        $('.material_total').html(format_money(total) + hidden_input('estimated_material_total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('estimated_material_total_tax', accounting.toFixed(total_tax_money, app.options.decimal_places)));
        repair_job_calculate_total();
        $(document).trigger('wshop-part-total-calculated');
    }

    function part_get_item_preview_values(row, part_id) {
        "use strict";

        var response = {};
        response.part_id = part_id;
        response.quantity = $(row).parents('tr').find('td input[name="quantity"]').val();
        return response;
    }

    function part_reorder_items(parent) {
        "use strict";

        var rows = $(parent + ' .table.part-items-table.has-calculations tbody tr.item');
        var i = 1;
        $.each(rows, function () {
            $(this).find('input.order').val(i);
            i++;
        });
    }

    function part_get_item_row_template(name, part_id, quantity, item_key)  {
        "use strict";

        jQuery.ajaxSetup({
         async: false
     });

        var d = $.post(admin_url + 'workshop/get_part_row_template', {
            name: name,
            part_id: part_id,
            quantity: quantity,
            item_key: item_key,
        });
        jQuery.ajaxSetup({
         async: true
     });
        return d;
    }

    function part_delete_item(row, itemid, parent) {
        "use strict";

        $(row).parents('tr').addClass('animated fadeOut', function () {
            setTimeout(function () {
                $(row).parents('tr').remove();
                part_calculate_total();
            }, 50);
        });
        if (itemid && $('input[name="isedit"]').length > 0) {
            $(parent+' #removed-part-items').append(hidden_input('removed_part_items[]', itemid));
        }
    }

    function repair_job_calculate_total()
    {
        "use strict";

        // Append, format to html and display
        var estimated_labour_discount_total = 0,
        estimated_labour_subtotal = 0,
        estimated_labour_total_tax = 0,
        estimated_labour_total = 0,
        estimated_material_discount_total = 0,
        estimated_material_subtotal = 0,
        estimated_material_total_tax = 0,
        estimated_material_total = 0,
        discount_total = 0,
        subtotal = 0,
        total_tax = 0,
        total = 0
        ;

        if($('input[name="estimated_labour_discount_total"]').val() != '' && $('input[name="estimated_labour_discount_total"]').val() != undefined){
            estimated_labour_discount_total = parseFloat($('input[name="estimated_labour_discount_total"]').val());
        }
        if($('input[name="estimated_labour_subtotal"]').val() != '' && $('input[name="estimated_labour_subtotal"]').val() != undefined){
            estimated_labour_subtotal = parseFloat($('input[name="estimated_labour_subtotal"]').val());
        }
        if($('input[name="estimated_labour_total_tax"]').val() != '' && $('input[name="estimated_labour_total_tax"]').val() != undefined){
            estimated_labour_total_tax = parseFloat($('input[name="estimated_labour_total_tax"]').val());
        }
        if($('input[name="estimated_labour_total"]').val() != '' && $('input[name="estimated_labour_total"]').val() != undefined){
            estimated_labour_total = parseFloat($('input[name="estimated_labour_total"]').val());
        }
        if($('input[name="estimated_material_discount_total"]').val() != '' && $('input[name="estimated_material_discount_total"]').val() != undefined){
            estimated_material_discount_total = parseFloat($('input[name="estimated_material_discount_total"]').val());
        }
        if($('input[name="estimated_material_subtotal"]').val() != '' && $('input[name="estimated_material_subtotal"]').val() != undefined){
            estimated_material_subtotal = parseFloat($('input[name="estimated_material_subtotal"]').val());
        }
        if($('input[name="estimated_material_total_tax"]').val() != '' && $('input[name="estimated_material_total_tax"]').val() != undefined){
            estimated_material_total_tax = parseFloat($('input[name="estimated_material_total_tax"]').val());
        }
        if($('input[name="estimated_material_total"]').val() != '' && $('input[name="estimated_material_total"]').val() != undefined){
            estimated_material_total = parseFloat($('input[name="estimated_material_total"]').val());
        }

        subtotal = estimated_labour_subtotal + estimated_material_subtotal; 
        discount_total = estimated_labour_discount_total + estimated_material_discount_total;
        total_tax = estimated_labour_total_tax + estimated_material_total_tax;
        
        // Calculate total before ISS retention
        var total_before_retention = estimated_labour_total + estimated_material_total;
        
        // Get ISS retention percentage and calculate amount
        var iss_retention_percent = 0;
        var iss_retention_amount = 0;
        
        if($('input[name="iss_retention_percent"]').val() != '' && $('input[name="iss_retention_percent"]').val() != undefined){
            iss_retention_percent = parseFloat($('input[name="iss_retention_percent"]').val());
            if(iss_retention_percent > 0 && total_before_retention > 0) {
                iss_retention_amount = (total_before_retention * iss_retention_percent) / 100;
            }
        }
        
        // Calculate final total with ISS retention applied
        total = total_before_retention - iss_retention_amount;

        $('.t_subtotal').html(format_money(subtotal) + hidden_input('subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
        $('.discount_area').html(format_money(discount_total) + hidden_input('discount_total', accounting.toFixed(discount_total, app.options.decimal_places)));
        $('.total_tax_area').html(format_money(total_tax));
        $('.iss_retention_amount_display').html(format_money(iss_retention_amount));
        $('.total').html(format_money(total) + hidden_input('total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('total_tax', accounting.toFixed(total_tax, app.options.decimal_places)) + hidden_input('iss_retention_percent', accounting.toFixed(iss_retention_percent, app.options.decimal_places)) + hidden_input('iss_retention_amount', accounting.toFixed(iss_retention_amount, app.options.decimal_places)));
    }

    function submit_form(argument) {
        "use strict";

        // On submit re-calculate total and reorder the items for all cases.
        labour_product_calculate_total();
        part_calculate_total();

        var $itemsTable = $('.labour_product-items-table');
        if ( $itemsTable.length && $itemsTable.find('.item').length === 0) {
            alert_float('warning', '<?php echo _l('wshop_enter_at_least_one_labour_product'); ?>', 3000);
            return false;
        }

        // Remove the disabled attribute from the disabled fields because if they are disabled won't be sent with the request.
        $('select[name="currency"]').prop("disabled", false);

        // Prevent multiple submissions
        var form = $(".repair_job-form");
        if (form.data('submitting')) {
            return false;
        }
        
        form.data('submitting', true);
        
        // Disable submit buttons to prevent double click
        $('.repair-submit').prop('disabled', true);
        
        // Submit form traditionally (no AJAX)
        form[0].submit();
    }

    $("body").on("click", ".repair-submit", function () {
        "use strict";

        var that = $(this);
        var form = that.parents("form._repair_form");
        
        // Prevent multiple clicks
        if (that.prop('disabled')) {
            return false;
        }
        
        if (form.valid()) {
            if (that.hasClass("save-as-draft")) {
                form.append(hidden_input("save_as_draft", "true"));
            } else if (that.hasClass("save-and-send")) {
                form.append(hidden_input("save_and_send", "true"));
            } else if (that.hasClass("save-and-record-payment")) {
                form.append(hidden_input("save_and_record_payment", "true"));
            } else if (that.hasClass("save-and-send-later")) {
                form.append(hidden_input("save_and_send_later", "true"));
            }
        }
        submit_form();

    });

    function calculated_estimated_completion_date() {
        "use strict";
        
        var estimated_hours = $('input[name="estimated_hours"]').val();
        requestGetJSON( "workshop/calculated_estimated_completion_date/" + estimated_hours ).done(function (response) {
            $('input[name="estimated_completion_date"]').val(response.estimated_completion_date);
        });  
    }

    /**
     * Sincroniza o campo appointment_date com estimated_completion_date
     * Mantém a mesma data mas adiciona 2 horas ao horário
     */
    function syncAppointmentWithEstimatedDate() {
        'use strict';
        
        // Evento de mudança no campo appointment_date
        $('body').on('change', 'input[name="appointment_date"]', function() {
            var appointmentValue = $(this).val();
            
            if (appointmentValue && appointmentValue.trim() !== '') {
                try {
                    // Parse da data e hora do appointment_date
                    var appointmentDate = new Date(appointmentValue.replace(/\//g, '-'));
                    
                    if (!isNaN(appointmentDate.getTime())) {
                        // Adicionar 2 horas
                        appointmentDate.setHours(appointmentDate.getHours() + 2);
                        
                        // Formatar a data para o formato esperado (YYYY-MM-DD HH:mm)
                        var year = appointmentDate.getFullYear();
                        var month = String(appointmentDate.getMonth() + 1).padStart(2, '0');
                        var day = String(appointmentDate.getDate()).padStart(2, '0');
                        var hours = String(appointmentDate.getHours()).padStart(2, '0');
                        var minutes = String(appointmentDate.getMinutes()).padStart(2, '0');
                        
                        var estimatedDateValue = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
                        
                        // Atualizar o campo estimated_completion_date
                        $('input[name="estimated_completion_date"]').val(estimatedDateValue);
                    }
                } catch (error) {
                    console.log('Erro ao processar data de agendamento:', error);
                }
            }
        });
    }
    
    // Debounce function para evitar chamadas excessivas
    var debounceTimer;
    function debounce(func, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(func, delay);
    }
    
    // Event handler para mudança na taxa de retenção ISS
    $('body').on('change', 'select[name="iss_retention_rate_id"]', function() {
        'use strict';
        
        var selectedOption = $(this).find('option:selected');
        var percentage = selectedOption.data('percentage') || 0;
        
        // Atualizar o campo de percentual
        $('input[name="iss_retention_percent"]').val(percentage);
        
        // Recalcular o total com debounce
        debounce(function() {
            repair_job_calculate_total();
        }, 300);
    });
    
</script>