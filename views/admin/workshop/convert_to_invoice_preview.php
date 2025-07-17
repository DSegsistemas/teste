<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="tw-font-semibold">
                                    <i class="fa fa-file-invoice"></i>
                                    <?php echo _l('wshop_convert_to_invoice_preview'); ?>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="margin-top: 10px;">
                                    <a href="<?php echo admin_url('workshop/'.($type == 'repair_job' ? 'repair_job_detail' : 'inspection_detail').'/'.$id); ?>" 
                                       class="btn btn-default mright5">
                                        <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                    </a>
                                    <button type="submit" form="convert_invoice_form" class="btn btn-success">
                                        <i class="fa fa-check"></i> <?php echo _l('wshop_confirm_convert_to_invoice'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">
                        
                        <!-- Cards Layout -->
                        <div class="row">
                            <!-- Card: Dados do Cliente -->
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <i class="fa fa-user"></i> <?php echo _l('client_data'); ?>
                                        </h4>
                                    </div>
                                    <div class="panel-body">
                                        <?php if(isset($client) && $client): ?>
                                            <div class="client-info">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <p><strong><?php echo _l('client_company'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->company) ? html_entity_decode($client->company) : ''; ?></span></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong><?php echo _l('wshop_cnpj'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->vat) ? preg_replace('/[^0-9]/', '', $client->vat) : ''; ?></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong><?php echo _l('client_zip'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->zip) ? preg_replace('/[^0-9]/', '', $client->zip) : ''; ?></span></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <p><strong><?php echo _l('client_address'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->address) ? html_entity_decode($client->address) : ''; ?></span></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong><?php echo _l('client_city'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->city) ? html_entity_decode($client->city) : ''; ?></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong><?php echo _l('client_state'); ?>:</strong><br>
                                                        <span class="text-muted"><?php echo isset($client->state) ? html_entity_decode($client->state) : ''; ?></span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                <?php echo _l('client_not_found'); ?> - Dados do cliente não foram carregados corretamente.
                                                <?php if(isset($type) && isset($id)): ?>
                                                    <br><small>Tipo: <?php echo $type; ?> | ID: <?php echo $id; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card: Valor da Nota Fiscal -->
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <i class="fa fa-calculator"></i> <?php echo _l('wshop_invoice_value'); ?>
                                        </h4>
                                    </div>
                                    <div class="panel-body">
                                        <div class="invoice-values">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="value-item">
                                                        <strong class="text-info"><?php echo _l('wshop_estimated_subtotal'); ?>:</strong><br>
                                                        <span class="value-amount">
                                                            <?php 
                                                            $subtotal = 0;
                                                            if($type == 'repair_job' && isset($repair_job)) {
                                                                $subtotal = $repair_job->subtotal ?? 0;
                                                            } elseif($type == 'inspection' && isset($inspection)) {
                                                                $subtotal = $inspection->subtotal ?? 0;
                                                            }
                                                            echo 'R$ ' . number_format($subtotal, 2, ',', '.');
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="value-item">
                                                        <strong class="text-warning"><?php echo _l('wshop_total_tax'); ?>:</strong><br>
                                                        <span class="value-amount">
                                                            <?php 
                                                            $total_tax = 0;
                                                            if($type == 'repair_job' && isset($repair_job)) {
                                                                $total_tax = $repair_job->total_tax ?? 0;
                                                            } elseif($type == 'inspection' && isset($inspection)) {
                                                                $total_tax = $inspection->total_tax ?? 0;
                                                            }
                                                            echo 'R$ ' . number_format($total_tax, 2, ',', '.');
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row" style="margin-top: 15px;">
                                                <div class="col-md-6">
                                                    <div class="value-item">
                                                        <strong class="text-danger"><?php echo _l('wshop_discount'); ?>:</strong><br>
                                                        <span class="value-amount">
                                                            <?php 
                                                            $discount_total = 0;
                                                            if($type == 'repair_job' && isset($repair_job)) {
                                                                $discount_total = $repair_job->discount_total ?? 0;
                                                            } elseif($type == 'inspection' && isset($inspection)) {
                                                                $discount_total = $inspection->discount_total ?? 0;
                                                            }
                                                            echo 'R$ ' . number_format($discount_total, 2, ',', '.');
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="value-item final-value">
                                                        <strong class="text-success" style="font-size: 14px;"><?php echo _l('wshop_invoice_final_value'); ?>:</strong><br>
                                                        <span class="value-amount text-success" style="font-size: 18px; font-weight: bold;">
                                                            <?php 
                                                            $invoice_value = $subtotal + $total_tax - $discount_total;
                                                            echo 'R$ ' . number_format($invoice_value, 2, ',', '.');
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <style>
                        .client-info p {
                            margin-bottom: 15px;
                        }
                        .client-info strong {
                            color: #333;
                            font-size: 12px;
                            text-transform: uppercase;
                        }
                        .invoice-values .value-item {
                            margin-bottom: 15px;
                            padding: 10px;
                            background-color: #f9f9f9;
                            border-radius: 4px;
                            border-left: 3px solid #ddd;
                        }
                        .invoice-values .value-item.final-value {
                            background-color: #f0f8f0;
                            border-left-color: #5cb85c;
                        }
                        .invoice-values .value-item strong {
                            font-size: 11px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        .invoice-values .value-amount {
                            font-size: 16px;
                            font-weight: 600;
                            color: #333;
                        }
                        </style>
                        
                        <br>
                        
                        <!-- INÍCIO DOS CAMPOS OCULTOS: Itens de Serviço e Produto -->
                        <div class="row" style="display: none;">
                            <div class="col-md-12">
                                <h5 class="tw-font-semibold text-primary"><?php echo _l('wshop_items_services_products'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('wshop_item_name'); ?></th>
                                                <th width="15%" class="text-center"><?php echo _l('quantity'); ?></th>
                                                <th width="20%" class="text-right"><?php echo _l('unit_price'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(isset($items) && count($items) > 0): ?>
                                                <?php foreach($items as $item): ?>
                                                    <tr>
                                                        <td><?php echo html_entity_decode($item['name'] ?? ''); ?></td>
                                                        <td class="text-center"><?php echo sprintf('%02d', $item['qty'] ?? 1); ?></td>
                                                        <td class="text-right"><?php echo 'R$ ' . number_format($item['rate'] ?? 0, 2, ',', '.'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted"><?php echo _l('no_items_found'); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- FIM DOS CAMPOS OCULTOS: Itens de Serviço e Produto -->
                        
                        <!-- INÍCIO DOS CAMPOS: Configurações da Fatura -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="tw-font-semibold text-primary"><?php echo _l('wshop_invoice_settings'); ?></h5>
                                <?php echo form_open(admin_url('workshop/convert_to_invoice/'.$id.'/'.$type), array('id' => 'convert_invoice_form')); ?>
                                
                                <!-- Descrição dos Serviços e Produtos -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="invoice_description" class="control-label"><?php echo _l('wshop_invoice_description'); ?></label>
                                            <textarea class="form-control" name="invoice_description" id="invoice_description" rows="10" placeholder="<?php echo _l('wshop_invoice_description_placeholder'); ?>"><?php 
                                            // Gerar descrição automática
                                            $description = "******DSeg Engenharia prestando os melhores servicos sempre para voce******\n";
                                            
                                            // Obter nome do mês em português
                                            $meses = array(
                                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril',
                                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                            );
                                            $mes_atual = $meses[date('n')];
                                            
                                            $description .= "Fechamento do Mes de " . $mes_atual . ", Seguem os serviços e produtos que utilizamos em seu atendimento:\n\n";
                                            
                                            // Separar serviços e produtos
                                            $services = array();
                                            $products = array();
                                            
                                            if(isset($items) && count($items) > 0) {
                                                foreach($items as $item) {
                                                    // Assumindo que itens com 'labour' no nome são serviços
                                                    if(stripos($item['name'], 'servico') !== false || stripos($item['name'], 'labour') !== false || stripos($item['name'], 'mao de obra') !== false) {
                                                        $services[] = $item;
                                                    } else {
                                                        $products[] = $item;
                                                    }
                                                }
                                            }
                                            
                                            // Adicionar serviços
                                             if(count($services) > 0) {
                                                 $description .= "Servicos:\n";
                                                 foreach($services as $service) {
                                                     $name = html_entity_decode(strip_tags($service['name'] ?? ''));
                                                     $desc = html_entity_decode(strip_tags($service['description'] ?? ''));
                                                     $qty = $service['qty'] ?? 1;
                                                     $rate = $service['rate'] ?? 0;
                                                     $description .= "- " . $name . ", " . $desc . ", Valor R$ " . number_format($rate, 2, ',', '.') . "\n";
                                                 }
                                                 $description .= "\n";
                                             }
                                             
                                             // Adicionar produtos
                                             if(count($products) > 0) {
                                                 $description .= "Produtos:\n";
                                                 foreach($products as $product) {
                                                     $name = html_entity_decode(strip_tags($product['name'] ?? ''));
                                                     $desc = html_entity_decode(strip_tags($product['description'] ?? ''));
                                                     $qty = $product['qty'] ?? 1;
                                                     $rate = $product['rate'] ?? 0;
                                                     $description .= "- " . $name . ", " . $desc . ", quantidade " . $qty . ", Valor R$ " . number_format($rate, 2, ',', '.') . "\n";
                                                 }
                                             }
                                            
                                            // Função para remover acentos
                                            function remove_accents_custom($string) {
                                                $accents = array(
                                                    'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
                                                    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                                                    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
                                                    'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
                                                    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                                                    'ç' => 'c', 'ñ' => 'n',
                                                    'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
                                                    'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                                                    'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
                                                    'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
                                                    'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
                                                    'Ç' => 'C', 'Ñ' => 'N'
                                                );
                                                return strtr($string, $accents);
                                            }
                                            
                                            // Remover acentos
                                            $description = remove_accents_custom($description);
                                            echo $description;
                                            ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notas Ocultas da Fatura -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="invoice_notes" class="control-label"><?php echo _l('wshop_invoice_notes'); ?></label>
                                            <textarea class="form-control" name="invoice_notes" id="invoice_notes" rows="4" placeholder="<?php echo _l('wshop_invoice_notes_placeholder'); ?>"><?php 
                                            // Gerar notas automáticas baseadas no tipo de cliente
                                            $notes = "";
                                            
                                            // Verificar se é condomínio (assumindo que condomínios têm 'condominio' no nome da empresa)
                                            $is_condominio = false;
                                            if(isset($client) && isset($client->company)) {
                                                $company_name = strtolower(html_entity_decode($client->company));
                                                $is_condominio = (stripos($company_name, 'condominio') !== false || 
                                                                stripos($company_name, 'condomínio') !== false ||
                                                                stripos($company_name, 'residencial') !== false ||
                                                                stripos($company_name, 'edificio') !== false ||
                                                                stripos($company_name, 'edifício') !== false);
                                            }
                                            
                                            // Adicionar informações de data e horário do chamado para todos os clientes
                                            $notes .= "INFORMACOES DO CHAMADO:\n\n";
                                            
                                            // Data do Chamado (data de criação)
                                            $data_chamado = '';
                                            if($type == 'repair_job' && isset($repair_job)) {
                                                $data_chamado = $repair_job->datecreated ?? '';
                                            } elseif($type == 'inspection' && isset($inspection)) {
                                                $data_chamado = $inspection->datecreated ?? '';
                                            }
                                            
                                            if($data_chamado) {
                                                $notes .= "Data do Chamado: " . date('d/m/Y', strtotime($data_chamado)) . "\n";
                                            }
                                            
                                            // Horário do atendimento (appointment_date)
                                            $horario_atendimento = '';
                                            if($type == 'repair_job' && isset($repair_job)) {
                                                $horario_atendimento = $repair_job->appointment_date ?? '';
                                            } elseif($type == 'inspection' && isset($inspection)) {
                                                $horario_atendimento = $inspection->start_date ?? '';
                                            }
                                            
                                            if($horario_atendimento) {
                                                $notes .= "Horario do atendimento: " . date('H:i', strtotime($horario_atendimento)) . "\n\n";
                                            }
                                            
                                            if($is_condominio) {
                                                // Mensagem para condomínios com informações sobre retenção de ISS
                                                $notes .= "INFORMACOES IMPORTANTES:\n\n";
                                                $notes .= "- A fatura detalha os serviços que foram prestados, de acordo com o que foi solicitado.\n";
                                                
                                                // Buscar valor real de retenção de ISS do banco de dados
                                                $iss_retention_value = 0;
                                                if($type == 'repair_job' && isset($repair_job)) {
                                                    $iss_retention_value = $repair_job->iss_retention_amount ?? 0;
                                                } elseif($type == 'inspection' && isset($inspection)) {
                                                    $iss_retention_value = $inspection->iss_retention_amount ?? 0;
                                                }
                                                
                                                $notes .= "- Esta fafura gerou retencao de ISS no valor de R$ " . number_format($iss_retention_value, 2, ',', '.') . " este valor foi descontado do valor total gerado na fatura de atedimento.\n";
                                                
                                                // Adicionar número da O.S do chamado
                                                $tracking_number = '';
                                                if($type == 'repair_job' && isset($repair_job)) {
                                                    $tracking_number = $repair_job->job_tracking_number ?? $repair_job->id ?? 'N/A';
                                                } elseif($type == 'inspection' && isset($inspection)) {
                                                    $tracking_number = $inspection->id ?? 'N/A';
                                                } else {
                                                    $tracking_number = $id ?? 'N/A';
                                                }
                                                
                                                $notes .= "- Esta fatura e referente a O.S de chamado Nº" . new_html_entity_decode($tracking_number) . "\n\n";
                                                $notes .= "DSeg Engenharia agradece a confiança e estamos à disposição.";
                                            } else {
                                                // Mensagem para clientes não-condomínios
                                                $notes .= "DSeg Engenharia agradece a confiança e estamos à disposição. ";
                                                $notes .= "Sua fatura de atendimento Nº";
                                                
                                                // Adicionar número do trabalho
                                                if($type == 'repair_job' && isset($repair_job)) {
                                                    $notes .= new_html_entity_decode($repair_job->job_tracking_number ?? $repair_job->id ?? 'N/A');
                                                } elseif($type == 'inspection' && isset($inspection)) {
                                                    $notes .= new_html_entity_decode($inspection->id ?? 'N/A');
                                                } else {
                                                    $notes .= new_html_entity_decode($id ?? 'N/A');
                                                }
                                            }
                                            
                                            
                                            // Remover acentos das notas
                                            $notes = remove_accents_custom($notes);
                                            echo $notes;
                                            ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Campo de Link da Nota Fiscal (DENTRO DO FORMULÁRIO) -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="invoice_link" class="control-label" style="font-weight: 600; color: #333;">
                                                <i class="fa fa-external-link" style="margin-right: 5px;"></i>
                                                <?php echo _l('wshop_invoice_link'); ?>
                                            </label>
                                            <div class="input-group" style="margin-top: 8px;">
                                                <span class="input-group-addon" style="background-color: #f8f9fa; border-color: #ddd;">
                                                    <i class="fa fa-link" style="color: #5bc0de;"></i>
                                                </span>
                                                <input type="url" class="form-control" name="invoice_link" id="invoice_link" 
                                                       style="border-color: #ddd; padding: 10px 12px; font-size: 14px;"
                                                       value="<?php echo isset($repair_job->invoice_link) ? $repair_job->invoice_link : (isset($inspection->invoice_link) ? $inspection->invoice_link : (isset($repair_job->fatura_link) ? $repair_job->fatura_link : (isset($inspection->fatura_link) ? $inspection->fatura_link : (isset($repair_job->nota_fiscal_link) ? $repair_job->nota_fiscal_link : (isset($inspection->nota_fiscal_link) ? $inspection->nota_fiscal_link : ''))))); ?>"
                                                       placeholder="<?php echo _l('wshop_invoice_link_placeholder'); ?>" required>
                                            </div>
                                            <small class="text-muted" style="margin-top: 5px; display: block; font-style: italic;">
                                                <i class="fa fa-info-circle" style="margin-right: 3px;"></i>
                                                <?php echo _l('wshop_invoice_link_help'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                        <!-- FIM DOS CAMPOS OCULTOS: Configurações da Fatura -->
                        
                        <!-- Campo de Data de Vencimento e Valor Total (Visível) -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="tw-font-semibold text-primary">
                                    <i class="fa fa-calendar"></i> Data de Vencimento e Valor
                                </h5>
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <?php 
                                                    // Calcular data de vencimento: dia 15 do mês posterior
                                                    $next_month = date('15-m-Y', strtotime('+1 month'));
                                                    
                                                    // Verificar se cai em fim de semana e ajustar para segunda-feira
                                                    $day_of_week = date('w', strtotime($next_month)); // 0=domingo, 6=sábado
                                                    
                                                    if ($day_of_week == 0) { // Domingo
                                                        $due_date = date('Y-m-d', strtotime($next_month . ' +1 day')); // Segunda
                                                    } elseif ($day_of_week == 6) { // Sábado
                                                        $due_date = date('Y-m-d', strtotime($next_month . ' +2 days')); // Segunda
                                                    } else {
                                                        $due_date = $next_month;
                                                    }
                                                    
                                                    echo render_date_input('duedate', 'wshop_invoice_due_date', $due_date); 
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="invoice_total" class="control-label"><?php echo _l('wshop_invoice_total_estimated'); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-addon">R$</span>
                                                        <input type="number" step="0.01" min="0" class="form-control" name="invoice_total" id="invoice_total" 
                                                               value="<?php 
                                                               // Pegar o valor total estimado do repair_job ou inspection
                                                               $total = 0;
                                                               if($type == 'repair_job' && isset($repair_job)) {
                                                                   $total = $repair_job->total ?? 0;
                                                               } elseif($type == 'inspection' && isset($inspection)) {
                                                                   $total = $inspection->total ?? 0;
                                                               } else {
                                                                   // Fallback: calcular dos itens
                                                                   if(isset($items) && count($items) > 0) {
                                                                       foreach($items as $item) {
                                                                           $total += ($item['qty'] ?? 1) * ($item['rate'] ?? 0);
                                                                       }
                                                                   }
                                                               }
                                                               echo number_format($total, 2, '.', '');
                                                               ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                        <!-- Seção de Direcionamentos -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="tw-font-semibold text-primary">
                                    <i class="fa fa-external-link"></i> <?php echo _l('wshop_direcionamento_external_links'); ?>
                                </h5>
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <?php 
                                        // Carregar direcionamentos ativos
                                        $CI = &get_instance();
                                        $CI->load->model('workshop_model');
                                        $direcionamentos = $CI->workshop_model->get_direcionamentos(false, true);
                                        
                                        if(!empty($direcionamentos)) {
                            echo '<div class="row">';
                            

                            
                            foreach($direcionamentos as $direcionamento) {
                                echo '<div class="col-md-3 col-sm-4 col-xs-6 mb-3" style="margin-bottom: 15px;">';
                                echo '<a href="' . $direcionamento['url'] . '" target="_blank" class="btn btn-success btn-block" style="background-color: #28a745; border-color: #28a745; color: white; padding: 12px 15px; font-weight: bold; text-decoration: none; border-radius: 5px; display: block; text-align: center;">';
                                echo '<i class="fa fa-external-link" style="margin-right: 8px;"></i>';
                                echo html_entity_decode($direcionamento['name']);
                                echo '</a>';
                                if(!empty($direcionamento['description'])) {
                                    echo '<p class="text-muted text-center" style="font-size: 11px; margin-top: 5px; margin-bottom: 0;">' . html_entity_decode($direcionamento['description']) . '</p>';
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                                        } else {

                                            
                                            echo '<div class="alert alert-info text-center">';
                                            echo '<i class="fa fa-info-circle"></i> ' . _l('wshop_direcionamento_no_data');
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(document).ready(function(){
    // Função para colar automaticamente link copiado
    function autoFillInvoiceLink() {
        // Verificar se o navegador suporta a API de clipboard
        if (navigator.clipboard && navigator.clipboard.readText) {
            navigator.clipboard.readText().then(function(clipboardText) {
                // Verificar se o texto copiado é uma URL válida
                if (clipboardText && isValidUrl(clipboardText.trim())) {
                    var currentValue = $('#invoice_link').val().trim();
                    
                    // Só preencher se o campo estiver vazio
                    if (!currentValue) {
                        $('#invoice_link').val(clipboardText.trim());
                        
                        // Mostrar notificação visual
                        showLinkPastedNotification();
                    }
                }
            }).catch(function(err) {
                // Silenciosamente ignorar erros de permissão
                console.log('Não foi possível acessar o clipboard:', err);
            });
        }
    }
    
    // Função para mostrar notificação de link colado
    function showLinkPastedNotification() {
        var notification = $('<div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; opacity: 0;">' +
            '<i class="fa fa-check-circle"></i> Link colado automaticamente no campo!' +
            '</div>');
        
        $('body').append(notification);
        
        // Animação de entrada
        notification.animate({opacity: 1}, 300);
        
        // Remover após 3 segundos
        setTimeout(function() {
            notification.animate({opacity: 0}, 300, function() {
                notification.remove();
            });
        }, 3000);
    }
    
    // Adicionar botão para colar link manualmente
    var pasteButton = $('<button type="button" class="btn btn-info btn-sm" style="margin-left: 5px;" title="Colar link do clipboard">' +
        '<i class="fa fa-paste"></i> Colar Link' +
        '</button>');
    
    // Inserir o botão após o campo de input
    $('#invoice_link').parent().after(pasteButton);
    
    // Evento do botão de colar
    pasteButton.on('click', function() {
        if (navigator.clipboard && navigator.clipboard.readText) {
            navigator.clipboard.readText().then(function(clipboardText) {
                if (clipboardText && isValidUrl(clipboardText.trim())) {
                    $('#invoice_link').val(clipboardText.trim());
                    showLinkPastedNotification();
                } else {
                    alert('Nenhum link válido encontrado no clipboard!');
                }
            }).catch(function(err) {
                alert('Erro ao acessar o clipboard. Verifique as permissões do navegador.');
            });
        } else {
            alert('Seu navegador não suporta a funcionalidade de clipboard.');
        }
    });
    
    // Detectar quando a página ganha foco (usuário volta para a aba)
    $(window).on('focus', function() {
        setTimeout(autoFillInvoiceLink, 500); // Pequeno delay para garantir que o clipboard foi atualizado
    });
    
    // Detectar eventos de teclado (Ctrl+V)
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 86) { // Ctrl+V ou Cmd+V
            setTimeout(autoFillInvoiceLink, 100); // Pequeno delay para o paste acontecer
        }
    });
    
    // Tentar colar automaticamente quando a página carrega
    setTimeout(autoFillInvoiceLink, 1000);
    
    // Validação do formulário
    $('#convert_invoice_form').on('submit', function(e) {
        var linkValue = $('#invoice_link').val().trim();
        
        // Verificar se o campo está vazio
        if (!linkValue) {
            e.preventDefault();
            alert('<?php echo _l("wshop_nota_fiscal_link_required"); ?>');
            $('#invoice_link').focus();
            return false;
        }
        
        // Verificar se é uma URL válida
        if (linkValue && !isValidUrl(linkValue)) {
            e.preventDefault();
            alert('<?php echo _l("wshop_nota_fiscal_invalid_url"); ?>');
            $('#invoice_link').focus();
            return false;
        }
    });
    
    // Função para validar URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});
</script>
</body>
</html>