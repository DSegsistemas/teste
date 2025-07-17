<?php if(count(get_included_files()) == 1) exit("No direct script access allowed");

defined('LB_API_DEBUG') or define("LB_API_DEBUG", false);
defined('LB_SHOW_UPDATE_PROGRESS') or define("LB_SHOW_UPDATE_PROGRESS", true);

defined('LB_TEXT_CONNECTION_FAILED') or define("LB_TEXT_CONNECTION_FAILED", 'Server is unavailable at the moment, please try again.');
defined('LB_TEXT_INVALID_RESPONSE') or define("LB_TEXT_INVALID_RESPONSE", 'Server returned an invalid response, please contact support.');
defined('LB_TEXT_VERIFIED_RESPONSE') or define("LB_TEXT_VERIFIED_RESPONSE", 'Verified! Thanks for purchasing.');
defined('LB_TEXT_PREPARING_MAIN_DOWNLOAD') or define("LB_TEXT_PREPARING_MAIN_DOWNLOAD", 'Preparing to download main update...');
defined('LB_TEXT_MAIN_UPDATE_SIZE') or define("LB_TEXT_MAIN_UPDATE_SIZE", 'Main Update size:');
defined('LB_TEXT_DONT_REFRESH') or define("LB_TEXT_DONT_REFRESH", '(Please do not refresh the page).');
defined('LB_TEXT_DOWNLOADING_MAIN') or define("LB_TEXT_DOWNLOADING_MAIN", 'Downloading main update...');
defined('LB_TEXT_UPDATE_PERIOD_EXPIRED') or define("LB_TEXT_UPDATE_PERIOD_EXPIRED", 'Your update period has ended or your license is invalid, please contact support.');
defined('LB_TEXT_UPDATE_PATH_ERROR') or define("LB_TEXT_UPDATE_PATH_ERROR", 'Folder does not have write permission or the update file path could not be resolved, please contact support.');
defined('LB_TEXT_MAIN_UPDATE_DONE') or define("LB_TEXT_MAIN_UPDATE_DONE", 'Main update files downloaded and extracted.');
defined('LB_TEXT_UPDATE_EXTRACTION_ERROR') or define("LB_TEXT_UPDATE_EXTRACTION_ERROR", 'Update zip extraction failed.');
defined('LB_TEXT_PREPARING_SQL_DOWNLOAD') or define("LB_TEXT_PREPARING_SQL_DOWNLOAD", 'Preparing to download SQL update...');
defined('LB_TEXT_SQL_UPDATE_SIZE') or define("LB_TEXT_SQL_UPDATE_SIZE", 'SQL Update size:');
defined('LB_TEXT_DOWNLOADING_SQL') or define("LB_TEXT_DOWNLOADING_SQL", 'Downloading SQL update...');
defined('LB_TEXT_SQL_UPDATE_DONE') or define("LB_TEXT_SQL_UPDATE_DONE", 'SQL update files downloaded.');
defined('LB_TEXT_UPDATE_WITH_SQL_IMPORT_FAILED') or define("LB_TEXT_UPDATE_WITH_SQL_IMPORT_FAILED", 'Application was successfully updated but automatic SQL importing failed, please import the downloaded SQL file in your database manually.');
defined('LB_TEXT_UPDATE_WITH_SQL_IMPORT_DONE') or define("LB_TEXT_UPDATE_WITH_SQL_IMPORT_DONE", 'Application was successfully updated and SQL file was automatically imported.');
defined('LB_TEXT_UPDATE_WITH_SQL_DONE') or define("LB_TEXT_UPDATE_WITH_SQL_DONE", 'Application was successfully updated, please import the downloaded SQL file in your database manually.');
defined('LB_TEXT_UPDATE_WITHOUT_SQL_DONE') or define("LB_TEXT_UPDATE_WITHOUT_SQL_DONE", 'Application was successfully updated, there were no SQL updates.');

