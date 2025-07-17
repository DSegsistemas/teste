<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mechanic Commission model
 */
#[\AllowDynamicProperties]
class Mechanic_commission_model extends App_Model
{
    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get mechanic commissions history
     * @param int $mechanic_id Mechanic staff ID
     * @param array $filters Optional filters (date_from, date_to, year, month, day)
     * @return array
     */
    public function get_mechanic_commissions($mechanic_id, $filters = [])
    {
        $this->db->select('rj.id, rj.job_number, rj.job_name, rj.date, rj.datecreated as datefinished, rj.total, rj.commission_percentage, rj.commission_amount, c.company');
        $this->db->from(db_prefix() . 'wshop_repair_jobs rj');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = rj.clientid', 'left');
        $this->db->where('rj.sale_agent', $mechanic_id);
        $this->db->where('rj.status', 'Completed');
        
        // Apply date filters if provided
        if (!empty($filters['date_from'])) {
            $this->db->where('rj.datecreated >=', to_sql_date($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('rj.datecreated <=', to_sql_date($filters['date_to']));
        }
        
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(rj.datecreated)', $filters['year']);
        }
        
        if (!empty($filters['month'])) {
            $this->db->where('MONTH(rj.datecreated)', $filters['month']);
        }
        
        if (!empty($filters['day'])) {
            $this->db->where('DAY(rj.datecreated)', $filters['day']);
        }
        
        $this->db->order_by('rj.datecreated', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get monthly commission totals for a mechanic
     * @param int $mechanic_id Mechanic staff ID
     * @param int $year Year to get data for (defaults to current year)
     * @return array Monthly totals indexed by month number
     */
    public function get_monthly_commission_totals($mechanic_id, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }
        
        $this->db->select('MONTH(datecreated) as month, SUM(commission_amount) as total');
        $this->db->from(db_prefix() . 'wshop_repair_jobs');
        $this->db->where('sale_agent', $mechanic_id);
        $this->db->where('status', 'Completed');
        $this->db->where('YEAR(datecreated)', $year);
        $this->db->group_by('MONTH(datecreated)');
        
        $results = $this->db->get()->result_array();
        
        // Initialize all months with zero
        $monthly_totals = array_fill(1, 12, 0);
        
        // Fill in actual values
        foreach ($results as $row) {
            $monthly_totals[(int)$row['month']] = (float)$row['total'];
        }
        
        return $monthly_totals;
    }

    /**
     * Get daily commission data for calendar view
     * @param int $mechanic_id Mechanic staff ID
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return array
     */
    public function get_calendar_commission_data($mechanic_id, $start_date, $end_date)
    {
        $this->db->select('rj.id, rj.job_number, rj.job_name, rj.datecreated as date, rj.commission_amount, c.company');
        $this->db->from(db_prefix() . 'wshop_repair_jobs rj');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = rj.clientid', 'left');
        $this->db->where('rj.sale_agent', $mechanic_id);
        $this->db->where('rj.status', 'Completed');
        $this->db->where('rj.datecreated >=', $start_date);
        $this->db->where('rj.datecreated <=', $end_date);
        
        return $this->db->get()->result_array();
    }

    /**
     * Get current month commission total for a mechanic
     * @param int $mechanic_id Mechanic staff ID
     * @return float Total commission amount for current month
     */
    public function get_current_month_commission($mechanic_id)
    {
        $this->db->select('SUM(commission_amount) as total');
        $this->db->from(db_prefix() . 'wshop_repair_jobs');
        $this->db->where('sale_agent', $mechanic_id);
        $this->db->where('status', 'Completed');
        $this->db->where('MONTH(datecreated)', date('m'));
        $this->db->where('YEAR(datecreated)', date('Y'));
        
        $result = $this->db->get()->row();
        return $result ? (float)$result->total : 0;
    }

    /**
     * Sincronizar dados de comissão da tabela repair_jobs para a nova tabela mechanic_commissions
     * @return bool
     */
    public function sync_commission_data()
    {
        // Primeiro, limpar a tabela de comissões
        $this->db->truncate(db_prefix() . 'wshop_mechanic_commissions');
        
        // Buscar todos os repair jobs com comissão
        $this->db->select('id, sale_agent as tecnico, client_id as cliente, appointment_date as data, commission_amount as comissao, datecreated, staffid');
        $this->db->from(db_prefix() . 'wshop_repair_jobs');
        $this->db->where('commission_amount >', 0);
        $this->db->where('sale_agent >', 0);
        $repair_jobs = $this->db->get()->result_array();
        
        if (!empty($repair_jobs)) {
            foreach ($repair_jobs as $job) {
                $commission_data = [
                    'tecnico' => $job['tecnico'],
                    'cliente' => $job['cliente'],
                    'data' => $job['data'],
                    'comissao' => $job['comissao'],
                    'repair_job_id' => $job['id'],
                    'datecreated' => $job['data'], // Usar data do atendimento
                    'staffid' => $job['staffid']
                ];
                
                $this->db->insert(db_prefix() . 'wshop_mechanic_commissions', $commission_data);
            }
        }
        
        return true;
    }

    /**
     * Adicionar nova comissão quando um repair job é criado/atualizado
     * @param array $repair_job_data Dados do repair job
     * @return bool
     */
    public function add_commission_record($repair_job_data)
    {
        if (empty($repair_job_data['commission_amount']) || $repair_job_data['commission_amount'] <= 0) {
            return false;
        }
        
        // Verificar se já existe um registro para este repair job
        $this->db->where('repair_job_id', $repair_job_data['id']);
        $existing = $this->db->get(db_prefix() . 'wshop_mechanic_commissions')->row();
        
        $commission_data = [
            'tecnico' => $repair_job_data['sale_agent'],
            'cliente' => $repair_job_data['client_id'],
            'data' => $repair_job_data['appointment_date'],
            'comissao' => $repair_job_data['commission_amount'],
            'repair_job_id' => $repair_job_data['id'],
            'datecreated' => $repair_job_data['appointment_date'], // Usar data do atendimento
            'staffid' => get_staff_user_id()
        ];
        
        if ($existing) {
            // Atualizar registro existente
            $this->db->where('repair_job_id', $repair_job_data['id']);
            return $this->db->update(db_prefix() . 'wshop_mechanic_commissions', $commission_data);
        } else {
            // Inserir novo registro
            return $this->db->insert(db_prefix() . 'wshop_mechanic_commissions', $commission_data);
        }
    }

    /**
     * Obter dados para os cards de comissão - Updated 2024-12-19: Adicionado suporte para filtros de período
     * @param array $filters Filtros opcionais (mechanic_filter, period_filter, start_date, end_date)
     * @return array
     */
    public function get_commission_cards_data($filters = [])
    {
        // Definir datas baseadas no filtro de período se fornecido
        $use_period_filter = !empty($filters['period_filter']);
        
        if ($use_period_filter) {
            // Quando um período específico é selecionado, todos os cards mostram dados desse período
            switch ($filters['period_filter']) {
                case 'day':
                    $filter_start = date('Y-m-d');
                    $filter_end = date('Y-m-d');
                    break;
                case 'week':
                    $filter_start = date('Y-m-d', strtotime('monday this week'));
                    $filter_end = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'month':
                    $filter_start = date('Y-m-01');
                    $filter_end = date('Y-m-t');
                    break;
                case 'last_month':
                    $filter_start = date('Y-m-01', strtotime('first day of last month'));
                    $filter_end = date('Y-m-t', strtotime('last day of last month'));
                    break;
                case 'last_year':
                    $filter_start = date('Y-01-01', strtotime('last year'));
                    $filter_end = date('Y-12-31', strtotime('last year'));
                    break;
                case 'all':
                    $filter_start = null;
                    $filter_end = null;
                    break;
                default:
                    $filter_start = date('Y-m-01');
                    $filter_end = date('Y-m-t');
                    break;
            }
        }
        
        // Definir datas padrão para quando não há filtro de período
        $today = date('Y-m-d');
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));
        $start_of_month = date('Y-m-01');
        $end_of_month = date('Y-m-t');
        
        $data = [
            'daily' => 0,
            'weekly' => 0,
            'monthly' => 0,
            'total' => 0
        ];
        
        try {
            // Verificar se as tabelas existem
            $commissions_table = db_prefix() . 'wshop_mechanic_commissions';
            $repair_jobs_table = db_prefix() . 'wshop_repair_jobs';
            $invoices_table = db_prefix() . 'invoices';
            
            if (!$this->db->table_exists($commissions_table)) {
                log_activity('Workshop: Tabela wshop_mechanic_commissions não existe');
                return $data;
            }
            
            // Verificar se a coluna commission_amount existe na tabela repair_jobs
            if (!$this->db->field_exists('commission_amount', $repair_jobs_table)) {
                log_activity('Workshop: Coluna commission_amount não existe na tabela wshop_repair_jobs');
                return $data;
            }
            
            // Limpar cache do query builder
            $this->db->reset_query();
            
            // Preparar filtros
            $mechanic_filter = '';
            $mechanic_params = [];
            
            if (!empty($filters['mechanic_filter'])) {
                $mechanic_filter = ' AND rj.sale_agent = ?';
                $mechanic_params[] = (int)$filters['mechanic_filter'];
            }
            
            // Adicionar contagem de atendimentos do mês - Updated 2024-12-19
            $attendances_count = 0;
            $sql_attendances = "SELECT COUNT(*) as total
                               FROM {$repair_jobs_table} rj
                               WHERE DATE(rj.datecreated) >= ? AND DATE(rj.datecreated) <= ?
                               {$mechanic_filter}";
            
            $params_attendances = [$start_of_month, $end_of_month];
            $params_attendances = array_merge($params_attendances, $mechanic_params);
            
            $result = $this->db->query($sql_attendances, $params_attendances);
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $attendances_count = $row && $row->total ? (int)$row->total : 0;
            }
            
            $data['attendances_count'] = $attendances_count;
            
            if ($use_period_filter) {
                // Quando um período específico é selecionado, todos os cards mostram o mesmo valor
                $period_value = 0;
                
                if ($filter_start === null && $filter_end === null) {
                    // Caso 'all' - sem filtro de data
                    $sql_period = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                                  FROM {$repair_jobs_table} rj
                                  LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                                  WHERE rj.invoice_id IS NOT NULL AND i.status = 2
                                  {$mechanic_filter}";
                    $result = $this->db->query($sql_period, $mechanic_params);
                } else {
                    // Período específico
                    $params = [$filter_start, $filter_end];
                    $params = array_merge($params, $mechanic_params);
                    
                    $sql_period = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                                  FROM {$repair_jobs_table} rj
                                  LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                                  WHERE rj.invoice_id IS NOT NULL AND i.status = 2
                                  AND DATE(rj.datecreated) >= ? AND DATE(rj.datecreated) <= ?
                                  {$mechanic_filter}";
                    $result = $this->db->query($sql_period, $params);
                }
                
                if ($result && $result->num_rows() > 0) {
                    $row = $result->row();
                    $period_value = $row && $row->total ? (float)$row->total : 0;
                }
                
                // Todos os cards mostram o mesmo valor do período selecionado
                $data['daily'] = $period_value;
                $data['weekly'] = $period_value;
                $data['monthly'] = $period_value;
                
                // Total sempre inclui todas as comissões (pagas e não pagas) - Updated 2024-12-19
                $sql_total_all = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                                 FROM {$repair_jobs_table} rj
                                 WHERE rj.commission_amount > 0
                                 {$mechanic_filter}";
                
                $result_total = $this->db->query($sql_total_all, $mechanic_params);
                if ($result_total && $result_total->num_rows() > 0) {
                    $row_total = $result_total->row();
                    $data['total'] = $row_total && $row_total->total ? (float)$row_total->total : 0;
                } else {
                    $data['total'] = 0;
                }
                
            } else {
                // Comportamento padrão - cada card mostra seu período específico
                $params_daily = [$today];
                $params_weekly = [$start_of_week, $end_of_week];
                $params_monthly = [$start_of_month, $end_of_month];
                $params_total = [];
                
                $params_daily = array_merge($params_daily, $mechanic_params);
                $params_weekly = array_merge($params_weekly, $mechanic_params);
                $params_monthly = array_merge($params_monthly, $mechanic_params);
                $params_total = array_merge($params_total, $mechanic_params);
                
                // Comissão do dia
                $sql_daily = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                             FROM {$repair_jobs_table} rj
                             LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                             WHERE rj.invoice_id IS NOT NULL AND i.status = 2
                             AND DATE(rj.datecreated) = ?
                             {$mechanic_filter}";
                
                $result = $this->db->query($sql_daily, $params_daily);
                if ($result && $result->num_rows() > 0) {
                    $row = $result->row();
                    $data['daily'] = $row && $row->total ? (float)$row->total : 0;
                }
                
                // Comissão da semana
                $sql_weekly = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                              FROM {$repair_jobs_table} rj
                              LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                              WHERE rj.invoice_id IS NOT NULL AND i.status = 2
                              AND DATE(rj.datecreated) >= ? AND DATE(rj.datecreated) <= ?
                              {$mechanic_filter}";
                
                $result = $this->db->query($sql_weekly, $params_weekly);
                if ($result && $result->num_rows() > 0) {
                    $row = $result->row();
                    $data['weekly'] = $row && $row->total ? (float)$row->total : 0;
                }
                
                // Comissão do mês
                $sql_monthly = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                               FROM {$repair_jobs_table} rj
                               LEFT JOIN {$invoices_table} i ON i.id = rj.invoice_id
                               WHERE rj.invoice_id IS NOT NULL AND i.status = 2
                               AND DATE(rj.datecreated) >= ? AND DATE(rj.datecreated) <= ?
                               {$mechanic_filter}";
                
                $result = $this->db->query($sql_monthly, $params_monthly);
                if ($result && $result->num_rows() > 0) {
                    $row = $result->row();
                    $data['monthly'] = $row && $row->total ? (float)$row->total : 0;
                }
                
                // Total geral - Updated 2024-12-19: Incluir todas as comissões (pagas e não pagas)
                $sql_total = "SELECT SUM(COALESCE(rj.commission_amount, 0)) as total
                             FROM {$repair_jobs_table} rj
                             WHERE rj.commission_amount > 0
                             {$mechanic_filter}";
                
                $result = $this->db->query($sql_total, $params_total);
                if ($result && $result->num_rows() > 0) {
                    $row = $result->row();
                    $data['total'] = $row && $row->total ? (float)$row->total : 0;
                }
            }
            
        } catch (Exception $e) {
            log_activity('Workshop: Erro ao obter dados dos cards de comissão - ' . $e->getMessage());
        }
        
        return $data;
    }
}