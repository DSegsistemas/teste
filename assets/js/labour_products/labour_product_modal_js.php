<script type="text/javascript">
    var expenseDropzone;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});
        });

        $('#add_edit_labour_product').appFormValidator({
            rules: {
                code: 'required',
                name: {
                    required: true,
                    remote: {
                        url: admin_url + "workshop/labour_product_exists",
                        type: 'post',
                        data: {
                            name: function() {
                                return $('input[name="name"]').val();
                            },
                            id: function() {
                                return $('input[name="id"]').val();
                            }
                        }
                    }
                }
            },
            onSubmit: SubmitHandler,
            messages: {
                name: '<?php echo _l("wshop_labour_product_already_exists"); ?>',
            },
        });

    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_labour_product');

        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("description", tinyMCE.activeEditor.getContent());

        $('#box-loading').show();
        $('.labour_product_submit_button').attr( "disabled", "disabled" );

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

            if(response.success == true || response.success == 'true'){
                alert_float('success', response.message);
            }
            
            $("#labour_productModal").modal("hide");
            $('.table-labour_product_table').DataTable().ajax.reload();

        });
        return false;
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