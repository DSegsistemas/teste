<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Commission Calculator Helper for Workshop Module
 * 
 * Este arquivo contém métodos para calcular comissões automaticamente
 * quando um repair job é marcado como 'Completed'
 */
class Commission_calculator
{
    private $CI;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Calculate commission for repair job when status changes to 'Completed'
     * @param int $repair_job_id Repair job ID
     * @return bool
     */
    public function calculate_repair_job_commission($repair_job_id)
    {
        // Get repair job details with mechanic commission info
        $this->CI->db->select('rj.*, s.commission_percentage as staff_commission');
        $this->CI->db->from(db_prefix() . 'wshop_repair_jobs rj');
        $this->CI->db->join(db_prefix() . 'staff s', 's.staffid = rj.mechanic_id', 'left');
        $this->CI->db->where('rj.id', $repair_job_id);
        $repair_job = $this->CI->db->get()->row();
        
        if (!$repair_job) {
            log_activity('Commission Calculation Failed: Repair Job not found [ID: ' . $repair_job_id . ']');
            return false;
        }
        
        // Calculate total from labour and materials
        $total_amount = $this->calculate_repair_job_total($repair_job_id);
        
        // Determine commission percentage (priority order)
        $commission_percentage = $this->get_commission_percentage($repair_job);
        
        // Calculate commission amount
        $commission_amount = ($total_amount * $commission_percentage) / 100;
        
        // Update repair job with commission data
        $update_data = [
            'total' => $total_amount,
            'commission_percentage' => $commission_percentage,
            'commission_amount' => $commission_amount
        ];
        
        $this->CI->db->where('id', $repair_job_id);
        $this->CI->db->update(db_prefix() . 'wshop_repair_jobs', $update_data);
        
        if ($this->CI->db->affected_rows() > 0) {
            log_activity('Commission Calculated Successfully [Repair Job ID: ' . $repair_job_id . ', Total: ' . $total_amount . ', Commission: ' . $commission_amount . ' (' . $commission_percentage . '%)]');
            return true;
        }
        
        log_activity('Commission Calculation Failed: Database update failed [Repair Job ID: ' . $repair_job_id . ']');
        return false;
    }
    
    /**
     * Calculate total amount from labour and materials
     * @param int $repair_job_id
     * @return float
     */
    private function calculate_repair_job_total($repair_job_id)
    {
        $total_amount = 0;
        
        // Get labour total
        $this->CI->db->select('SUM((qty * rate) - COALESCE(discount_total, 0)) as labour_total');
        $this->CI->db->from(db_prefix() . 'wshop_repair_job_labour_materials');
        $this->CI->db->where('repair_job_id', $repair_job_id);
        $this->CI->db->where('type', 'labour');
        $labour_result = $this->CI->db->get()->row();
        $labour_total = $labour_result && $labour_result->labour_total ? (float)$labour_result->labour_total : 0;
        
        // Get materials total
        $this->CI->db->select('SUM((qty * rate) - COALESCE(discount_total, 0)) as materials_total');
        $this->CI->db->from(db_prefix() . 'wshop_repair_job_labour_materials');
        $this->CI->db->where('repair_job_id', $repair_job_id);
        $this->CI->db->where('type', 'material');
        $materials_result = $this->CI->db->get()->row();
        $materials_total = $materials_result && $materials_result->materials_total ? (float)$materials_result->materials_total : 0;
        
        $total_amount = $labour_total + $materials_total;
        
        log_activity('Repair Job Total Calculated [ID: ' . $repair_job_id . ', Labour: ' . $labour_total . ', Materials: ' . $materials_total . ', Total: ' . $total_amount . ']');
        
        return $total_amount;
    }
    
    /**
     * Get commission percentage based on priority:
     * 1. Repair job specific commission
     * 2. Staff/Mechanic commission
     * 3. Default system commission
     * @param object $repair_job
     * @return float
     */
    private function get_commission_percentage($repair_job)
    {
        // Priority 1: Repair job specific commission
        if (!empty($repair_job->commission_percentage) && $repair_job->commission_percentage > 0) {
            log_activity('Using repair job specific commission: ' . $repair_job->commission_percentage . '%');
            return (float)$repair_job->commission_percentage;
        }
        
        // Priority 2: Staff/Mechanic commission
        if (!empty($repair_job->staff_commission) && $repair_job->staff_commission > 0) {
            log_activity('Using staff commission: ' . $repair_job->staff_commission . '%');
            return (float)$repair_job->staff_commission;
        }
        
        // Priority 3: Default system commission
        $this->CI->db->select('percentage');
        $this->CI->db->from(db_prefix() . 'wshop_commissions');
        $this->CI->db->where('status', 1);
        $this->CI->db->order_by('id', 'ASC');
        $this->CI->db->limit(1);
        $default_commission = $this->CI->db->get()->row();
        
        if ($default_commission && $default_commission->percentage > 0) {
            log_activity('Using default system commission: ' . $default_commission->percentage . '%');
            return (float)$default_commission->percentage;
        }
        
        // No commission found
        log_activity('No commission percentage found, using 0%');
        return 0.0;
    }
    
    /**
     * Check if repair job should have commission calculated
     * @param string $status
     * @return bool
     */
    public function should_calculate_commission($status)
    {
        $commission_statuses = ['Completed', 'Finalised', 'Job_Complete'];
        return in_array($status, $commission_statuses);
    }
}