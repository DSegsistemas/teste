<?php

defined('BASEPATH') or exit('No direct script access allowed');
include_once APPPATH . 'libraries/pdf/App_pdf.php';

class Repair_job_label_pdf extends App_pdf
{
	protected $repair_job_label;

	private $repair_job_label_number;

	public function __construct($repair_job_label, $tag = '')
	{
		$this->load_language($repair_job_label->client_id);
		$repair_job_label                = hooks()->apply_filters('repair_job_label_html_pdf_data', $repair_job_label);
		$GLOBALS['repair_job_label_pdf'] = $repair_job_label;

		parent::__construct();

		$this->repair_job_label        = $repair_job_label;
		$this->repair_job_label_number = $this->repair_job_label->job_tracking_number;

		$this->SetTitle($this->repair_job_label->job_tracking_number);
	}

	public function prepare()
	{

		$this->set_view_vars([
			'repair_job_label_number' => $this->repair_job_label_number,
			'repair_job_label'        => $this->repair_job_label,
		]);

		return $this->build();
	}

	protected function type()
	{
		return 'repair_job_label';
	}

	protected function file_path()
	{
		$actualPath = APP_MODULES_PATH . '/workshop/views/repair_jobs/print_labels/repair_job_label_pdf.php';
		return $actualPath;
	}

}
