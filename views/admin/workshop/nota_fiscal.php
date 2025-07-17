<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="tw-font-semibold">
                                    <i class="fa fa-receipt"></i>
                                    <?php echo _l('wshop_nota_fiscal_title'); ?>
                                </h4>
                                <hr class="hr-panel-heading">
                                <p class="text-muted">
                                    <?php echo _l('wshop_nota_fiscal_description'); ?>
                                </p>
                            </div>
                        </div>
                       
                        <!-- Filtros -->
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <i class="fa fa-filter"></i> <?php echo _l('wshop_nota_fiscal_filters'); ?>
                                        </h4>
                                    </div>
                                    <div class="panel-body">
                                        <div class="_filters">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select class="form-control" id="filter_period" name="period">
                                                    <option value=""><?php echo _l('wshop_custom_period'); ?></option>
                                                    <option value="this_month"><?php echo _l('this_month'); ?></option>
                                                    <option value="last_month"><?php echo _l('last_month'); ?></option>
                                                    <option value="this_year"><?php echo _l('this_year'); ?></option>
                                                    <option value="last_year"><?php echo _l('last_year'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="date" class="form-control" id="filter_date_from" name="date_from" placeholder="<?php echo _l('wshop_start_date_commission'); ?>" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="date" class="form-control" id="filter_date_to" name="date_to" placeholder="<?php echo _l('wshop_end_date_commission'); ?>" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                            <div class="form-group">
                                <select class="form-control" id="filter_client" name="client_id">
                                    <option value=""><?php echo _l('wshop_all_clients'); ?></option>
                                    <?php if (!empty($clients_in_invoices)) : ?>
                                        <?php foreach ($clients_in_invoices as $client) : ?>
                                            <option value="<?php echo $client->userid; ?>" <?php echo (isset($filters['client_id']) && $filters['client_id'] == $client->userid) ? 'selected' : ''; ?>>
                                                <?php echo $client->company; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                                            <button type="button" class="btn btn-default" id="clear_filters">
                                                <i class="fa fa-refresh"></i> <?php echo _l('clear_filters'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        
                        <?php if (!empty($invoices_with_links)) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table dt-table table-invoices" data-order-col="1" data-order-type="desc" data-page-length="25">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('invoice_number'); ?></th>
                                                <th><?php echo _l('invoice_duedate'); ?></th>
                                                <th><?php echo _l('client'); ?></th>
                                                <th><?php echo _l('invoice_total'); ?></th>
                                                <th><?php echo _l('invoice_status'); ?></th>
                                                <th><?php echo _l('wshop_nota_fiscal'); ?></th>
                                                <th><?php echo _l('wshop_boleto'); ?></th>
                                                <th><?php echo _l('wshop_fatura'); ?></th>
                                                <th><?php echo _l('options'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($invoices_with_links as $invoice) { ?>
                                            <tr>
                                                <td>
                                    <a href="<?php echo admin_url('invoices/list_invoices/' . $invoice->id); ?>" target="_blank">
                                        <?php echo format_invoice_number($invoice->id); ?>
                                        <?php 
                                        // Verificar se esta é a fatura mais recente
                                        if (isset($newest_invoice_id) && $invoice->id == $newest_invoice_id) {
                                            echo ' <span class="label label-success" style="font-size: 10px; margin-left: 5px;">NOVO</span>';
                                        }
                                        ?>
                                    </a>
                                    <div class="row-options">
                                        <a href="#" class="edit-invoice-links" 
                                           data-invoice-id="<?php echo $invoice->id; ?>"
                                           data-nota-fiscal="<?php echo htmlspecialchars($invoice->invoice_link ?? ''); ?>"
                                           data-boleto="<?php echo htmlspecialchars($invoice->boleto_link ?? ''); ?>"
                                           data-fatura="<?php echo htmlspecialchars($invoice->fatura_link ?? ''); ?>">
                                            <?php echo _l('edit'); ?>
                                        </a>
                                        |
                                        <a href="#" class="delete-invoice-row text-danger" 
                                           data-invoice-id="<?php echo $invoice->id; ?>">
                                            <?php echo _l('delete'); ?>
                                        </a>
                                    </div>
                                </td>
                                                <td><?php echo _d($invoice->duedate); ?></td>
                                                <td>
                                                    <a href="<?php echo admin_url('clients/client/' . $invoice->clientid); ?>" target="_blank">
                                                        <?php echo html_entity_decode($invoice->client_name ?? ''); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo app_format_money($invoice->total, get_base_currency()); ?></td>
                                                <td>
                                                    <?php echo format_invoice_status($invoice->status, '', true); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($invoice->invoice_link)) { ?>
                                        <a href="<?php echo $invoice->invoice_link; ?>" target="_blank" class="btn btn-success btn-xs" style="color: white; font-weight: bold;">
                                                            <i class="fa fa-file-text-o"></i> <?php echo _l('view'); ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($invoice->boleto_link)) { ?>
                                                        <a href="<?php echo $invoice->boleto_link; ?>" target="_blank" class="btn btn-primary btn-xs" style="color: white; font-weight: bold;">
                                                            <i class="fa fa-credit-card"></i> <?php echo _l('view'); ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($invoice->fatura_link)) { ?>
                                                        <a href="<?php echo $invoice->fatura_link; ?>" target="_blank" class="btn btn-info btn-xs" style="color: white; font-weight: bold;">
                                                            <i class="fa fa-file-pdf-o"></i> <?php echo _l('view'); ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $hasAnyLink = !empty($invoice->invoice_link) || !empty($invoice->boleto_link) || !empty($invoice->fatura_link);
                                                    if ($hasAnyLink) { ?>
                                                        <button type="button" class="btn btn-warning btn-xs share-invoice-documents" 
                                                                data-invoice-id="<?php echo $invoice->id; ?>"
                                                                data-nota-fiscal="<?php echo htmlspecialchars($invoice->invoice_link ?? ''); ?>"
                                                                data-boleto="<?php echo htmlspecialchars($invoice->boleto_link ?? ''); ?>"
                                                                data-fatura="<?php echo htmlspecialchars($invoice->fatura_link ?? ''); ?>"
                                                                title="<?php echo _l('wshop_share_all_documents'); ?>">
                                                            <i class="fa fa-share-alt"></i> <?php echo _l('share'); ?>
                                                        </button>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info text-center">
                                    <i class="fa fa-info-circle"></i>
                                    <strong><?php echo _l('wshop_nota_fiscal_no_invoices'); ?></strong><br>
                                    <?php echo _l('wshop_nota_fiscal_help_text'); ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar links -->
<div class="modal fade" id="editInvoiceLinksModal" tabindex="-1" role="dialog" aria-labelledby="editInvoiceLinksModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editInvoiceLinksModalLabel"><?php echo _l('wshop_edit_invoice_links'); ?></h4>
            </div>
            <div class="modal-body">
                <form id="editInvoiceLinksForm">
                    <input type="hidden" id="edit_invoice_id" name="invoice_id">
                    
                    <div class="form-group">
                        <label for="edit_nota_fiscal_link"><?php echo _l('wshop_nota_fiscal_link'); ?></label>
                        <input type="url" class="form-control" id="edit_nota_fiscal_link" name="invoice_link" placeholder="<?php echo _l('wshop_nota_fiscal_link_placeholder'); ?>">
                        <small class="form-text text-muted"><?php echo _l('wshop_nota_fiscal_link_help'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_boleto_link"><?php echo _l('wshop_boleto_link'); ?></label>
                        <input type="url" class="form-control" id="edit_boleto_link" name="boleto_link" placeholder="<?php echo _l('wshop_boleto_link_placeholder'); ?>">
                        <small class="form-text text-muted"><?php echo _l('wshop_boleto_link_help'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_fatura_link"><?php echo _l('wshop_fatura_link'); ?></label>
                        <input type="url" class="form-control" id="edit_fatura_link" name="fatura_link" placeholder="<?php echo _l('wshop_fatura_link_placeholder'); ?>">
                        <small class="form-text text-muted"><?php echo _l('wshop_fatura_link_help'); ?></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" id="saveInvoiceLinks"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(document).ready(function(){
    // Inicializar DataTable com configurações padrão do Perfex
    var table = initDataTable('.table-invoices', window.location.href, [], []);
    
    // Definir 'este mês' como filtro padrão se não houver filtros aplicados
    var urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('date_from') && !urlParams.has('date_to') && !urlParams.has('period')) {
        $('#filter_period').val('this_month');
        // Definir as datas do mês atual nos campos
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        $('#filter_date_from').val(firstDay.toISOString().split('T')[0]);
        $('#filter_date_to').val(lastDay.toISOString().split('T')[0]);
    }
    
    // Função para aplicar filtros automaticamente
    function applyFiltersAuto() {
        var dateFrom = $('#filter_date_from').val();
        var dateTo = $('#filter_date_to').val();
        
        var url = window.location.href.split('?')[0];
        var params = [];
        
        if (dateFrom) params.push('date_from=' + dateFrom);
        if (dateTo) params.push('date_to=' + dateTo);
        
        var clientId = $('#filter_client').val();
        if (clientId) params.push('client_id=' + clientId);
        
        var linkStatus = $('#filter_link_status').val();
        if (linkStatus) params.push('link_status=' + linkStatus);
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        window.location.href = url;
    }
    
    // Função para aplicar período pré-definido
    $('#filter_period').on('change', function() {
        var period = $(this).val();
        var today = new Date();
        var dateFrom = '';
        var dateTo = '';
        
        switch(period) {
            case 'this_month':
                dateFrom = new Date(today.getFullYear(), today.getMonth(), 1);
                dateTo = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'last_month':
                dateFrom = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                dateTo = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'this_year':
                dateFrom = new Date(today.getFullYear(), 0, 1);
                dateTo = new Date(today.getFullYear(), 11, 31);
                break;
            case 'last_year':
                dateFrom = new Date(today.getFullYear() - 1, 0, 1);
                dateTo = new Date(today.getFullYear() - 1, 11, 31);
                break;
        }
        
        if (dateFrom && dateTo) {
            $('#filter_date_from').val(dateFrom.toISOString().split('T')[0]);
            $('#filter_date_to').val(dateTo.toISOString().split('T')[0]);
        }
        
        applyFiltersAuto();
    });
    
    // Aplicar filtros automaticamente quando os campos mudarem
    $('#filter_date_from, #filter_date_to, #filter_client, #filter_link_status').on('change', function() {
        applyFiltersAuto();
    });
    
    // Limpar filtros
    $('#clear_filters').click(function() {
        $('#filter_period').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        $('#filter_client').val('');
        $('#filter_link_status').val('');
        window.location.href = window.location.href.split('?')[0];
    });
    
    // Editar links da fatura
    $(document).on('click', '.edit-invoice-links', function() {
        var invoiceId = $(this).data('invoice-id');
        var notaFiscalLink = $(this).data('nota-fiscal');
        var boletoLink = $(this).data('boleto');
        var faturaLink = $(this).data('fatura');
        
        $('#edit_invoice_id').val(invoiceId);
        $('#edit_nota_fiscal_link').val(notaFiscalLink);
        $('#edit_boleto_link').val(boletoLink);
        $('#edit_fatura_link').val(faturaLink);
        
        $('#editInvoiceLinksModal').modal('show');
    });
    
    // Salvar links da fatura
    $('#saveInvoiceLinks').click(function() {
        var formData = {
            invoice_id: $('#edit_invoice_id').val(),
            invoice_link: $('#edit_nota_fiscal_link').val(),
            boleto_link: $('#edit_boleto_link').val(),
            fatura_link: $('#edit_fatura_link').val()
        };
        
        $.post(admin_url + 'workshop/update_invoice_links', formData)
            .done(function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    $('#editInvoiceLinksModal').modal('hide');
                    location.reload();
                } else {
                    alert_float('danger', response.message);
                }
            })
            .fail(function() {
                alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
            });
    });
    
    // Excluir linha da fatura
    $(document).on('click', '.delete-invoice-row', function() {
        var invoiceId = $(this).data('invoice-id');
        var confirmMessage = '<?php echo _l("wshop_confirm_delete_invoice"); ?>';
        
        if (confirm(confirmMessage)) {
            $.post(admin_url + 'workshop/delete_invoice_row', { invoice_id: invoiceId })
                .done(function(response) {
                    if (response.success) {
                        alert_float('success', response.message);
                        location.reload();
                    } else {
                        alert_float('danger', response.message);
                    }
                })
                .fail(function() {
                    alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
                });
        }
    });
    
    // Botão de compartilhamento individual de documentos
    $(document).on('click', '.share-invoice-documents', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        // Coletar dados do documento específico
        var invoiceId = $button.data('invoice-id');
        var notaFiscal = $button.data('nota-fiscal') || '';
        var boleto = $button.data('boleto') || '';
        var fatura = $button.data('fatura') || '';
        
        // Verificar se há pelo menos um link
        if (!notaFiscal && !boleto && !fatura) {
            alert('<?php echo _l("wshop_no_documents_to_share"); ?>');
            return;
        }
        
        var documents = [{
            invoice_id: invoiceId,
            nota_fiscal: notaFiscal,
            boleto: boleto,
            fatura: fatura
        }];
        
        // Desabilitar botão e mostrar loading
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        
        // Enviar para o backend
        $.ajax({
            url: '<?php echo admin_url("workshop/generate_combined_pdf"); ?>',
            type: 'POST',
            data: {
                documents: documents
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Abrir PDF em nova aba
                    window.open(response.pdf_url, '_blank');
                    alert_float('success', response.message);
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', '<?php echo _l("wshop_error_generating_pdf"); ?>');
                console.error('Erro:', error);
            },
            complete: function() {
                // Restaurar botão
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    

});
</script>
</body>
</html>