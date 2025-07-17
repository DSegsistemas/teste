$(function() {
    // Verificar se a tabela existe
    if ($('#iss_retention_table').length === 0) {
        return;
    }
    
    // Aplicar tooltips diretamente na tabela sem DataTable
    $('[data-toggle="tooltip"]').tooltip();
    
    // Adicionar funcionalidade de ordenação simples se necessário
    $('#iss_retention_table').addClass('table-hover');

    // Validação do formulário com mensagens traduzidas
    $('#iss_retention_form').validate({
        rules: {
            name: {
                required: true,
                minlength: 2
            },
            percentage: {
                required: true,
                number: true,
                min: 0,
                max: 100
            }
        },
        messages: {
            name: {
                required: app.lang.wshop_iss_retention_name_required,
                minlength: app.lang.wshop_iss_retention_name_min_length
            },
            percentage: {
                required: app.lang.wshop_iss_retention_percentage_invalid,
                number: app.lang.wshop_iss_retention_percentage_invalid,
                min: app.lang.wshop_iss_retention_percentage_invalid,
                max: app.lang.wshop_iss_retention_percentage_invalid
            }
        },
        errorElement: 'span',
        errorClass: 'text-danger',
        errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function(element) {
            $(element).closest('.form-group').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).closest('.form-group').removeClass('has-error');
        },
        success: function(element) {
            element.closest('.form-group').removeClass('has-error');
            element.remove();
        },
        submitHandler: function(form) {
            var submitBtn = $(form).find('[type="submit"]');
            var originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html(app.lang.wshop_processing || '<i class="fa fa-spinner fa-spin"></i> Processando...');
            form.submit();
        }
    });

    // Gerenciar modal de formulário
    $('#iss_retention_modal').on('hidden.bs.modal', function() {
        $('#iss_retention_form')[0].reset();
        $('#rate_id').val('');
        $('#iss_retention_modal_title').text(app.lang.wshop_add_iss_retention_rate);
        $('#iss_retention_form').find('.form-group').removeClass('has-error');
        $('#iss_retention_form').find('.text-danger').remove();
    });
});

// Função para adicionar nova taxa
function addNewRate() {
    $('#iss_retention_form')[0].reset();
    $('#rate_id').val('');
    $('#iss_retention_modal_title').text(app.lang.wshop_add_iss_retention_rate);
    $('#iss_retention_modal').modal('show');
}

// Função para editar taxa existente
function editRate(id, name, percentage) {
    $('#rate_id').val(id);
    $('#rate_name').val(name);
    $('#rate_percentage').val(percentage);
    $('#iss_retention_modal_title').text(app.lang.wshop_edit_iss_retention_rate);
    $('#iss_retention_modal').modal('show');
}

// Função para confirmar exclusão
function confirmDeleteRate(id, name, deleteUrl) {
    var message = app.lang.wshop_confirm_delete_iss_retention.replace('{0}', name);
    if (confirm(message)) {
        window.location.href = deleteUrl;
    }
}