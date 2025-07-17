<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Iss_retention_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obter todas as taxas de retenção de ISS
     * @param array $where
     * @return array
     */
    public function get($where = [])
    {
        if (is_numeric($where)) {
            $this->db->where('id', $where);
            return $this->db->get(db_prefix() . 'wshop_iss_retention_rates')->row_array();
        }

        if (is_array($where) && count($where) > 0) {
            $this->db->where($where);
        }

        $this->db->where('status', 1);
        $this->db->order_by('name', 'ASC');
        
        return $this->db->get(db_prefix() . 'wshop_iss_retention_rates')->result_array();
    }

    /**
     * Adicionar nova taxa de retenção
     * @param array $data
     * @return int
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['staffid'] = get_staff_user_id();
        $data['status'] = 1;
        
        // Validar se já existe uma taxa com o mesmo nome
        $existing = $this->db->where('name', $data['name'])
                            ->where('status', 1)
                            ->get(db_prefix() . 'wshop_iss_retention_rates')
                            ->row();
        
        if ($existing) {
            return false;
        }
        
        $this->db->insert(db_prefix() . 'wshop_iss_retention_rates', $data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            log_activity('Nova taxa de retenção ISS adicionada [ID: ' . $insert_id . ', Nome: ' . $data['name'] . ']');
        }
        
        return $insert_id;
    }

    /**
     * Atualizar taxa de retenção
     * @param array $data
     * @param int $id
     * @return boolean
     */
    public function update($data, $id)
    {
        // Validar se já existe uma taxa com o mesmo nome (exceto a atual)
        $existing = $this->db->where('name', $data['name'])
                            ->where('id !=', $id)
                            ->where('status', 1)
                            ->get(db_prefix() . 'wshop_iss_retention_rates')
                            ->row();
        
        if ($existing) {
            return false;
        }
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'wshop_iss_retention_rates', $data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Taxa de retenção ISS atualizada [ID: ' . $id . ', Nome: ' . $data['name'] . ']');
            return true;
        }
        
        return false;
    }

    /**
     * Excluir taxa de retenção (soft delete)
     * @param int $id
     * @return boolean
     */
    public function delete($id)
    {
        // Verificar se a taxa está sendo usada em algum repair job
        $this->db->where('iss_retention_rate_id', $id);
        $used_in_jobs = $this->db->get(db_prefix() . 'wshop_repair_jobs')->num_rows();
        
        if ($used_in_jobs > 0) {
            return 'used_in_jobs';
        }
        
        // Soft delete - apenas marcar como inativo
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'wshop_iss_retention_rates', ['status' => 0]);
        
        if ($this->db->affected_rows() > 0) {
            $rate = $this->get($id);
            log_activity('Taxa de retenção ISS excluída [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }

    /**
     * Obter taxas ativas para dropdown
     * @return array
     */
    public function get_dropdown_data()
    {
        $rates = $this->get();
        $dropdown = [];
        
        foreach ($rates as $rate) {
            $dropdown[$rate['id']] = $rate['name'] . ' (' . $rate['percentage'] . '%)';
        }
        
        return $dropdown;
    }

    /**
     * Obter porcentagem por ID
     * @param int $id
     * @return float
     */
    public function get_percentage_by_id($id)
    {
        if (!$id) {
            return 0;
        }
        
        $rate = $this->get($id);
        return $rate ? (float)$rate['percentage'] : 0;
    }

    /**
     * Verificar se existe taxa padrão
     * @return boolean
     */
    public function has_default_rates()
    {
        $this->db->where('status', 1);
        $count = $this->db->count_all_results(db_prefix() . 'wshop_iss_retention_rates');
        
        return $count > 0;
    }

    /**
     * Criar taxas padrão se não existirem
     * @return boolean
     */
    public function create_default_rates()
    {
        if ($this->has_default_rates()) {
            return false;
        }
        
        $default_rates = [
            ['name' => 'ISS Padrão 2%', 'percentage' => 2.00],
            ['name' => 'ISS Padrão 3%', 'percentage' => 3.00],
            ['name' => 'ISS Padrão 5%', 'percentage' => 5.00]
        ];
        
        foreach ($default_rates as $rate) {
            $this->add($rate);
        }
        
        return true;
    }
}