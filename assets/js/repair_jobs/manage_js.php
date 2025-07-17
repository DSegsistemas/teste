<script type="text/javascript">
    $(function(){
        'use strict';

        // Inicializar selectpicker para os filtros de comissão
        setTimeout(function() {
            $('#commission_period_filter').selectpicker({
                style: 'btn-default',
                size: 4,
                liveSearch: false,
                showSubtext: false
            });
            
            $('[name="mechanic_filter"]').selectpicker({
                style: 'btn-default',
                size: 4,
                liveSearch: true,
                showSubtext: false
            });
        }, 100);

        var repair_job_params = {
            "commission_period_filter": "#commission_period_filter",
            "mechanic_filter": "[name='mechanic_filter']"
        };
        var repair_job_table = $('table.table-repair_job_table');
        var _table_api = initDataTable(repair_job_table, admin_url+'workshop/repair_job_table', [0], [0], repair_job_params, ['0', 'desc']);
        var hidden_columns = [0]; // Apenas ocultando a coluna ID
        $('.table-repair_job_table').DataTable().columns(hidden_columns).visible(false, false);

        $.each(repair_job_params, function(i, obj) {
            $('select' + obj).on('change', function() {  
                $('.table-repair_job_table').DataTable().ajax.reload();
            });
        });
        
        // Filtros agora são fixos - código de toggle removido
        
        // Filtro de período pré-definido
        $('#commission_period_filter').on('change', function() {
            var period = $(this).val();
            if (period) {
                var dates = getPredefinedPeriodDates(period);
                $('#commission_from_date').val(dates.from);
                $('#commission_to_date').val(dates.to);
            }
        });
        
        // Eventos de limpar filtros removidos - interface simplificada
        
        // Botão de debug removido
        
        // Auto-aplicar filtros quando qualquer filtro mudar
        var filterTimeout;
        $('#commission_from_date, #commission_to_date, #commission_period_filter').on('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() {
                applyCommissionFilters();
            }, 300);
        });
        
        // Filtro de mecânico - aplicar imediatamente
        $('[name="mechanic_filter"]').on('change', function() {
            applyCommissionFilters();
        });
        

        
        // Atualizar cards na inicialização da página
        updateCommissionCards();
        
        // Função para obter datas de períodos pré-definidos
        function getPredefinedPeriodDates(period) {
            var today = new Date();
            var from, to;
            
            switch(period) {
                case 'today':
                    from = to = formatDate(today);
                    break;
                case 'yesterday':
                    var yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    from = to = formatDate(yesterday);
                    break;
                case 'this_week':
                    var startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    from = formatDate(startOfWeek);
                    to = formatDate(today);
                    break;
                case 'last_week':
                    var lastWeekStart = new Date(today);
                    lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
                    var lastWeekEnd = new Date(lastWeekStart);
                    lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
                    from = formatDate(lastWeekStart);
                    to = formatDate(lastWeekEnd);
                    break;
                case 'this_month':
                    from = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    to = formatDate(today);
                    break;
                case 'last_month':
                    var lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    var lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    from = formatDate(lastMonth);
                    to = formatDate(lastMonthEnd);
                    break;
                case 'this_quarter':
                    var quarter = Math.floor(today.getMonth() / 3);
                    from = formatDate(new Date(today.getFullYear(), quarter * 3, 1));
                    to = formatDate(today);
                    break;
                case 'last_quarter':
                    var lastQuarter = Math.floor(today.getMonth() / 3) - 1;
                    if (lastQuarter < 0) {
                        lastQuarter = 3;
                        from = formatDate(new Date(today.getFullYear() - 1, lastQuarter * 3, 1));
                        to = formatDate(new Date(today.getFullYear() - 1, lastQuarter * 3 + 3, 0));
                    } else {
                        from = formatDate(new Date(today.getFullYear(), lastQuarter * 3, 1));
                        to = formatDate(new Date(today.getFullYear(), lastQuarter * 3 + 3, 0));
                    }
                    break;
                case 'this_year':
                    from = formatDate(new Date(today.getFullYear(), 0, 1));
                    to = formatDate(today);
                    break;
                case 'last_year':
                    from = formatDate(new Date(today.getFullYear() - 1, 0, 1));
                    to = formatDate(new Date(today.getFullYear() - 1, 11, 31));
                    break;
                default:
                    from = to = '';
            }
            
            return { from: from, to: to };
        }
        
        // Função para formatar data no formato YYYY-MM-DD
        function formatDate(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }
        
        // Botão para limpar filtros de comissão
        $('#clear_filters_btn').on('click', function() {
            // Limpar filtros de comissão
            $('#commission_period_filter').val('').selectpicker('refresh');
            $('#commission_from_date').val('');
            $('#commission_to_date').val('');
            $('[name="mechanic_filter"]').val('').selectpicker('refresh');
            

            
            // Atualizar cards de comissão para valores padrão
            updateCommissionCards();
            
            // Recarregar a tabela
            $('.table-repair_job_table').DataTable().ajax.reload();
        });
        
        // Função para aplicar filtros de comissão
        function applyCommissionFilters() {
            console.log('applyCommissionFilters chamada');
            
            var fromDate = $('#commission_from_date').val();
            var toDate = $('#commission_to_date').val();
            var mechanic = $('[name="mechanic_filter"]').val();
            var period = $('#commission_period_filter').val();
            
            console.log('Filtros aplicados:', {
                fromDate: fromDate,
                toDate: toDate,
                mechanic: mechanic,
                period: period
            });
            
            // Atualizar cards de comissão
            console.log('Chamando updateCommissionCards...');
            updateCommissionCards(fromDate, toDate, mechanic, period);
            
            // Atualizar tabela com os filtros de comissão
            var table = repair_job_table.DataTable();
            
            // Adicionar os parâmetros de filtro de comissão aos dados da tabela
            table.settings()[0].ajax.data = function(d) {
                d.commission_from_date = fromDate;
                d.commission_to_date = toDate;
                d.commission_period_filter = period;
                d.mechanic_filter = mechanic;
                return d;
            };
            
            table.ajax.reload(function() {
                console.log('Tabela recarregada com sucesso');
            });
        }
        
        // Funções de limpar filtros e info removidas - interface simplificada
        
        // Função para atualizar os cards de comissão
        function updateCommissionCards(fromDate, toDate, mechanic, period) {
            // Se os parâmetros não foram fornecidos, ler dos filtros
            if (arguments.length === 0) {
                fromDate = $('#commission_from_date').val();
                toDate = $('#commission_to_date').val();
                mechanic = $('[name="mechanic_filter"]').val();
                period = $('#commission_period_filter').val();
            }
            
            console.log('updateCommissionCards chamada com:', {
                fromDate: fromDate,
                toDate: toDate,
                mechanic: mechanic,
                period: period
            });
            
            $.ajax({
                url: admin_url + 'workshop/get_commission_data',
                type: 'POST',
                data: {
                    period: period,
                    mechanic_id: mechanic,
                    from_date: fromDate,
                    to_date: toDate
                },
                dataType: 'json',
                beforeSend: function() {
                    console.log('Enviando requisição AJAX para:', admin_url + 'workshop/get_commission_data');
                    // Mostrar loading nos cards
                    $('.widget-drilldown-item-content h3').html('<i class="fa fa-spinner fa-spin"></i>');
                },
                success: function(response) {
                    console.log('Resposta recebida:', response);
                    if(response.success && response.data) {
                        console.log('Dados válidos recebidos, atualizando cards...');
                        // Atualizar os valores dos cards
                        updateCardValues(response.data);
                    } else {
                        console.error('Erro na resposta:', response);
                        // Mostrar valores zerados em caso de erro
                        updateCardValues({
                            today: {total_commission: 0},
                            week: {total_commission: 0},
                            month: {total_commission: 0},
                            total: {total_commission: 0}
                        });
                    }
                    
                    // Cards atualizados
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    
                    // Mostrar valores zerados em caso de erro
                    updateCardValues({
                        today: {total_commission: 0},
                        week: {total_commission: 0},
                        month: {total_commission: 0},
                        total: {total_commission: 0}
                    });
                }
            });
        }
        
        function updateCardValues(data) {
            console.log('updateCardValues chamada com dados:', data);
            
            // Usar seletores mais específicos para cada card
            var cardRow = $('.row.mtop15');
            console.log('Row dos cards encontrada:', cardRow.length);
            
            if (cardRow.length === 0) {
                console.error('Row dos cards não encontrada');
                return;
            }
            
            var cards = cardRow.find('.widget-drilldown-item-content h3');
            console.log('Número de cards encontrados:', cards.length);
            
            if (cards.length === 0) {
                console.error('Nenhum card encontrado');
                return;
            }
            
            // Atualizar cada card individualmente
            if (data.today && data.today.total_commission !== undefined) {
                var todayValue = 'R$ ' + parseFloat(data.today.total_commission).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                console.log('Atualizando card do dia (posição 0) com valor:', todayValue);
                $(cards[0]).text(todayValue);
            }
            
            if (data.week && data.week.total_commission !== undefined) {
                var weekValue = 'R$ ' + parseFloat(data.week.total_commission).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                console.log('Atualizando card da semana (posição 1) com valor:', weekValue);
                $(cards[1]).text(weekValue);
            }
            
            if (data.month && data.month.total_commission !== undefined) {
                var monthValue = 'R$ ' + parseFloat(data.month.total_commission).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                console.log('Atualizando card do mês (posição 2) com valor:', monthValue);
                $(cards[2]).text(monthValue);
            }
            
            if (data.total && data.total.total_commission !== undefined) {
                var totalValue = 'R$ ' + parseFloat(data.total.total_commission).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                console.log('Atualizando card total (posição 3) com valor:', totalValue);
                $(cards[3]).text(totalValue);
            }
            
            console.log('updateCardValues concluída');
        }
        
        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Tornar a função updateCommissionCards global para uso em outros scripts
        window.updateCommissionCards = updateCommissionCards;
        
        // Código de filtros personalizados removido

    });

    function repair_job_modal(repair_job_id) {
        "use strict";

        $("#modal_wrapper").load("<?php echo admin_url('workshop/load_repair_job_modal'); ?>", {
          repair_job_id: repair_job_id,
      }, function() {
          $("body").find('#repair_jobModal').modal({ show: true, backdrop: 'static' });
          init_selectpicker();
        
        // Inicializar selectpicker especificamente para os filtros de comissão
        $('#commission_period_filter').selectpicker({
            style: 'btn-default',
            size: 4,
            liveSearch: false,
            showSubtext: false
        });
        
        $('[name="mechanic_filter"]').selectpicker({
            style: 'btn-default',
            size: 4,
            liveSearch: true,
            showSubtext: false
        });
          init_datepicker();

      });

    }

    function delete_repair_job(id) {
        "use strict";

        if (confirm_delete()) {
            $.post(admin_url + "workshop/delete_repair_job/" + id).done(function (response) {
                response = JSON.parse(response);

                if (response.success === true || response.success == "true") {
                    alert_float('success', response.message)
                    $('.table-repair_job_table').DataTable().ajax.reload();
                    // Atualizar cards de comissão
                        updateCommissionCards();
                }
            });
        }
    }

    function delete_repair_job_attachment(wrapper, attachment_id) {
        "use strict";  

        if (confirm_delete()) {
            $.get(admin_url + 'workshop/delete_repair_job_attachment/' +attachment_id, function (response) {
                if (response.success == true) {
                    $(wrapper).parents('.dz-preview').remove();

                    var totalAttachmentsIndicator = $('.dz-preview'+attachment_id);
                    var totalAttachments = totalAttachmentsIndicator.text().trim();

                    if(totalAttachments == 1) {
                        totalAttachmentsIndicator.remove();
                    } else {
                        totalAttachmentsIndicator.text(totalAttachments-1);
                    }
                    alert_float('success', "<?php echo _l('wshop_deleted_repair_job_image_successfully') ?>");

                } else {
                    alert_float('danger', "<?php echo _l('wshop_deleted_repair_job_image_failed') ?>");
                }
            }, 'json');
        }
        return false;
    }

    function repair_job_status_mark_as(status, id, type) {
        "use strict"; 
        
        var url = 'workshop/repair_job_status_mark_as/' + status + '/' + id + '/' + type;
        var taskModalVisible = $('#task-modal').is(':visible');
        url += '?single_task=' + taskModalVisible;
        $("body").append('<div class="dt-loader"></div>');

        requestGetJSON(url).done(function (response) {
            $("body").find('.dt-loader').remove();
            if (response.success === true || response.success == 'true') {

                var av_tasks_tables = ['table.table-repair_job_table'];
                $.each(av_tasks_tables, function (i, selector) {
                    if ($.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().ajax.reload(null, false);
                    }
                });
                alert_float('success', response.message);
            }
        });
    }

</script>