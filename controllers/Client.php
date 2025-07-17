<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Realestate Client Controller
 */
class Client extends ClientsController
{

	/**
	 * construct
	 */
	public function __construct()
	{

		parent::__construct();
		$this->load->model('workshop_model');

	}

	/**
	 * layout
	 * @param  boolean $notInThemeViewFiles 
	 * @return [type]                       
	 */
	public function layout($notInThemeViewFiles = false)
	{
		/**
		 * Navigation and submenu
		 * @deprecated 2.3.2
		 * @var boolean
		 */

		$this->data['use_navigation'] = $this->use_navigation == true;
		$this->data['use_submenu']    = $this->use_submenu == true;

		/**
		 * @since  2.3.2 new variables
		 * @var array
		 */
		$this->data['navigationEnabled'] = $this->use_navigation == true;
		$this->data['subMenuEnabled']    = $this->use_submenu == true;

		/**
		 * Theme head file
		 * @var string
		 */
		$this->template['head'] = $this->load->view('themes/' . active_clients_theme() . '/head', $this->data, true);

		$GLOBALS['customers_head'] = $this->template['head'];

		/**
		 * Load the template view
		 * @var string
		 */
		$module                       = CI::$APP->router->fetch_module();
		$this->data['current_module'] = $module;
		$viewPath                     = !is_null($module) || $notInThemeViewFiles ?
		$this->view :
		$this->createThemeViewPath($this->view);

		$this->template['view']    = $this->load->view($viewPath, $this->data, true);
		$GLOBALS['customers_view'] = $this->template['view'];

		/**
		 * Theme footer
		 * @var string
		 */
		$this->template['footer'] = $this->use_footer == true
		? $this->load->view('themes/' . active_clients_theme() . '/footer', $this->data, true)
		: '';
		$GLOBALS['customers_footer'] = $this->template['footer'];

		/**
		 * @deprecated 2.3.0
		 * Theme scripts.php file is no longer used since vresion 2.3.0, add app_customers_footer() in themes/[theme]/index.php
		 * @var string
		 */
		
		$this->template['scripts'] = '';
		if (file_exists(VIEWPATH . 'clients/scripts.php')) {
			if (ENVIRONMENT != 'production') {
				trigger_error(sprintf('%1$s', 'Clients area theme file scripts.php file is no longer used since version 2.3.0, add app_customers_footer() in themes/[theme]/index.php. You can check the original theme index.php for example.'));
			}

		}
		$this->template['scripts'] = $this->load->view('clients/scripts', $this->data, true);

		/**
		 * Load the theme compiled template
		 */
		$this->load->view('clients/index', $this->template);
	}

	/**
	 * repair jobs
	 * @return [type] 
	 */
	public function repair_jobs()
	{
		if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}

		// Hook antes do carregamento da lista de repair jobs do cliente
		hooks()->do_action('before_repair_job_list_load');

