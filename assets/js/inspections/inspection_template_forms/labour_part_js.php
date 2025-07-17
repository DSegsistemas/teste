<script type="text/javascript">
    $(function(){
        'use strict';

        $( document ).ready(function() {
            init_inspection_currency()
            labour_product_calculate_total();
            part_calculate_total();
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
    rows = $('#template_form_'+active_form_id+" "+ '.table.labour_product-items-table.has-calculations tbody tr.item'),
    subtotal_area = $('#template_form_'+active_form_id+" "+'#labour_product_subtotal'),
    discount_area = $('#template_form_'+active_form_id+" "+'#labour_product_discount_area'),
        // discount_percent = $('input[name="discount_percent"]').val(),
    discount_percent = 'before_tax',
    discount_fixed = $('input[name="discount_total"]').val(),
    discount_total_type = 'percent',
    discount_type = $('select[name="discount_type"]').val();

    $('#template_form_'+active_form_id+" "+'.labour_product-tax-area').remove();

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
        $('#template_form_'+active_form_id+" "+'#labour_tax_id_' + slugify(taxname)).html(total_tax);
    });

    total = (total + subtotal);
    total_money = total;

    total = total - total_discount_calculated;

        // Append, format to html and display
    $('#template_form_'+active_form_id+" "+'.labour_product_discount_area').html(format_money(total_discount_calculated) + hidden_input('estimated_labour_discount_total', accounting.toFixed(total_discount_calculated, app.options.decimal_places)));

    $('#template_form_'+active_form_id+" "+'.labour_product_subtotal').html(format_money(subtotal) + hidden_input('estimated_labour_subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
    $('#template_form_'+active_form_id+" "+'.wh-total-tax').html(format_money(total_tax_money));
    $('#template_form_'+active_form_id+" "+'.labour_product_total').html(format_money(total) + hidden_input('estimated_labour_total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('estimated_labour_total_tax', accounting.toFixed(total_tax_money, app.options.decimal_places))+ hidden_input('estimated_hours', accounting.toFixed(total_estimated_hours, app.options.decimal_places)));
    repair_job_calculate_total();

    $(document).trigger('wshop-labour-product-total-calculated');
}

function labour_product_get_item_preview_values(row, labour_product_id, inspection_form_detail_id) {
    "use strict";

    var response = {};
    response.labour_product_id = labour_product_id;
    response.inspection_id = inspection_id;
    response.inspection_form_id = active_form_id;
    response.inspection_form_detail_id = inspection_form_detail_id;

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

function labour_product_get_item_row_template(name, labour_product_id, inspection_id, inspection_form_id, inspection_form_detail_id, estimated_hours, item_key, part_item_key)  {
    "use strict";

    jQuery.ajaxSetup({
       async: false
   });

    var d = $.post(admin_url + 'workshop/inspection_get_labour_product_row_template', {
        name: name,
        labour_product_id: labour_product_id,
        inspection_id: inspection_id,
        inspection_form_id: inspection_form_id,
        inspection_form_detail_id: inspection_form_detail_id,
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
    rows = $('#template_form_'+active_form_id+" "+'.table.part-items-table.has-calculations tbody tr.item'),
    subtotal_area = $('#template_form_'+active_form_id+" "+'#material_subtotal'),
    discount_area = $('#template_form_'+active_form_id+" "+'#material_discount_area'),
        // discount_percent = $('#template_form_'+active_form_id+" "+'input[name="discount_percent"]').val(),
    discount_percent = 'before_tax',
    discount_fixed = $('#template_form_'+active_form_id+" "+'input[name="discount_total"]').val(),
    discount_total_type = 'percent',
    discount_type = $('#template_form_'+active_form_id+" "+'select[name="discount_type"]').val();

    $('#template_form_'+active_form_id+" "+'.part-tax-area').remove();

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
    $('#template_form_'+active_form_id+" "+'.material_discount_area').html(format_money(total_discount_calculated) + hidden_input('estimated_material_discount_total', accounting.toFixed(total_discount_calculated, app.options.decimal_places)));

    $('#template_form_'+active_form_id+" "+'.material_subtotal').html(format_money(subtotal) + hidden_input('estimated_material_subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
    $('#template_form_'+active_form_id+" "+'.wh-total-tax').html(format_money(total_tax_money));
    $('#template_form_'+active_form_id+" "+'.material_total').html(format_money(total) + hidden_input('estimated_material_total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('estimated_material_total_tax', accounting.toFixed(total_tax_money, app.options.decimal_places)));
    repair_job_calculate_total();
    $(document).trigger('wshop-part-total-calculated');
}

function part_get_item_preview_values(row, part_id, inspection_form_detail_id) {
    "use strict";

    var response = {};
    response.part_id = part_id;
    response.inspection_id = inspection_id;
    response.inspection_form_id = active_form_id;
    response.inspection_form_detail_id = inspection_form_detail_id;
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

function part_get_item_row_template(name, part_id, inspection_id, inspection_form_id, inspection_form_detail_id, quantity, item_key)  {
    "use strict";

    jQuery.ajaxSetup({
       async: false
   });

    var d = $.post(admin_url + 'workshop/inspection_get_part_row_template', {
        name: name,
        part_id: part_id,
        inspection_id: inspection_id,
        inspection_form_id: inspection_form_id,
        inspection_form_detail_id: inspection_form_detail_id,
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
        $('#template_form_'+active_form_id+" "+parent+' #removed-part-items').append(hidden_input('removed_part_items[]', itemid));
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

    var total_form = $('.tab-content .tab-pane');

    $.each(total_form, function () {

        if($(this).find('input[name="estimated_labour_discount_total"]').val() != '' && $(this).find('input[name="estimated_labour_discount_total"]').val() != undefined){
            estimated_labour_discount_total = parseFloat($(this).find('input[name="estimated_labour_discount_total"]').val());
        }
        if($(this).find('input[name="estimated_labour_subtotal"]').val() != '' && $(this).find('input[name="estimated_labour_subtotal"]').val() != undefined){
            estimated_labour_subtotal = parseFloat($(this).find('input[name="estimated_labour_subtotal"]').val());
        }
        if($(this).find('input[name="estimated_labour_total_tax"]').val() != '' && $(this).find('input[name="estimated_labour_total_tax"]').val() != undefined){
            estimated_labour_total_tax = parseFloat($(this).find('input[name="estimated_labour_total_tax"]').val());
        }
        if($(this).find('input[name="estimated_labour_total"]').val() != '' && $(this).find('input[name="estimated_labour_total"]').val() != undefined){
            estimated_labour_total = parseFloat($(this).find('input[name="estimated_labour_total"]').val());
        }
        if($(this).find('input[name="estimated_material_discount_total"]').val() != '' && $(this).find('input[name="estimated_material_discount_total"]').val() != undefined){
            estimated_material_discount_total = parseFloat($(this).find('input[name="estimated_material_discount_total"]').val());
        }
        if($(this).find('input[name="estimated_material_subtotal"]').val() != '' && $(this).find('input[name="estimated_material_subtotal"]').val() != undefined){
            estimated_material_subtotal = parseFloat($(this).find('input[name="estimated_material_subtotal"]').val());
        }
        if($(this).find('input[name="estimated_material_total_tax"]').val() != '' && $(this).find('input[name="estimated_material_total_tax"]').val() != undefined){
            estimated_material_total_tax = parseFloat($(this).find('input[name="estimated_material_total_tax"]').val());
        }
        if($(this).find('input[name="estimated_material_total"]').val() != '' && $(this).find('input[name="estimated_material_total"]').val() != undefined){
            estimated_material_total = parseFloat($(this).find('input[name="estimated_material_total"]').val());
        }

        subtotal += estimated_labour_subtotal + estimated_material_subtotal; 
        discount_total += estimated_labour_discount_total + estimated_material_discount_total;
        total_tax += estimated_labour_total_tax + estimated_material_total_tax;
        total += estimated_labour_total + estimated_material_total;
    });

    $('#template_form_'+active_form_id+" "+'.t_subtotal').html(format_money(subtotal) + hidden_input('subtotal', accounting.toFixed(subtotal, app.options.decimal_places)));
    $('#template_form_'+active_form_id+" "+'.discount_area').html(format_money(discount_total) + hidden_input('discount_total', accounting.toFixed(discount_total, app.options.decimal_places)));
    $('#template_form_'+active_form_id+" "+'.total_tax_area').html(format_money(total_tax));
    $('#template_form_'+active_form_id+" "+'.total').html(format_money(total) + hidden_input('total', accounting.toFixed(total, app.options.decimal_places))+ hidden_input('total_tax', accounting.toFixed(total_tax, app.options.decimal_places)));
}

$('body').on('click', 'input[type="radio"]', function() {
    "use strict";

    var comment = $(this).attr('data-comment');
    var value = $(this).attr('value');
    if(value == 'good'){
        $('textarea[name="'+comment+'"]').addClass('hide');
    }else if(value == 'repair'){
        $('textarea[name="'+comment+'"]').removeClass('hide');
    }
});



</script>