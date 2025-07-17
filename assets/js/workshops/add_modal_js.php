<script type="text/javascript">
    var expenseDropzone, repair_job_id;
    $(function(){
        'use strict';

        $( document ).ready(function() {
            form_init_editor('.tinymce', {height:150, auto_focus: true});

        $('#add_edit_workshop').appFormValidator({
            rules: {
                name: 'required',
                repair_job_id: 'required',
                sale_agent: 'required',
                
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
            expenseDropzone = new Dropzone("#add_edit_workshop", appCreateDropzoneOptions({
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
                        
                        var workshop_detail = $('input[name="workshop_detail"]').val();
                        if(workshop_detail == 1){
                            window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=workshop');
                        }else{
                            $("#workshopModal").modal("hide");
                            $('.table-workshop_table').DataTable().ajax.reload();
                        }

                    }else{
                        expenseDropzone.processQueue();
                    }
                },
            }));
        }

        });


    });

    function SubmitHandler(form) {
        "use strict";

        form = $('#add_edit_workshop');
        $('#add_edit_workshop select[name="repair_job_id"]').prop("disabled", false);
        var workshop_id = 0;
        repair_job_id = $('#add_edit_workshop select[name="repair_job_id"]').val();
        var formURL = form[0].action;
        var formData = new FormData($(form)[0]);
        formData.append("description", tinyMCE.activeEditor.getContent());

        $('#box-loading').show();
        $('.workshop_submit_button').attr( "disabled", "disabled" );

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
            if (response.workshop_id) {
                workshop_id = response.workshop_id;
                if(typeof(expenseDropzone) !== 'undefined'){
                    if (expenseDropzone.getQueuedFiles().length > 0) {
                        
                        expenseDropzone.options.url = admin_url + 'workshop/add_workshop_attachment/' + response.workshop_id;
                        expenseDropzone.processQueue();

                    } else {
                        if(response.success == true || response.success == 'true'){
                            alert_float('success', response.message);
                        }
                        
                        var workshop_detail = $('input[name="workshop_detail"]').val();
                        if(workshop_detail == 1){
                            window.location.assign('<?php echo admin_url('workshop/repair_job_detail/') ?>'+repair_job_id+'?tab=workshop');
                        }else{
                            $("#workshopModal").modal("hide");
                            $('.table-workshop_table').DataTable().ajax.reload();
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
            $("#workshopModal").modal("hide");
            
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

</script>