		$data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, "client_id = ".get_client_user_id()."");

		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$data['base_currency_id'] = $base_currency_id;

		// Hook após o carregamento dos dados do cliente
		hooks()->do_action('repair_job_list_loaded', $data);

		$data['title']    = _l('wshop_repair_jobs');
		$this->data($data);
		$this->view('clients/repair_jobs/manage');
		$this->layout();
	}

	/**
	 * repair job detail
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function repair_job_detail($id, $hash = '')
	{

		if($hash != ''){
			$repair_job = $this->workshop_model->get_repair_job(false, true, ['hash' => $hash]);
			if(count($repair_job) > 0 ){
				$id = $repair_job[0]['id'];
			}
		}else{
			if(!is_client_logged_in()){ 
				redirect_after_login_to_current_url();
				redirect(site_url('authentication/login'));
			}
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
        $data['workshops'] = $this->workshop_model->get_workshop(false, 'repair_job_id = '.$id.' AND visible_to_customer = 1');
        $data['_inspection'] = $this->workshop_model->get_inspection(false, ['repair_job_id' => $id]);

        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'repair_job');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }

		$data['title']    = _l('wshop_repair_job');
		$this->data($data);
		$this->view('clients/repair_jobs/repair_job_detail');
		$this->layout();
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
     * inspections
     * @return [type] 
     */
    public function inspections()
	{
		if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}

		$data['inspections'] = $this->workshop_model->get_inspection(false, "client_id = ".get_client_user_id()." AND visible_to_customer = 1");

		$data['title']    = _l('wshop_inspections');
		$this->data($data);
		$this->view('clients/inspections/manage');
		$this->layout();
	}
	
	/**
	 * inspection detail
	 * @param  string $id 
	 * @return [type]     
	 */
	public function inspection_detail($id = '')
    {
        if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}

        if(!is_numeric($id) || $id == ''){
            blank_page('Inspection Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['inspection'] = $this->workshop_model->get_inspection($id);
        $data['inspection_attachments'] = $this->workshop_model->get_attachment_file($id, 'wshop_inspection');
        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'inspection');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }

        $this->data($data);
		$this->view('clients/inspections/inspection_detail');
		$this->layout();
    }

    /**
     * inspection form detail
     * @param  [type] $id 
     * @return [type]     
     */
    public function inspection_form_detail($id)
    {
    	if(!is_client_logged_in()){ 
    		redirect_after_login_to_current_url();
    		redirect(site_url('authentication/login'));
    	}

        if(!is_numeric($id) || $id == ''){
            blank_page('Inspection Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['inspection'] = $this->workshop_model->get_inspection($id);
        $data['inspection_forms'] = $this->workshop_model->get_inspection_form(false, false,'inspection_id = '. $id);
        $data['check_change_inspection_status'] = check_change_inspection_status($id);
        if(wshop_get_status_modules('warehouse')){
            $data['check_parts_available'] = $this->workshop_model->check_parts_available($id, 'inspection');
        }else{
            $data['check_parts_available'] = [
                'status' => true,
                'message' => '',
            ];
        }
        
        $this->data($data);
		$this->view('clients/inspections/inspection_template_forms/inspection_form_detail');
		$this->layout();

    }

    /**
     * devices
     * @return [type] 
     */
    public function devices()
	{
		if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}

		$data['devices'] = $this->workshop_model->get_device(false, true, "client_id = ".get_client_user_id()."");
		$data['title']    = _l('wshop_devices');
		$this->data($data);
		$this->view('clients/devices/manage');
		$this->layout();
	}

	/**
     * device detail
     * @param  string $id 
     * @return [type]     
     */
    public function device_detail($id = '')
    {
        if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}

        if(!is_numeric($id) || $id == ''){
            blank_page('Device Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['device'] = $this->workshop_model->get_device($id);
        $data['device_images'] = $this->workshop_model->get_device_images($id);
        $data['device_attachments'] = $this->workshop_model->get_attachment_file($id, 'wshop_device');
        $data['repair_jobs'] = $this->workshop_model->get_repair_job(false, true, "client_id = ".get_client_user_id()." AND device_id = ".$id);
        $data['inspections'] = $this->workshop_model->get_inspection(false, "client_id = ".get_client_user_id()." AND device_id = ".$id." AND visible_to_customer = 1");
        $data['workshops'] = $this->workshop_model->get_workshop(false, db_prefix().'wshop_workshops.repair_job_id IN (SELECT id FROM '.db_prefix().'wshop_repair_jobs WHERE '.db_prefix().'wshop_repair_jobs.device_id = '.$id.') AND visible_to_customer = 1');

        $this->data($data);
		$this->view('clients/devices/device_detail');
		$this->layout();
    }

    /**
     * track repair
     * @return [type] 
     */
    public function track_repair()
    {
        $search = $this->input->post('search');
        $page = $this->input->post('page');
        $status = true;

        // Hook antes da busca de rastreamento
        hooks()->do_action('before_repair_job_search');

        $data['title']            = _l('showing_search_result', $search);
        $data['repair_jobs'] = $this->workshop_model->do_track_repair_search($status, $search, $page = 1, $count = false, $where = []);
        $data['repair_job_total'] = $this->workshop_model->do_track_repair_search($status, $search, $page = 1, $count = true, $where = []);

        $data['search'] = $search;
        $data['page'] = (float)$page+1;
        
        // Hook após a busca de rastreamento
        hooks()->do_action('after_repair_job_search', $data);
        
        $this->data($data);

        $this->view('clients/tracking_pages/tracking');
        $this->layout();
    }

}