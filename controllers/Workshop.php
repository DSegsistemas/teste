<?php

defined('BASEPATH') or exit('No direct script access allowed');
use app\services\utilities\Arr;

/**
 * Class Workshop
 */
class Workshop extends AdminController
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staff_model'); // Adicionado para carregar o model de staff
        $this->load->model('workshop_model');
        hooks()->do_action('workshop_init');
        $this->workshop_model->mechanic_role_exists();
    }
    


    /**
     * Método para desinstalação manual do módulo Workshop
     * Remove todas as tabelas e configurações do banco de dados
     * Apenas administradores podem executar esta ação
     */
    public function uninstall_module()
    {
        if (!is_admin()) {
            access_denied('admin');
        }
        
        // Verificar se é uma requisição POST para confirmar a ação
        if ($this->input->post('confirm_uninstall') == 'yes') {
            try {
                // Executar o script de desinstalação
                require_once(FCPATH . 'modules/workshop/uninstall.php');
                
                set_alert('success', 'Módulo Workshop desinstalado com sucesso! Todas as tabelas foram removidas do banco de dados.');
                redirect(admin_url('modules'));
            } catch (Exception $e) {
                set_alert('danger', 'Erro durante a desinstalação: ' . $e->getMessage());
                redirect(admin_url('workshop/setting?group=reset_data'));
            }
        } else {
            // Mostrar página de confirmação
            $data['title'] = 'Desinstalar Módulo Workshop';
            $data['group'] = 'reset_data';
            $data['tab'][] = 'general_settings';
            $data['tab'][] = 'appointment_types';
            $data['tab'][] = 'holidays';
            $data['tab'][] = 'manufacturers';
            $data['tab'][] = 'categories';
            $data['tab'][] = 'commissions';
            $data['tab'][] = 'models';
            $data['tab'][] = 'delivery_methods';
            $data['tab'][] = 'fieldsets';
            $data['tab'][] = 'intervals';
            $data['tab'][] = 'inspection_templates';
            $data['tab'][] = 'repair_job_statuses';
            $data['tab'][] = 'prefixs';
            $data['tab'][] = 'permissions';
            if(is_admin()){
                $data['tab'][] = 'reset_data';
            }
            $data['tabs']['view'] = 'settings/uninstall_confirmation';
            $this->load->view('settings/manage_setting', $data);
        }
    }

    public function mechanic($id = '')
    {
        if (!has_permission('workshop_mechanic', '', 'edit')) {
            access_denied('workshop_mechanic');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $success = $this->workshop_model->update_mechanic($data, $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('wshop_mechanic')));
            }
            redirect(admin_url('workshop/mechanics'));
        }

        if ($id == '') {
            $title = _l('add_new', _l('wshop_mechanic_lowercase'));
            $data['mechanic'] = null;
        } else {
            $data['mechanic'] = (array) $this->staff_model->get($id);
            $title = _l('edit', _l('wshop_mechanic_lowercase'));
        }

        $data['title'] = $title;
        $data['commissions'] = $this->workshop_model->get_commissions(false, true);
        $this->load->view('mechanics/mechanic', $data);
    }



    /**
     * Gerencia status de serviço (adicionar/editar)
     * @param  integer $id ID do status (vazio para novo status)
     * @return mixed       Carrega view do formulário ou redireciona após salvar
     */
    public function repair_job_status($id = '')
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin()) {
            access_denied('workshop_setting');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            if ($id == '') {
                $success = $this->workshop_model->add_repair_job_status($data);
                if ($success) {
                    set_alert('success', _l('added_successfully', _l('wshop_repair_job_status')));
                } else {
                    set_alert('danger', _l('problem_adding', _l('wshop_repair_job_status')));
                }
            } else {
                $success = $this->workshop_model->update_repair_job_status($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('wshop_repair_job_status')));
                } else {
                    set_alert('danger', _l('problem_updating', _l('wshop_repair_job_status')));
                }
            }
            redirect(admin_url('workshop/setting?group=repair_job_statuses'));
        }

        $data = [];
        if ($id != '') {
            $data['status'] = $this->workshop_model->get_repair_job_status($id);
            if (!$data['status']) {
                set_alert('warning', _l('not_found', _l('wshop_repair_job_status')));
                redirect(admin_url('workshop/setting?group=repair_job_statuses'));
            }
        }

        $data['title'] = _l('wshop_repair_job_status');
        $data['group'] = 'repair_job_statuses';
        $data['tab'][] = 'general_settings';
        $data['tab'][] = 'appointment_types';
        $data['tab'][] = 'holidays';
        $data['tab'][] = 'manufacturers';
        $data['tab'][] = 'categories';
        $data['tab'][] = 'commissions';
        $data['tab'][] = 'models';
        $data['tab'][] = 'delivery_methods';
        $data['tab'][] = 'fieldsets';
        $data['tab'][] = 'intervals';
        $data['tab'][] = 'inspection_templates';
        $data['tab'][] = 'repair_job_statuses';
        $data['tab'][] = 'iss_retention';
        $data['tab'][] = 'direcionamentos';
        $data['tab'][] = 'prefixs';
        $data['tab'][] = 'permissions';
        if(is_admin()){
            $data['tab'][] = 'reset_data';
        }
        $data['tabs']['view'] = 'settings/repair_job_statuses/repair_job_status_form';
        $this->load->view('settings/manage_setting', $data);
    }

    /**
     * Exclui um status de serviço
     * @param  integer $id ID do status a ser excluído
     * @return mixed       Redireciona após a exclusão
     */
    public function delete_repair_job_status($id)
    {
        if (!has_permission('workshop_setting', '', 'delete') && !is_admin()) {
            access_denied('workshop_setting');
        }
        
        // Verificar se é um status predefinido baseado no status_id
        $status = $this->workshop_model->get_repair_job_status($id);
        if ($status) {
            $restricted_statuses = ['Booked_In', 'In_Progress', 'Cancelled', 'Waiting_For_Parts', 'Finalised', 'Waiting_For_User_Approval', 'Job_Complete'];
            
            if (in_array($status->status_id, $restricted_statuses)) {
                set_alert('warning', _l('wshop_cannot_delete_default_status'));
                redirect(admin_url('workshop/setting?group=repair_job_statuses'));
                return;
            }
        }
        
        $success = $this->workshop_model->delete_repair_job_status($id);
        if ($success) {
            set_alert('success', _l('deleted', _l('wshop_repair_job_status')));
        } else {
            set_alert('danger', _l('problem_deleting', _l('wshop_repair_job_status')));
        }
        
        redirect(admin_url('workshop/setting?group=repair_job_statuses'));
    }



    /**
     * repair_job_status_table
     * @return [type] 
     */
    public function repair_job_status_table()
    {
        if (!has_permission('workshop_setting', '', 'view') && !is_admin()) {
            ajax_access_denied();
        }
        
        // Buscar dados diretamente do banco de dados
        $aColumns = [
            'status_id',
            'name',
            'color',
            'order',
            'filter_default',
            'id',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'wshop_repair_job_statuses';

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['id']);
        $output = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            $row[] = $aRow['status_id'];
            $row[] = $aRow['name'];
            
            // Color with preview
            $row[] = '<span class="label" style="background-color: ' . $aRow['color'] . '">' . $aRow['color'] . '</span>';
            
            $row[] = $aRow['order'];
            
            // Filter default
            $row[] = ($aRow['filter_default'] == 1) ? _l('wshop_label_yes') : _l('wshop_label_no');
            
            // Options
            $options = icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', ['onclick' => 'edit_repair_job_status(' . $aRow['id'] . '); return false;']);
            $options .= icon_btn('workshop/delete_repair_job_status/' . $aRow['id'], 'fa fa-remove', 'btn-danger _delete', ['data-original-title' => _l('delete'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']);
            
            $row[] = $options;

            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * get_repair_job_status
     * @param  integer $id 
     * @return [type]     
     */
    public function get_repair_job_status($id)
    {
        if (!has_permission('workshop_setting', '', 'view') && !is_admin()) {
            ajax_access_denied();
        }

        $status = $this->workshop_model->get_repair_job_status($id);
        
        header('Content-Type: application/json');
        
        if ($status) {
            echo json_encode($status);
        } else {
            // Retornar erro 404 se o status não for encontrado
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Status not found']);
            die();
        }
    }

    // Método delete_repair_job_status removido para evitar duplicação
    // Já existe uma implementação idêntica acima

    /**
     * setting
     * @return [type] 
     */
    public function setting()
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin() && !has_permission('workshop_setting', '', 'create') && !has_permission('workshop_setting', '', 'delete')) {
            access_denied('workshop_setting');
        }

        // Processar ações POST para direcionamentos
        if ($this->input->post() && $this->input->get('group') == 'direcionamentos') {
            $action = $this->input->post('action');
            
            if ($action == 'add_direcionamento' || $action == 'update_direcionamento') {
                $data_form = [
                    'name' => $this->input->post('name'),
                    'url' => $this->input->post('url'),
                    'description' => $this->input->post('description'),
                    'status' => $this->input->post('status') ? 1 : 0
                ];
                
                if ($action == 'add_direcionamento') {
                    if ($this->workshop_model->add_direcionamento($data_form)) {
                        set_alert('success', _l('added_successfully', _l('wshop_direcionamento')));
                    } else {
                        set_alert('danger', _l('problem_adding', _l('wshop_direcionamento')));
                    }
                } else {
                    $id = $this->input->post('id');
                    if ($this->workshop_model->update_direcionamento($data_form, $id)) {
                        set_alert('success', _l('updated_successfully', _l('wshop_direcionamento')));
                    } else {
                        set_alert('danger', _l('problem_updating', _l('wshop_direcionamento')));
                    }
                }
            } elseif ($action == 'delete_direcionamento') {
                $id = $this->input->post('id');
                if ($this->workshop_model->delete_direcionamento($id)) {
                    set_alert('success', _l('deleted', _l('wshop_direcionamento')));
                } else {
                    set_alert('danger', _l('problem_deleting', _l('wshop_direcionamento')));
                }
            }
            
            redirect(admin_url('workshop/setting?group=direcionamentos'));
        }

        $data['group'] = $this->input->get('group');
        $data['title'] = _l('setting');

        $data['tab'][] = 'general_settings';
        $data['tab'][] = 'appointment_types';
        $data['tab'][] = 'holidays';
        $data['tab'][] = 'manufacturers';
        $data['tab'][] = 'categories';
        $data['tab'][] = 'commissions';
        $data['tab'][] = 'models';
        $data['tab'][] = 'delivery_methods';
        $data['tab'][] = 'fieldsets';
        $data['tab'][] = 'intervals';
        $data['tab'][] = 'inspection_templates';
        $data['tab'][] = 'repair_job_statuses';
        $data['tab'][] = 'iss_retention';
        $data['tab'][] = 'direcionamentos';
        $data['tab'][] = 'prefixs';
        $data['tab'][] = 'permissions';
        if(is_admin()){
            $data['tab'][] = 'reset_data';
        }

        if ($data['group'] == 'general_settings') {
            $data['tabs']['view'] = 'settings/general/' . $data['group'];
        }elseif ($data['group'] == 'appointment_types') {
            $data['tabs']['view'] = 'settings/appointment_types/appointment_type';
        }elseif ($data['group'] == 'holidays') {
            $data['tabs']['view'] = 'settings/holidays/holiday';
        }elseif ($data['group'] == 'manufacturers') {
            $data['group'] = 'category';
            $data['tabs']['view'] = 'settings/manufacturers/manufacturer';
        }elseif ($data['group'] == 'categories') {
            $data['group'] = 'categories';
            $data['tabs']['view'] = 'settings/categories/category';
        }elseif ($data['group'] == 'commissions') {
            $data['group'] = 'commissions';
            $data['commissions'] = $this->workshop_model->get_commissions();
            $data['tabs']['view'] = 'settings/commissions/commission';
        }elseif ($data['group'] == 'models') {
            $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => "device"]);
            $data['manufacturers'] = $this->workshop_model->get_manufacturer(false, true);
            $data['fieldsets'] = $this->workshop_model->get_fieldset(false, true);
            $data['tabs']['view'] = 'settings/models/model';
        }elseif ($data['group'] == 'delivery_methods') {
            $data['tabs']['view'] = 'settings/delivery_methods/delivery_method';
        }elseif ($data['group'] == 'fieldsets') {
            $data['tabs']['view'] = 'settings/fieldsets/fieldset';
        }elseif ($data['group'] == 'intervals') {
            $data['tabs']['view'] = 'settings/intervals/interval';
        }elseif ($data['group'] == 'inspection_templates') {
            $data['tabs']['view'] = 'settings/inspection_templates/inspection_template';
        }elseif ($data['group'] == 'repair_job_statuses') {
            $data['tabs']['view'] = 'settings/repair_job_statuses/repair_job_status';
        }elseif ($data['group'] == 'iss_retention') {
            $this->load->model('iss_retention_model');
            $data['iss_retention_rates'] = $this->iss_retention_model->get();
            $data['tabs']['view'] = 'settings/iss_retention/iss_retention';
        }elseif ($data['group'] == 'direcionamentos') {
            // Se há um edit_id, carregar dados para edição
            $edit_id = $this->input->get('edit_id');
            if ($edit_id) {
                $data['edit_direcionamento'] = $this->workshop_model->get_direcionamento($edit_id);
            }
            $data['tabs']['view'] = 'settings/direcionamentos/direcionamento';
        }elseif($data['group'] == 'prefixs'){
            $data['tabs']['view'] = 'settings/prefixs/' . $data['group'];
        }elseif($data['group'] == 'permissions'){
            $data['tabs']['view'] = 'settings/permissions/' . $data['group'];
        }elseif($data['group'] == 'reset_data'){
            $data['tabs']['view'] = 'settings/reset_data/' . $data['group'];
        }

        $this->load->view('settings/manage_setting', $data);
    }

    /**
     * general
     * @return [type] 
     */
    public function general()
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin() && !has_permission('workshop_setting', '', 'create')) {
            access_denied('workshop_setting');
        }

        $data = $this->input->post();

        if ($data) {
            if(isset($data['wshop_working_day'])){
                $data['wshop_working_day'] = implode(",", $data['wshop_working_day']);
            }else{
                $data['wshop_working_day'] = '';
            }

            $data['wshop_repair_job_terms'] = $this->input->post('wshop_repair_job_terms', false);
            $data['wshop_report_footer'] = $this->input->post('wshop_report_footer', false);
            $data['wshop_loan_terms'] = $this->input->post('wshop_loan_terms', false);

            $success = $this->workshop_model->update_prefix_number($data);
            if ($success == true) {
                set_alert('success', _l('updated_successfully', _l('wshop_general_settings')));
            }
            redirect(admin_url('workshop/setting?group=general_settings'));
        }
    }

    /**
     * prefix number
     * @return [type] 
     */
    public function prefix_number()
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin() && !has_permission('workshop_setting', '', 'create')) {
            access_denied('wshop_prefixs');
        }

        $data = $this->input->post();

        if ($data) {

            $success = $this->workshop_model->update_prefix_number($data);
            if ($success == true) {
                $message = _l('updated_successfully');
                set_alert('success', $message);
            }

            redirect(admin_url('workshop/setting?group=prefixs'));
        }
    }

    /**
     * holiday table
     * @return [type] 
     */
    public function holiday_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/holidays/holiday_table'));
    }

    /**
     * change holiday status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_holiday_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_holiday_status($id, (int)$status);
            }
        }
    }

    /**
     * holiday
     * @return [type] 
     */
    public function holiday()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_holiday($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_holiday'));
                }
            } else {
                $success = $this->workshop_model->add_holiday($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_holiday'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete holiday
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_holiday($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=holidays'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_prefixs');
        }

        $response = $this->workshop_model->delete_holiday($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=holidays'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=holidays'));
        }

    }

    /**
     * holiday days off exists
     * @return [type] 
     */
    public function holiday_days_off_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_holiday = $this->db->get(db_prefix() . 'wshop_holidays')->row();
                    if ($_current_holiday->days_off == to_sql_date($this->input->post('days_off'))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('days_off', to_sql_date($this->input->post('days_off')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_holidays');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * manufacturer table
     * @return [type] 
     */
    public function manufacturer_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/manufacturers/manufacturer_table'));
    }

    /**
     * load manufacturer modal
     * @return [type] 
     */
    public function load_manufacturer_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $manufacturer_id = $this->input->post('manufacturer_id');
        $data['title'] = _l('wshop_add_manufacturer');
        if(is_numeric($manufacturer_id) && $manufacturer_id != 0){
            $data['manufacturer'] = $this->workshop_model->get_manufacturer($manufacturer_id);
            $data['title'] = _l('wshop_edit_manufacturer');
        }

        $this->load->view('settings/manufacturers/manufacturer_modal', $data);
    }

    /**
     * change manufacturer status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_manufacturer_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_manufacturer_status($id, (int)$status);
            }
        }
    }

    /**
     * manufacturer
     * @return [type] 
     */
    public function add_edit_manufacturer($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if (is_numeric($id) && $id != 0) {
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_manufacturer($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_manufacturer'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);die;
            } else {
                $response = $this->workshop_model->add_manufacturer($this->input->post());
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_manufacturer'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);die;
            }
        }
        
    }

    /**
     * delete manufacturer
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_manufacturer($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=manufacturers'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_manufacturers');
        }

        $response = $this->workshop_model->delete_manufacturer($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=manufacturers'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=manufacturers'));
        }

    }

    /**
     * manufacturer exists
     * @return [type] 
     */
    public function manufacturer_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if manufacturer is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_manufacturer = $this->db->get(db_prefix() . 'wshop_manufacturers')->row();
                    if (strtoupper($_current_manufacturer->name) == strtoupper(($this->input->post('name')))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', ($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_manufacturers');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * delete manufacturer image
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_manufacturer_image($id){

        $deleted    = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix().'wshop_manufacturers', [
            'manufacture_image' => '',
        ]);
        if ($this->db->affected_rows() > 0) {
            $deleted = true;
        }
        if (is_dir(MANUFACTURER_IMAGES_FOLDER. $id)) {
            // Check if no attachments left, so we can delete the folder also
            $other_attachments = list_files(MANUFACTURER_IMAGES_FOLDER. $id);
                // okey only index.html so we can delete the folder also
            delete_dir(MANUFACTURER_IMAGES_FOLDER. $id);
        }
        
        echo json_encode($deleted);
    }

    /**
     * category table
     * @return [type] 
     */
    // Método category_table removido - usando tabela HTML simples

    // Método change_category_status removido - funcionalidade de status integrada na edição

    /**
     * Corrigir estrutura da tabela de categorias
     * @return [type] 
     */
    public function fix_categories_table()
    {
        if (!has_permission('workshop_setting', '', 'view')) {
            access_denied('workshop_setting');
        }
        
        $message = '';
        $success = false;
        
        // Verificar se a tabela existe
        if (!$this->db->table_exists(db_prefix() . 'wshop_categories')) {
            $message = 'Tabela wshop_categories não existe!';
        } else {
            // Verificar se a coluna 'code' existe
            $fields = $this->db->field_data(db_prefix() . 'wshop_categories');
            $code_exists = false;
            foreach ($fields as $field) {
                if ($field->name == 'code') {
                    $code_exists = true;
                    break;
                }
            }
            
            if (!$code_exists) {
                // Adicionar coluna 'code'
                $this->db->query('ALTER TABLE `' . db_prefix() . 'wshop_categories` ADD `code` TEXT NULL AFTER `id`');
                $message .= 'Coluna code adicionada. ';
            }
            
            // Verificar se a coluna 'use_for' existe
            $use_for_exists = false;
            foreach ($fields as $field) {
                if ($field->name == 'use_for') {
                    $use_for_exists = true;
                    break;
                }
            }
            
            if (!$use_for_exists) {
                // Adicionar coluna 'use_for'
                $this->db->query('ALTER TABLE `' . db_prefix() . 'wshop_categories` ADD `use_for` TEXT NULL AFTER `name`');
                $message .= 'Coluna use_for adicionada. ';
            }
            
            $success = true;
            $message .= 'Estrutura da tabela verificada e corrigida!';
        }
        
        set_alert($success ? 'success' : 'danger', $message);
         redirect(admin_url('workshop/setting?group=categories'));
     }



    /**
     * Debug da estrutura da tabela de categorias
     * @return [type] 
     */
    public function debug_categories_table()
    {
        if (!is_admin()) {
            access_denied('workshop_setting');
        }
        
        echo '<h3>Estrutura da tabela wshop_categories:</h3>';
        
        // Verificar se a tabela existe
        if (!$this->db->table_exists(db_prefix() . 'wshop_categories')) {
            echo '<p style="color: red;">Tabela wshop_categories não existe!</p>';
            return;
        }
         
         // Mostrar estrutura da tabela
          $fields = $this->db->field_data(db_prefix() . 'wshop_categories');
          echo '<table border="1" style="border-collapse: collapse; margin: 10px 0;">';
          echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>';
          foreach ($fields as $field) {
              echo '<tr>';
              echo '<td>' . $field->name . '</td>';
              echo '<td>' . $field->type . '</td>';
              echo '<td>' . (isset($field->null) && $field->null ? 'SIM' : 'NÃO') . '</td>';
              echo '<td>' . (isset($field->primary_key) && $field->primary_key ? 'PRI' : '') . '</td>';
              echo '<td>' . (isset($field->default) ? $field->default : '') . '</td>';
              echo '</tr>';
          }
          echo '</table>';
         
         // Mostrar dados da tabela
         $this->db->select('*');
         $this->db->from(db_prefix() . 'wshop_categories');
         $categories = $this->db->get()->result_array();
         
         echo '<h3>Dados na tabela (' . count($categories) . ' registros):</h3>';
         if (!empty($categories)) {
             echo '<table border="1" style="border-collapse: collapse; margin: 10px 0;">';
             // Cabeçalho
             echo '<tr>';
             foreach (array_keys($categories[0]) as $column) {
                 echo '<th>' . $column . '</th>';
             }
             echo '</tr>';
             // Dados
             foreach ($categories as $category) {
                  echo '<tr>';
                  foreach ($category as $value) {
                      echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
                  }
                  echo '</tr>';
              }
             echo '</table>';
         } else {
             echo '<p>Nenhum registro encontrado.</p>';
         }
         
         echo '<br><a href="' . admin_url('workshop/fix_categories_table') . '" style="background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">🔧 Corrigir Estrutura da Tabela</a><br><br>';
         echo '<a href="' . admin_url('workshop/setting?group=categories') . '">Voltar para Categorias</a>';
     }

    /**
     * category_manage - Gerenciar categorias sem AJAX
     * @return [type] 
     */
    public function category_manage()
    {
        if (!has_permission('workshop_setting', '', 'view')) {
            access_denied('workshop_setting');
        }
        
        if ($this->input->post()) {
            $id = $this->input->post('id');
            
            if ($id) {
                // Editar categoria existente
                if (!has_permission('workshop_setting', '', 'edit')) {
                    access_denied('workshop_setting');
                }
                
                $data = $this->input->post();
                unset($data['id']);
                
                $success = $this->workshop_model->update_category($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('wshop_category')));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                }
            } else {
                // Adicionar nova categoria
                if (!has_permission('workshop_setting', '', 'create')) {
                    access_denied('workshop_setting');
                }
                
                $success = $this->workshop_model->add_category($this->input->post());
                if ($success) {
                    set_alert('success', _l('added_successfully', _l('wshop_category')));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                }
            }
        }
        
        redirect(admin_url('workshop/setting?group=categories'));
    }

    /**
     * category - Método AJAX original (mantido para compatibilidade)
     * @return [type] 
     */
    public function category()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_category($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_category'));
                }
            } else {
                $success = $this->workshop_model->add_category($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_category'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }



    /* Commissions */
    public function commission($id = '')
    {
        if (!has_permission('workshop_setting', '', 'view')) {
            access_denied('workshop_setting');
        }
        if ($this->input->post()) {
            if ($id == '') {
                if (!has_permission('workshop_setting', '', 'create')) {
                    access_denied('workshop_setting');
                }
                $data = $this->input->post();
                $success = $this->workshop_model->add_commission($data);
                if ($success) {
                    set_alert('success', _l('added_successfully', _l('wshop_commission')));
                }
            } else {
                if (!has_permission('workshop_setting', '', 'edit')) {
                    access_denied('workshop_setting');
                }
                $success = $this->workshop_model->update_commission($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('wshop_commission')));
                }
            }
            redirect(admin_url('workshop/setting?group=commissions'));
        }


    }

    public function delete_commission($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=commissions'));
        }
        $this->workshop_model->delete_commission($id);
        set_alert('success', _l('deleted', _l('wshop_commission')));
        redirect(admin_url('workshop/setting?group=commissions'));
    }

    /**
     * delete category
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_category($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=categories'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_category');
        }

        $response = $this->workshop_model->delete_category($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=categories'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=categories'));
        }

    }

    /**
     * category exists
     * @return [type] 
     */
    public function category_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_category = $this->db->get(db_prefix() . 'wshop_categories')->row();
                    if ($_current_category->name == to_sql_date($this->input->post('name'))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', to_sql_date($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_categories');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * delivery_method table
     * @return [type] 
     */
    public function delivery_method_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/delivery_methods/delivery_method_table'));
    }

    /**
     * change delivery_method status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_delivery_method_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_delivery_method_status($id, (int)$status);
            }
        }
    }

    /**
     * delivery_method
     * @return [type] 
     */
    public function delivery_method()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_delivery_method($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_delivery_method'));
                }
            } else {
                $success = $this->workshop_model->add_delivery_method($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_delivery_method'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete delivery_method
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_delivery_method($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=delivery_methods'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_delivery_method');
        }

        $response = $this->workshop_model->delete_delivery_method($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=delivery_methods'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=delivery_methods'));
        }

    }

    /**
     * delivery_method  exists
     * @return [type] 
     */
    public function delivery_method_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_delivery_method = $this->db->get(db_prefix() . 'wshop_delivery_methods')->row();
                    if ($_current_delivery_method->name == $this->input->post('name')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', $this->input->post('name'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_delivery_methods');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * interval table
     * @return [type] 
     */
    public function interval_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/intervals/interval_table'));
    }

    /**
     * change interval status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_interval_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_interval_status($id, (int)$status);
            }
        }
    }

    /**
     * interval
     * @return [type] 
     */
    public function interval()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_interval($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_interval'));
                }
            } else {
                $success = $this->workshop_model->add_interval($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_interval'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete interval
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_interval($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=intervals'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_interval');
        }

        $response = $this->workshop_model->delete_interval($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=intervals'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=intervals'));
        }

    }

    /**
     * interval  exists
     * @return [type] 
     */
    public function interval_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_interval = $this->db->get(db_prefix() . 'wshop_intervals')->row();
                    if (($_current_interval->value == $this->input->post('value')) && ($_current_interval->type == $this->input->post('type'))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('value', $this->input->post('value'));
                $this->db->where('type', $this->input->post('type'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_intervals');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * model table
     * @return [type] 
     */
    public function model_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/models/model_table'));
    }

    /**
     * change model status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_model_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_model_status($id, (int)$status);
            }
        }
    }

    /**
     * model
     * @return [type] 
     */
    public function model()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_model($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_model'));
                }
            } else {
                $success = $this->workshop_model->add_model($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_model'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete model
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_model($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=models'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_model');
        }

        $response = $this->workshop_model->delete_model($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=models'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=models'));
        }

    }

    /**
     * appointment_type table
     * @return [type] 
     */
    public function appointment_type_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/appointment_types/appointment_type_table'));
    }

    /**
     * load appointment type modal
     * @return [type] 
     */
    public function load_appointment_type_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $appointment_type_id = $this->input->post('appointment_type_id');
        $data['title'] = _l('wshop_add_appointment_type');
        $this->load->model('invoice_items_model');
        $data['products'] = $this->workshop_model->get_labour_product();
        if(is_numeric($appointment_type_id) && $appointment_type_id != 0){
            $data['appointment_type'] = $this->workshop_model->get_appointment_type($appointment_type_id);
            $data['appointment_type_products'] = $this->workshop_model->get_appointment_type_products($appointment_type_id, true);

            $data['title'] = _l('wshop_edit_appointment_type');
        }

        $this->load->view('settings/appointment_types/appointment_type_modal', $data);
    }

    /**
     * change appointment_type status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_appointment_type_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_appointment_type_status($id, (int)$status);
            }
        }
    }

    /**
     * appointment_type
     * @return [type] 
     */
    public function add_edit_appointment_type()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_appointment_type($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_appointment_type'));
                }
            } else {
                $success = $this->workshop_model->add_appointment_type($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_appointment_type'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete appointment_type
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_appointment_type($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=appointment_types'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_appointment_type');
        }

        $response = $this->workshop_model->delete_appointment_type($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=appointment_types'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=appointment_types'));
        }

    }

    /**
     * appointment_type_exists
     * @return [type] 
     */
    public function appointment_type_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_category = $this->db->get(db_prefix() . 'wshop_appointment_types')->row();
                    if ($_current_category->name == ($this->input->post('name'))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', ($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_appointment_types');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * fieldset table
     * @return [type] 
     */
    public function fieldset_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/fieldsets/fieldset_table'));
    }

    /**
     * change fieldset status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_fieldset_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_fieldset_status($id, (int)$status);
            }
        }
    }

    /**
     * fieldset
     * @return [type] 
     */
    public function fieldset()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_fieldset($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_fieldset'));
                }
            } else {
                $success = $this->workshop_model->add_fieldset($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_fieldset'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete fieldset
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_fieldset($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=custom_fields'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_fieldset');
        }

        $response = $this->workshop_model->delete_fieldset($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=custom_fields'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=custom_fields'));
        }

    }

    /**
     * fieldset  exists
     * @return [type] 
     */
    public function fieldset_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_fieldset = $this->db->get(db_prefix() . 'wshop_fieldsets')->row();
                    if ($_current_fieldset->name == $this->input->post('name')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', $this->input->post('name'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_fieldsets');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * fieldset detail
     * @param  string $fieldset_id 
     * @return [type]              
     */
    public function fieldset_detail($fieldset_id = '')
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin() && !has_permission('workshop_setting', '', 'create') && !has_permission('workshop_setting', '', 'delete')) {
            access_denied('workshop_custom_fields');
        }
        if(!is_numeric($fieldset_id) || $fieldset_id == ''){
            blank_page('Staff Member Not Found', 'danger');
        }

        $data = [];
        $data['fieldset_id'] = $fieldset_id;
        $this->load->view('settings/custom_fields/custom_field', $data);
    }

    /**
     * custom_field table
     * @return [type] 
     */
    public function custom_field_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/custom_fields/custom_field_table'));
    }

    /**
     * change custom_field status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_custom_field_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_custom_field_status($id, (int)$status);
            }
        }
    }

    /**
     * custom_field
     * @return [type] 
     */
    public function custom_field()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_custom_field($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_custom_field'));
                }
            } else {
                $success = $this->workshop_model->add_custom_field($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_custom_field'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }


    /**
     * custom_field  exists
     * @return [type] 
     */
    public function custom_field_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_custom_field = $this->db->get(db_prefix() . 'wshop_customfields')->row();
                    if ($_current_custom_field->name == $this->input->post('name')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', $this->input->post('name'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_customfields');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * load custom field modal
     * @return [type] 
     */
    public function load_custom_field_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $custom_field_id = $this->input->post('custom_field_id');
        $fieldset_id = $this->input->post('fieldset_id');

        $data['title'] = _l('wshop_add_custom_field');
        $data['fieldsets'] = $this->workshop_model->get_fieldset_for_custom_field();
        $data['fieldset_id'] = $fieldset_id;

        if(is_numeric($custom_field_id) && $custom_field_id != 0){
            $data['custom_field'] = $this->workshop_model->get_custom_field($custom_field_id);
            $data['custom_field_products'] = $this->workshop_model->get_custom_field($custom_field_id);

            $data['title'] = _l('wshop_edit_custom_field');
        }

        $this->load->view('settings/custom_fields/custom_field_modal', $data);
    }

    /**
     * add edit custom field
     * @param string $id 
     */
    public function add_edit_custom_field($id = '')
    {
        if ($this->input->post()) {
            if ($id == '') {
                $id = $this->workshop_model->add_custom_field($this->input->post());
                set_alert('success', _l('added_successfully', _l('custom_field')));

                if($this->input->is_ajax_request()){
                    echo json_encode(['id' => $id]);
                    die;
                }else{
                    $get_custom_field = $this->workshop_model->get_custom_field($id);
                    if($get_custom_field){
                        $fieldset_id = $get_custom_field->fieldset_id;
                        redirect(admin_url('workshop/fieldset_detail/'.$fieldset_id));
                    }else{
                        redirect(admin_url('workshop/setting?group=fieldsets'));
                    }
                }
            }
            $success = $this->workshop_model->update_custom_field($this->input->post(), $id);
            if (is_array($success) && isset($success['cant_change_option_custom_field'])) {
                set_alert('warning', _l('cf_option_in_use'));
            } elseif ($success === true) {
                set_alert('success', _l('updated_successfully', _l('custom_field')));
            }
            if($this->input->is_ajax_request()){
                echo json_encode(['id' => $id]);
                die;
            }else{
                $get_custom_field = $this->workshop_model->get_custom_field($id);
                if($get_custom_field){
                    $fieldset_id = $get_custom_field->fieldset_id;
                    redirect(admin_url('workshop/fieldset_detail/'.$fieldset_id));
                }else{
                    redirect(admin_url('workshop/setting?group=fieldsets'));

                }

            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('custom_field_lowercase'));
        } else {
            $data['custom_field'] = $this->workshop_model->get_custom_field($id);
            $title                = _l('edit', _l('custom_field_lowercase'));
        }

        $data['pdf_fields']             = $this->pdf_fields;
        $data['client_portal_fields']   = $this->client_portal_fields;
        $data['client_editable_fields'] = $this->client_editable_fields;
        $data['title']                  = $title;
        $this->load->view('admin/custom_fields/customfield', $data);
    }

    /* Delete announcement from database */
    public function delete_custom_field($id, $fieldset_id)
    {
        if (!$id) {
            redirect(admin_url('workshop/fieldset_detail/'.$fieldset_id));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_custom_field');
        }

        $response = $this->workshop_model->delete_custom_field($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('custom_field')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('custom_field_lowercase')));
        }
        redirect(admin_url('workshop/fieldset_detail/'.$fieldset_id));
    }

    /**
     * inspection_template table
     * @return [type] 
     */
    public function inspection_template_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'settings/inspection_templates/inspection_template_table'));
    }

    /**
     * change inspection_template status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_inspection_template_status($id, $status) {
        if (has_permission('workshop_setting', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_inspection_template_status($id, (int)$status);
            }
        }
    }

    /**
     * inspection_template
     * @return [type] 
     */
    public function inspection_template()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if ($this->input->post('id')) {
                $id = $this->input->post('id');
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $success = $this->workshop_model->update_inspection_template($data, $id);
                if ($success == true) {
                    $message = _l('updated_successfully', _l('wshop_inspection_template'));
                }
            } else {
                $success = $this->workshop_model->add_inspection_template($this->input->post());
                if ($success == true) {
                    $message = _l('added_successfully', _l('wshop_inspection_template'));
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);die;
    }

    /**
     * delete inspection_template
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_inspection_template($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/setting?group=inspection_templates'));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_inspection_template');
        }

        $response = $this->workshop_model->delete_inspection_template($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/setting?group=inspection_templates'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/setting?group=inspection_templates'));
        }

    }

    /**
     * inspection_template  exists
     * @return [type] 
     */
    public function inspection_template_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if Day off is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_inspection_template = $this->db->get(db_prefix() . 'wshop_inspection_templates')->row();
                    if ($_current_inspection_template->name == $this->input->post('name')) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', $this->input->post('name'));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_inspection_templates');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * inspection_template detail
     * @param  string $inspection_template_id 
     * @return [type]              
     */
    public function inspection_template_detail($inspection_template_id = '')
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin() && !has_permission('workshop_setting', '', 'create') && !has_permission('workshop_setting', '', 'delete')) {
            access_denied('workshop_inspection_templates');
        }
        if(!is_numeric($inspection_template_id) || $inspection_template_id == ''){
            blank_page('Inspection Template Not Found', 'danger');
        }

        $data = [];
        $data['inspection_template_id'] = $inspection_template_id;
        $data['inspection_template'] = $this->workshop_model->get_inspection_template($inspection_template_id);
        $data['inspection_template_forms'] = $this->workshop_model->get_inspection_template_form(false, false,'inspection_template_id = '. $inspection_template_id);
        $this->load->view('settings/inspection_templates/inspection_template_forms/manage', $data);
    }

    /**
     * load inspection template form modal
     * @return [type] 
     */
    public function load_inspection_template_form_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $form_id = $this->input->post('form_id');
        $inspection_template_id = $this->input->post('inspection_template_id');

        $data['title'] = _l('wshop_add_inspection_template_form');
        $data['inspection_template_id'] = $inspection_template_id;

        if(is_numeric($form_id) && $form_id != 0){
            $data['inspection_template_form'] = $this->workshop_model->get_inspection_template_form($form_id);
            $data['title'] = _l('wshop_edit_inspection_template_form');
        }

        $this->load->view('settings/inspection_templates/inspection_template_forms/modals/inspection_template_form_modal', $data);
    }

    /**
     * add edit custom field
     * @param string $id 
     */
    public function add_edit_inspection_template_form($id = '')
    {
        if ($this->input->post()) {
            $status = false;
            $message = '';

            $inspection_template_id = $this->input->post('inspection_template_id');
            if ($id == '') {
                $id = $this->workshop_model->add_inspection_template_form($this->input->post());
                $name = $this->input->post('name');
                $description = $this->input->post('description');

                $data['inspection_template_forms'] = $this->workshop_model->get_inspection_template_form(false, false,'inspection_template_id = '. $inspection_template_id);
                $data['form_active'] = $id;
                $inspection_template_form_tab_html = $this->load->view('settings/inspection_templates/inspection_template_forms/inspection_template_form_tab', $data, true);

                $inspection_template_form_tab_content = '<div class="tab-pane" id="template_form_'.$id.'" role="tabpanel" aria-labelledby="template_form_'.$id.'-tab">
                <a href="#" onclick="inspection_template_form_detail_modal(0, '.$id.'); return false;" class="btn btn-info pull-right display-block">
                New Question                                                    </a>

                <h4>'.$name.'</h4>
                <p class="tw-flex tw-text-justify">'.$description.'</p>
                <div class="clearfix"></div><hr>

                <div id="form_detail_'.$id.'"></div>
                </div>';

                if($id){
                    $status = true;
                    $message = _l('added_successfully', _l('wshop_inspection_template_form'));
                }
                echo json_encode([
                    'id' => $id,
                    'status' => $status,
                    'message' => $message,
                    'inspection_template_form_tab_html' => $inspection_template_form_tab_html,
                    'is_add' => true,
                    'inspection_template_form_tab_content' => $inspection_template_form_tab_content,
                ]);
                die;
            }
            $success = $this->workshop_model->update_inspection_template_form($this->input->post(), $id);
            if($success){
                $status = true;
                $message = _l('updated_successfully', _l('wshop_inspection_template_form'));
            }
            $data['inspection_template_forms'] = $this->workshop_model->get_inspection_template_form(false, false,'inspection_template_id = '. $inspection_template_id);
            $data['form_active'] = $id;

            $inspection_template_form_tab_html = $this->load->view('settings/inspection_templates/inspection_template_forms/inspection_template_form_tab', $data, true);

            echo json_encode([
                'id' => $id,
                'status' => $status,
                'message' => $message,
                'is_add' => false,
                'inspection_template_form_tab_html' => $inspection_template_form_tab_html,
            ]);
            die;
        }
        redirect(admin_url('workshop/setting?group=inspection_templates'));
    }

    /* Delete announcement from database */
    public function delete_inspection_template_form($id, $template_id)
    {
        if (!$id) {
            redirect(admin_url('workshop/inspection_template_detail/'.$template_id));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_inspection_template_form');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_inspection_template_form($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('custom_field'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }

    /**
     * update inspection template form order
     * @return [type] 
     */
    public function update_inspection_template_form_order()
    {
        $data = $this->input->post();
        foreach ($data['data'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update(db_prefix() . 'wshop_inspection_template_forms', [
                'form_order' => $order[1],
            ]);
        }
    }

    /**
     * load inspection template form detail modal
     * @return [type] 
     */
    public function load_inspection_template_form_detail_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $inspection_template_form_detail_id = $this->input->post('form_detail_id');
        $inspection_template_form_id = $this->input->post('inspection_template_form_id');

        $data['title'] = _l('wshop_add_inspection_template_form_detail');
        $data['inspection_template_form_id'] = $inspection_template_form_id;

        if(is_numeric($inspection_template_form_detail_id) && $inspection_template_form_detail_id != 0){
            $data['inspection_template_form_detail'] = $this->workshop_model->get_inspection_template_form_detail($inspection_template_form_detail_id);

            $data['title'] = _l('wshop_edit_inspection_template_form_detail');
        }

        $this->load->view('settings/inspection_templates/inspection_template_forms/modals/inspection_template_form_detail_modal', $data);
    }

    /**
     * add edit inspection template form detail
     * @param string $id 
     */
    public function add_edit_inspection_template_form_detail($id = '')
    {
        if ($this->input->post()) {
            $status = false;
            $message = '';
            $inspection_template_form_question_html = '';

            $data = $this->input->post();
            $data['name'] = $this->input->post('name', false);
            $inspection_template_form_id = $this->input->post('inspection_template_form_id');
            if ($id == '') {
                $id = $this->workshop_model->add_inspection_template_form_detail($data);
                if($id){
                    $status = true;
                    $message = _l('added_successfully', _l('wshop_inspection_template_form_question'));
                }

                $inspection_template_form_question_html .= wshop_render_inspection_template_form_fields('form_fieldset_'.$inspection_template_form_id, false, ['id' => $id], ['items_pr' => true]);

                echo json_encode([
                    'type' => 'insert',
                    'id' => $id,
                    'status' => $status,
                    'message' => $message,
                    'inspection_template_form_question_html' => $inspection_template_form_question_html,
                ]);
                die;
            }
            $success = $this->workshop_model->update_inspection_template_form_detail($data, $id);
            if($success){
                $status = true;
                $message = _l('updated_successfully', _l('wshop_inspection_template_form_question'));
            }

            $inspection_template_form_question_html .= wshop_render_inspection_template_form_fields('form_fieldset_'.$inspection_template_form_id, false, ['id' => $id], ['items_pr' => true]);

            echo json_encode([
                'type' => 'update',
                'id' => $id,
                'status' => $status,
                'message' => $message,
                'inspection_template_form_question_html' => $inspection_template_form_question_html,
            ]);
            die;
        }
        redirect(admin_url('workshop/setting?group=inspection_templates'));
    }

    /**
     * get inspection template form details
     * @param  [type] $inspection_template_form_id 
     * @return [type]                              
     */
    public function get_inspection_template_form_details($inspection_template_form_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $inspection_template_form_details = '';
        $data['inspection_template_form_details'] = $this->workshop_model->get_inspection_template_form_detail(false, false,'inspection_template_form_id = '. $inspection_template_form_id);

        $inspection_template_form_details .= wshop_render_inspection_template_form_fields('form_fieldset_'.$inspection_template_form_id, false, [], ['items_pr' => true]);

        echo json_encode([
            'status' => true,
            'inspection_template_form_details' => $inspection_template_form_details,
        ]);
    }

    /**
     * delete inspection template form detail
     * @param  [type] $id          
     * @param  [type] $template_id 
     * @return [type]              
     */
    public function delete_inspection_template_form_detail($id, $template_id)
    {
        if (!$id) {
            redirect(admin_url('workshop/inspection_template_detail/'.$template_id));
        }

        if(!has_permission('workshop_setting', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_inspection_template_form_detail');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_inspection_template_form_detail($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('inspection_template_form_detail_name'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * update question required
     * @param  [type] $question_id       
     * @param  [type] $question_required 
     * @return [type]                    
     */
    public function update_question_required($question_id, $question_required)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $status = false;
        $message = '';
        $this->db->where('id', $question_id);
        $this->db->update(db_prefix().'wshop_inspection_template_form_details', ['required' => $question_required]);
        if ($this->db->affected_rows() > 0) {
            $message = _l('updated_successfully', _l('wshop_inspection_template_form_question'));
            $status = true;
        }

        echo json_encode([
            'status' => $status,
            'message' => $message,
        ]);
    }

    /**
     * update inspection template form question order
     * @return [type] 
     */
    public function update_inspection_template_form_question_order()
    {
        $data = $this->input->post();
        foreach ($data['data'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update(db_prefix() . 'wshop_inspection_template_form_details', [
                'field_order' => $order[1],
            ]);
        }
    }

    public function mechanics() {
        if (!has_permission('workshop_mechanic', '', 'view') && !has_permission('workshop_mechanic', '', 'view_own')) {
            access_denied('wshop_mechanics');
        }

        $this->load->model('roles_model');
        $this->load->model('staff_model');
        $this->load->model('mechanic_commission_model');
        
        // Buscar mecânicos
        $data['mechanics'] = $this->workshop_model->get_mechanics();
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
        
        // Calcular totais de comissões para os cards
        $data['commission_day'] = $this->calculate_total_commissions('day');
        $data['commission_week'] = $this->calculate_total_commissions('week');
        $data['commission_month'] = $this->calculate_total_commissions('month');
        
        $data['title'] = _l('wshop_mechanics');
        $this->load->view('mechanics/manage_mechanic', $data);
    }

    /**
     * table
     */
    public function mechanic_table() {
        $this->app->get_table_data(module_views_path('workshop', 'mechanics/mechanic_table'));
    }

    /**
     * delete staff
     * @return [type] 
     */
    public function delete_staff() {
        if (!has_permission('workshop_mechanic', '', 'delete')) {
            access_denied('workshop_mechanic');
        }
        $id = $this->input->post('id');
        if (!$id) {
            redirect(admin_url('workshop/mechanics'));
        }
        if (!is_admin() && is_admin($id)) {
            die('Busted, you can\'t delete administrators');
        }
        $response = $this->staff_model->delete($id, $this->input->post('transfer_data_to'));
        if ($response == true) {
            set_alert('success', _l('deleted', _l('staff_member')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('staff_member_lowercase')));
        }
        redirect(admin_url('workshop/mechanics'));
    }
    
    /**
     * Calcula o total de comissões para todos os mecânicos
     * @param string $period Período (day, week, month)
     * @return float Total de comissões no período
     * Corrigido em 2024-12-19: Verificação de existência da coluna commission_amount e correção definitiva do alias
     */
    private function calculate_total_commissions($period = 'month')
    {
        try {
            // Verificar se a coluna commission_amount existe na tabela wshop_repair_jobs
            if (!$this->db->field_exists('commission_amount', db_prefix() . 'wshop_repair_jobs')) {
                log_activity('Workshop: Coluna commission_amount não existe na tabela wshop_repair_jobs');
                return 0;
            }
            
            // Definir datas de início e fim com base no período
            $end_date = date('Y-m-d');
            
            switch ($period) {
                case 'day':
                    $start_date = date('Y-m-d');
                    break;
                case 'week':
                    $start_date = date('Y-m-d', strtotime('monday this week'));
                    break;
                case 'month':
                    $start_date = date('Y-m-01');
                    break;
                default:
                    $start_date = date('Y-m-01');
                    break;
            }
            
            // Limpar cache do query builder
            $this->db->reset_query();
            
            // Buscar comissões de chamados faturados no período
            // Corrigido em 2023-11-10: Alterado o alias da tabela de tblrj para rj
            // Corrigido em 2024-12-19: Adicionada verificação de existência da coluna e tratamento de erro
            $table_name = db_prefix() . 'wshop_repair_jobs';
            $invoices_table = db_prefix() . 'invoices';
            
            $sql = "SELECT COALESCE(SUM(rj.commission_amount), 0) as commission_amount 
                    FROM {$table_name} rj 
                    LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id 
                    WHERE rj.invoice_id IS NOT NULL 
                    AND i.status = 2 
                    AND DATE(rj.datecreated) >= ? 
                    AND DATE(rj.datecreated) <= ?";
            
            $query = $this->db->query($sql, [$start_date, $end_date]);
            $result = $query->row();
            
            return $result && $result->commission_amount ? (float)$result->commission_amount : 0;
            
        } catch (Exception $e) {
            log_activity('Workshop: Erro ao calcular comissões - ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Carrega a página de dashboard de comissões
     * @return void
     */
    public function commissions_dashboard()
    {
        if (!has_permission('workshop_mechanic', '', 'view') && !has_permission('workshop_mechanic', '', 'view_own')) {
            access_denied('wshop_mechanics');
        }
        
        $this->load->model('staff_model');
        $this->load->model('mechanic_commission_model');
        $this->load->model('currencies_model');
        
        // Buscar mecânicos
        $data['mechanics'] = $this->workshop_model->get_mechanics();
        
        // Calcular totais de comissões para os cards
        $data['commission_day'] = $this->calculate_total_commissions('day');
        $data['commission_week'] = $this->calculate_total_commissions('week');
        $data['commission_month'] = $this->calculate_total_commissions('month');
        
        // Moeda base
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        
        $data['title'] = _l('wshop_mechanic_commissions');
        $this->load->view('mechanics/commissions_dashboard', $data);
    }
    
    /**
     * Busca as comissões de um mecânico específico
     * Retorna apenas comissões de chamados faturados
     * Corrigido em 2024-12-19: Adicionada verificação de coluna e tratamento de erro
     */
    public function get_mechanic_commissions()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $mechanic_id = $this->input->post('mechanic_id');
        if (!$mechanic_id) {
            echo json_encode([]);
            die();
        }
        
        try {
            // Verificar se a coluna commission_amount existe
            if (!$this->db->field_exists('commission_amount', db_prefix() . 'wshop_repair_jobs')) {
                echo json_encode([]);
                die();
            }
            
            // Limpar cache do query builder
            $this->db->reset_query();
            
            // Buscar chamados faturados com comissão para o mecânico selecionado
            $table_name = db_prefix() . 'wshop_repair_jobs';
            $clients_table = db_prefix() . 'clients';
            $staff_table = db_prefix() . 'staff';
            
            $sql = "SELECT rj.id, rj.number, rj.datecreated, rj.commission_amount, 
                           c.company as client_name, 
                           CONCAT(s.firstname, ' ', s.lastname) as mechanic_name
                    FROM {$table_name} rj
                    LEFT JOIN {$clients_table} c ON c.userid = rj.client_id
                    LEFT JOIN {$staff_table} s ON s.staffid = rj.sale_agent
                    WHERE rj.sale_agent = ?
                    AND rj.invoice_id IS NOT NULL
                    ORDER BY rj.datecreated DESC
                    LIMIT 10";
            
            $query = $this->db->query($sql, [$mechanic_id]);
            $result = $query->result_array();
            
            // Formatar os valores para exibição
            foreach ($result as &$row) {
                $row['datecreated'] = _dt($row['datecreated']);
                $row['commission_amount'] = app_format_money($row['commission_amount'], get_base_currency());
            }
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            log_activity('Workshop: Erro ao buscar comissões do mecânico - ' . $e->getMessage());
            echo json_encode([]);
        }
        
        die();
    }
    
    /**
     * Busca comissões filtradas para o dashboard de comissões
     * Retorna dados para AJAX
     */
    public function get_filtered_commissions()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        if (!has_permission('workshop_mechanic', '', 'view') && !has_permission('workshop_mechanic', '', 'view_own')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $this->load->model('mechanic_commission_model');
        $this->load->model('currencies_model');
        $this->load->model('invoices_model');
        
        // Obter filtros da requisição
        $mechanic_id = $this->input->post('mechanic_id');
        $period = $this->input->post('period');
        $page = $this->input->post('page') ? (int)$this->input->post('page') : 1;
        $per_page = 10; // Número de registros por página
        
        // Definir datas com base no período - Updated 2024-12-19: Adicionado suporte para last_month e last_year
        $end_date = date('Y-m-d');
        switch ($period) {
            case 'day':
                $start_date = date('Y-m-d');
                break;
            case 'week':
                $start_date = date('Y-m-d', strtotime('monday this week'));
                break;
            case 'month':
                $start_date = date('Y-m-01');
                break;
            case 'last_month':
                $start_date = date('Y-m-01', strtotime('first day of last month'));
                $end_date = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'last_year':
                $start_date = date('Y-01-01', strtotime('last year'));
                $end_date = date('Y-12-31', strtotime('last year'));
                break;
            case 'all':
                $start_date = null;
                break;
            default:
                $start_date = date('Y-m-01'); // Padrão: mês atual
                break;
        }
        
        // Calcular offset para paginação
        $offset = ($page - 1) * $per_page;
        
        // Verificar se a coluna commission_amount existe
        if (!$this->db->field_exists('commission_amount', db_prefix() . 'wshop_repair_jobs')) {
            echo json_encode([
                'success' => false,
                'message' => 'Coluna commission_amount não existe na tabela wshop_repair_jobs'
            ]);
            die();
        }
        
        // Limpar cache do query builder
        $this->db->reset_query();
        
        try {
            // Preparar tabelas
            $repair_jobs_table = db_prefix() . 'wshop_repair_jobs';
            $clients_table = db_prefix() . 'clients';
            $staff_table = db_prefix() . 'staff';
            $invoices_table = db_prefix() . 'invoices';
            
            // Construir WHERE clause
            $where_conditions = [];
            $params = [];
            
            // Aplicar filtro de mecânico
            if ($mechanic_id && $mechanic_id != 'all') {
                $where_conditions[] = 'rj.sale_agent = ?';
                $params[] = $mechanic_id;
            }
            
            // Aplicar filtro de período
            if ($start_date) {
                $where_conditions[] = 'DATE(rj.datecreated) >= ?';
                $where_conditions[] = 'DATE(rj.datecreated) <= ?';
                $params[] = $start_date;
                $params[] = $end_date;
            }
            
            $where_clause = '';
            if (!empty($where_conditions)) {
                $where_clause = 'AND ' . implode(' AND ', $where_conditions);
            }
            
            // Contar total de registros para paginação - Updated 2024-12-19: Incluir todas as comissões
            $count_sql = "SELECT COUNT(*) as total
                         FROM {$repair_jobs_table} rj
                         LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                         WHERE rj.commission_amount > 0
                         {$where_clause}";
            
            $count_query = $this->db->query($count_sql, $params);
            $total_count = $count_query->row()->total;
            
            // Buscar dados de comissões com paginação - Updated 2024-12-19: Incluir todas as comissões
            $sql = "SELECT rj.id, rj.number, rj.datecreated, rj.commission_amount, 
                           c.company as client_name, 
                           CONCAT(s.firstname, ' ', s.lastname) as mechanic_name, 
                           rj.invoice_id,
                           CASE 
                               WHEN rj.invoice_id IS NOT NULL AND i.status = 2 THEN 'Faturado'
                               WHEN rj.invoice_id IS NOT NULL THEN 'Fatura Pendente'
                               ELSE 'Não Faturado'
                           END as invoice_status
                    FROM {$repair_jobs_table} rj
                    LEFT JOIN {$clients_table} c ON c.userid = rj.client_id
                    LEFT JOIN {$staff_table} s ON s.staffid = rj.sale_agent
                    LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                    WHERE rj.commission_amount > 0
                      {$where_clause}
                      ORDER BY rj.datecreated DESC
                      LIMIT {$per_page} OFFSET {$offset}";
            
            $query = $this->db->query($sql, $params);
            $commissions = $query->result_array();
            
        } catch (Exception $e) {
            log_activity('Workshop: Erro ao buscar comissões filtradas - ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar comissões: ' . $e->getMessage()
            ]);
            die();
        }
        
        // Formatar dados para exibição
        foreach ($commissions as &$row) {
            $row['datecreated_raw'] = $row['datecreated'];
            $row['datecreated'] = _dt($row['datecreated']);
            $row['commission_amount_raw'] = $row['commission_amount'];
            $row['commission_amount'] = app_format_money($row['commission_amount'], get_base_currency());
        }
        
        // Obter dados para os cards de comissão - Updated 2024-12-19: Adicionado filtro de período
        $filters = [];
        if ($mechanic_id && $mechanic_id != 'all') {
            $filters['mechanic_filter'] = $mechanic_id;
        }
        if ($period) {
            $filters['period_filter'] = $period;
        }
        
        $cards_data = $this->mechanic_commission_model->get_commission_cards_data($filters);
        
        // Formatar valores monetários para os cards
        $base_currency = $this->currencies_model->get_base_currency();
        $cards_data['daily_formatted'] = app_format_money($cards_data['daily'], $base_currency);
        $cards_data['weekly_formatted'] = app_format_money($cards_data['weekly'], $base_currency);
        $cards_data['monthly_formatted'] = app_format_money($cards_data['monthly'], $base_currency);
        $cards_data['total_formatted'] = app_format_money($cards_data['total'], $base_currency);
        
        // Calcular informações de paginação
        $total_pages = ceil($total_count / $per_page);
        
        // Retornar dados em formato JSON
        echo json_encode([
            'success' => true,
            'commissions' => $commissions,
            'cards_data' => $cards_data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total_count
            ]
        ]);
        die();
    }

    /**
     * new member
     * @return [type]
     */
    public function new_mechanic() {

        if (!has_permission('workshop_mechanic', '', 'create')) {
            access_denied('staff');
        }

        $data['hr_profile_member_add'] = true;
        $title = _l('add_new', _l('staff_member_lowercase'));

        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['roles_value'] = $this->roles_model->get();
        $data['departments'] = $this->departments_model->get();
        $data['title'] = $title;
        $data['staff'] = $this->staff_model->get();
        $data['list_staff'] = $this->staff_model->get();
        $data['funcData'] = ['staff_id' => isset($staff_id) ? $staff_id : null];
        $data['mechanic_role'] = $this->workshop_model->mechanic_role_exists();

        $this->load->view('mechanics/new_mechanic', $data);
    }

    public function mechanic_modal() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $this->load->model('staff_model');

        if ($this->input->post('slug') === 'create') {

            $this->load->view('hr_record/mew_member', $data);

        } else if ($this->input->post('slug') === 'update') {
            $staff_id = $this->input->post('staff_id');
            $role_id = $this->input->post('role_id');

            $data = ['funcData' => ['staff_id' => isset($staff_id) ? $staff_id : null]];

            if (isset($staff_id)) {
                $data['mechanic'] = $this->staff_model->get($staff_id);
            }

            $data['roles_value'] = $this->roles_model->get();
            $add_new = $this->input->post('add_new');

            if ($add_new == ' hide') {
                $data['add_new'] = ' hide';
                $data['display_staff'] = '';
            } else {
                $data['add_new'] = '';
                $data['display_staff'] = ' hide';
            }
            $this->load->model('currencies_model');

            $data['list_staff'] = $this->staff_model->get();
            $data['base_currency'] = $this->currencies_model->get_base_currency();
            $data['departments'] = $this->departments_model->get();
            $data['staff_departments'] = $this->departments_model->get_staff_departments($staff_id);
            $data['staff_cover_image'] = $this->workshop_model->get_attachment_file($staff_id, 'staff_profile_images');
            $data['manage_staff'] = $this->input->post('manage_staff');
            $data['mechanic_role'] = $this->workshop_model->mechanic_role_exists();

            $this->load->view('mechanics/update_mechanic', $data);
        }
    }

    /**
     * add edit member
     * @param string $id
     */
    public function add_edit_mechanic($id = '') {
        if (!has_permission('workshop_mechanic', '', 'view') && !has_permission('workshop_mechanic', '', 'view_own') && get_staff_user_id() != $id) {
            access_denied('staff');
        }
        hooks()->do_action('staff_member_edit_view_profile', $id);

        $this->load->model('departments_model');
        if ($this->input->post()) {
            $data = $this->input->post();

            // Remove campos que não devem ser salvos no banco
            if(isset($data['memberid'])){
                unset($data['memberid']);
            }
            if(isset($data['isedit'])){
                unset($data['isedit']);
            }

            if(isset($data['role_v'])){
                $data['role'] = $data['role_v'];
                unset($data['role_v']);
            }

            // Don't do XSS clean here.
            $data['email_signature'] = $this->input->post('email_signature', false);
            $data['email_signature'] = new_html_entity_decode($data['email_signature']);

            if ($data['email_signature'] == strip_tags($data['email_signature'])) {
                // not contains HTML, add break lines
                $data['email_signature'] = nl2br_save_html($data['email_signature']);
            }

            $data['password'] = $this->input->post('password', false);

            if (isset($data['comissao'])) {
                $data['comissao'] = str_replace(',', '.', $data['comissao']);
            }

            if (isset($data['formacao'])) {
                $data['formacao'] = $data['formacao'];
            }

            $this->load->model('staff_model');
            if ($id == '') {
                if (!has_permission('workshop_mechanic', '', 'create')) {
                    access_denied('staff');
                }
                $id = $this->staff_model->add($data);

                if ($id) {
                    handle_staff_profile_image_upload($id);
                    set_alert('success', _l('added_successfully', _l('wshop_mechanic')));
                    redirect(admin_url('workshop/mechanics'));
                }
            } else {
                if (!has_permission('workshop_mechanic', '', 'edit') && get_staff_user_id() != $id) {
                    access_denied('staff');
                }

                $manage_staff = false;
                if (isset($data['manage_staff'])) {
                    $manage_staff = true;
                    unset($data['manage_staff']);
                }
                handle_staff_profile_image_upload($id);
                $response = $this->staff_model->update($data, $id);
                if (is_array($response)) {
                    if (isset($response['cant_remove_main_admin'])) {
                        set_alert('warning', _l('staff_cant_remove_main_admin'));
                    } elseif (isset($response['cant_remove_yourself_from_admin'])) {
                        set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
                    }
                } elseif ($response == true) {
                    set_alert('success', _l('updated_successfully', _l('wshop_mechanic')));
                }

                if ($manage_staff) {
                    redirect(admin_url('workshop/mechanics'));
                } else {
                    redirect(admin_url('workshop/mechanics'));
                }
            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('staff_member_lowercase'));
        } else {
            $title = _l('edit', _l('wshop_mechanic_lowercase'));
        }
        
        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['roles_value'] = $this->roles_model->get();
        $data['departments'] = $this->departments_model->get();
        $data['title'] = $title;
        $data['staff'] = $this->staff_model->get();
        $data['list_staff'] = $this->staff_model->get();
        $data['funcData'] = ['staff_id' => isset($staff_id) ? $staff_id : null];

        if ($id == '') {
            $this->load->view('mechanics/new_mechanic', $data);
        } else {
            $data['mechanic'] = $this->staff_model->get($id);
            $data['staff_departments'] = $this->departments_model->get_staff_departments($id);
            $data['staff_cover_image'] = $this->workshop_model->get_attachment_file($id, 'staff_profile_images');
            $data['mechanic_role'] = $this->workshop_model->mechanic_role_exists();
            $data['commissions'] = $this->workshop_model->get_commissions(false, true);
            $this->load->view('mechanics/new_mechanic', $data);
        }
    }

    /**
     * change staff status: Change status to staff active or inactive
     * @param  [type] $id
     * @param  [type] $status
     * @return [type]
     */
    public function change_staff_status($id, $status) {
        if (has_permission('workshop_mechanic', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->staff_model->change_staff_status($id, $status);
            }
        }
    }

    /**
     * branches
     * @return [type] 
     */
    public function branches()
    {
        if (!has_permission('workshop_branch', '', 'view') && !has_permission('workshop_branch', '', 'view_own')) {
            access_denied('wshop_branches');
        }

        $data['title'] = _l('wshop_branches');

        $this->load->view('branches/manage', $data);
    }

    /**
     * branch table
     * @return [type] 
     */
    public function branch_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'branches/branch_table'));
    }

    /**
     * load branch modal
     * @return [type] 
     */
    public function load_branch_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $branch_id = $this->input->post('branch_id');
        $data['title'] = _l('wshop_add_branch');
        if(is_numeric($branch_id) && $branch_id != 0){
            $data['branch'] = $this->workshop_model->get_branch($branch_id);
            $data['title'] = _l('wshop_edit_branch');
        }

        $this->load->view('branches/branch_modal', $data);
    }

    /**
     * change branch status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_branch_status($id, $status) {
        if (has_permission('workshop_branch', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_branch_status($id, (int)$status);
            }
        }
    }

    /**
     * branch
     * @return [type] 
     */
    public function add_edit_branch($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            if (is_numeric($id) && $id != 0) {
                $data = $this->input->post();
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_branch($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_branch'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);die;
            } else {
                $response = $this->workshop_model->add_branch($this->input->post());
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_branch'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);die;
            }
        }
        
    }

    /**
     * delete branch
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_branch($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/branches'));
        }

        if(!has_permission('workshop_branch', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_branchs');
        }

        $response = $this->workshop_model->delete_branch($id);
        if ($response) {
            set_alert('success', _l('deleted'));
            redirect(admin_url('workshop/branches'));
        } else {
            set_alert('warning', _l('problem_deleting'));
            redirect(admin_url('workshop/branches'));
        }

    }

    /**
     * branch exists
     * @return [type] 
     */
    public function branch_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if branch is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_branch = $this->db->get(db_prefix() . 'wshop_branches')->row();
                    if (strtoupper($_current_branch->name) == strtoupper(($this->input->post('name')))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', ($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_branches');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * send mail to branch
     * @return [type] 
     */
    public function send_mail_to_branch()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['content'] = $this->input->post('email_content', false);
            $rs = $this->workshop_model->send_mail_to_branch($data);
            if ($rs == true) {
                set_alert('success', _l('wshop_send_mail_successfully'));

            }
            redirect(admin_url('workshop/branches'));
        }
    }


    /**
     * devices
     * @return [type] 
     */
    public function devices()
    {
        if (!has_permission('workshop_device', '', 'view') && !has_permission('workshop_device', '', 'view_own')) {
            access_denied('wshop_devices');
        }

        $data['title'] = _l('wshop_devices');
        $data['clients'] = $this->clients_model->get();
        $data['devices'] = $this->workshop_model->get_device();
        $data['models'] = $this->workshop_model->get_model(false, true);


        $this->load->view('devices/manage', $data);
    }

    /**
     * device table
     * @return [type] 
     */
    public function device_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'devices/device_table'));
    }

    /**
     * products management
     * @return [type] 
     */
    public function products()
    {
        if (!has_permission('workshop_product', '', 'view') && !has_permission('workshop_product', '', 'view_own')) {
            access_denied('wshop_products');
        }

        // Verificar se é para editar ou criar produto
        $edit_id = $this->input->get('edit');
        $new_product = $this->input->get('new');
        
        if ($edit_id || $new_product) {
            // Carregar modal de produto
            $this->load_product_form($edit_id);
            return;
        }
        
        // Processar formulário de produto se enviado
        if ($this->input->post()) {
            $this->add_edit_product($this->input->post('product_id'));
            return;
        }

        $data['title'] = _l('wshop_products');
        $data['categories'] = $this->workshop_model->get_product_categories();
        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        
        // Aplicar filtros
        $filters = [];
        if ($this->input->get('category_filter')) {
            $filters['category_id'] = $this->input->get('category_filter');
        }
        if ($this->input->get('status_filter') !== '' && $this->input->get('status_filter') !== null) {
            $filters['status'] = $this->input->get('status_filter');
        }
        if ($this->input->get('search_product')) {
            $filters['search'] = $this->input->get('search_product');
        }
        
        // Carregar produtos com filtros
        $data['products'] = $this->workshop_model->get_products_filtered($filters);

        $this->load->view('products/manage', $data);
    }

    /**
     * product table
     * @return [type] 
     */
    public function product_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'products/product_table'));
    }

    /**
     * load product form
     * @return [type] 
     */
    public function load_product_form($id = '')
    {
        if (!has_permission('workshop_product', '', 'create') && !has_permission('workshop_product', '', 'edit')) {
            access_denied('wshop_products');
        }
        
        $data = [];
        $product_id = $id;
        $data['title'] = _l('wshop_new_product');
        
        if(is_numeric($product_id) && $product_id != 0){
            if (!has_permission('workshop_product', '', 'edit')) {
                access_denied('wshop_products');
            }
            $data['product'] = $this->workshop_model->get_product($product_id);
            $data['title'] = _l('wshop_edit_product');
        } else {
            if (!has_permission('workshop_product', '', 'create')) {
                access_denied('wshop_products');
            }
        }
        
        $data['categories'] = $this->workshop_model->get_product_categories();
        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        
        $this->load->view('products/product_form', $data);
    }
    
    /**
     * load product modal (mantido para compatibilidade)
     * @return [type] 
     */
    public function load_product_modal($id = '')
    {
        $this->load_product_form($id);
    }

    /**
     * add edit product
     * @param string $id 
     * @return [type]     
     */
    public function add_edit_product($id ='')
    {
        if (!has_permission('workshop_product', '', 'create') && !has_permission('workshop_product', '', 'edit')) {
            access_denied('wshop_products');
        }
        
        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Se product_id está no POST, usar ele para edição
            if (isset($data['product_id']) && !empty($data['product_id'])) {
                $id = $data['product_id'];
                unset($data['product_id']); // Remove do array de dados
            }
            
            if ($id == '' || $id == '0') {
                if (!has_permission('workshop_product', '', 'create')) {
                    access_denied('wshop_products');
                }
                $id = $this->workshop_model->add_product($data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('wshop_product')));
                    redirect(admin_url('workshop/products'));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                    redirect(admin_url('workshop/products?new=1'));
                }
            } else {
                if (!has_permission('workshop_product', '', 'edit')) {
                    access_denied('wshop_products');
                }
                $success = $this->workshop_model->update_product($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('wshop_product')));
                    redirect(admin_url('workshop/products'));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                    redirect(admin_url('workshop/products?edit=' . $id));
                }
            }
        }
    }

    /**
     * delete product
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_product($id)
    {
        if (!has_permission('workshop_product', '', 'delete')) {
            access_denied('wshop_products');
        }
        if (!$id) {
            redirect(admin_url('workshop/products'));
        }
        $response = $this->workshop_model->delete_product($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('wshop_product_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('wshop_product')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('wshop_product_lowercase')));
        }
        redirect(admin_url('workshop/products'));
    }

    /**
     * load device modal
     * @return [type] 
     */
    public function load_device_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $device_id = $this->input->post('device_id');
        $data['title'] = _l('wshop_add_device');
        if(is_numeric($device_id) && $device_id != 0){
            $data['device'] = $this->workshop_model->get_device($device_id);
            $data['title'] = _l('wshop_edit_device');
            $data['product_attachments'] = $this->workshop_model->get_attachment_file($device_id, 'wshop_device');
            $fieldset_id = 0;

            if($data['device'] && $data['device']->model_id != 0){
                $model_id = $data['device']->model_id;
                $model = $this->workshop_model->get_model($model_id);
                if($model && !is_null($model->fieldset_id) && $model->fieldset_id != 0){
                    $fieldset_id = $model->fieldset_id;
                }
            }
            $data['fieldset_id'] = $fieldset_id;

        }
        $this->load->model('clients_model');
        $data['clients'] = $this->clients_model->get();
        $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => "device"]);
        $data['manufacturers'] = $this->workshop_model->get_manufacturer(false, true);
        $data['fieldsets'] = $this->workshop_model->get_fieldset(false, true);
        $data['models'] = $this->workshop_model->get_model(false, true);

        $this->load->view('devices/device_modal', $data);
    }

    /**
     * change device status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_device_status($id, $status) {
        if (has_permission('workshop_device', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_device_status($id, (int)$status);
            }
        }
    }

    /**
     * device
     * @return [type] 
     */
    public function add_edit_device($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_device($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_device'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'device_id' => $id,
                    'url' => admin_url('workshop/devices'),
                ]);die;
            } else {
                $response = $this->workshop_model->add_device($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_device'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'device_id' => $response,
                    'url' => admin_url('workshop/devices'),

                ]);die;
            }
        }
        
    }

    /**
     * delete device
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_device($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/device'));
        }

        if(!has_permission('workshop_device', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_device');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_device($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('wshop_device'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }

    /**
     * device exists
     * @return [type] 
     */
    public function device_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if device is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_device = $this->db->get(db_prefix() . 'wshop_devices')->row();
                    if (strtoupper($_current_device->name) == strtoupper(($this->input->post('name')))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', ($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_devices');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * delete device image
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_device_image($id){

        $deleted    = false;

        $this->db->where('id', $id);
        $this->db->update(db_prefix().'wshop_devices', [
            'manufacture_image' => '',
        ]);
        if ($this->db->affected_rows() > 0) {
            $deleted = true;
        }
        if (is_dir(DEVICES_IMAGES_FOLDER. $id)) {
            // Check if no attachments left, so we can delete the folder also
            $other_attachments = list_files(DEVICES_IMAGES_FOLDER. $id);
                // okey only index.html so we can delete the folder also
            delete_dir(DEVICES_IMAGES_FOLDER. $id);
        }
        
        echo json_encode($deleted);
    }

    /**
     * add device attachment
     * @param [type] $id 
     */
    public function add_device_attachment($id)
    {
        wshop_handle_device_attachments($id);
        $url = admin_url('workshop/devices');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * delete device attachment
     * @param  [type]  $attachment_id 
     * @param  boolean $folder_name   
     * @return [type]                 
     */
    public function delete_device_attachment($attachment_id, $folder_name = false)
    {
        if (!has_permission('workshop_device', '', 'delete') && !is_admin()) {
            access_denied('workshop_device');
        }
        $_folder_name = DEVICES_IMAGES_FOLDER;

        echo json_encode([
            'success' => $this->workshop_model->delete_workshop_file($attachment_id, $_folder_name),
        ]);
    }

    /**
     * get model ajax
     * @param  [type] $model_id  
     * @param  [type] $device_id 
     * @return [type]            
     */
    public function get_model_ajax($model_id, $device_id)
    {
        
        $message = '';
        $success = true;
        $fieldset = '';
        $fieldset_id = 0;
        if ($this->input->get()) {
            if($model_id != 0){
                $fieldset_id = wshop_get_fieldset_id_by_model($model_id);
                $fieldset = wshop_render_custom_fields('fieldset_'.$fieldset_id, $device_id);
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'fieldset' => $fieldset,
        ]);die;
    }

    /**
     * device detail
     * @param  string $id 
     * @return [type]     
     */
    public function device_detail($id = '')
    {
        if (!has_permission('workshop_device', '', 'view') && !has_permission('workshop_device', '', 'view_own') && !has_permission('workshop_device', '', 'edit') && !is_admin() && !has_permission('workshop_device', '', 'create')) {
            access_denied('workshop_device');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Device Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['device'] = $this->workshop_model->get_device($id);
        $data['device_images'] = $this->workshop_model->get_device_images($id);
        $data['device_attachments'] = $this->workshop_model->get_attachment_file($id, 'wshop_device');

        $this->load->view('devices/device_detail', $data);
    }

    /**
     * load transfer ownership modal
     * @return [type] 
     */
    public function load_transfer_ownership_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $device_id = $this->input->post('device_id');
        $data['title'] = _l('wshop_transfer_ownership_of_device');
        if(is_numeric($device_id) && $device_id != 0){
            $data['device'] = $this->workshop_model->get_device($device_id);
        }
        $this->load->model('clients_model');
        $data['clients'] = $this->clients_model->get();

        $this->load->view('devices/transfer_ownership_modal', $data);
    }

    /**
     * get client data
     * @param  string $client_id 
     * @return [type]            
     */
    public function get_client_data($client_id = '')
    {
        $message = '';
        $success = true;
        $client_address = '---';
        $client_phone = '---';

        $contact_phone = '---';
        $contact_email = '---';
        if ($this->input->get()) {
            if($client_id != ''){
                $this->load->model('clients_model');
                $client = $this->clients_model->get($client_id);
                $invoice = new stdClass();
                $invoice = $client;
                $invoice->client = $client;
                $invoice->clientid = $client_id;

                $client_address = format_customer_info($invoice, 'invoice', 'billing');
                $client_phone = $client->phonenumber;
                $contact = $this->clients_model->get_contact(get_primary_contact_user_id($client_id));
                if($contact){
                    $contact_phone = $contact->phonenumber;
                    $contact_email = $contact->email;
                }
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'client_address' => $client_address,
            'client_phone' => $client_phone,
            'contact_phone' => $contact_phone,
            'contact_email' => $contact_email,
        ]);die;
    }

    /**
     * edit transfer ownwership
     * @param  string $device_id 
     * @return [type]            
     */
    public function edit_transfer_ownwership($device_id='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {

            if (is_numeric($device_id) && $device_id != 0) {
                $data = $this->input->post();

                $response = $this->workshop_model->update_device(['client_id' => $data['client_id']], $device_id);
                if ($response == true) {
                    set_alert('success', _l('updated_successfully', _l('wshop_device')));
                }
            }
        }
        redirect(admin_url('workshop/device_detail/'.$device_id));
    }


    /**
     * labour_products
     * @return [type] 
     */
    public function labour_products()
    {
        if (!has_permission('workshop_labour_product', '', 'view') && !has_permission('workshop_labour_product', '', 'view_own')) {
            access_denied('wshop_labour_products');
        }

        $data['title'] = _l('wshop_labour_products');
        $data['clients'] = $this->clients_model->get();
        $data['models'] = $this->workshop_model->get_model(false, true);
        $data['staffs'] = $this->staff_model->get();
        $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => "Labour_Product"]);


        $this->load->view('labour_products/manage', $data);
    }

    /**
     * labour_product table
     * @return [type] 
     */
    public function labour_product_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'labour_products/labour_product_table'));
    }

    /**
     * load labour_product modal
     * @return [type] 
     */
    public function load_labour_product_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $labour_product_id = $this->input->post('labour_product_id');
        $data['title'] = _l('wshop_add_labour_product');
        if(is_numeric($labour_product_id) && $labour_product_id != 0){
            $data['labour_product'] = $this->workshop_model->get_labour_product($labour_product_id);
            $data['title'] = _l('wshop_edit_labour_product');
        }
        $this->load->model('taxes_model');
        $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => "Labour_Product"]);
        $data['staffs'] = $this->staff_model->get();
        $data['taxes'] = $this->taxes_model->get();

        $this->load->view('labour_products/labour_product_modal', $data);
    }

    /**
     * change labour_product status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_labour_product_status($id, $status) {
        if (has_permission('workshop_labour_product', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_labour_product_status($id, (int)$status);
            }
        }
    }

    /**
     * labour_product
     * @return [type] 
     */
    public function add_edit_labour_product($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_labour_product($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_labour_product'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'labour_product_id' => $id,
                    'url' => admin_url('workshop/labour_products'),
                ]);die;
            } else {

                $response = $this->workshop_model->add_labour_product($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_labour_product'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'labour_product_id' => $response,
                    'url' => admin_url('workshop/labour_products'),

                ]);die;
            }
        }
        
    }

    /**
     * delete labour_product
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_labour_product($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/labour_product'));
        }

        if(!has_permission('workshop_labour_product', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_labour_product');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_labour_product($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('wshop_labour_product'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }

    /**
     * labour_product exists
     * @return [type] 
     */
    public function labour_product_exists()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                // First we need to check if labour_product is the same
                $id = $this->input->post('id');
                if ($id != '') {
                    $this->db->where('id', $id);
                    $_current_labour_product = $this->db->get(db_prefix() . 'wshop_labour_products')->row();
                    if (strtoupper($_current_labour_product->name) == strtoupper(($this->input->post('name')))) {
                        echo json_encode(true);
                        die();
                    }
                }
                $this->db->where('name', ($this->input->post('name')));
                $total_rows = $this->db->count_all_results(db_prefix() . 'wshop_labour_products');
                if ($total_rows > 0) {
                    echo json_encode(false);
                } else {
                    echo json_encode(true);
                }
                die();
            }
        }
    }

    /**
     * labour_product detail
     * @param  string $id 
     * @return [type]     
     */
    public function labour_product_detail($id = '')
    {
        if (!has_permission('workshop_labour_product', '', 'view') && !has_permission('workshop_labour_product', '', 'view_own') && !has_permission('workshop_labour_product', '', 'edit') && !is_admin() && !has_permission('workshop_labour_product', '', 'create')) {
            access_denied('workshop_labour_product');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Device Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['labour_product'] = $this->workshop_model->get_labour_product($id);

        $this->load->view('labour_products/labour_product_detail', $data);
    }


    /**
     * material table
     * @return [type] 
     */
    public function material_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'labour_products/materials/material_table'));
    }

    /**
     * load material modal
     * @return [type] 
     */
    public function load_material_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $material_id = $this->input->post('material_id');
        $data['labour_product_id'] = $this->input->post('labour_product_id');
        $data['title'] = _l('wshop_add_material');
        if(is_numeric($material_id) && $material_id != 0){
            $data['material'] = $this->workshop_model->get_material($material_id);
            $data['title'] = _l('wshop_edit_material');
        }
        $this->load->model('invoice_items_model');
        $data['items'] = $this->invoice_items_model->get();
        $this->load->view('labour_products/materials/material_modal', $data);
    }

    /**
     * material
     * @return [type] 
     */
    public function add_edit_material($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_material($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_material'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'material_id' => $id,
                    'url' => admin_url('workshop/materials'),
                ]);die;
            } else {

                $response = $this->workshop_model->add_material($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_material'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'material_id' => $response,
                    'url' => admin_url('workshop/materials'),

                ]);die;
            }
        }

    }

    /**
     * delete material
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_material($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/material'));
        }

        if(!has_permission('workshop_labour_product', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_material');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_material($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('wshop_material'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }

    /**
     * repair_jobs
     * @return [type] 
     */
    public function repair_jobs()
    {
        if (!has_permission('workshop_repair_job', '', 'view') && !has_permission('workshop_repair_job', '', 'view_own')) {
            access_denied('wshop_repair_jobs');
        }

        // Hook antes do carregamento da lista
        hooks()->do_action('before_repair_job_list_load');

        $data['title'] = _l('wshop_repair_jobs');
        $data['clients'] = $this->clients_model->get();
        $data['repair_jobs'] = $this->workshop_model->get_repair_job();
        $data['models'] = $this->workshop_model->get_model(false, true);
        $data['appointment_types'] = $this->workshop_model->get_appointment_type('', true);
        
        // Dados de comissão para os cards (mantido para compatibilidade)
        $data['commission_data'] = $this->workshop_model->get_commission_dashboard_data();
        
        // Dados dos novos cards de repair jobs - Added 2024-12-19
        $data['repair_jobs_dashboard_data'] = $this->workshop_model->get_repair_jobs_dashboard_data();
        
        // Lista de mecânicos para o filtro
        $data['mechanics'] = $this->workshop_model->get_mechanics();
        
        // Filtros personalizados removidos
        
        // Hook após o carregamento dos dados
        hooks()->do_action('repair_job_list_loaded', $data);

        $this->load->view('repair_jobs/manage', $data);
    }

    /**
     * repair_job table
     * @return [type] 
     */
    public function repair_job_table()
    {
        // Hook antes da busca/filtro na tabela
        hooks()->do_action('before_repair_job_search');
        
        $this->app->get_table_data(module_views_path('workshop', 'repair_jobs/repair_job_table'));
        
        // Hook após a busca/filtro na tabela
        hooks()->do_action('after_repair_job_search');
    }
    
    /**
     * Get commission data for AJAX requests
     * @return [type] 
     */
    public function get_commission_data()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        // Hook antes do filtro de dados de comissão
        hooks()->do_action('before_repair_job_filter');
        
        try {
            $period = $this->input->post('period');
            $mechanic_id = $this->input->post('mechanic_id');
            $from_date = $this->input->post('from_date');
            $to_date = $this->input->post('to_date');
            

            
            // Validar mechanic_id se fornecido
            if ($mechanic_id && !is_numeric($mechanic_id)) {
                $mechanic_id = null;
            }
            
            // Validar datas se fornecidas
            if ($from_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
                $from_date = null;
            }
            if ($to_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
                $to_date = null;
            }
            
            // Se temos datas personalizadas ou período selecionado, usar filtro customizado
            if (($from_date && $to_date) || $period) {
                // Se não temos datas mas temos período, calcular as datas
                if (!$from_date || !$to_date) {
                    switch ($period) {
                        case 'today':
                            $from_date = $to_date = date('Y-m-d');
                            break;
                        case 'yesterday':
                            $from_date = $to_date = date('Y-m-d', strtotime('-1 day'));
                            break;
                        case 'this_week':
                            $from_date = date('Y-m-d', strtotime('monday this week'));
                            $to_date = date('Y-m-d', strtotime('sunday this week'));
                            break;
                        case 'last_week':
                            $from_date = date('Y-m-d', strtotime('monday last week'));
                            $to_date = date('Y-m-d', strtotime('sunday last week'));
                            break;
                        case 'this_month':
                            $from_date = date('Y-m-01');
                            $to_date = date('Y-m-t');
                            break;
                        case 'last_month':
                            $from_date = date('Y-m-01', strtotime('first day of last month'));
                            $to_date = date('Y-m-t', strtotime('last day of last month'));
                            break;
                        case 'this_year':
                            $from_date = date('Y-01-01');
                            $to_date = date('Y-12-31');
                            break;
                        case 'last_year':
                            $from_date = date('Y-01-01', strtotime('last year'));
                            $to_date = date('Y-12-31', strtotime('last year'));
                            break;
                        default:
                            $from_date = $to_date = null;
                            break;
                    }
                }
                
                if ($from_date && $to_date) {
                    $data = $this->workshop_model->get_commission_dashboard_data_custom($mechanic_id, $from_date, $to_date);
                } else {
                    $data = $this->workshop_model->get_commission_dashboard_data($mechanic_id);
                }
            } else {
                $data = $this->workshop_model->get_commission_dashboard_data($mechanic_id);
            }
            

            
            // Garantir que os dados estão no formato correto
            if (!is_array($data)) {
                $data = [
                    'today' => ['total_commission' => 0],
                    'week' => ['total_commission' => 0],
                    'month' => ['total_commission' => 0],
                    'total' => ['total_commission' => 0]
                ];
            }
            
            // Hook após o filtro de dados de comissão
            hooks()->do_action('after_repair_job_filter', $data);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar dados de comissão: ' . $e->getMessage(),
                'data' => [
                    'today' => ['total_commission' => 0],
                    'week' => ['total_commission' => 0],
                    'month' => ['total_commission' => 0],
                    'total' => ['total_commission' => 0]
                ]
            ]);
        }
        
        // Hook final após processamento completo
        hooks()->do_action('dashboard_data_loaded');
    }
    
    /**
     * Placeholder para manter a estrutura do arquivo
     */

    /**
     * Sync commission data from repair_jobs to mechanic_commissions table
     * Sincroniza os dados de comissão da tabela repair_jobs para a tabela mechanic_commissions
     * @return void
     */
    /**
     * Sincronização manual de dados de comissão (backup/emergência)
     * Agora a sincronização é automática, mas mantemos esta função para casos especiais
     */
    public function sync_commission_data()
    {
        if (!is_admin()) {
            access_denied('admin');
        }
        
        $this->load->model('mechanic_commission_model');
        
        try {
            $result = $this->mechanic_commission_model->sync_commission_data();
            
            if ($result) {
                $message = 'Sincronização manual concluída! Todas as comissões foram atualizadas. Nota: A sincronização agora é automática a cada criação/edição de repair job.';
                log_activity('Manual Commission Data Sync - All repair job commissions synced to mechanic_commissions table');
                set_alert('success', $message);
            } else {
                $message = 'Nenhum dado de comissão foi encontrado para sincronizar.';
                set_alert('warning', $message);
            }
        } catch (Exception $e) {
            $error_message = 'Erro durante a sincronização: ' . $e->getMessage();
            log_activity('Manual Commission Data Sync Error: ' . $e->getMessage());
            set_alert('danger', $error_message);
        }
        
        redirect(admin_url('workshop/setting?group=reset_data'));
    }
    
    /**
     * Get updated repair jobs dashboard data via AJAX - Updated 2024-12-19
     * Obter dados atualizados dos cards do dashboard de repair jobs via AJAX
     */
    public function get_repair_jobs_dashboard_data()
    {
        if (!has_permission('workshop_repair_job', '', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $dashboard_data = $this->workshop_model->get_repair_jobs_dashboard_data();
        
        // Debug: adicionar log para verificar os dados
        log_activity('Workshop Dashboard Data: ' . json_encode($dashboard_data));
        
        echo json_encode([
            'success' => true,
            'data' => $dashboard_data
        ]);
    }

    /**
     * repair_job
     * @return [type] 
     */
    public function add_edit_repair_job($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['terms'] = $this->input->post('terms', false);

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_repair_job($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_repair_job'));
                    set_alert('success', $message);
                    redirect(admin_url('workshop/repair_jobs'));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                    redirect(admin_url('workshop/add_edit_repair_job/'.$id));
                }
            } else {
                $response = $this->workshop_model->add_repair_job($data);
                if ($response) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_repair_job'));
                    set_alert('success', $message);
                    redirect(admin_url('workshop/repair_jobs'));
                } else {
                    set_alert('danger', _l('something_went_wrong'));
                    redirect(admin_url('workshop/add_edit_repair_job'));
                }
            }
        }
        $data = [];
        $labour_product_row_template = '';
        $part_row_template = '';
        $mechanic_role_id = $this->workshop_model->mechanic_role_exists();
        $this->load->model('currencies_model');
        $this->load->model('iss_retention_model');
        
        // Verificar e criar taxas padrão se não existirem
        if (!$this->iss_retention_model->has_default_rates()) {
            $this->iss_retention_model->create_default_rates();
        }
        
        $data['currencies'] = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['staff']             = $this->staff_model->get('', 'role = '.$mechanic_role_id.' AND active = 1');
        $data['appointment_types']             = $this->workshop_model->get_appointment_type('', true);
        $data['devices'] = [];
        $data['branches'] = $this->workshop_model->get_branch('', true);
        $data['billing_types'] = $this->workshop_model->get_category(false, true, ['use_for' => "billing_type"]);
        $data['delivery_types'] = $this->workshop_model->get_category(false, true, ['use_for' => "delivery_type"]);
        $data['collection_types'] = $this->workshop_model->get_category(false, true, ['use_for' => "collection_type"]);
        $data['iss_retention_rates'] = $this->iss_retention_model->get();
        
        // Adicionar categorias de produtos para o modal de peças
        $data['product_categories'] = $this->workshop_model->get_product_categories();


        if(is_numeric($id) && $id != 0){
            $repair_job = $this->workshop_model->get_repair_job($id);
            if(!$repair_job){
                blank_page('Repair Job Not Found', 'danger');
            }
            $data['devices'] = $this->workshop_model->get_device(false, true, ['client_id' => $repair_job->client_id]);
            $data['repair_job'] = $repair_job;
            $data['generate_job_tracking_number'] = $data['repair_job']->job_tracking_number;

            if(isset($repair_job->repair_job_labour_products)){
                $labour_index = 0;
                foreach ($repair_job->repair_job_labour_products as $key => $labour_product) {
                    $labour_index++;

                    $labour_product_row_template .= $this->workshop_model->create_labour_product_row_template('labouritems[' . $labour_index . ']', $labour_product['labour_product_id'], $labour_product['name'], $labour_product['description'], $labour_product['labour_type'], $labour_product['estimated_hours'], $labour_product['unit_price'], $labour_product['qty'], $labour_product['tax_id'], $labour_product['tax_rate'], $labour_product['tax_name'], $labour_product['discount'], $labour_product['subtotal'], $labour_product['id'], true);
                }
            }

            if(isset($repair_job->repair_job_labour_materials)){
                $part_index = 0;
                foreach ($repair_job->repair_job_labour_materials as $key => $material) {
                    $part_index++;

                    $part_row_template .= $this->workshop_model->create_part_row_template('partitems[' . $part_index . ']', $material['item_id'], $material['name'], $material['description'], $material['rate'], $material['qty'], $material['estimated_qty'], $material['tax_id'], $material['tax_rate'], $material['tax_name'],  $material['discount'], $material['subtotal'], $material['id'], true);
                }
            }

        }else{
            $data['generate_job_tracking_number'] = $this->workshop_model->generate_job_tracking_number();

        }
        $data['labour_product_row_template'] = $labour_product_row_template;
        $data['part_row_template'] = $part_row_template;
        


        $this->load->view('repair_jobs/repair_job', $data);
    }

    /**
     * Criar taxas padrão de retenção ISS
     */
    public function create_default_iss_rates()
    {
        $this->load->model('iss_retention_model');
        
        echo "<h3>Criando Taxas Padrão de Retenção ISS</h3>";
        
        // Verificar se a tabela existe
        $table_exists = $this->db->table_exists(db_prefix() . 'wshop_iss_retention_rates');
        echo "<p>Tabela existe: " . ($table_exists ? 'SIM' : 'NÃO') . "</p>";
        
        if (!$table_exists) {
            echo "<p style='color: red;'>ERRO: Tabela não existe! Execute o instalador do módulo.</p>";
            return;
        }
        
        // Verificar se já existem taxas
        $has_rates = $this->iss_retention_model->has_default_rates();
        echo "<p>Já possui taxas: " . ($has_rates ? 'SIM' : 'NÃO') . "</p>";
        
        if ($has_rates) {
            echo "<p style='color: orange;'>Taxas já existem. Listando taxas atuais:</p>";
            $rates = $this->iss_retention_model->get();
            if (!empty($rates)) {
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Nome</th><th>Percentual</th><th>Status</th></tr>";
                foreach ($rates as $rate) {
                    echo "<tr>";
                    echo "<td>" . $rate['id'] . "</td>";
                    echo "<td>" . $rate['name'] . "</td>";
                    echo "<td>" . $rate['percentage'] . "%</td>";
                    echo "<td>" . $rate['status'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>Criando taxas padrão...</p>";
            $result = $this->iss_retention_model->create_default_rates();
            
            if ($result) {
                echo "<p style='color: green;'>Taxas padrão criadas com sucesso!</p>";
                
                // Listar as taxas criadas
                $rates = $this->iss_retention_model->get();
                if (!empty($rates)) {
                    echo "<table border='1'>";
                    echo "<tr><th>ID</th><th>Nome</th><th>Percentual</th><th>Status</th></tr>";
                    foreach ($rates as $rate) {
                        echo "<tr>";
                        echo "<td>" . $rate['id'] . "</td>";
                        echo "<td>" . $rate['name'] . "</td>";
                        echo "<td>" . $rate['percentage'] . "%</td>";
                        echo "<td>" . $rate['status'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p style='color: red;'>Erro ao criar taxas padrão!</p>";
            }
        }
    }



    /**
     * delete repair_job
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_repair_job($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/repair_job'));
        }

        if(!has_permission('workshop_repair_job', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_repair_job');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_repair_job($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('wshop_repair_job'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }

    /**
     * client change data
     * @param  [type] $customer_id     
     * @param  string $current_invoice 
     * @return [type]                  
     */
    public function client_change_data($customer_id, $device_id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('clients_model');
            $data                     = [];
            $data['billing_shipping'] = $this->clients_model->get_customer_billing_and_shipping_details($customer_id);
            $data['client_currency']  = $this->clients_model->get_customer_default_currency($customer_id);

            $phonenumber = '';
            $contact_email = '';
            $contact_name = '';

            $client = $this->clients_model->get($customer_id);
            if($client){
                $phonenumber = $client->phonenumber;
            }

            $this->db->where('userid', $customer_id);
            $this->db->where('is_primary', 1);
            $contact = $this->db->get(db_prefix() . 'contacts')->row();
            if($contact){
                $contact_email = $contact->email;
                $contact_name = $contact->firstname.' '.$contact->lastname;
            }
            // device html
            $device_html = '';
            $devices = $this->workshop_model->get_device(false, true, ['client_id' => $customer_id]);
            foreach ($devices as $key => $value) {
                $selected='';
                $device_html .= '<option value="'.$value['id'].'" ' .$selected.'>'.$value['name'].'</option>';
            }

            $data['phonenumber'] = $phonenumber;
            $data['contact_email'] = $contact_email;
            $data['contact_name'] = $contact_name;
            $data['device_html'] = $device_html;

            echo json_encode($data);
        }
    }

    /**
     * part table
     * @return [type] 
     */
    public function part_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'repair_jobs/parts/part_table'));
    }

    /**
     * get labour product row template
     * @return [type] 
     */
    public function get_labour_product_row_template()
    {
        $name = $this->input->post('name');
        $labour_product_id = $this->input->post('labour_product_id');
        $product_name = '';
        $description = '';
        $estimated_hours = (float)$this->input->post('estimated_hours');
        $unit_price = 0;
        $qty = 1;
        $tax_id = '';
        $tax_rate = '';
        $tax_name = '';
        $discount = 0;
        $subtotal = 0;
        $item_id = $this->input->post('item_key');
        $part_item_key = $this->input->post('part_item_key');
        $labour_type = 'fixed';

        $labour_product = $this->workshop_model->get_labour_product($labour_product_id);
        if($labour_product){
            $tax_id_temp = [];
            $tax_rate_temp = [];
            $tax_name_temp = [];
            $product_name = $labour_product->name;
            $description = $labour_product->description;
            if(is_numeric($labour_product->tax) && $labour_product->tax != 0){
                $get_tax_name = $this->workshop_model->get_tax_name($labour_product->tax);
                $get_tax_rate = $this->workshop_model->tax_rate_by_id($labour_product->tax);
                if($get_tax_name != ''){
                    $tax_name_temp[] = $get_tax_name;
                    $tax_id_temp[] = $labour_product->tax;
                    $tax_rate_temp[] = $get_tax_rate;
                }
            }

            if(is_numeric($labour_product->tax2) && $labour_product->tax2 != 0){
                $get_tax_name = $this->workshop_model->get_tax_name($labour_product->tax2);
                $get_tax_rate = $this->workshop_model->tax_rate_by_id($labour_product->tax2);
                if($get_tax_name != ''){
                    $tax_name_temp[] = $get_tax_name;
                    $tax_id_temp[] = $labour_product->tax2;
                    $tax_rate_temp[] = $get_tax_rate;
                }
            }
            $tax_id = implode('|', $tax_id_temp);
            $tax_rate = implode('|', $tax_rate_temp);
            $tax_name = implode('|', $tax_name_temp);
            $labour_type = $labour_product->labour_type;
            $unit_price = $labour_product->labour_cost;
            if($labour_type == 'fixed'){
                $subtotal = (float)$labour_product->labour_cost;
            }else{
                $subtotal = (float)$labour_product->labour_cost * (float)$estimated_hours;
            }
        }

        $labour_product_row_template = $this->workshop_model->create_labour_product_row_template($name, $labour_product_id, $product_name, $description, $labour_type, $estimated_hours, $unit_price, $qty, $tax_id, $tax_rate, $tax_name, $discount, $subtotal, $item_id, false );


        // get part relation
        $part_row_template = '';
        if(isset($labour_product->parts)){
            $part_name = str_replace('newlabouritems', 'newpartitems', $name);
            foreach ($labour_product->parts as $key => $part) {
                $part_name = 'newpartitems['.$part_item_key.']';

                $part_row_template .= $this->workshop_model->get_part_row_template($part_name, $part['item_id'], $part['quantity'], $key+1);
                $part_item_key++;

            }
        }
        echo json_encode([
            'labour_product_row_template'  => $labour_product_row_template,
            'part_row_template'  => $part_row_template,
            'part_item_key'  => $part_item_key,
        ]);die;

    }

    /**
     * get part row template
     * @return [type]           
     */
    public function get_part_row_template()
    {
        $name = $this->input->post('name');
        $item_id = $this->input->post('part_id');
        $quantity = (float)$this->input->post('quantity');
        $item_key = $this->input->post('item_key');

        echo $this->workshop_model->get_part_row_template($name, $item_id, $quantity, $item_key);
    }

    /**
     * TODO
     * calculated estimated completion date
     * @param  [type] $estimated_hours 
     * @return [type]                  
     */
    public function calculated_estimated_completion_date($estimated_hours)
    {
        if ($this->input->is_ajax_request()) {
            $data = [];

            echo json_encode($data);
        }
    }

    /**
     * repair job status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function repair_job_status_mark_as($status, $id, $type = '')
    {
        $success = $this->workshop_model->repair_job_status_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('wshop_change_repair_job_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * repair job detail
     * @param  string $id 
     * @return [type]     
     */
    public function repair_job_detail($id = '')
    {
        if (!has_permission('workshop_repair_job', '', 'view') && !has_permission('workshop_repair_job', '', 'view_own') && !has_permission('workshop_repair_job', '', 'edit') && !is_admin() && !has_permission('workshop_repair_job', '', 'create')) {
            access_denied('workshop_repair_job');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Repair Job Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;

        $data['repair_job'] = $this->workshop_model->get_repair_job($id);
        if(!file_exists(REPAIR_JOB_BARCODE. md5($data['repair_job']->job_tracking_number).'.svg')){
            $this->workshop_model->getBarcode($data['repair_job']->job_tracking_number);
        }

        _maybe_create_upload_path(REPAIR_JOB_QR_UPLOAD_PATH . $id . '/');
        $this->workshop_model->generate_movement_qrcode(site_url('workshop/client/repair_job_detail/0/'.$data['repair_job']->hash.'?tab=detail'), REPAIR_JOB_QR_UPLOAD_PATH.$id.'/');

        $data['device'] = $this->workshop_model->get_device($data['repair_job']->device_id);
        $data['tax_labour_data'] = $this->workshop_model->get_html_tax_labour_repair_job($id, $data['repair_job']->currency);
        $data['tax_part_data'] = $this->workshop_model->get_html_tax_part_repair_job($id, $data['repair_job']->currency);
        $mechanic_role_id = $this->workshop_model->mechanic_role_exists();
        $data['staffs']             = $this->staff_model->get('', ['role' => $mechanic_role_id,'staffid !=' => $data['repair_job']->sale_agent]);
        $data['returns'] = $this->workshop_model->get_transaction(false, '', ['repair_job_id' => $id, 'transaction_type' => 'return']);
        if(count($data['returns']) > 0){
            $data['return_attachments'] = $this->workshop_model->get_attachment_file($data['returns'][0]['id'], 'wshop_transaction');
            $data['return_notes'] = $this->workshop_model->get_note(false, ['return_delivery_id' => $data['returns'][0]['id'], 'transaction_type' => 'return']);
        }

        $data['deliveries'] = $this->workshop_model->get_transaction(false, '', ['repair_job_id' => $id, 'transaction_type' => 'delivery']);
        if(count($data['deliveries']) > 0){
            $data['delivery_attachments'] = $this->workshop_model->get_attachment_file($data['deliveries'][0]['id'], 'wshop_transaction');
            $data['delivery_notes'] = $this->workshop_model->get_note(false, ['return_delivery_id' => $data['deliveries'][0]['id'], 'transaction_type' => 'delivery']);
        }
        $data['workshops'] = $this->workshop_model->get_workshop(false, ['repair_job_id' => $id]);
        $data['_inspection'] = $this->workshop_model->get_inspection(false, ['repair_job_id' => $id]);

        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'repair_job');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }

        $this->load->view('repair_jobs/repair_job_detail', $data);
    }

    /**
     * reassign mechanic
     * @param  [type] $repair_job_id 
     * @param  [type] $mechanic_id   
     * @return [type]                
     */
    public function reassign_mechanic($repair_job_id, $mechanic_id)
    {
        $success = false;
        $message = '';

        $this->db->where('id', $repair_job_id);
        $this->db->update(db_prefix() . 'wshop_repair_jobs', ['sale_agent' => $mechanic_id]);
        if ($this->db->affected_rows() > 0) {
            $success = true;
            $this->workshop_model->log_workshop_activity($repair_job_id, 'wshop_reassign_mechanic_activity', false, '', 'repair_job');
        }

        if ($success) {
            $message = _l('wshop_reassign_mechanic_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * repair job calendar
     * @return [type] 
     */
    public function repair_job_calendar()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $data = $this->input->post();

            $create_timeline = false;
            if(isset($data['create_timeline'])){
                $create_timeline = true;
                unset($data['create_timeline']);
            }else{
                $create_timeline = false;
            }

            if($create_timeline == true){
                $this->load->model('projects_model');
                if(isset($data['id']) && is_numeric($data['id']) && $data['id'] != 0){
                    $success = $this->projects_model->update_timeline($data, $data['id']);
                }else{
                    $success = $this->projects_model->add_timeline($data);
                }
                if(is_numeric($success)){
                   $success = true; 
                }

            }else{
                $success = $this->utilities_model->event($data);
            }

            $message = '';
            if ($create_timeline == true) {
                $message = _l('utility_calendar_event_added_successfully');
            }else{
                if (isset($data['eventid'])) {
                    $message = _l('event_updated');
                } else {
                    $message = _l('utility_calendar_event_added_successfully');
                }
            }
            
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            die();
        }
        $data['google_ids_calendars'] = $this->misc_model->get_google_calendar_ids();
        $data['google_calendar_api']  = get_option('google_calendar_api_key');
        $data['title']                = _l('calendar');
        add_calendar_assets();

        $this->load->view('repair_jobs/calendar', $data);
    }

    /**
     * get repair job calendar data
     * @return [type] 
     */
    public function get_repair_job_calendar_data()
    {
        echo json_encode($this->workshop_model->get_repair_job_calendar_data(
                date('Y-m-d', strtotime($this->input->get('start'))),
                date('Y-m-d', strtotime($this->input->get('end'))),
                '',
                '',
                $this->input->get()
            ));
        die();
    }

    /**
     * repair job print lable pdf
     * @param  [type] $id 
     * @return [type]     
     */
    public function repair_job_print_lable_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/repair_jobs'));
        }
        $this->load->model('clients_model');
        $this->load->model('currencies_model');

        $repair_job_number = '';
        $repair_job = $this->workshop_model->get_repair_job($id);

        $base_currency = $this->currencies_model->get_base_currency();
        $currency = $base_currency;
        if(is_numeric($repair_job->currency) && $repair_job->currency != 0){
            $currency = $repair_job->currency;
        }

        $repair_job->client = $this->clients_model->get($repair_job->client_id);
        $repair_job->currency = $currency;

        if($repair_job){
            $repair_job_number .= $repair_job->job_tracking_number;
        }
        try {
            $pdf = $this->workshop_model->repair_job_label_pdf($repair_job);

        } catch (Exception $e) {
            echo new_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';
        ob_end_clean();

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($repair_job_number)).'.pdf', $type);
    }

    /**
     * returns
     * @return [type] 
     */
    public function returns()
    {
        if (!has_permission('workshop_inspection', '', 'view') && !has_permission('workshop_inspection', '', 'view_own')) {
            access_denied('workshop_inspection');
        }

        $data['title'] = _l('wshop_returns');
        $data['clients'] = $this->clients_model->get();
        $data['devices'] = $this->workshop_model->get_device();

        $this->load->view('returns/manage', $data);
    }

    /**
     * return table
     * @return [type] 
     */
    public function return_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'returns/table'), ['transaction_type' => 'return']);
    }

    /**
     * delete transaction
     * @param  [type] $id 
     * @transaction [type]     
     */
    public function delete_transaction($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/return'));
        }

        if(!has_permission('workshop_repair_job', '', 'delete')  &&  !is_admin()) {
            access_denied('workshop_repair_job');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_transaction($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('workshop_repair_job'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load transaction modal
     * @return [type] 
     */
    public function load_transaction_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $repair_job_id = $this->input->post('repair_job_id');
        $transaction_id = $this->input->post('transaction_id');
        $transaction_type = $this->input->post('transaction_type');
        $data['transaction_type'] = $transaction_type;
        if($transaction_type == 'return'){
            $data['title'] = _l('wshop_add_return');
        }else{
            $data['title'] = _l('wshop_add_delivery');
        }

        if(is_numeric($transaction_id) && $transaction_id != 0){
            $data['transaction'] = $this->workshop_model->get_transaction($transaction_id);
            $data['title'] = _l('wshop_edit_'.$transaction_type);
            $data['transaction_attachments'] = $this->workshop_model->get_attachment_file($transaction_id, 'wshop_transaction');
        }

        $this->load->model('clients_model');
        $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Delivery_Type']);
        $data['repair_job'] = $this->workshop_model->get_repair_job($repair_job_id);
        $client_id = $data['repair_job']->client_id;
        $data['clients'] = $this->clients_model->get();
        $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, ['id' => $repair_job_id]);
        $data['repair_job_id'] = $repair_job_id;
        $data['client_id'] = $client_id;

        $this->load->view('returns/add_modal', $data);
    }

    /**
     * transaction
     * @return [type] 
     */
    public function add_edit_transaction($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_transaction($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_'.$data['transaction_type']));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'transaction_id' => $id,
                    'url' => admin_url('workshop/devices'),
                ]);die;
            } else {
                $response = $this->workshop_model->add_transaction($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_'.$data['transaction_type']));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'transaction_id' => $response,
                    'url' => admin_url('workshop/devices'),

                ]);die;
            }
        }
        
    }

    /**
     * add transaction attachment
     * @param [type] $id 
     */
    public function add_transaction_attachment($id)
    {
        wshop_handle_transaction_attachments($id);
        $url = admin_url('workshop/repair_jobs');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * delete transaction attachment
     * @param  [type]  $attachment_id 
     * @param  boolean $folder_name   
     * @return [type]                 
     */
    public function delete_workshop_attachment($attachment_id, $folder_name = false)
    {
        if (!has_permission('workshop_repair_job', '', 'delete') && !is_admin()) {
            access_denied('workshop_repair_job');
        }
        $_folder_name = TRANSACTION_FOLDER;

        if($folder_name == 'NOTE_FOLDER'){
            $_folder_name = NOTE_FOLDER;
        }elseif($folder_name == 'WORKSHOP_FOLDER'){
            $_folder_name = WORKSHOP_FOLDER;
        }elseif($folder_name == 'INSPECTION_FOLDER'){
            $_folder_name = INSPECTION_FOLDER;
        }

        echo json_encode([
            'success' => $this->workshop_model->delete_workshop_file($attachment_id, $_folder_name),
        ]);
    }

    /**
     * workshop pdf file
     * @param  [type] $id     
     * @param  [type] $rel_id 
     * @return [type]         
     */
    public function transaction_pdf_file($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin'] = is_admin();
        $data['file'] = $this->misc_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }
        $this->load->view('returns/preview_pdf_file', $data);
    }

    /**
     * load note modal
     * @return [type] 
     */
    public function load_note_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $repair_job_id = $this->input->post('repair_job_id');
        $return_delivery_id = $this->input->post('return_delivery_id');
        $note_id = $this->input->post('note_id');
        $transaction_type = $this->input->post('transaction_type');
        $data['transaction_type'] = $transaction_type;
        $data['title'] = _l('wshop_add_note');

        if(is_numeric($note_id) && $note_id != 0){
            $data['note'] = $this->workshop_model->get_note($note_id);
            $data['title'] = _l('wshop_edit_note');
            $data['note_attachments'] = $this->workshop_model->get_attachment_file($note_id, 'wshop_note');
        }

        $this->load->model('clients_model');
        $data['categories'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Delivery_Type']);
        $data['repair_job'] = $this->workshop_model->get_repair_job($repair_job_id);
        $client_id = $data['repair_job']->client_id;
        $data['clients'] = $this->clients_model->get();
        $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, ['id' => $repair_job_id]);
        $data['repair_job_id'] = $repair_job_id;
        $data['return_delivery_id'] = $return_delivery_id;
        $data['client_id'] = $client_id;

        $this->load->view('returns/notes/add_note_modal', $data);
    }

    /**
     * note
     * @return [type] 
     */
    public function add_edit_note($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_note($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_note'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'note_id' => $id,
                    'url' => admin_url('workshop/devices'),
                ]);die;
            } else {
                $response = $this->workshop_model->add_note($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_note'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'note_id' => $response,
                    'url' => admin_url('workshop/devices'),

                ]);die;
            }
        }
        
    }

    /**
     * add note attachment
     * @param [type] $id 
     */
    public function add_note_attachment($id)
    {
        wshop_handle_note_attachments($id);
        $url = admin_url('workshop/repair_jobs');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * note pdf file
     * @param  [type] $id     
     * @param  [type] $rel_id 
     * @return [type]         
     */
    public function preview_file($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin'] = is_admin();
        $data['file'] = $this->misc_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        $upload_path = TRANSACTION_FOLDER;
        $upload_folder = 'return_deliveries';

        if($data['file']->rel_type == 'wshop_note'){
            $upload_path = NOTE_FOLDER;
            $upload_folder = 'notes';
        }elseif($data['file']->rel_type == 'wshop_workshop'){
            $upload_path = WORKSHOP_FOLDER;
            $upload_folder = 'workshops';
        }elseif($data['file']->rel_type == 'wshop_inspection'){
            $upload_path = INSPECTION_FOLDER;
            $upload_folder = 'inspections';
        }elseif($data['file']->rel_type == 'wshop_inspection_qs'){
            $upload_path = INSPECTION_QUESTION_FOLDER;
            $upload_folder = 'inspection_questions';
        }
        

        $data['upload_path'] = $upload_path;
        $data['upload_folder'] = $upload_folder;

        $this->load->view('returns/preview_pdf_file', $data);
    }

    /**
     * delete note
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_note($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/repair_job'));
        }

        if(!has_permission('workshop_repair_job', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_repair_job');
        }

        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_note($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('wshop_note'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);

    }


    /**
     * workshops
     * @workshop [type] 
     */
    public function workshops()
    {
        if (!has_permission('workshop_workshop', '', 'view') && !has_permission('workshop_workshop', '', 'view_own')) {
            access_denied('wshop_workshop');
        }

        $data['title'] = _l('wshop_workshops');
        $data['report_types'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Report_type']);
        $data['report_statuses'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Report_status']);
        $data['repair_jobs'] = $this->workshop_model->get_repair_job();

        $this->load->view('workshops/manage', $data);
    }

    /**
     * workshop table
     * @workshop [type] 
     */
    public function workshop_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'workshops/table'));
    }

    /**
     * delete workshop
     * @param  [type] $id 
     * @workshop [type]     
     */
    public function delete_workshop($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/workshops'));
        }

        if(!has_permission('workshop_workshop', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_workshop');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_workshop($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('workshop_repair_job'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load workshop modal
     * @return [type] 
     */
    public function load_workshop_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $repair_job_id = $this->input->post('repair_job_id');
        $workshop_id = $this->input->post('workshop_id');
        $data['title'] = _l('wshop_add_workshop');
        if($repair_job_id) {
            $data['_repair_job_id'] = $repair_job_id;
        }

        if(is_numeric($workshop_id) && $workshop_id != 0){
            $data['workshop'] = $this->workshop_model->get_workshop($workshop_id);
            $data['title'] = _l('wshop_edit_workshop');
            $data['workshop_attachments'] = $this->workshop_model->get_attachment_file($workshop_id, 'wshop_workshop');
        }

        $this->load->model('clients_model');
        $data['report_types'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Report_type']);
        $data['report_statuses'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Report_status']);
        $data['repair_jobs'] = $this->workshop_model->get_repair_job();
        $mechanic_role_id = $this->workshop_model->mechanic_role_exists();
        $data['staffs']             = $this->staff_model->get('', 'role = '.$mechanic_role_id.' AND active = 1');

        $this->load->view('workshops/add_modal', $data);
    }

    /**
     * workshop
     * @return [type] 
     */
    public function add_edit_workshop($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_workshop($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_workshop_name'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'workshop_id' => $id,
                    'url' => admin_url('workshop/workshops'),
                ]);die;
            } else {
                $response = $this->workshop_model->add_workshop($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_workshop_name'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'workshop_id' => $response,
                    'url' => admin_url('workshop/workshops'),

                ]);die;
            }
        }
        
    }

    /**
     * add workshop attachment
     * @param [type] $id 
     */
    public function add_workshop_attachment($id)
    {
        wshop_handle_workshop_attachments($id);
        $url = admin_url('workshop/workshops');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * change category status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_workshop_status($id, $status) {
        if (has_permission('workshop_workshop', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_workshop_status($id, (int)$status);
            }
        }
    }

    /**
     * inspections
     * @inspection [type] 
     */
    public function inspections()
    {
        if (!has_permission('workshop_inspection', '', 'view') && !has_permission('workshop_inspection', '', 'view_own')) {
            access_denied('wshop_inspection');
        }

        $data['title'] = _l('wshop_inspections');
        $data['repair_jobs'] = $this->workshop_model->get_repair_job();
        $data['inspection_types'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Inspection']);
        $data['clients'] = $this->clients_model->get();
        $data['statuses'] = inspection_status();
        $data['devices'] = $this->workshop_model->get_device();

        $this->load->view('inspections/manage', $data);
    }

    /**
     * inspection table
     * @inspection [type] 
     */
    public function inspection_table()
    {
        $this->app->get_table_data(module_views_path('workshop', 'inspections/table'));
    }

    /**
     * delete inspection
     * @param  [type] $id 
     * @inspection [type]     
     */
    public function delete_inspection($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/inspections'));
        }

        if(!has_permission('workshop_inspection', '', 'delete')  &&  !is_admin()) {
            access_denied('wshop_workshop');
        }
        $success = false;
        $message = '';

        $response = $this->workshop_model->delete_inspection($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('workshop_inspection'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load inspection modal
     * @return [type] 
     */
    public function load_inspection_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $repair_job_id = $this->input->post('repair_job_id');
        $inspection_id = $this->input->post('inspection_id');
        $data['title'] = _l('wshop_add_inspection');
        $data['devices'] = [];
        $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, 'id NOT IN (SELECT repair_job_id FROM '.db_prefix().'wshop_inspections WHERE repair_job_id > 0)');

        if($repair_job_id) {
            $data['_repair_job_id'] = $repair_job_id;
            $repair_job = $this->workshop_model->get_repair_job($repair_job_id);
            if($repair_job){
                $data['customer_id'] = $repair_job->client_id;
                $data['_device_id'] = $repair_job->device_id;
            }
        }

        if(is_numeric($inspection_id) && $inspection_id != 0){
            $data['inspection'] = $this->workshop_model->get_inspection($inspection_id);
            $data['title'] = _l('wshop_edit_inspection');
            $data['inspection_attachments'] = $this->workshop_model->get_attachment_file($inspection_id, 'wshop_inspection');
            $data['devices'] = $this->workshop_model->get_device(false, true, ['client_id' => $data['inspection']->client_id]);
            if($data['inspection']->repair_job_id != '' && $data['inspection']->repair_job_id > 0){
                $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, 'id = '.$data['inspection']->repair_job_id);
            }else{
                $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, 'id NOT IN (SELECT repair_job_id FROM '.db_prefix().'wshop_inspections WHERE repair_job_id > 0)');
            }
        }

        $this->load->model('clients_model');
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['inspection_types'] = $this->workshop_model->get_category(false, true, ['use_for' => 'Inspection']);
        $mechanic_role_id = $this->workshop_model->mechanic_role_exists();
        $data['staffs']             = $this->staff_model->get('', 'role = '.$mechanic_role_id.' AND active = 1');
        $data['inspection_templates'] = $this->workshop_model->get_inspection_template(false, true);
        $data['intervals'] = $this->workshop_model->get_interval(false, true);

        $this->load->view('inspections/add_modal', $data);
    }

    /**
     * inspection
     * @return [type] 
     */
    public function add_edit_inspection($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            // Sanitizar a descrição removendo tags HTML
            $data['description'] = strip_tags($this->input->post('description', false));

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->workshop_model->update_inspection($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('wshop_inspection'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'inspection_id' => $id,
                    'url' => admin_url('workshop/inspections'),
                ]);die;
            } else {
                $response = $this->workshop_model->add_inspection($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('wshop_inspection'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'inspection_id' => $response,
                    'url' => admin_url('workshop/inspections'),

                ]);die;
            }
        }
        
    }

    /**
     * add inspection attachment
     * @param [type] $id 
     */
    public function add_inspection_attachment($id)
    {
        wshop_handle_inspection_attachments($id);
        $url = admin_url('workshop/inspections');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * change category status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_inspection_visible($id, $status) {
        if (has_permission('workshop_inspection', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->workshop_model->change_inspection_visible($id, (int)$status);
            }
        }
    }

    /**
     * calculate next inspection date
     * @return [type] 
     */
    public function calculate_next_inspection_date()
    {
        if ($this->input->is_ajax_request()) {
            $data = $this->input->post();
            $start_date = to_sql_date($data['start_date'], true);
            $interval_id = $data['interval_id'];
            $next_inspection_date = '';

            if($data['interval_id'] != '' && $data['interval_id'] != 0){
                $interval = $this->workshop_model->get_interval($data['interval_id']);
                if($interval){
                    switch ($interval->type) {
                        case 'day':
                        $temp_day = $interval->value;
                        $next_inspection_date = date('Y-m-d', strtotime('+'.(int)$temp_day.' days', strtotime($start_date)));
                            break;
                        case 'month':
                            $temp_day = $interval->value;
                        $next_inspection_date = date('Y-m-d', strtotime('+'.(int)$temp_day.' months', strtotime($start_date)));
                            break;
                        case 'year':
                            
                            break;
                        $temp_day = $interval->value;
                        $next_inspection_date = date('Y-m-d', strtotime('+'.(int)$temp_day.' years', strtotime($start_date)));
                        default:
                            // code...
                            break;
                    }
                }
                $next_inspection_date = _d($next_inspection_date);
            }

            echo json_encode([
                'success' => true,
                'next_inspection_date' => $next_inspection_date,
            ]);die;

        }
    }

    /**
     * get repair job infor
     * @return [type] 
     */
    public function get_repair_job_infor($repair_job_id = 0){
        if ($this->input->is_ajax_request()) {
            $status = true;
            $device_id = 0;
            $client_id = 0;
            $client_html = '';
            if(is_numeric($repair_job_id) && $repair_job_id != 0){
                $repair_job = $this->workshop_model->get_repair_job($repair_job_id);
                if($repair_job){
                    $device_id = $repair_job->device_id;

                    $client_html = '';
                    $client_html .= '<option value=""></option>';
                    $selected=' selected';
                    $client_html .= '<option value="'.$repair_job->client_id.'" ' .$selected.'>'.get_company_name($repair_job->client_id).'</option>';

                    $client_id = $repair_job->client_id; 
                }
            }
            echo json_encode([
                'success' => $status,
                'device_id' => $device_id,
                'client_id' => $client_id,
                'client_html' => $client_html,
            ]);
        }
    }

    /**
     * inspection status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function inspection_status_mark_as($status, $id, $type = '')
    {
        $success = $this->workshop_model->inspection_status_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('wshop_change_inspection_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * inspection detail
     * @param  string $id 
     * @return [type]     
     */
    public function inspection_detail($id = '')
    {
        if (!has_permission('workshop_inspection', '', 'view') && !has_permission('workshop_inspection', '', 'view_own') && !has_permission('workshop_inspection', '', 'edit') && !is_admin() && !has_permission('workshop_inspection', '', 'create')) {
            access_denied('workshop_inspection');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Inspection Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['inspection'] = $this->workshop_model->get_inspection($id);
        $data['inspection_attachments'] = $this->workshop_model->get_attachment_file($id, 'wshop_inspection');
        $allow_create_invoice = false;
        if(isset($data['inspection']->inspection_labour_products) || isset($data['inspection']->inspection_labour_materials)){
            $allow_create_invoice = true;
        }
        $data['allow_create_invoice'] = $allow_create_invoice;
        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'inspection');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }

        $this->load->view('inspections/inspection_detail', $data);
    }

    /**
     * inspection form
     * @param  string $inspection_id 
     * @return [type]                
     */
    public function inspection_form($inspection_id = '')
    {
        if (!has_permission('workshop_inspection', '', 'edit')) {
            access_denied('workshop_inspection_form');
        }
        $insert_inpsection_template = $this->workshop_model->insert_inpsection_template($inspection_id);

        $data = [];

        $data['inspection'] = $this->workshop_model->get_inspection($inspection_id);
        $data['inspection_forms'] = $this->workshop_model->get_inspection_form(false, false,'inspection_id = '. $inspection_id);

        $this->load->view('inspections/inspection_template_forms/manage', $data);
    }

    /**
     * get inspection form details
     * @param  [type] $inspection_form_id 
     * @return [type]                     
     */
    public function get_inspection_form_details($inspection_form_id, $inspection_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $inspection_form_details = '';
        $data['inspection_form_details'] = $this->workshop_model->get_inspection_form_detail(false, false,'inspection_form_id = '. $inspection_form_id);

        $inspection_form_details .= wshop_render_inspection_form_fields('form_fieldset_'.$inspection_form_id, $inspection_id, [], ['items_pr' => true]);

        echo json_encode([
            'status' => true,
            'inspection_form_details' => $inspection_form_details,
        ]);
    }

    /**
     * add edit inspection form
     * @param string $inspection_id 
     */
    public function add_edit_inspection_form($inspection_id='')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $status = false;
        $message = '';

        $data = $this->input->post();
        $result = $this->workshop_model->add_edit_inspection_form($data, $inspection_id);
        if($result){
            $status = true;
            $message = _l('updated_successfully');
        }

        echo json_encode([
            'status' => $status,
            'message' => $message,
        ]);
    }

    // TOTO
    /**
     * get labour product row template
     * @return [type] 
     */
    public function inspection_get_labour_product_row_template()
    {
        $name = $this->input->post('name');
        $labour_product_id = $this->input->post('labour_product_id');
        $inspection_id = $this->input->post('inspection_id');
        $inspection_form_id = $this->input->post('inspection_form_id');
        $inspection_form_detail_id = $this->input->post('inspection_form_detail_id');

        $product_name = '';
        $description = '';
        $estimated_hours = (float)$this->input->post('estimated_hours');
        $unit_price = 0;
        $qty = 1;
        $tax_id = '';
        $tax_rate = '';
        $tax_name = '';
        $discount = 0;
        $subtotal = 0;
        $item_id = $this->input->post('item_key');
        $part_item_key = $this->input->post('part_item_key');

        $labour_type = 'fixed';

        $labour_product = $this->workshop_model->get_labour_product($labour_product_id);
        if($labour_product){
            $tax_id_temp = [];
            $tax_rate_temp = [];
            $tax_name_temp = [];
            $product_name = $labour_product->name;
            $description = $labour_product->description;
            if(is_numeric($labour_product->tax) && $labour_product->tax != 0){
                $get_tax_name = $this->workshop_model->get_tax_name($labour_product->tax);
                $get_tax_rate = $this->workshop_model->tax_rate_by_id($labour_product->tax);
                if($get_tax_name != ''){
                    $tax_name_temp[] = $get_tax_name;
                    $tax_id_temp[] = $labour_product->tax;
                    $tax_rate_temp[] = $get_tax_rate;
                }
            }

            if(is_numeric($labour_product->tax2) && $labour_product->tax2 != 0){
                $get_tax_name = $this->workshop_model->get_tax_name($labour_product->tax2);
                $get_tax_rate = $this->workshop_model->tax_rate_by_id($labour_product->tax2);
                if($get_tax_name != ''){
                    $tax_name_temp[] = $get_tax_name;
                    $tax_id_temp[] = $labour_product->tax2;
                    $tax_rate_temp[] = $get_tax_rate;
                }
            }
            $tax_id = implode('|', $tax_id_temp);
            $tax_rate = implode('|', $tax_rate_temp);
            $tax_name = implode('|', $tax_name_temp);
            $labour_type = $labour_product->labour_type;
            $unit_price = $labour_product->labour_cost;
            if($labour_type == 'fixed'){
                $subtotal = (float)$labour_product->labour_cost;
            }else{
                $subtotal = (float)$labour_product->labour_cost * (float)$estimated_hours;
            }
        }

        $labour_product_row_template = $this->workshop_model->inspection_create_labour_product_row_template($name, $labour_product_id, $product_name, $description, $inspection_id, $inspection_form_id, $inspection_form_detail_id, $labour_type, $estimated_hours, $unit_price, $qty, $tax_id, $tax_rate, $tax_name, $discount, $subtotal, $item_id, false );

        // get part relation
        $part_row_template = '';
        if(isset($labour_product->parts)){
            $part_name = str_replace('newlabouritems', 'newpartitems', $name);

            foreach ($labour_product->parts as $key => $part) {
                $part_name = 'newpartitems['.$part_item_key.']';
                $part_row_template .= $this->workshop_model->inspection_get_part_row_template($part_name, $part['item_id'], $inspection_id, $inspection_form_id, $inspection_form_detail_id, $part['quantity'], $key+1);
                $part_item_key++;
            }
        }

        echo json_encode([
            'labour_product_row_template'  => $labour_product_row_template,
            'part_row_template'  => $part_row_template,
            'part_item_key'  => $part_item_key,
        ]);die;

    }

    /**
     * get part row template
     * @return [type]           
     */
    public function inspection_get_part_row_template()
    {
        $name = $this->input->post('name');
        $item_id = $this->input->post('part_id');
        $inspection_id = $this->input->post('inspection_id');
        $inspection_form_id = $this->input->post('inspection_form_id');
        $inspection_form_detail_id = $this->input->post('inspection_form_detail_id');
        $quantity = (float)$this->input->post('quantity');
        $item_key = $this->input->post('item_key');

        echo $this->workshop_model->inspection_get_part_row_template($name, $item_id, $inspection_id, $inspection_form_id, $inspection_form_detail_id, $quantity, $item_key);
    }

    /**
     * inspection form detail
     * @param  [type] $id 
     * @return [type]     
     */
    public function inspection_form_detail($id)
    {
        if ( !has_permission('workshop_inspection', '', 'edit') ) {
            access_denied('workshop_inspection');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Inspection Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['inspection'] = $this->workshop_model->get_inspection($id);
        $data['inspection_forms'] = $this->workshop_model->get_inspection_form(false, false,'inspection_id = '. $id);
        $data['check_change_inspection_status'] = check_change_inspection_status($id);
        $allow_create_invoice = false;
        if(isset($data['inspection']->inspection_labour_products) || isset($data['inspection']->inspection_labour_materials)){
            $allow_create_invoice = true;
        }
        $data['allow_create_invoice'] = $allow_create_invoice;
        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'inspection');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }

        $this->load->view('inspections/inspection_template_forms/inspection_form_detail', $data);
    }

    /**
     * inspection approval form
     * @return [type] 
     */
    public function inspection_approval_form()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $status = false;
        $message = '';
        $response_text = '';
        $update_inspection_status = false;
        $data = $this->input->post();

        if($data['inspection_form_detail_id'] == 0){
            $this->db->where('relid', $data['inspection_id']);
            $update_inspection_status = true;

        }else{
            $this->db->where('relid', $data['inspection_id']);
            $this->db->where('inspection_form_detail_id', $data['inspection_form_detail_id']);
        }
        if($data['approve'] == 'rejected'){
            $response_text = '<span class="text-danger tw-font-semibold">'.$data['approve'].' on '._dt(date('Y-m-d H:i:s')).'</span>';
        }else{
            $response_text = '<span class="text-success tw-font-semibold">'.$data['approve'].' on '._dt(date('Y-m-d H:i:s')).'</span>';
        }
        $this->db->update(db_prefix() . 'wshop_inspection_values', [
            'approve' => $data['approve'],
            'approve_comment' => $data['approve_comment'],
            'approved_date' => date('Y-m-d H:i:s'),
        ]);
        if($this->db->affected_rows() > 0){
            $status = true;
            $message = _l('updated_successfully');

            $this->workshop_model->re_caculate_inspection($data['inspection_id']);
        }

        // check change inspection_status
         $this->db->where('relid', $data['inspection_id']);
         $this->db->where('inspection_result', 'repair');
         $this->db->where('approve is NULL');
         $inspection_value = $this->db->get(db_prefix() . 'wshop_inspection_values')->result_array();
         if(count($inspection_value) == 0){
            $update_inspection_status = true;
         }

        if($update_inspection_status){
            $success = $this->workshop_model->inspection_status_mark_as('Complete_Awaiting_Finalise', $data['inspection_id']);
        }

        echo json_encode([
            'status' => $status,
            'message' => $message,
            'update_inspection_status' => $update_inspection_status,
            'response_text' => $response_text,
        ]);
    }

    /* Convert estimate to invoice preview */
    public function convert_to_invoice_preview($id, $type)
    {
        if (!has_permission('workshop_repair_job', '', 'create') && !has_permission('workshop_inspection', '', 'create') && !has_permission('workshop_repair_job', '', 'edit') && !has_permission('workshop_inspection', '', 'edit') ) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No '.$type.' found');
        }

        // Buscar dados do repair job ou inspection
        if($type == 'repair_job') {
            $data['repair_job'] = $this->workshop_model->get_repair_job($id);
            if(!$data['repair_job']) {
                show_404();
            }
            $data['client'] = $this->clients_model->get($data['repair_job']->client_id);
            $data['items'] = $this->workshop_model->get_repair_job_items($id);
        } else {
            $data['inspection'] = $this->workshop_model->get_inspection($id);
            if(!$data['inspection']) {
                show_404();
            }
            $data['client'] = $this->clients_model->get($data['inspection']->client_id);
            $data['items'] = $this->workshop_model->get_inspection_items($id);
        }
        
        // Verificar se o cliente foi carregado corretamente
        if(!$data['client']) {
            $client_id = ($type == 'repair_job') ? $data['repair_job']->client_id : $data['inspection']->client_id;
            log_message('error', 'Cliente não encontrado para ID: ' . $client_id . ' no tipo: ' . $type . ' com ID: ' . $id);
            set_alert('danger', 'Cliente não encontrado. Verifique se o cliente ainda existe no sistema.');
            if($type == 'repair_job') {
                redirect(admin_url('workshop/repair_job_detail/'.$id));
            } else {
                redirect(admin_url('workshop/inspection_detail/'.$id));
            }
        }
        
        $data['type'] = $type;
        $data['id'] = $id;
        $data['title'] = _l('wshop_convert_to_invoice_preview');
        
        $this->load->view('admin/workshop/convert_to_invoice_preview', $data);
    }

    /* Convert estimate to invoice */
    public function convert_to_invoice($id, $type)
    {
        if (!has_permission('workshop_repair_job', '', 'create') && !has_permission('workshop_inspection', '', 'create') && !has_permission('workshop_repair_job', '', 'edit') && !has_permission('workshop_inspection', '', 'edit') ) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No '.$type.' found');
        }

        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        
        // Capturar dados do formulário de preview
        $custom_data = array();
        if ($this->input->post()) {
            if ($this->input->post('duedate')) {
                $custom_data['duedate'] = to_sql_date($this->input->post('duedate'));
            }
            if ($this->input->post('invoice_total')) {
                $custom_data['invoice_total'] = $this->input->post('invoice_total');
            }
            if ($this->input->post('invoice_notes')) {
                $custom_data['invoice_notes'] = strip_tags($this->input->post('invoice_notes', false));
            }
            
            // Capturar informações detalhadas da fatura
            if ($this->input->post('subtotal_produtos')) {
                $custom_data['subtotal_produtos'] = $this->input->post('subtotal_produtos');
            }
            if ($this->input->post('icms_value')) {
                $custom_data['icms_value'] = $this->input->post('icms_value');
            }
            if ($this->input->post('iss_value')) {
                $custom_data['iss_value'] = $this->input->post('iss_value');
            }
            if ($this->input->post('desconto_value')) {
                $custom_data['desconto_value'] = $this->input->post('desconto_value');
            }
            if ($this->input->post('total_produtos')) {
                $custom_data['total_produtos'] = $this->input->post('total_produtos');
            }
            
            // Validar e salvar o link da nota fiscal apenas se for enviado via POST
            $invoice_link = $this->input->post('invoice_link');
            if (isset($_POST['invoice_link'])) {
                if (empty($invoice_link)) {
                    set_alert('danger', _l('wshop_nota_fiscal_link_required'));
                    redirect(admin_url('workshop/convert_to_invoice_preview/'.$id.'/'.$type));
                    return;
                }
                
                // Validar se é uma URL válida
                if (!filter_var($invoice_link, FILTER_VALIDATE_URL)) {
                    set_alert('danger', _l('wshop_nota_fiscal_invalid_url'));
                    redirect(admin_url('workshop/convert_to_invoice_preview/'.$id.'/'.$type));
                    return;
                }
                
                // Salvar o link da nota fiscal
                if($type == 'repair_job') {
                    $this->workshop_model->update_repair_job(['invoice_link' => $invoice_link], $id);
                } else {
                    $this->workshop_model->update_inspection(['invoice_link' => $invoice_link], $id);
                }
            }
        }

        $invoiceid = $this->workshop_model->convert_transaction_to_invoice($id, $type, false, $draft_invoice, $custom_data);
        if ($invoiceid) {
            set_alert('success', _l('estimate_convert_to_invoice_successfully'));
            redirect(admin_url('workshop/nota_fiscal'));
        } else {
            if($type == 'repair_job'){
                redirect(admin_url('workshop/repair_job_detail/'.$id.'?tab=detail'));
            }else{
                redirect(admin_url('workshop/inspection_detail/'.$id.'?tab=detail'));
            }
        }
    }

    /**
     * repair job send mail client
     * @return [type] 
     */
    public function repair_job_send_mail_client()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $repair_job_id = $data['repair_job_id'];
            $data['content'] = $this->input->post('content', false);
            $rs = $this->workshop_model->repair_job_send_mail_client($data);
            if ($rs == true) {
                set_alert('success', _l('wshop_send_mail_successfully'));
            }
            redirect(admin_url('workshop/repair_job_detail/'.$repair_job_id.'?tab=detail'));
        }
    }

    /**
     * inspection send mail client
     * @return [type] 
     */
    public function inspection_send_mail_client()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $inspection_id = $data['inspection_id'];
            $data['content'] = $this->input->post('content', false);
            $rs = $this->workshop_model->inspection_send_mail_client($data);
            if ($rs == true) {
                set_alert('success', _l('wshop_send_mail_successfully'));
            }
            redirect(admin_url('workshop/inspection_detail/'.$inspection_id.'?tab=detail'));
        }
    }

    /**
     * repair job report by status
     * @return [type] 
     */
    public function report_by_repair_job_month()
    {
        if ($this->input->is_ajax_request()) { 
            $data = $this->input->get();

            $months_report = $data['months_report'];
            $report_from = $data['report_from'];
            $report_to = $data['report_to'];

            if($months_report == ''){

                $from_date = date('Y-m-d', strtotime('1997-01-01'));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'this_month'){
                $from_date = date('Y-m-01');
                $to_date   = date('Y-m-t');
            }

            if($months_report == '1'){ 
                $from_date = date('Y-m-01', strtotime('first day of last month'));
                $to_date   = date('Y-m-t', strtotime('last day of last month'));
            }

            if($months_report == 'this_year'){
                $from_date = date('Y-m-d', strtotime(date('Y-01-01')));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'last_year'){

                $from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


            }

            if($months_report == '3'){
                $months_report = 3;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');

            }

            if($months_report == '6'){
                $months_report = 6;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == '12'){
                $months_report = 12;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == 'custom'){
                $from_date = to_sql_date($report_from);
                $to_date   = to_sql_date($report_to);
            }
    
            $mo_data = $this->workshop_model->get_repair_job_month($from_date, $to_date);


            echo json_encode([
                'categories' => $mo_data['categories'],
                'total' => $mo_data['total'],
                'labour_total' => $mo_data['labour_total'],
                'estimated_hours' => $mo_data['estimated_hours'],
            ]); 
        }
    }

    /**
     * report by repair job weekly
     * @return [type] 
     */
    public function report_by_repair_job_weekly()
    {
        if ($this->input->is_ajax_request()) { 
            $data = $this->input->get();

            $months_report = $data['months_report'];
            $report_from = $data['report_from'];
            $report_to = $data['report_to'];

            if($months_report == ''){

                $from_date = date('Y-m-d', strtotime('1997-01-01'));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'this_month'){
                $from_date = date('Y-m-01');
                $to_date   = date('Y-m-t');
            }

            if($months_report == '1'){ 
                $from_date = date('Y-m-01', strtotime('first day of last month'));
                $to_date   = date('Y-m-t', strtotime('last day of last month'));
            }

            if($months_report == 'this_year'){
                $from_date = date('Y-m-d', strtotime(date('Y-01-01')));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'last_year'){

                $from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


            }

            if($months_report == '3'){
                $months_report = 3;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');

            }

            if($months_report == '6'){
                $months_report = 6;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == '12'){
                $months_report = 12;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == 'custom'){
                $from_date = to_sql_date($report_from);
                $to_date   = to_sql_date($report_to);
            }
    
            $mo_data = $this->workshop_model->get_repair_job_weekly($from_date, $to_date);


            echo json_encode([
                'categories' => $mo_data['categories'],
                'total' => $mo_data['total'],
                'labour_total' => $mo_data['labour_total'],
                'estimated_hours' => $mo_data['estimated_hours'],
            ]); 
        }
    }

    /**
     * report by mechanic performance
     * @return [type] 
     */
    public function report_by_mechanic_performance()
    {
        if ($this->input->is_ajax_request()) { 
            $data = $this->input->get();

            $months_report = $data['months_report'];
            $report_from = $data['report_from'];
            $report_to = $data['report_to'];

            if($months_report == ''){

                $from_date = date('Y-m-d', strtotime('1997-01-01'));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'this_month'){
                $from_date = date('Y-m-01');
                $to_date   = date('Y-m-t');
            }

            if($months_report == '1'){ 
                $from_date = date('Y-m-01', strtotime('first day of last month'));
                $to_date   = date('Y-m-t', strtotime('last day of last month'));
            }

            if($months_report == 'this_year'){
                $from_date = date('Y-m-d', strtotime(date('Y-01-01')));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'last_year'){

                $from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


            }

            if($months_report == '3'){
                $months_report = 3;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');

            }

            if($months_report == '6'){
                $months_report = 6;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == '12'){
                $months_report = 12;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == 'custom'){
                $from_date = to_sql_date($report_from);
                $to_date   = to_sql_date($report_to);
            }
    
            $mo_data = $this->workshop_model->get_report_mechanic_performance($from_date, $to_date);


            echo json_encode([
                'categories' => $mo_data['categories'],
                'estimated_hours' => $mo_data['estimated_hours'],
            ]); 
        }
    }


    /**
     * dashboard
     * @return [type] 
     */
    public function dashboard()
    {
        if (!has_permission('workshop_dashboard', '', 'view')  && !is_admin()) {
            access_denied('dashboard');
        }

        $data['title'] = _l('wshop_dashboard');
        $data['baseCurrency'] = get_base_currency();
        $data['repair_job_by_time_range'] = $this->workshop_model->repair_job_by_time_range();
        $data['count_inspection_by_status'] = $this->workshop_model->count_inspection_by_status();
        $data['financial_metrics'] = $this->workshop_model->get_financial_metrics();
        $data['invoice_metrics'] = $this->workshop_model->get_invoice_metrics();
        $data['chart_data'] = $this->workshop_model->get_repair_jobs_chart_data();

        $this->load->view('workshop/dashboards/dashboard', $data);
    }

    /**
     * repair job print report pdf
     * @param  [type] $id 
     * @return [type]     
     */
    public function repair_job_print_report_80_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/repair_jobs'));
        }
        $this->load->model('clients_model');
        $this->load->model('currencies_model');

        $repair_job_number = '';
        $repair_job = $this->workshop_model->get_repair_job($id);

        $base_currency = $this->currencies_model->get_base_currency();
        $currency = $base_currency;
        if(is_numeric($repair_job->currency) && $repair_job->currency != 0){
            $currency = $repair_job->currency;
        }

        $repair_job->client = $this->clients_model->get($repair_job->client_id);
        $repair_job->currency = $currency;

        $repair_job->workshops = $this->workshop_model->get_workshop(false, ['repair_job_id' => $id]);
        $repair_job->inspection = $this->workshop_model->get_inspection(false, ['repair_job_id' => $id]);
        $repair_job->device = $this->workshop_model->get_device($repair_job->device_id);

        if($repair_job){
            $repair_job_number .= $repair_job->job_tracking_number;
        }
        try {
            $pdf = $this->workshop_model->receipt_report_80_pdf($repair_job);

        } catch (Exception $e) {
            echo new_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';
        ob_end_clean();

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($repair_job_number)).'.pdf', $type);
    }

    /**
     * repair job print a4 report pdf
     * @param  [type] $id 
     * @return [type]     
     */
    public function repair_job_print_a4_report_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('workshop/repair_jobs'));
        }
        $this->load->model('clients_model');
        $this->load->model('currencies_model');

        $repair_job_number = '';
        $repair_job = $this->workshop_model->get_repair_job($id);

        $base_currency = $this->currencies_model->get_base_currency();
        $currency = $base_currency;
        if(is_numeric($repair_job->currency) && $repair_job->currency != 0){
            $currency = $repair_job->currency;
        }

        $repair_job->client = $this->clients_model->get($repair_job->client_id);
        $repair_job->currency = $currency;

        $repair_job->workshops = $this->workshop_model->get_workshop(false, ['repair_job_id' => $id]);
        $repair_job->inspection = $this->workshop_model->get_inspection(false, ['repair_job_id' => $id]);
        $repair_job->device = $this->workshop_model->get_device($repair_job->device_id);

        $repair_job->tax_labour_data = $this->workshop_model->get_html_tax_labour_repair_job($id, $repair_job->currency);
        $repair_job->tax_part_data = $this->workshop_model->get_html_tax_part_repair_job($id, $repair_job->currency);

        if(count($repair_job->inspection) > 0){
            if(is_numeric($repair_job->inspection[0]['id'])){
                $inspection_id = $repair_job->inspection[0]['id'];

                $get_inspection = $this->workshop_model->get_inspection($inspection_id);
                $repair_job->inspection_data = $get_inspection;
                if($get_inspection){
                    if(isset($get_inspection->inspection_labour_products)){
                        $repair_job->inspection_labour_products = $get_inspection->inspection_labour_products;
                    }
                    if(isset($get_inspection->inspection_labour_materials)){
                        $repair_job->inspection_parts = $get_inspection->inspection_labour_materials;
                    }
                }

            }
        }

        $client = $this->clients_model->get($repair_job->client_id);
        $client->clientid = $client->userid;
        $client->client = $client;

        $repair_job->clientid = $repair_job->client_id;
        $repair_job->client = $client;


        if($repair_job){
            $repair_job_number .= $repair_job->job_tracking_number;
        }
        try {
            $pdf = $this->workshop_model->receipt_a4_report_pdf($repair_job);

        } catch (Exception $e) {
            echo new_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';
        ob_end_clean();

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($repair_job_number)).'.pdf', $type);
    }

    /**
     * real permission table
     * @return [type] 
     */
    public function workshop_permission_table() {
        if ($this->input->is_ajax_request()) {

            $select = [
                'staffid',
                'CONCAT(firstname," ",lastname) as full_name',
                'firstname', //for role name
                'email',
                'phonenumber',
            ];
            $where = [];
            $where[] = 'AND ' . db_prefix() . 'staff.admin != 1';

            $arr_staff_id = workshop_get_staff_id_permissions();

            if (count($arr_staff_id) > 0) {
                $where[] = 'AND ' . db_prefix() . 'staff.staffid IN (' . implode(', ', $arr_staff_id) . ')';
            } else {
                $where[] = 'AND ' . db_prefix() . 'staff.staffid IN ("")';
            }

            $aColumns = $select;
            $sIndexColumn = 'staffid';
            $sTable = db_prefix() . 'staff';
            $join = ['LEFT JOIN ' . db_prefix() . 'roles ON ' . db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role'];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'roles.name as role_name', db_prefix() . 'staff.role']);

            $output = $result['output'];
            $rResult = $result['rResult'];

            $not_hide = '';

            foreach ($rResult as $aRow) {
                $row = [];

                $row[] = '<a href="' . admin_url('staff/member/' . $aRow['staffid']) . '">' . $aRow['full_name'] . '</a>';

                $row[] = $aRow['role_name'];
                $row[] = $aRow['email'];
                $row[] = $aRow['phonenumber'];

                $options = '';

                if (has_permission('workshop_setting', '', 'edit')) {
                    $options = icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
                        'title' => _l('edit'),
                        'onclick' => 'workshop_permissions_update(' . $aRow['staffid'] . ', ' . $aRow['role'] . ', ' . $not_hide . '); return false;',
                    ]);
                }

                if (has_permission('workshop_setting', '', 'delete')) {
                    $options .= icon_btn('workshop/delete_workshop_permission/' . $aRow['staffid'], 'fa fa-remove', 'btn-danger _delete', ['title' => _l('delete')]);
                }

                $row[] = $options;

                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            die();
        }
    }

    /**
     * permission modal
     * @return [type] 
     */
    public function permission_modal() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $this->load->model('staff_model');

        if ($this->input->post('slug') === 'update') {
            $staff_id = $this->input->post('staff_id');
            $role_id = $this->input->post('role_id');

            $data = ['funcData' => ['staff_id' => isset($staff_id) ? $staff_id : null]];

            if (isset($staff_id)) {
                $data['member'] = $this->staff_model->get($staff_id);
            }

            $data['roles_value'] = $this->roles_model->get();
            $data['staffs'] = workshop_get_staff_id_dont_permissions();
            $add_new = $this->input->post('add_new');

            if ($add_new == ' hide') {
                $data['add_new'] = ' hide';
                $data['display_staff'] = '';
            } else {
                $data['add_new'] = '';
                $data['display_staff'] = ' hide';
            }
            
            $this->load->view('settings/permissions/permission_modal', $data);
        }
    }

    /**
     * workshop update permissions
     * @param  string $id 
     * @return [type]     
     */
    public function workshop_update_permissions($id = '') {
        if (!is_admin() && !has_permission('workshop_setting', '', 'create') && !has_permission('workshop_setting', '', 'edit')) {
            access_denied('workshop');
        }
        $data = $this->input->post();

        if (!isset($id) || $id == '') {
            $id = $data['staff_id'];
        }

        if (isset($id) && $id != '') {

            $data = hooks()->apply_filters('before_update_staff_member', $data, $id);

            if (is_admin()) {
                if (isset($data['administrator'])) {
                    $data['admin'] = 1;
                    unset($data['administrator']);
                } else {
                    if ($id != get_staff_user_id()) {
                        if ($id == 1) {
                            return [
                                'cant_remove_main_admin' => true,
                            ];
                        }
                    } else {
                        return [
                            'cant_remove_yourself_from_admin' => true,
                        ];
                    }
                    $data['admin'] = 0;
                }
            }

            $this->db->where('staffid', $id);
            $this->db->update(db_prefix() . 'staff', [
                'role' => $data['role'],
            ]);

            $response = $this->staff_model->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $data['permissions']), $id);
        } else {
            $this->load->model('roles_model');

            $role_id = $data['role'];
            unset($data['role']);
            unset($data['staff_id']);

            $data['update_staff_permissions'] = true;

            $response = $this->roles_model->update($data, $role_id);
        }

        if (is_array($response)) {
            if (isset($response['cant_remove_main_admin'])) {
                set_alert('warning', _l('staff_cant_remove_main_admin'));
            } elseif (isset($response['cant_remove_yourself_from_admin'])) {
                set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
            }
        } elseif ($response == true) {
            set_alert('success', _l('updated_successfully', _l('staff_member')));
        }
        redirect(admin_url('workshop/setting?group=permissions'));

    }

    /**
     * delete workshop permission
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_workshop_permission($id) {
        if (!is_admin()) {
            access_denied('hr_profile');
        }

        $response = $this->workshop_model->delete_workshop_permission($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('hr_is_referenced', _l('department_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('hr_department')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
        }
        redirect(admin_url('workshop/setting?group=permissions'));

    }

    /**
     * role changed
     * @param  [type] $id 
     * @return [type]     
     */
    public function role_changed($id)
    {
        echo json_encode($this->roles_model->get($id)->permissions);
    }

    /**
     * reset data
     * @return [type] 
     */
    public function reset_data()
    {

        if ( !is_admin()) {
            access_denied('workshop');
        }
            // Truncate apenas tabelas que existem no módulo workshop
            // Baseado no arquivo install.php - Corrigido pela Equipe WORKENTERPRISE
            
            //delete wshop_repair_jobs
            $this->db->truncate(db_prefix().'wshop_repair_jobs');
            //delete wshop_inspections
            $this->db->truncate(db_prefix().'wshop_inspections');
            //delete wshop_workshops
            $this->db->truncate(db_prefix().'wshop_workshops');
            //delete wshop_return_deliveries
            $this->db->truncate(db_prefix().'wshop_return_deliveries');
            //delete wshop_return_delivery_notes
            $this->db->truncate(db_prefix().'wshop_return_delivery_notes');
            //delete wshop_repair_job_labour_products
            $this->db->truncate(db_prefix().'wshop_repair_job_labour_products');
            //delete wshop_repair_job_labour_materials
            $this->db->truncate(db_prefix().'wshop_repair_job_labour_materials');
            //delete wshop_inspection_values
            $this->db->truncate(db_prefix().'wshop_inspection_values');
            //delete wshop_inspection_forms
            $this->db->truncate(db_prefix().'wshop_inspection_forms');
            //delete wshop_inspection_form_details
            $this->db->truncate(db_prefix().'wshop_inspection_form_details');
            //delete wshop_form_submissions
            $this->db->truncate(db_prefix().'wshop_form_submissions');
            //delete wshop_auto_fill_logs
            $this->db->truncate(db_prefix().'wshop_auto_fill_logs');
            //delete wshop_form_templates
            $this->db->truncate(db_prefix().'wshop_form_templates');
            //delete wshop_settings
            $this->db->truncate(db_prefix().'wshop_settings');
            //delete wshop_holidays
            $this->db->truncate(db_prefix().'wshop_holidays');
            //delete wshop_repair_job_statuses
            $this->db->truncate(db_prefix().'wshop_repair_job_statuses');
            //delete wshop_products
            $this->db->truncate(db_prefix().'wshop_products');
            //delete wshop_categories
            $this->db->truncate(db_prefix().'wshop_categories');
            //delete wshop_manufacturers
            $this->db->truncate(db_prefix().'wshop_manufacturers');
            //delete wshop_delivery_methods
            $this->db->truncate(db_prefix().'wshop_delivery_methods');
            //delete wshop_intervals
            $this->db->truncate(db_prefix().'wshop_intervals');
            //delete wshop_models
            $this->db->truncate(db_prefix().'wshop_models');
            //delete wshop_direcionamentos
            $this->db->truncate(db_prefix().'wshop_direcionamentos');
            //delete wshop_appointment_types
            $this->db->truncate(db_prefix().'wshop_appointment_types');
            //delete wshop_appointment_products
            $this->db->truncate(db_prefix().'wshop_appointment_products');
            //delete wshop_fieldsets
            $this->db->truncate(db_prefix().'wshop_fieldsets');
            //delete wshop_customfields
            $this->db->truncate(db_prefix().'wshop_customfields');
            //delete wshop_customfieldsvalues
            $this->db->truncate(db_prefix().'wshop_customfieldsvalues');
            //delete wshop_inspection_templates
            $this->db->truncate(db_prefix().'wshop_inspection_templates');
            //delete wshop_inspection_template_forms
            $this->db->truncate(db_prefix().'wshop_inspection_template_forms');
            //delete wshop_inspection_template_form_details
            $this->db->truncate(db_prefix().'wshop_inspection_template_form_details');
            //delete wshop_inspection_template_values
            $this->db->truncate(db_prefix().'wshop_inspection_template_values');
            //delete wshop_branches
            $this->db->truncate(db_prefix().'wshop_branches');
            //delete wshop_devices
            $this->db->truncate(db_prefix().'wshop_devices');
            //delete wshop_activity
            $this->db->truncate(db_prefix().'wshop_activity');
            //delete wshop_labour_products
            $this->db->truncate(db_prefix().'wshop_labour_products');
            //delete wshop_labour_product_materials
            $this->db->truncate(db_prefix().'wshop_labour_product_materials');
            //delete wshop_repair_inspection_templates
            $this->db->truncate(db_prefix().'wshop_repair_inspection_templates');
            //delete wshop_appointments
            $this->db->truncate(db_prefix().'wshop_appointments');
            //delete wshop_commissions
            $this->db->truncate(db_prefix().'wshop_commissions');
            //delete wshop_mechanic_commissions
            $this->db->truncate(db_prefix().'wshop_mechanic_commissions');
            //delete wshop_iss_retention_rates
            $this->db->truncate(db_prefix().'wshop_iss_retention_rates');
            //delete wshop_inspection_form_details
            $this->db->truncate(db_prefix().'wshop_inspection_form_details');


            //delete sub folder REPAIR_JOB_BARCODE
            foreach(glob(REPAIR_JOB_BARCODE . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(REPAIR_JOB_BARCODE.$filename);
                }
            }

            //delete sub folder TRANSACTION_FOLDER
            foreach(glob(TRANSACTION_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(TRANSACTION_FOLDER.$filename);
                }
            }

            //delete sub folder NOTE_FOLDER
            foreach(glob(NOTE_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(NOTE_FOLDER.$filename);
                }
            }
            
            //delete sub folder WORKSHOP_FOLDER
            foreach(glob(WORKSHOP_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(WORKSHOP_FOLDER.$filename);
                }
            }

            //delete sub folder INSPECTION_FOLDER
            foreach(glob(INSPECTION_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(INSPECTION_FOLDER.$filename);
                }
            }

            //delete sub folder INSPECTION_QUESTION_FOLDER
            foreach(glob(INSPECTION_QUESTION_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(INSPECTION_QUESTION_FOLDER.$filename);
                }
            }

            //delete sub folder REPAIR_JOB_QR_FOLDER
            foreach(glob(REPAIR_JOB_QR_FOLDER . '*') as $file) { 
                $file_arr = new_explode("/",$file);
                $filename = array_pop($file_arr);

                if(is_dir($file)) {
                    delete_dir(REPAIR_JOB_QR_FOLDER.$filename);
                }
            }
            

            //delete create task rel_type: "wshop_inspection"
            $this->db->where('rel_type', 'wshop_inspection');
            $this->db->delete(db_prefix() . 'tasks');

            set_alert('success',_l('reset_data_successful'));
            
            redirect(admin_url('workshop/setting?group=reset_data'));

    }

    public function re_caculate_inspection($inspection_id){
        $this->workshop_model->re_caculate_inspection($inspection_id);
    }

    /**
     * ISS Retention Rate Management
     */
    
    /**
     * Add/Edit ISS retention rate
     * Processa formulário tradicional sem AJAX
     * 
     * @author Fabio Caetano - Especialista PerfexCRM & Backend Avançado
     * @version 1.3
     */
    /**
     * ISS Retention Rate Action - Add/Edit ISS retention rate
     * Processa formulário tradicional sem AJAX
     * 
     * @author Fabio Caetano - Especialista PerfexCRM & Backend Avançado
     * @version 1.4
     */
    public function iss_retention_rate_action()
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin()) {
            access_denied('workshop_setting');
        }
        
        if (!$this->input->post()) {
            redirect(admin_url('workshop/setting?group=iss_retention'));
        }
        
        $this->load->model('iss_retention_model');
        
        $data = $this->input->post();
        $rate_id = $data['rate_id'];
        unset($data['rate_id']);
        
        // Validações
        if (empty($data['name'])) {
            $this->session->set_flashdata('error', 'O nome da taxa é obrigatório.');
            redirect(admin_url('workshop/setting?group=iss_retention'));
        }
        
        if (!is_numeric($data['percentage']) || $data['percentage'] < 0 || $data['percentage'] > 100) {
            $this->session->set_flashdata('error', 'O percentual deve estar entre 0 e 100.');
            redirect(admin_url('workshop/setting?group=iss_retention'));
        }
        
        if (!empty($rate_id)) {
            // Update existing rate
            $result = $this->iss_retention_model->update($data, $rate_id);
            if ($result === true) {
                $this->session->set_flashdata('success', _l('wshop_iss_retention_rate_updated'));
            } else {
                $this->session->set_flashdata('error', 'Já existe uma taxa com este nome.');
            }
        } else {
            // Add new rate
            $result = $this->iss_retention_model->add($data);
            if ($result) {
                $this->session->set_flashdata('success', _l('wshop_iss_retention_rate_added'));
            } else {
                $this->session->set_flashdata('error', 'Já existe uma taxa com este nome.');
            }
        }
        
        redirect(admin_url('workshop/setting?group=iss_retention'));
    }

    /**
     * Direcionamento form - Add/Edit direcionamento
     */
    /**
     * Add/Edit direcionamento - Método tradicional POST sem AJAX
     * Conforme diretrizes WORKENTERPRISE - James (Gerente/Líder Técnico)
     */
    public function direcionamento_form()
    {
        if (!has_permission('workshop_setting', '', 'edit') && !is_admin()) {
            access_denied('workshop_setting');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $id = $this->input->post('id');

            // Validar campos obrigatórios
            if (empty($data['name']) || empty($data['url'])) {
                set_alert('danger', 'Nome e URL são obrigatórios');
                redirect(admin_url('workshop/settings?group=direcionamentos'));
            }

            unset($data['id']);
            
            if ($id && is_numeric($id)) {
                // Update
                $success = $this->workshop_model->update_direcionamento($data, $id);
                $message = $success ? _l('updated_successfully', _l('wshop_direcionamento')) : _l('something_went_wrong');
            } else {
                // Add new
                $success = $this->workshop_model->add_direcionamento($data);
                $message = $success ? _l('added_successfully', _l('wshop_direcionamento')) : _l('something_went_wrong');
            }

            if ($success) {
                set_alert('success', $message);
            } else {
                set_alert('danger', $message);
            }
            
            redirect(admin_url('workshop/settings?group=direcionamentos'));
        } else {
            // Carregar view do formulário
            $data['title'] = _l('wshop_new_direcionamento');
            $this->load->view('settings/direcionamentos/form', $data);
        }
    }

    /**
     * Get direcionamento data for editing - Método tradicional GET
     * Conforme diretrizes WORKENTERPRISE - James (Gerente/Líder Técnico)
     */
    public function edit_direcionamento($id = '')
    {
        if (!has_permission('workshop_setting', '', 'view') && !is_admin()) {
            access_denied('workshop_setting');
        }

        if (!$id || !is_numeric($id)) {
            set_alert('danger', 'ID inválido');
            redirect(admin_url('workshop/settings?group=direcionamentos'));
        }

        $data['direcionamento'] = $this->workshop_model->get_direcionamentos($id);
        
        if (!$data['direcionamento']) {
            set_alert('danger', 'Direcionamento não encontrado');
            redirect(admin_url('workshop/settings?group=direcionamentos'));
        }
        
        $data['title'] = _l('wshop_edit_direcionamento');
        $this->load->view('settings/direcionamentos/form', $data);
    }

    /**
     * Delete direcionamento - Método tradicional POST
     * Conforme diretrizes WORKENTERPRISE - James (Gerente/Líder Técnico)
     */
    public function delete_direcionamento($id = '')
    {
        if (!has_permission('workshop_setting', '', 'delete') && !is_admin()) {
            access_denied('workshop_setting');
        }

        // Se for POST, pegar ID do formulário
        if ($this->input->post()) {
            $id = $this->input->post('id');
        }
        
        if (!$id || !is_numeric($id)) {
            set_alert('danger', 'ID inválido');
            redirect(admin_url('workshop/settings?group=direcionamentos'));
        }
        
        $success = $this->workshop_model->delete_direcionamento($id);
        $message = $success ? _l('deleted', _l('wshop_direcionamento')) : _l('something_went_wrong');

        if ($success) {
            set_alert('success', $message);
        } else {
            set_alert('danger', $message);
        }
        
        redirect(admin_url('workshop/settings?group=direcionamentos'));
    }



    /**
     * Método removido - Funcionalidade movida para iss_retention_rate_action()
     */
    public function iss_retention_rate_action_form()
    {
        // Método removido - Funcionalidade movida para iss_retention_rate_action()
        redirect(admin_url('workshop/setting?group=iss_retention'));
    }
    

    /**
     * Delete ISS retention rate
     * Processa exclusão via GET sem AJAX
     * 
     * @author Fabio Caetano - Especialista PerfexCRM & Backend Avançado
     * @version 1.3
     * @param int $id ID da taxa a ser excluída
     */
    public function delete_iss_retention_rate($id = null)
    {
        if (!$id || !is_numeric($id)) {
            $this->session->set_flashdata('error', 'ID da taxa inválido.');
            redirect(admin_url('workshop/setting?group=iss_retention'));
        }
        
        $this->load->model('iss_retention_model');
        
        $result = $this->iss_retention_model->delete($id);
        
        if ($result === true) {
            $this->session->set_flashdata('success', _l('wshop_iss_retention_rate_deleted'));
        } elseif ($result === 'used_in_jobs') {
            $this->session->set_flashdata('error', 'Esta taxa não pode ser excluída pois está sendo utilizada em chamados.');
        } else {
            $this->session->set_flashdata('error', 'Erro ao excluir a taxa de retenção.');
        }
        
        redirect(admin_url('workshop/setting?group=iss_retention'));
    }

    /**
     * Save appointment from calendar
     * Salva agendamento do calendário integrado com sistema de eventos do PerfexCRM
     * 
     * @author James - Líder Técnico WORKENTERPRISE
     * @version 1.0
     */
    public function save_appointment()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Validar dados de entrada
            $client_id = $this->input->post('client_id');
            $appointment_date = $this->input->post('appointment_date');
            $appointment_time = $this->input->post('appointment_time');
            $service_type = $this->input->post('service_type');
            $duration = $this->input->post('duration') ?: '60';
            $notes = $this->input->post('notes');
            
            if (empty($client_id) || empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
                $response['message'] = _l('wshop_fill_required_fields');
                echo json_encode($response);
                return;
            }
            
            // Verificar se o cliente existe
            $this->load->model('clients_model');
            $client = $this->clients_model->get($client_id);
            if (!$client) {
                $response['message'] = 'Cliente não encontrado.';
                echo json_encode($response);
                return;
            }
            
            // Preparar dados do evento
            $datetime = $appointment_date . ' ' . $appointment_time;
            $end_time = date('Y-m-d H:i:s', strtotime($datetime . ' +' . $duration . ' minutes'));
            
            // Buscar nome do tipo de serviço
            $service_name = $service_type;
            if (is_numeric($service_type)) {
                $appointment_type = $this->db->get_where(db_prefix() . 'wshop_appointment_types', ['id' => $service_type])->row();
                if ($appointment_type) {
                    $service_name = $appointment_type->name;
                }
            }
            
            // Inserir evento na tabela de eventos do PerfexCRM (se existir)
            $event_data = [
                'title' => 'Agendamento: ' . $service_name . ' - ' . $client->company,
                'description' => $notes ?: 'Agendamento via calendário Workshop',
                'start' => $datetime,
                'end' => $end_time,
                'userid' => get_staff_user_id(),
                'public' => 0,
                'color' => '#28a745'
            ];
            
            // Verificar se a tabela de eventos existe
            if ($this->db->table_exists(db_prefix() . 'events')) {
                $this->db->insert(db_prefix() . 'events', $event_data);
                $event_id = $this->db->insert_id();
            } else {
                $event_id = 0;
            }
            
            // Inserir na tabela de agendamentos do workshop usando o model
            $appointment_data = [
                'client_id' => $client_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'duration' => $duration,
                'service_type' => $service_type,
                'notes' => $notes,
                'status' => 'scheduled',
                'created_by' => get_staff_user_id()
            ];
            
            $appointment_id = $this->workshop_model->add_appointment($appointment_data);
            
            if ($appointment_id) {
                $response['success'] = true;
                $response['message'] = 'Agendamento salvo com sucesso!';
                $response['appointment_id'] = $appointment_id;
            } else {
                $response['message'] = 'Erro ao salvar agendamento. Verifique os dados e tente novamente.';
            }
            
        } catch (Exception $e) {
            $response['message'] = 'Erro interno: ' . $e->getMessage();
        }
        
        echo json_encode($response);
    }
    
    /**
     * Create appointments table if not exists
     * Cria tabela de agendamentos se não existir
     */
    private function create_appointments_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "wshop_appointments` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id` int(11) NOT NULL,
            `appointment_date` date NOT NULL,
            `appointment_time` time NOT NULL,
            `duration` int(11) DEFAULT '60',
            `service_type` varchar(100) NOT NULL,
            `notes` text,
            `event_id` int(11) DEFAULT '0',
            `status` varchar(50) DEFAULT 'scheduled',
            `created_by` int(11) NOT NULL,
            `datecreated` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `client_id` (`client_id`),
            KEY `appointment_date` (`appointment_date`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $this->db->query($sql);
    }

    /**
     * form_auto_filler_example
     * Exibe a página de exemplo do preenchedor automático de formulários
     */
    public function form_auto_filler_example()
    {
        // Verificar permissões
        if (!has_permission('workshop_setting', '', 'view')) {
            access_denied('workshop');
        }

        $data['title'] = _l('wshop_form_auto_filler_title');
        $this->load->view('examples/form_auto_filler_example', $data);
    }
    
    /**
     * formulario_exemplo
     * Exibe o formulário de exemplo completo para testes
     * Formulário com diversos tipos de campos para demonstração
     */
    public function formulario_exemplo()
    {
        // Verificar permissões
        if (!has_permission('workshop_setting', '', 'view')) {
            access_denied('workshop');
        }

        $data['title'] = _l('workshop_form_auto_filler_example_title');
        $this->load->view('examples/formulario_exemplo', $data);
    }
    
    /**
     * nota_fiscal
     * Página para gerenciar links de notas fiscais das faturas
     * Exibe faturas que possuem links de notas fiscais vinculadas
     */
    public function nota_fiscal()
    {
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'view') && !has_permission('workshop_nota_fiscal', '', 'view_own') && !is_admin()) {
            access_denied('workshop');
        }
        
        $this->load->model('invoices_model');
        
        // Processar filtros
        $filters = [];
        
        // Definir filtro padrão "este mês" se nenhum filtro de data for fornecido
        if (!$this->input->get('date_from') && !$this->input->get('date_to') && !$this->input->get('period')) {
            $filters['date_from'] = date('Y-m-01'); // Primeiro dia do mês atual
            $filters['date_to'] = date('Y-m-t');   // Último dia do mês atual
        } else {
            if ($this->input->get('date_from')) {
                $filters['date_from'] = $this->input->get('date_from');
            }
            
            if ($this->input->get('date_to')) {
                $filters['date_to'] = $this->input->get('date_to');
            }
        }
        
        if ($this->input->get('link_status')) {
            $filters['link_status'] = $this->input->get('link_status');
        }
        
        if ($this->input->get('client_id')) {
            $filters['client_id'] = $this->input->get('client_id');
        }
        
        // Buscar faturas que possuem links de nota fiscal com filtros
        $data['invoices_with_links'] = $this->workshop_model->get_invoices_with_nota_fiscal_links($filters);
        
        // Identificar a fatura mais recente para exibir a etiqueta NOVO
        $data['newest_invoice_id'] = null;
        if (!empty($data['invoices_with_links'])) {
            // Como as faturas já estão ordenadas por datecreated DESC, a primeira é a mais recente
            $data['newest_invoice_id'] = $data['invoices_with_links'][0]->id;
        }
        
        // Buscar todos os clientes que aparecem nas faturas para o filtro
        $data['clients_in_invoices'] = $this->workshop_model->get_clients_from_invoices_with_links();
        $data['title'] = 'Notas Fiscais';
        $data['filters'] = $filters;
        
        $this->load->view('admin/workshop/nota_fiscal', $data);
    }
    
    /**
     * update_nota_fiscal_link
     * Atualiza o link da nota fiscal de uma fatura
     */
    public function update_nota_fiscal_link()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }
        
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'edit') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }
        
        $invoice_id = $this->input->post('invoice_id');
        $nota_fiscal_link = $this->input->post('invoice_link');
        
        if (empty($invoice_id) || empty($nota_fiscal_link)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_nota_fiscal_required_fields')
            ]);
            return;
        }
        
        // Validar URL
        if (!filter_var($nota_fiscal_link, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_nota_fiscal_invalid_url')
            ]);
            return;
        }
        
        $result = $this->workshop_model->update_nota_fiscal_link($invoice_id, $nota_fiscal_link);
        
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_nota_fiscal_updated_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_nota_fiscal_update_failed')
            ]);
        }
    }
    
    /**
     * delete_nota_fiscal_link
     * Remove o link da nota fiscal de uma fatura
     */
    public function delete_nota_fiscal_link()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }
        
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'delete') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }
        
        $invoice_id = $this->input->post('invoice_id');
        
        if (empty($invoice_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_nota_fiscal_invoice_id_required')
            ]);
            return;
        }
        
        $result = $this->workshop_model->delete_nota_fiscal_link($invoice_id);
        
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_nota_fiscal_deleted_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_nota_fiscal_delete_failed')
            ]);
        }
    }
    
    /**
     * update_invoice_links
     * Atualiza os links de nota fiscal, boleto e fatura
     */
    public function update_invoice_links()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }
        
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'edit') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }
        
        $invoice_id = $this->input->post('invoice_id');
        $nota_fiscal_link = $this->input->post('invoice_link');
        $boleto_link = $this->input->post('boleto_link');
        $fatura_link = $this->input->post('fatura_link');
        
        if (empty($invoice_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_id_required')
            ]);
            return;
        }
        
        // Validar URLs se fornecidas
        $links = [
            'invoice_link' => $nota_fiscal_link,
            'boleto' => $boleto_link,
            'fatura' => $fatura_link
        ];
        
        foreach ($links as $type => $link) {
            if (!empty($link) && !filter_var($link, FILTER_VALIDATE_URL)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => _l('wshop_invalid_url_for') . ' ' . $type
                ]);
                return;
            }
        }
        
        $result = $this->workshop_model->update_invoice_links($invoice_id, $nota_fiscal_link, $boleto_link, $fatura_link);
        
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_invoice_links_updated_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_links_update_failed')
            ]);
        }
    }
    
    /**
     * generate_combined_pdf
     * Gera um PDF combinado com todos os documentos selecionados
     */
    public function generate_combined_pdf()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }
        
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'view') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }
        
        $documents = $this->input->post('documents');
        
        if (empty($documents) || !is_array($documents)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_no_documents_to_share')
            ]);
            return;
        }
        
        try {
            // Log de debug
            error_log('Workshop: Iniciando generate_combined_pdf');
            error_log('Workshop: Documentos recebidos: ' . json_encode($documents));
            
            // Criar pasta temporária
            $module_path = dirname(__DIR__) . '/uploads/temp_pdfs/';
            error_log('Workshop: Caminho base para uploads: ' . $module_path);
            error_log('Workshop: Pasta base existe? ' . (is_dir($module_path) ? 'SIM' : 'NÃO'));
            
            // Verificar se a pasta base existe e criar se necessário
            if (!is_dir($module_path)) {
                error_log('Workshop: Criando pasta base: ' . $module_path);
                if (!mkdir($module_path, 0755, true)) {
                    error_log('Workshop: Erro ao criar pasta base');
                    throw new Exception(_l('wshop_temp_folder_error'));
                }
            }
            
            $temp_dir = $module_path . uniqid('combined_', true) . '/';
            error_log('Workshop: Criando pasta temporária: ' . $temp_dir);
            
            if (!is_dir($temp_dir)) {
                if (!mkdir($temp_dir, 0755, true)) {
                    error_log('Workshop: Erro ao criar pasta temporária');
                    error_log('Workshop: Erro detalhado: ' . error_get_last()['message']);
                    throw new Exception(_l('wshop_temp_folder_error'));
                }
            }
            error_log('Workshop: Pasta temporária criada com sucesso');
            error_log('Workshop: Pasta temporária é gravável? ' . (is_writable($temp_dir) ? 'SIM' : 'NÃO'));
            
            $downloaded_files = [];
            $pdf_files = [];
            
            // Processar cada documento
            foreach ($documents as $doc) {
                error_log('Workshop: Processando documento ID: ' . ($doc['invoice_id'] ?? 'N/A'));
                
                $links = [
                    'nota_fiscal' => $doc['nota_fiscal'] ?? '',
                    'boleto' => $doc['boleto'] ?? '',
                    'fatura' => $doc['fatura'] ?? ''
                ];
                
                error_log('Workshop: Links encontrados: ' . json_encode($links));
                
                foreach ($links as $type => $url) {
                    if (!empty($url)) {
                        error_log('Workshop: Baixando ' . $type . ' de: ' . $url);
                        $file_info = $this->download_and_process_file($url, $temp_dir, $type, $doc['invoice_id']);
                        
                        if ($file_info) {
                            error_log('Workshop: Arquivo baixado com sucesso: ' . $file_info['path']);
                            $downloaded_files[] = $file_info['path'];
                            if ($file_info['type'] === 'pdf') {
                                $pdf_files[] = $file_info['path'];
                            }
                        } else {
                            error_log('Workshop: Falha ao baixar ' . $type . ' de: ' . $url);
                        }
                    }
                }
            }
            
            error_log('Workshop: Total de arquivos baixados: ' . count($downloaded_files));
            error_log('Workshop: Total de PDFs: ' . count($pdf_files));
            
            if (empty($pdf_files)) {
                throw new Exception(_l('wshop_no_documents_to_share'));
            }
            
            // Combinar PDFs
            $combined_pdf_path = $this->combine_pdfs($pdf_files, $temp_dir);
            
            if (!$combined_pdf_path) {
                throw new Exception(_l('wshop_pdf_merge_error'));
            }
            
            // Gerar URL para acesso
            $pdf_url = base_url('workshop/download_combined_pdf/' . basename(dirname($combined_pdf_path)) . '/' . basename($combined_pdf_path));
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_combined_pdf_generated'),
                'pdf_url' => $pdf_url,
                'temp_dir' => basename(dirname($combined_pdf_path))
            ]);
            
        } catch (Exception $e) {
            // Limpar arquivos temporários em caso de erro
            if (isset($temp_dir) && is_dir($temp_dir)) {
                $this->cleanup_temp_files($temp_dir);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * download_and_process_file
     * Baixa e processa um arquivo (PDF, XML ou HTML)
     */
    private function download_and_process_file($url, $temp_dir, $type, $invoice_id)
    {
        try {
            error_log('Workshop: Iniciando download_and_process_file para URL: ' . $url);
            error_log('Workshop: Tipo: ' . $type . ', Invoice ID: ' . $invoice_id);
            error_log('Workshop: Pasta temporária: ' . $temp_dir);
            
            // Validar URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                error_log('Workshop: URL inválida: ' . $url);
                return false;
            }
            
            error_log('Workshop: URL válida, configurando contexto de download');
            
            // Verificar se allow_url_fopen está habilitado
            if (!ini_get('allow_url_fopen')) {
                error_log('Workshop: ERRO - allow_url_fopen está desabilitado no PHP');
                return false;
            }
            error_log('Workshop: allow_url_fopen está habilitado');
            
            // Configurar contexto para download com headers apropriados
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'header' => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                        'Accept-Encoding: gzip, deflate',
                        'Connection: keep-alive',
                        'Upgrade-Insecure-Requests: 1'
                    ]
                ]
            ]);
            
            error_log('Workshop: Tentando baixar arquivo de: ' . $url);
            
            // Baixar arquivo
            $file_content = @file_get_contents($url, false, $context);
            
            if ($file_content === false) {
                error_log('Workshop: Falha ao baixar arquivo com file_get_contents de: ' . $url);
                $last_error = error_get_last();
                if ($last_error) {
                    error_log('Workshop: Erro do file_get_contents: ' . $last_error['message']);
                } else {
                    error_log('Workshop: Nenhum erro específico capturado');
                }
                
                // Tentar com cURL como fallback
                error_log('Workshop: Tentando download com cURL como fallback');
                $file_content = $this->download_with_curl($url);
                
                if ($file_content === false) {
                    error_log('Workshop: Falha também com cURL');
                    return false;
                }
                
                error_log('Workshop: Download com cURL bem-sucedido');
            }
            
            error_log('Workshop: Arquivo baixado com sucesso. Tamanho: ' . strlen($file_content) . ' bytes');
            
            // Determinar tipo de conteúdo
            $content_type = $this->get_content_type_from_content($file_content, $url);
            
            // Gerar nome único para o arquivo
            $filename = 'invoice_' . $invoice_id . '_' . $type . '_' . uniqid();
            
            error_log('Workshop: Tipo de conteúdo detectado: ' . $content_type);
            error_log('Workshop: Nome do arquivo: ' . $filename);
            
            // Processar baseado no tipo de conteúdo
            switch ($content_type) {
                case 'pdf':
                    $file_path = $temp_dir . $filename . '.pdf';
                    error_log('Workshop: Tentando salvar PDF em: ' . $file_path);
                    error_log('Workshop: Pasta existe? ' . (is_dir($temp_dir) ? 'SIM' : 'NÃO'));
                    error_log('Workshop: Pasta é gravável? ' . (is_writable($temp_dir) ? 'SIM' : 'NÃO'));
                    
                    $bytes_written = file_put_contents($file_path, $file_content);
                    if ($bytes_written === false) {
                        error_log('Workshop: Falha ao salvar PDF em: ' . $file_path);
                        error_log('Workshop: Erro: ' . error_get_last()['message']);
                        return false;
                    }
                    
                    error_log('Workshop: PDF salvo com sucesso. Bytes escritos: ' . $bytes_written);
                    error_log('Workshop: Arquivo existe após salvamento? ' . (file_exists($file_path) ? 'SIM' : 'NÃO'));
                    
                    return [
                        'path' => $file_path,
                        'type' => 'pdf',
                        'original_type' => 'pdf'
                    ];
                    
                case 'xml':
                    $file_path = $temp_dir . $filename . '.xml';
                    error_log('Workshop: Tentando salvar XML em: ' . $file_path);
                    
                    $bytes_written = file_put_contents($file_path, $file_content);
                    if ($bytes_written === false) {
                        error_log('Workshop: Falha ao salvar XML em: ' . $file_path);
                        error_log('Workshop: Erro: ' . error_get_last()['message']);
                        return false;
                    }
                    
                    error_log('Workshop: XML salvo com sucesso. Bytes escritos: ' . $bytes_written);
                    
                    // Converter XML para PDF
                    error_log('Workshop: Iniciando conversão XML para PDF');
                    $pdf_path = $this->convert_xml_to_pdf($file_path, $temp_dir);
                    if ($pdf_path) {
                        error_log('Workshop: Conversão XML para PDF bem-sucedida: ' . $pdf_path);
                        unlink($file_path); // Remover XML original
                        return [
                            'path' => $pdf_path,
                            'type' => 'pdf',
                            'original_type' => 'xml'
                        ];
                    }
                    error_log('Workshop: Falha na conversão XML para PDF');
                    return false;
                    
                case 'html':
                    // Para páginas HTML (como nota fiscal de Goiânia), converter para PDF
                    error_log('Workshop: Iniciando conversão HTML para PDF');
                    $pdf_path = $this->convert_html_to_pdf($file_content, $temp_dir, $filename, $url);
                    if ($pdf_path) {
                        error_log('Workshop: Conversão HTML para PDF bem-sucedida: ' . $pdf_path);
                        return [
                            'path' => $pdf_path,
                            'type' => 'pdf',
                            'original_type' => 'html'
                        ];
                    }
                    error_log('Workshop: Falha na conversão HTML para PDF');
                    return false;
                    
                default:
                    // Tentar salvar como PDF por padrão
                    $file_path = $temp_dir . $filename . '.pdf';
                    error_log('Workshop: Tipo desconhecido, salvando como PDF em: ' . $file_path);
                    
                    $bytes_written = file_put_contents($file_path, $file_content);
                    if ($bytes_written === false) {
                        error_log('Workshop: Falha ao salvar arquivo padrão em: ' . $file_path);
                        error_log('Workshop: Erro: ' . error_get_last()['message']);
                        return false;
                    }
                    
                    error_log('Workshop: Arquivo padrão salvo com sucesso. Bytes escritos: ' . $bytes_written);
                    return [
                        'path' => $file_path,
                        'type' => 'pdf',
                        'original_type' => 'unknown'
                    ];
            }
            
        } catch (Exception $e) {
            error_log('Workshop: Exceção capturada em download_and_process_file: ' . $e->getMessage());
            error_log('Workshop: Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * download_with_curl
     * Método alternativo para download usando cURL
     */
    private function download_with_curl($url)
    {
        if (!function_exists('curl_init')) {
            error_log('Workshop: cURL não está disponível');
            return false;
        }
        
        error_log('Workshop: Iniciando download com cURL para: ' . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive'
        ]);
        
        // Para HTTPS, configurar SSL
        if (strpos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            error_log('Workshop: Configurações SSL aplicadas para HTTPS');
        }
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($content === false || !empty($error)) {
            error_log('Workshop: Erro no cURL: ' . $error);
            error_log('Workshop: HTTP Code: ' . $http_code);
            return false;
        }
        
        if ($http_code >= 400) {
            error_log('Workshop: HTTP Error Code: ' . $http_code);
            return false;
        }
        
        error_log('Workshop: cURL download bem-sucedido. HTTP Code: ' . $http_code . ', Tamanho: ' . strlen($content) . ' bytes');
        return $content;
    }
    
    /**
     * get_content_type_from_content
     * Determina o tipo de conteúdo baseado no conteúdo e URL
     */
    private function get_content_type_from_content($content, $url)
    {
        // Verificar assinatura do arquivo PDF
        $pdf_signature = substr($content, 0, 4);
        if ($pdf_signature === '%PDF') {
            return 'pdf';
        }
        
        // Verificar se é XML
        if (strpos(trim($content), '<?xml') === 0 || strpos($content, '<nfeProc') !== false) {
            return 'xml';
        }
        
        // Verificar se é HTML
        $content_lower = strtolower(trim($content));
        if (strpos($content_lower, '<!doctype html') === 0 || 
            strpos($content_lower, '<html') === 0 || 
            strpos($content, '<title>') !== false ||
            strpos($content, '<body>') !== false) {
            return 'html';
        }
        
        // Verificar pela URL
        $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
        if (isset($path_info['extension'])) {
            $ext = strtolower($path_info['extension']);
            if (in_array($ext, ['pdf', 'xml', 'html', 'htm'])) {
                return $ext === 'htm' ? 'html' : $ext;
            }
        }
        
        // Verificar se a URL contém parâmetros típicos de sistemas web
        if (strpos($url, '.asp') !== false || strpos($url, '.php') !== false || 
            strpos($url, '?') !== false || strpos($url, 'sistemas') !== false) {
            return 'html';
        }
        
        // Padrão para PDF
        return 'pdf';
    }
    
    /**
     * convert_html_to_pdf
     * Converte conteúdo HTML para PDF
     */
    private function convert_html_to_pdf($html_content, $temp_dir, $filename, $original_url = '')
    {
        try {
            $pdf_path = $temp_dir . $filename . '.pdf';
            
            // Tentar usar wkhtmltopdf se disponível
            if ($this->is_wkhtmltopdf_available()) {
                $html_file = $temp_dir . 'temp_' . uniqid() . '.html';
                
                // Melhorar o HTML para melhor renderização
                $improved_html = $this->improve_html_for_pdf($html_content, $original_url);
                file_put_contents($html_file, $improved_html);
                
                $command = "wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in --encoding UTF-8 --quiet --enable-local-file-access \"$html_file\" \"$pdf_path\"";
                exec($command, $output, $return_code);
                
                unlink($html_file);
                
                if ($return_code === 0 && file_exists($pdf_path)) {
                    return $pdf_path;
                }
            }
            
            // Fallback: criar PDF simples com o conteúdo HTML
            return $this->create_simple_html_pdf($html_content, $pdf_path, $original_url);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * improve_html_for_pdf
     * Melhora o HTML para melhor renderização em PDF
     */
    private function improve_html_for_pdf($html_content, $original_url = '')
    {
        // Adicionar meta tags e CSS básico se não existir
        if (strpos($html_content, '<head>') === false) {
            $html_content = str_replace('<html>', '<html><head><meta charset="UTF-8"><title>Documento</title></head>', $html_content);
        }
        
        // Adicionar CSS básico para melhor formatação
        $css = '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .header { background-color: #f2f2f2; font-weight: bold; }
            .center { text-align: center; }
            .right { text-align: right; }
        </style>';
        
        if (strpos($html_content, '</head>') !== false) {
            $html_content = str_replace('</head>', $css . '</head>', $html_content);
        } else {
            $html_content = $css . $html_content;
        }
        
        // Adicionar informações do documento original
        if (!empty($original_url)) {
            $header = '<div style="margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">';
            $header .= '<strong>Documento Original:</strong> ' . htmlspecialchars($original_url);
            $header .= '<br><strong>Data de Captura:</strong> ' . date('d/m/Y H:i:s');
            $header .= '</div>';
            
            if (strpos($html_content, '<body>') !== false) {
                $html_content = str_replace('<body>', '<body>' . $header, $html_content);
            } else {
                $html_content = $header . $html_content;
            }
        }
        
        return $html_content;
    }
    
    /**
     * create_simple_html_pdf
     * Cria um PDF simples com conteúdo HTML usando biblioteca básica
     */
    private function create_simple_html_pdf($html_content, $pdf_path, $original_url = '')
    {
        try {
            // Usar a biblioteca TCPDF se disponível no Perfex
            if (class_exists('TCPDF')) {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator('Perfex CRM - Workshop Module');
                $pdf->SetTitle('Documento HTML');
                $pdf->SetMargins(15, 15, 15);
                $pdf->AddPage();
                $pdf->SetFont('helvetica', '', 10);
                
                // Adicionar cabeçalho com informações do documento
                if (!empty($original_url)) {
                    $pdf->writeHTML('<h3>Documento Capturado</h3>');
                    $pdf->writeHTML('<p><strong>URL Original:</strong> ' . htmlspecialchars($original_url) . '</p>');
                    $pdf->writeHTML('<p><strong>Data de Captura:</strong> ' . date('d/m/Y H:i:s') . '</p>');
                    $pdf->writeHTML('<hr>');
                }
                
                // Limpar e processar HTML
                $clean_html = strip_tags($html_content, '<p><br><div><span><strong><b><em><i><u><table><tr><td><th><h1><h2><h3><h4><h5><h6>');
                $pdf->writeHTML($clean_html);
                
                $pdf->Output($pdf_path, 'F');
                
                return file_exists($pdf_path) ? $pdf_path : false;
            }
            
            // Fallback: salvar como arquivo de texto com extensão PDF
            $content = "DOCUMENTO HTML CAPTURADO\n\n";
            if (!empty($original_url)) {
                $content .= "URL Original: $original_url\n";
                $content .= "Data de Captura: " . date('d/m/Y H:i:s') . "\n\n";
            }
            $content .= strip_tags($html_content);
            
            return file_put_contents($pdf_path, $content) ? $pdf_path : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * convert_xml_to_pdf
     * Converte arquivo XML para PDF
     */
    private function convert_xml_to_pdf($xml_path, $temp_dir)
    {
        try {
            // Ler conteúdo XML
            $xml_content = file_get_contents($xml_path);
            
            // Criar HTML básico para conversão
            $html = $this->xml_to_html($xml_content);
            
            // Gerar PDF usando a biblioteca do Perfex (se disponível)
            $pdf_filename = 'converted_' . uniqid() . '.pdf';
            $pdf_path = $temp_dir . $pdf_filename;
            
            // Tentar usar wkhtmltopdf se disponível
            if ($this->is_wkhtmltopdf_available()) {
                $html_file = $temp_dir . 'temp_' . uniqid() . '.html';
                file_put_contents($html_file, $html);
                
                $command = "wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in --encoding UTF-8 --quiet \"$html_file\" \"$pdf_path\"";
                exec($command, $output, $return_code);
                
                unlink($html_file);
                
                if ($return_code === 0 && file_exists($pdf_path)) {
                    return $pdf_path;
                }
            }
            
            // Fallback: criar PDF simples com o conteúdo XML
            return $this->create_simple_xml_pdf($xml_content, $pdf_path);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * xml_to_html
     * Converte XML para HTML básico
     */
    private function xml_to_html($xml_content)
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nota Fiscal</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:12px;margin:20px;}pre{white-space:pre-wrap;word-wrap:break-word;}</style>';
        $html .= '</head><body>';
        $html .= '<h2>Documento XML - Nota Fiscal</h2>';
        $html .= '<pre>' . htmlspecialchars($xml_content) . '</pre>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * is_wkhtmltopdf_available
     * Verifica se wkhtmltopdf está disponível
     */
    private function is_wkhtmltopdf_available()
    {
        exec('wkhtmltopdf --version 2>&1', $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * create_simple_xml_pdf
     * Cria um PDF simples com conteúdo XML usando biblioteca básica
     */
    private function create_simple_xml_pdf($xml_content, $pdf_path)
    {
        try {
            // Usar a biblioteca TCPDF se disponível no Perfex
            if (class_exists('TCPDF')) {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator('Perfex CRM - Workshop Module');
                $pdf->SetTitle('Nota Fiscal XML');
                $pdf->SetMargins(15, 15, 15);
                $pdf->AddPage();
                $pdf->SetFont('helvetica', '', 10);
                $pdf->writeHTML('<h2>Documento XML - Nota Fiscal</h2><pre>' . htmlspecialchars($xml_content) . '</pre>');
                $pdf->Output($pdf_path, 'F');
                
                return file_exists($pdf_path) ? $pdf_path : false;
            }
            
            // Fallback: salvar como arquivo de texto com extensão PDF
            $content = "DOCUMENTO XML - NOTA FISCAL\n\n" . $xml_content;
            return file_put_contents($pdf_path, $content) ? $pdf_path : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * combine_pdfs
     * Combina múltiplos PDFs em um único arquivo
     */
    private function combine_pdfs($pdf_files, $temp_dir)
    {
        try {
            $combined_filename = 'combined_documents_' . date('Y-m-d_H-i-s') . '.pdf';
            $combined_path = $temp_dir . $combined_filename;
            
            // Tentar usar pdftk se disponível
            if ($this->is_pdftk_available()) {
                $files_string = implode(' ', array_map('escapeshellarg', $pdf_files));
                $command = "pdftk $files_string cat output " . escapeshellarg($combined_path);
                exec($command, $output, $return_code);
                
                if ($return_code === 0 && file_exists($combined_path)) {
                    return $combined_path;
                }
            }
            
            // Fallback: usar ghostscript se disponível
            if ($this->is_ghostscript_available()) {
                $files_string = implode(' ', array_map('escapeshellarg', $pdf_files));
                $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=" . escapeshellarg($combined_path) . " $files_string";
                exec($command, $output, $return_code);
                
                if ($return_code === 0 && file_exists($combined_path)) {
                    return $combined_path;
                }
            }
            
            // Fallback final: concatenar arquivos (não é um PDF válido, mas funcional)
            return $this->simple_pdf_combine($pdf_files, $combined_path);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * is_pdftk_available
     * Verifica se pdftk está disponível
     */
    private function is_pdftk_available()
    {
        exec('pdftk --version 2>&1', $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * is_ghostscript_available
     * Verifica se ghostscript está disponível
     */
    private function is_ghostscript_available()
    {
        exec('gs --version 2>&1', $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * simple_pdf_combine
     * Combina PDFs de forma simples (fallback)
     */
    private function simple_pdf_combine($pdf_files, $output_path)
    {
        try {
            // Se só há um arquivo, apenas copie
            if (count($pdf_files) === 1) {
                return copy($pdf_files[0], $output_path) ? $output_path : false;
            }
            
            // Para múltiplos arquivos, criar um arquivo ZIP como fallback
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                $zip_path = str_replace('.pdf', '.zip', $output_path);
                
                if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
                    foreach ($pdf_files as $index => $file) {
                        $zip->addFile($file, 'documento_' . ($index + 1) . '.pdf');
                    }
                    $zip->close();
                    
                    return file_exists($zip_path) ? $zip_path : false;
                }
            }
            
            // Último recurso: copiar o primeiro arquivo
            return copy($pdf_files[0], $output_path) ? $output_path : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * cleanup_temp_files
     * Remove arquivos temporários
     */
    private function cleanup_temp_files($temp_dir)
    {
        try {
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($temp_dir);
            }
        } catch (Exception $e) {
            // Ignorar erros de limpeza
        }
    }
    
    /**
     * download_combined_pdf
     * Permite download do PDF combinado
     */
    public function download_combined_pdf($temp_id, $filename)
    {
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'view') && !is_admin()) {
            show_404();
        }
        
        $module_path = dirname(__DIR__) . '/uploads/temp_pdfs/';
        $temp_dir = $module_path . $temp_id . '/';
        $file_path = $temp_dir . $filename;
        
        if (!file_exists($file_path)) {
            show_404();
        }
        
        // Determinar tipo de conteúdo
        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
        $content_type = $file_extension === 'zip' ? 'application/zip' : 'application/pdf';
        
        // Headers para download
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Enviar arquivo
        readfile($file_path);
        
        // Agendar limpeza dos arquivos temporários (após 1 hora)
        $this->schedule_cleanup($temp_dir);
        
        exit;
    }
    
    /**
     * schedule_cleanup
     * Agenda limpeza de arquivos temporários
     */
    private function schedule_cleanup($temp_dir)
    {
        // Criar arquivo de marcação para limpeza posterior
        $cleanup_file = $temp_dir . '.cleanup_' . (time() + 3600); // 1 hora
        touch($cleanup_file);
    }
    
    /**
     * cleanup_old_temp_files
     * Remove arquivos temporários antigos (pode ser chamado via cron)
     */
    public function cleanup_old_temp_files()
    {
        if (!is_admin()) {
            show_404();
        }
        
        $temp_base_dir = dirname(__DIR__) . '/uploads/temp_pdfs/';
        
        if (!is_dir($temp_base_dir)) {
            return;
        }
        
        $dirs = glob($temp_base_dir . '*', GLOB_ONLYDIR);
        $current_time = time();
        
        foreach ($dirs as $dir) {
            $cleanup_files = glob($dir . '/.cleanup_*');
            
            foreach ($cleanup_files as $cleanup_file) {
                $cleanup_time = (int) str_replace($dir . '/.cleanup_', '', $cleanup_file);
                
                if ($current_time >= $cleanup_time) {
                    $this->cleanup_temp_files($dir);
                    break;
                }
            }
        }
        
        echo "Limpeza de arquivos temporários concluída.";
    }

    /**
     * delete_invoice_row
     * Remove completamente uma linha de fatura
     */
    public function delete_invoice_row()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }
        
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'delete') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }
        
        $invoice_id = $this->input->post('invoice_id');
        
        if (empty($invoice_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_id_required')
            ]);
            return;
        }
        
        $result = $this->workshop_model->delete_invoice_row($invoice_id);
        
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_invoice_row_deleted_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_row_delete_failed')
            ]);
        }
    }

    /**
     * save_asaas_data
     * Salva ou atualiza dados do Asaas para uma fatura
     */
    public function save_asaas_data()
    {
        // Verificar se é uma requisição POST
        if (!$this->input->post()) {
            show_404();
        }

        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'edit') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }

        $invoice_id = $this->input->post('invoice_id');
        $bank_slip_url = $this->input->post('bank_slip_url');
        $xml_url = $this->input->post('xml_url');

        if (empty($invoice_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_id_required')
            ]);
            return;
        }

        // Validar URLs se fornecidas
        if (!empty($bank_slip_url) && !filter_var($bank_slip_url, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invalid_bank_slip_url')
            ]);
            return;
        }

        if (!empty($xml_url) && !filter_var($xml_url, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invalid_xml_url')
            ]);
            return;
        }

        $result = $this->workshop_model->save_asaas_data($invoice_id, $bank_slip_url, $xml_url);

        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('wshop_asaas_data_saved_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_asaas_data_save_failed')
            ]);
        }
    }

    /**
     * test_download
     * Método de teste para verificar download de arquivos
     */
    public function test_download()
    {
        if (!is_admin()) {
            show_404();
        }
        
        $test_url = $this->input->get('url');
        if (empty($test_url)) {
            echo "Uso: /workshop/test_download?url=URL_PARA_TESTAR";
            return;
        }
        
        error_log('Workshop: TESTE - Iniciando teste de download para: ' . $test_url);
        
        // Criar pasta temporária de teste
        $module_path = dirname(__DIR__) . '/uploads/temp_pdfs/';
        $temp_dir = $module_path . 'test_' . uniqid() . '/';
        
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $result = $this->download_and_process_file($test_url, $temp_dir, 'test', 'TEST');
        
        if ($result) {
            echo "<h3>SUCESSO!</h3>";
            echo "<p>Arquivo baixado e processado com sucesso:</p>";
            echo "<ul>";
            echo "<li>Caminho: " . $result['path'] . "</li>";
            echo "<li>Tipo: " . $result['type'] . "</li>";
            echo "<li>Tipo original: " . $result['original_type'] . "</li>";
            echo "<li>Arquivo existe: " . (file_exists($result['path']) ? 'SIM' : 'NÃO') . "</li>";
            if (file_exists($result['path'])) {
                echo "<li>Tamanho: " . filesize($result['path']) . " bytes</li>";
            }
            echo "</ul>";
        } else {
            echo "<h3>FALHA!</h3>";
            echo "<p>Não foi possível baixar ou processar o arquivo.</p>";
        }
        
        echo "<p><strong>Verifique os logs do PHP para mais detalhes.</strong></p>";
        
        // Limpar pasta de teste
        if (is_dir($temp_dir)) {
            $this->cleanup_temp_files($temp_dir);
        }
    }
    
    /**
     * get_asaas_data
     * Obtém dados do Asaas para uma fatura
     */
    public function get_asaas_data($invoice_id)
    {
        // Verificar permissões
        if (!has_permission('workshop_nota_fiscal', '', 'view') && !is_admin()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            return;
        }

        if (empty($invoice_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => _l('wshop_invoice_id_required')
            ]);
            return;
        }

        $asaas_data = $this->workshop_model->get_asaas_data($invoice_id);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $asaas_data
        ]);
    }

    /**
     * Get clients for select dropdown
     * @return json
     */
    // Página de gerenciamento de agendamentos
    public function appointments()
    {
        if (!has_permission('workshop_permission_dashboard', '', 'view')) {
            access_denied('workshop');
        }
        
        // Aplicar filtros
        $where = [];
        if ($this->input->get('date_from')) {
            $where['appointment_date >='] = $this->input->get('date_from');
        }
        if ($this->input->get('date_to')) {
            $where['appointment_date <='] = $this->input->get('date_to');
        }
        if ($this->input->get('status')) {
            $where['status'] = $this->input->get('status');
        }
        
        $data['appointments'] = $this->workshop_model->get_appointments(null, $where);
        $data['title'] = _l('wshop_appointments');
        $this->load->view('appointments/manage', $data);
    }
    
    // Criar novo agendamento
    public function new_appointment()
    {
        if (!has_permission('workshop_permission_dashboard', '', 'create')) {
            access_denied('workshop');
        }
        
        if ($this->input->post()) {
            $data = [
                'client_id' => $this->input->post('client_id'),
                'appointment_date' => $this->input->post('appointment_date'),
                'appointment_time' => $this->input->post('appointment_time'),
                'duration' => $this->input->post('duration'),
                'service_type' => $this->input->post('service_type'),
                'status' => 'scheduled',
                'notes' => $this->input->post('notes'),
                'created_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->workshop_model->add_appointment($data);
            
            if ($success) {
                set_alert('success', 'Agendamento criado com sucesso!');
            } else {
                set_alert('danger', 'Erro ao criar agendamento');
            }
            
            redirect(admin_url('workshop/appointments'));
        }
        
        $this->load->model('clients_model');
        $data['clients'] = $this->clients_model->get();
        $data['title'] = _l('wshop_new_appointment');
        $this->load->view('appointments/new', $data);
    }
    
    // Editar agendamento
    public function edit_appointment($id)
    {
        if (!has_permission('workshop_permission_dashboard', '', 'edit')) {
            access_denied('workshop');
        }
        
        $appointment = $this->workshop_model->get_appointments($id);
        if (!$appointment) {
            show_404();
        }
        
        if ($this->input->post()) {
            $data = [
                'client_id' => $this->input->post('client_id'),
                'appointment_date' => $this->input->post('appointment_date'),
                'appointment_time' => $this->input->post('appointment_time'),
                'duration' => $this->input->post('duration'),
                'service_type' => $this->input->post('service_type'),
                'status' => $this->input->post('status'),
                'notes' => $this->input->post('notes')
            ];
            
            $success = $this->workshop_model->update_appointment($id, $data);
            
            if ($success) {
                set_alert('success', 'Agendamento atualizado com sucesso!');
            } else {
                set_alert('danger', 'Erro ao atualizar agendamento');
            }
            
            redirect(admin_url('workshop/appointments'));
        }
        
        $this->load->model('clients_model');
        $data['appointment'] = $appointment;
        $data['clients'] = $this->clients_model->get();
        $data['title'] = _l('wshop_edit_appointment');
        $this->load->view('appointments/edit', $data);
    }
    
    // Deletar agendamento
    public function delete_appointment($id)
    {
        if (!has_permission('workshop_permission_dashboard', '', 'delete')) {
            access_denied('workshop');
        }
        
        $success = $this->workshop_model->delete_appointment($id);
        
        if ($success) {
            set_alert('success', 'Agendamento excluído com sucesso!');
        } else {
            set_alert('danger', 'Erro ao excluir agendamento');
        }
        
        redirect(admin_url('workshop/appointments'));
    }

    public function get_clients_for_select()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->load->model('clients_model');
        
        // Buscar todos os clientes ativos
        $this->db->select('userid, company, CONCAT(COALESCE(company, ""), " - ", COALESCE(vat, "")) as display_name');
        $this->db->where('active', 1);
        $this->db->order_by('company', 'ASC');
        $clients = $this->db->get(db_prefix() . 'clients')->result_array();
        
        // Formatar dados para o select
        $formatted_clients = [];
        foreach ($clients as $client) {
            $formatted_clients[] = [
                'userid' => $client['userid'],
                'company' => !empty($client['company']) ? $client['company'] : 'Cliente #' . $client['userid'],
                'display_name' => $client['display_name']
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($formatted_clients);
    }

    /**
     * Página de chamados faturados
     */
    public function invoiced_jobs()
    {
        if (!has_permission('workshop', '', 'view')) {
            access_denied('workshop');
        }

        // Carregar dados para os filtros
        $mechanic_role_id = $this->workshop_model->mechanic_role_exists();
        $data['mechanics'] = $this->staff_model->get('', ['role' => $mechanic_role_id]);

        // Calcular totais de comissão
        $commission_from_date = $this->input->get('commission_from_date');
        $commission_to_date = $this->input->get('commission_to_date');
        $mechanic_filter = $this->input->get('mechanic_filter');

        $commission_data = $this->workshop_model->get_commission_data(
            $commission_from_date,
            $commission_to_date,
            $mechanic_filter
        );

        $data['total_commission_day'] = $commission_data['total_commission_day'];
        $data['total_commission_week'] = $commission_data['total_commission_week'];
        $data['total_commission_month'] = $commission_data['total_commission_month'];
        $data['total_commission_all'] = $commission_data['total_commission_all'];

        $data['title'] = _l('wshop_invoiced_repair_jobs');
        $this->load->view('repair_jobs/invoiced_jobs', $data);
    }
    
    /**
     * Versão simplificada da página de chamados faturados
     * Esta função usa a mesma abordagem que chamados_faturados
     */
    public function invoiced_jobs_simple()
    {
        if (!has_permission('workshop', '', 'view')) {
            access_denied('workshop');
        }

        // Buscar dados diretamente do banco de dados
        $data['chamados_faturados'] = $this->workshop_model->get_chamados_faturados_simples();
        
        // Carregar os status para exibição
        $data['statuses'] = $this->workshop_model->get_repair_job_statuses();
        
        $data['title'] = _l('wshop_invoiced_repair_jobs');
        // Carregar a mesma view simplificada que chamados_faturados
        $this->load->view('repair_jobs/chamados_faturados_simples', $data);
    }
    
    /**
     * Página de chamados faturados em português - versão simples
     * Esta função substitui a versão anterior que usava DataTables
     * Usa consulta direta ao banco de dados para listar os chamados faturados
     */
    public function chamados_faturados()
    {
        if (!has_permission('workshop', '', 'view')) {
            access_denied('workshop');
        }

        // Buscar dados diretamente do banco de dados
        $data['chamados_faturados'] = $this->workshop_model->get_chamados_faturados_simples();
        
        // Carregar os status para exibição
        $data['statuses'] = $this->workshop_model->get_repair_job_statuses();
        
        $data['title'] = _l('wshop_invoiced_repair_jobs');
        // Carregar a nova view simplificada
        $this->load->view('repair_jobs/chamados_faturados_simples', $data);
    }

    // A função invoiced_repair_job_table foi removida pois foi substituída por uma consulta direta ao banco de dados
    
    // A função chamados_faturados_table foi removida pois foi substituída por uma consulta direta ao banco de dados

    /**
     * Obter dados para o gráfico de chamados faturados
     */
    public function get_invoiced_jobs_chart_data()
    {
        if (!has_permission('workshop', '', 'view')) {
            access_denied('workshop');
        }

        $commission_from_date = $this->input->post('commission_from_date');
        $commission_to_date = $this->input->post('commission_to_date');
        $mechanic_filter = $this->input->post('mechanic_filter');

        // Se não houver datas definidas, usar o mês atual
        if (empty($commission_from_date) && empty($commission_to_date)) {
            $commission_from_date = date('Y-m-01'); // Primeiro dia do mês atual
            $commission_to_date = date('Y-m-t'); // Último dia do mês atual
        }

        // Obter dados para o gráfico
        $chart_data = $this->workshop_model->get_invoiced_jobs_chart_data(
            $commission_from_date,
            $commission_to_date,
            $mechanic_filter
        );

        echo json_encode([
            'success' => true,
            'chart_data' => $chart_data
        ]);
    }

    // Gerenciamento de Filtros Personalizados


    // Métodos de filtros personalizados removidos

}
/* end of file */
