<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mechanic Commission Controller
 */
#[\AllowDynamicProperties]
class Mechanic_commission extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('workshop_model');
        $this->load->model('mechanic_commission_model');
    }

    /**
     * Exibe o dashboard de comissões do mecânico
     * @param int $mechanic_id ID do mecânico (opcional, usa o ID do usuário logado se for mecânico)
     */
    public function index($mechanic_id = null)
    {
        // Verifica permissões
        if (!has_permission('workshop_mechanic', '', 'view') && !is_admin()) {
            if (get_staff_user_id() != $mechanic_id) {
                access_denied('Mechanic Commission');
            }
        }

        // Se não for especificado um mecânico e o usuário for mecânico, usa o ID do usuário
        if ($mechanic_id === null && $this->workshop_model->mechanic_role_exists() == get_staff_user_id()) {
            $mechanic_id = get_staff_user_id();
        }

        if ($mechanic_id === null) {
            // Redireciona para a lista de mecânicos se nenhum mecânico for especificado
            redirect(admin_url('workshop/manage_mechanic'));
        }

        // Obtém dados do mecânico
        $this->db->where('staffid', $mechanic_id);
        $data['mechanic'] = $this->db->get(db_prefix() . 'staff')->row();

        if (!$data['mechanic']) {
            show_404();
        }

        // Obtém comissão do mês atual
        $data['current_month_commission'] = $this->mechanic_commission_model->get_current_month_commission($mechanic_id);

        // Obtém dados para o gráfico mensal
        $data['monthly_commissions'] = $this->mechanic_commission_model->get_monthly_commission_totals($mechanic_id);

        // Obtém histórico de comissões (últimos 30 dias por padrão)
        $filters = [
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d')
        ];
        $data['commission_history'] = $this->mechanic_commission_model->get_mechanic_commissions($mechanic_id, $filters);

        // Prepara dados para o calendário
        $data['calendar_data'] = $this->mechanic_commission_model->get_calendar_commission_data(
            $mechanic_id,
            date('Y-m-01'), // Primeiro dia do mês atual
            date('Y-m-t')  // Último dia do mês atual
        );

        $data['title'] = _l('wshop_mechanic_commissions') . ' - ' . $data['mechanic']->firstname . ' ' . $data['mechanic']->lastname;
        $data['mechanic_id'] = $mechanic_id;

        $this->load->view('mechanics/commissions/dashboard', $data);
    }

    /**
     * Exibe o histórico de comissões com filtros
     * @param int $mechanic_id ID do mecânico
     */
    public function history($mechanic_id)
    {
        // Verifica permissões
        if (!has_permission('workshop_mechanic', '', 'view') && !is_admin()) {
            if (get_staff_user_id() != $mechanic_id) {
                access_denied('Mechanic Commission');
            }
        }

        // Obtém dados do mecânico
        $this->db->where('staffid', $mechanic_id);
        $data['mechanic'] = $this->db->get(db_prefix() . 'staff')->row();

        if (!$data['mechanic']) {
            show_404();
        }

        // Hook antes do filtro de histórico de comissões
        hooks()->do_action('before_repair_job_filter');
        
        // Processa filtros
        $filters = [];
        
        if ($this->input->post('date_from')) {
            $filters['date_from'] = $this->input->post('date_from');
        }
        
        if ($this->input->post('date_to')) {
            $filters['date_to'] = $this->input->post('date_to');
        }
        
        if ($this->input->post('year')) {
            $filters['year'] = $this->input->post('year');
        }
        
        if ($this->input->post('month')) {
            $filters['month'] = $this->input->post('month');
        }
        
        if ($this->input->post('day')) {
            $filters['day'] = $this->input->post('day');
        }

        // Se não houver filtros, usa os últimos 30 dias por padrão
        if (empty($filters)) {
            $filters = [
                'date_from' => date('Y-m-d', strtotime('-30 days')),
                'date_to' => date('Y-m-d')
            ];
        }

        $data['commission_history'] = $this->mechanic_commission_model->get_mechanic_commissions($mechanic_id, $filters);
        
        // Hook após o filtro de histórico de comissões
        hooks()->do_action('after_repair_job_filter', $data);
        $data['filters'] = $filters;
        $data['title'] = _l('wshop_mechanic_commission_history') . ' - ' . $data['mechanic']->firstname . ' ' . $data['mechanic']->lastname;
        $data['mechanic_id'] = $mechanic_id;

        $this->load->view('mechanics/commissions/history', $data);
    }

    /**
     * Exibe o calendário de comissões
     * @param int $mechanic_id ID do mecânico
     */
    public function calendar($mechanic_id)
    {
        // Verifica permissões
        if (!has_permission('workshop_mechanic', '', 'view') && !is_admin()) {
            if (get_staff_user_id() != $mechanic_id) {
                access_denied('Mechanic Commission');
            }
        }

        // Obtém dados do mecânico
        $this->db->where('staffid', $mechanic_id);
        $data['mechanic'] = $this->db->get(db_prefix() . 'staff')->row();

        if (!$data['mechanic']) {
            show_404();
        }

        // Obtém mês e ano do calendário (padrão: mês atual)
        $month = $this->input->get('month') ? $this->input->get('month') : date('m');
        $year = $this->input->get('year') ? $this->input->get('year') : date('Y');

        // Calcula primeiro e último dia do mês
        $start_date = $year . '-' . $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Hook antes do carregamento do calendário de comissões
        hooks()->do_action('before_repair_job_filter');
        
        // Obtém dados para o calendário
        $data['calendar_data'] = $this->mechanic_commission_model->get_calendar_commission_data(
            $mechanic_id,
            $start_date,
            $end_date
        );
        
        // Hook após o carregamento do calendário de comissões
        hooks()->do_action('after_repair_job_filter', $data);

        $data['month'] = $month;
        $data['year'] = $year;
        $data['title'] = _l('wshop_mechanic_commission_calendar') . ' - ' . $data['mechanic']->firstname . ' ' . $data['mechanic']->lastname;
        $data['mechanic_id'] = $mechanic_id;

        $this->load->view('mechanics/commissions/calendar', $data);
    }

    /**
     * Gera relatório de comissões para impressão
     * @param int $mechanic_id ID do mecânico
     */
    public function print_report($mechanic_id)
    {
        // Verifica permissões
        if (!has_permission('workshop_mechanic', '', 'view') && !is_admin()) {
            if (get_staff_user_id() != $mechanic_id) {
                access_denied('Mechanic Commission');
            }
        }

        // Obtém dados do mecânico
        $this->db->where('staffid', $mechanic_id);
        $data['mechanic'] = $this->db->get(db_prefix() . 'staff')->row();

        if (!$data['mechanic']) {
            show_404();
        }

        // Processa filtros
        $filters = [];
        
        if ($this->input->get('date_from')) {
            $filters['date_from'] = $this->input->get('date_from');
        }
        
        if ($this->input->get('date_to')) {
            $filters['date_to'] = $this->input->get('date_to');
        }
        
        if ($this->input->get('year')) {
            $filters['year'] = $this->input->get('year');
        }
        
        if ($this->input->get('month')) {
            $filters['month'] = $this->input->get('month');
        }
        
        if ($this->input->get('day')) {
            $filters['day'] = $this->input->get('day');
        }

        // Se não houver filtros, usa o mês atual por padrão
        if (empty($filters)) {
            $filters = [
                'month' => date('m'),
                'year' => date('Y')
            ];
        }

        // Obtém dados da empresa
        $this->load->model('settings_model');
        $data['company'] = $this->settings_model->get_company_info();

        $data['commission_history'] = $this->mechanic_commission_model->get_mechanic_commissions($mechanic_id, $filters);
        $data['filters'] = $filters;
        $data['title'] = _l('wshop_mechanic_commission_report');

        // Calcula total
        $total = 0;
        foreach ($data['commission_history'] as $commission) {
            $total += $commission['commission_amount'];
        }
        $data['total_commission'] = $total;

        // Carrega a view de impressão
        $this->load->view('mechanics/commissions/print_report', $data);
    }

    /**
     * Obtém dados de comissões para o calendário via AJAX
     * @param int $mechanic_id ID do mecânico
     */
    public function get_calendar_data($mechanic_id)
    {
        // Verifica permissões
        if (!has_permission('workshop_mechanic', '', 'view') && !is_admin()) {
            if (get_staff_user_id() != $mechanic_id) {
                echo json_encode([]);
                die();
            }
        }

        $start = $this->input->get('start');
        $end = $this->input->get('end');

        $calendar_data = $this->mechanic_commission_model->get_calendar_commission_data(
            $mechanic_id,
            $start,
            $end
        );

        $events = [];

        foreach ($calendar_data as $commission) {
            $events[] = [
                'title' => $commission['job_name'] . ' - ' . app_format_money($commission['commission_amount'], get_base_currency()),
                'start' => $commission['date'],
                'end' => $commission['date'],
                'description' => _l('wshop_client') . ': ' . $commission['company'],
                'url' => admin_url('workshop/repair_job_detail/' . $commission['id']),
                'backgroundColor' => '#28B8DA',
                'borderColor' => '#28B8DA'
            ];
        }

        echo json_encode($events);
        die();
    }
}