<script type="text/javascript">
    $(function() {
        "use strict";

        $( document ).ready(function() {
            appValidateForm($('#add_edit_custom_field'), {
                fieldto: 'required',
                name: 'required',
                type: 'required',
                bs_column: 'required',
                options: {
                    required: {
                        depends: function(element) {
                            return ['select', 'checkbox', 'multiselect'].indexOf($('#type').val()) > -1
                        }
                    }
                }
            }, function(form) {
                validateDefaultValueField().then(function(validation) {
                    if (validation.valid) {
                        $('#fieldto,#type').prop('disabled', false);

                        $.post(form.action, $(form).serialize(), function(data) {
                            $("#custom_fieldModal").modal("hide");
                            $('.table-customfield_table').DataTable().ajax.reload();

                        }, 'json');
                    }
                });

                return false;
            });
        });

        $('.add_new_row').on('click', function() {
            "use strict";

            var parent = $(this).parents('.list-option');
            var row = parent.find('.row').eq(0).clone().appendTo('.list-option');
            row.find('button').removeClass('add_new_row').addClass('remove_row').removeClass('btn-success').addClass('btn-danger').find('i').removeClass('fa-plus').addClass('fa-minus');
            row.find('input').val('');
        });
        $(document).on("click", ".remove_row", function() { 
            "use strict";

            $(this).parents('.row').remove();
        });


        $('select[name="type"]').on('change', function() {
            "use strict";

            var type = $(this).val();
            var options_wrapper = $('#options_wrapper');
            var display_inline = $('.display-inline-checkbox')
            var default_value = $('#default-value-field');

            $('textarea.default-value, input.default-value').val('');

            if (type !== 'link' && type !== 'textarea') {
                $('textarea.default-value').removeAttr('name');
                $('input.default-value').attr('name', 'default_value');
                $('.default-value-textarea-input').addClass('hide');
                $('.default-value-text-input').removeClass('hide');
            }

            if (type == 'select' || type == 'checkbox' || type == 'multiselect') {
                options_wrapper.removeClass('hide');
                if (type == 'checkbox') {
                    display_inline.removeClass('hide');
                } else {
                    display_inline.addClass('hide');
                    display_inline.find('input').prop('checked', false);
                }
            } else if (type === 'link') {
                default_value.addClass('hide');
            } else if (type === 'textarea') {
                $('textarea.default-value').attr('name', 'default_value');
                $('input.default-value').removeAttr('name');
                $('.default-value-textarea-input').removeClass('hide');
                $('.default-value-text-input').addClass('hide');
                options_wrapper.addClass('hide');

            } else {
                options_wrapper.addClass('hide');
                display_inline.addClass('hide');
                default_value.removeClass('hide')
                display_inline.find('input').prop('checked', false);
            }

            validateDefaultValueField();
        });

        $('body').on('change', 'input[name="only_admin"]', function() {
            "use strict";

            $('#show_on_client_portal').prop('disabled', $(this).prop('checked')).prop('checked', false);
            $('#disalow_client_to_edit').prop('disabled', $(this).prop('checked')).prop('checked', false);
        });

        $('body').on('blur', '[name="default_value"], #options', function() {
            validateDefaultValueField();
        });
    });

    function validateDefaultValueField() {
        "use strict";


        var value = $('[name="default_value"]').val();
        var type = $('#type').val();

        var message = '';
        var valid = jQuery.Deferred();
        var $error = $('#default-value-error');
        var $label = $('label[for="default_value"]');
        $label.find('.sample').remove();

        if (type == '') {
            $error.addClass('hide');
            return;
        }

        if (value) {
            value = value.trim();
        }

        switch (type) {
        case 'input':
        case 'link':
        case 'textarea':
            valid.resolve({
                valid: true,
            });
            break;
        case 'number':
            valid.resolve({
                valid: value === '' ? true : new RegExp(/^-?(?:\d+|\d*\.\d+)$/).test(value),
                message: 'Enter a valid number.',
            });
            break;
        case 'multiselect':
        case 'checkbox':
        case 'select':
            if (value === '') {
                valid.resolve({
                    valid: true,
                });
            } else {
                var defaultOptions = value.split(',')
                .map(function(option) {
                    return option.trim();
                }).filter(function(option) {
                    return option !== ''
                });

                if (type === 'select' && defaultOptions.length > 1) {
                    valid.resolve({
                        valid: true,
                        message: 'You cannot have multiple options selected on "Select" field type.',
                    });
                } else {
                    var availableOptions = $('#options').val().split(',')
                    .map(function(option) {
                        return option.trim();
                    }).filter(function(option) {
                        return option !== ''
                    });

                    var nonExistentOptions = defaultOptions.filter(function(i) {
                        return availableOptions.indexOf(i) < 0;
                    });

                    valid.resolve({
                        valid: nonExistentOptions.length === 0,
                        message: nonExistentOptions.join(',') +
                        ' options are not available in the options field.',
                    });
                }
            }

            break;
        case 'date_picker':
        case 'date_picker_time':

            if (value !== '') {
                $.post(admin_url + 'custom_fields/validate_default_date', {
                    date: value,
                    type: type,
                }, function(data) {
                    valid.resolve({
                        valid: data.valid,
                        message: 'Enter date in ' + (type === 'date_picker' ? 'Y-m-d' : 'Y-m-d H:i') +
                        ' format or English date format for the PHP "<a href=\'https://www.php.net/manual/en/function.strtotime.php\'" target="_blank">strtotime</a> function.',
                    });

                    if (data.valid) {
                        $label.append(' <small class="sample">Sample: ' + data.sample + '</small>');
                    }
                }, 'json');
            } else {
                valid.resolve({
                    valid: true,
                });
            }

            break;
        case 'colorpicker':
            valid.resolve({
                valid: value === '' ? true : new RegExp(/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/gm).test(value),
                message: 'Enter color in HEX format, for example: #f2dede',
            })
            break;
        }

        valid.done(function(validation) {
            $('#submitForm').prop('disabled', !validation.valid);
            validation.valid ? $error.addClass('hide') : $error.removeClass('hide');
            $error.html(validation.message);
        });

        return valid;
    }


</script>