if(!LB_API_DEBUG){
	@ini_set('display_errors', 0);
}

if((@ini_get('max_execution_time')!=='0')&&(@ini_get('max_execution_time'))<600){
	@ini_set('max_execution_time', 600);
}

class WorkshopLic{

	private $product_id;
	private $api_url;
	private $api_key;
	private $api_language;
	private $current_version;
	private $verify_type;
	private $verification_period;
	private $current_path;
	private $root_path;
	private $license_file;

	public function __construct(){ 
		$this->product_id = 'C62D9383';
		$this->api_url = ''; // Removido URL de verificação
		$this->api_key = '801929B0DF7AFE6F02C6';
		$this->api_language = 'english';
		$this->current_version = 'v1.0.0';
		$this->verify_type = 'envato';
		$this->verification_period = 30;
		$this->current_path = realpath(__DIR__);
		$this->root_path = realpath($this->current_path.'/..');
		$this->license_file = $this->current_path.'/lictoken/.lic';
		
		// Criar diretório e arquivo de licença se não existir
		if(!is_dir(dirname($this->license_file))){
			@mkdir(dirname($this->license_file), 0755, true);
		}
		if(!is_file($this->license_file)){
			@file_put_contents($this->license_file, 'ACTIVATED', LOCK_EX);
		}
	}

	// Sempre retorna true para indicar que a licença existe
	public function check_local_license_exist(){
		return true;
	}

	public function get_current_version(){
		return $this->current_version;
	}

	// Mantido apenas para compatibilidade com o código original
	private function call_api($method, $url, $data = null){
		$res = array(
			'status' => TRUE, 
			'message' => LB_TEXT_VERIFIED_RESPONSE,
			'lic_response' => 'ACTIVATED'
		);
		return json_encode($res);
	}

	public function check_connection(){
		$response = array(
			'status' => TRUE,
			'message' => LB_TEXT_VERIFIED_RESPONSE
		);
		return $response;
	}

	public function get_latest_version(){
		$response = array(
			'status' => TRUE,
			'message' => LB_TEXT_VERIFIED_RESPONSE,
			'version' => $this->current_version,
			'update_id' => md5(uniqid()),
			'has_update' => FALSE
		);
		return $response;
	}

	// Sempre retorna ativo
	public function activate_license($license = '', $client = '', $create_lic = true){
		$response = array(
			'status' => TRUE,
			'message' => LB_TEXT_VERIFIED_RESPONSE,
			'lic_response' => 'ACTIVATED'
		);
		
		if(!empty($create_lic)){
			@file_put_contents($this->license_file, 'ACTIVATED', LOCK_EX);
		}
		
		return $response;
	}

	// Sempre retorna licença válida
	public function verify_license($time_based_check = false, $license = false, $client = false){
		$res = array(
			'status' => TRUE, 
			'message' => LB_TEXT_VERIFIED_RESPONSE
		);
		return $res;
	}

	// Mantido apenas para compatibilidade
	public function deactivate_license($license = false, $client = false){
		$response = array(
			'status' => TRUE,
			'message' => 'License deactivated successfully'
		);
		return $response;
	}

	public function check_update(){
		$response = array(
			'status' => TRUE,
			'message' => 'No updates available',
			'update_id' => md5(uniqid()),
			'has_update' => FALSE
		);
		return $response;
	}

	// Métodos abaixo mantidos apenas para compatibilidade
	public function download_update($update_id, $type, $version, $license = false, $client = false, $db_for_import = false){ 
		// Este método foi mantido vazio para compatibilidade
		return true;
	}

	private function progress($resource, $download_size, $downloaded, $upload_size, $uploaded){
		// Vazio para compatibilidade
	}

	private function get_ip_from_third_party(){
		return '127.0.0.1';
	}

	private function get_remote_filesize($url){
		return '0 B';
	}

	private function decrypt($data) {
		return '';
	}
}