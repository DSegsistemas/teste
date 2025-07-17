<script type="text/javascript">
    $(function() {
        "use strict";

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});
      

        appValidateForm($('#add_edit_inspection_template_form_detail'), {
            name: 'required',
            type: 'required',
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

                    $.post(form.action, $(form).serialize()).done(function (response) {
                        response = JSON.parse(response);

                        if(response.status == true || response.status == 'true'){
                            alert_float('success', response.message);
                        }
                        var inspection_template_form_id = $(form).find('input[name="inspection_template_form_id"]').val();
                        if(response.type == 'update'){
                            var id = $(form).find('input[name="id"]').val();
                            $("#form_question_"+inspection_template_form_id+"_"+id).replaceWith(response.inspection_template_form_question_html);
                        }else{
                            // insert
                            $("#form_detail_"+inspection_template_form_id).prepend(response.inspection_template_form_question_html);
                            $("html,body").animate(
                            {
                                scrollTop: $('#wrapper').height(),
                            },
                            "slow"
                            );
                        }
                        init_selectpicker();
                        $("#inspection_template_form_detailModal").modal("hide");

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
            default_value.addClass('hide');

            validateDefaultValueField();
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
        case 'attachment':
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


</script>