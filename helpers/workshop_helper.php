<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verifica se a integração do menu de faturas está habilitada
 * @return boolean
 */
function workshop_invoice_integration_enabled()
{
    return get_option('wshop_invoice_integration_enabled') == '1';
}

/**
 * Verifica se o usuário tem permissão para ver faturas integradas
 * @return boolean
 */
function workshop_can_view_integrated_invoices()
{
    return has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own');
}

/**
 * handle manufacturer image
 * @param  [type] $manufacturer_id 
 * @return [type]                  
 */
function handle_manufacturer_image($manufacturer_id){
    $path           = MANUFACTURER_IMAGES_FOLDER.$manufacturer_id .'/';
    $CI            = & get_instance();
    $totalUploaded = 0;
    if(isset($_FILES['manufacture_image']) && ($_FILES['manufacture_image'] != '' )){

        // Get the temp file path
        $tmpFilePath = $_FILES['manufacture_image']['tmp_name'];

                // Get the temp file path
                // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            if (_perfex_upload_error($_FILES['manufacture_image']['error']) || !_upload_extension_allowed($_FILES['manufacture_image']['name'])) {
                return false;
            }
            if (is_dir(MANUFACTURER_IMAGES_FOLDER. $manufacturer_id)) {
            // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(MANUFACTURER_IMAGES_FOLDER. $manufacturer_id);
                // okey only index.html so we can delete the folder also
                delete_dir(MANUFACTURER_IMAGES_FOLDER. $manufacturer_id);
            }

            _maybe_create_upload_path($path);
            $filename    = unique_filename($path, $_FILES['manufacture_image']['name']);
            $newFilePath = $path . $filename;
                    // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                if (is_image($newFilePath)) {
                    create_img_thumb($newFilePath, $filename);
                }

                $CI->db->where('id', $manufacturer_id);
                $CI->db->update(db_prefix().'wshop_manufacturers', [
                    'manufacture_image' => $filename,
                ]);

                $totalUploaded++;
            }

        }
    }

    if($totalUploaded > 0){
        return true;
    }

    return false;
}

/**
 * wshop_use_for
 * @return [type] 
 */
function wshop_use_for()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'Labour_Product',
            'label' => _l('wshop_Labour_Product'),
        ],
        [
            'name' => 'Device',
            'label' => _l('wshop_Device'),
        ],
        [
            'name' => 'Collection_Type',
            'label' => _l('wshop_Collection_Type'),
        ],
        [
            'name' => 'Billing_Type',
            'label' => _l('wshop_Billing_Type'),
        ],
        [
            'name' => 'Delivery_Type',
            'label' => _l('wshop_Delivery_Type'),
        ],
        [
            'name' => 'Report_Type',
            'label' => _l('wshop_Report_Type'),
        ],
        [
            'name' => 'Report_Status',
            'label' => _l('wshop_Report_Status'),
        ],
        [
            'name' => 'Inspection',
            'label' => _l('wshop_inspection'),
        ],
        
    ];

    return $array_data;
}

/**
 * wshop interval types
 * @return [type] 
 */
function wshop_interval_types()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'day',
            'label' => _l('wshop_day_s'),
        ],
        [
            'name' => 'month',
            'label' => _l('wshop_month_s'),
        ],
        [
            'name' => 'year',
            'label' => _l('wshop_year_s'),
        ],
        
    ];

    return $array_data;
}

/**
 * wshop warranty status
 * @return [type] 
 */
function wshop_warranty_status()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'being_under_warranty',
            'label' => _l('wshop_being_under_warranty'),
        ],
        [
            'name' => 'out_of_warranty',
            'label' => _l('wshop_out_of_warranty'),
        ],
    ];

    return $array_data;
}

/**
 * wshop get category name
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_get_category_name($id)
{
    $name = '';
    $CI = &get_instance();
    $CI->db->where('id', $id);
    $category = $CI->db->get(db_prefix() . 'wshop_categories')->row();
    if($category){
        $name = $category->name;
    }

    return $name;
}

/**
 * get product name
 * @param  boolean $id 
 * @return [type]      
 */
function get_device_name($id = false)
{
    $CI           = & get_instance();
    $item_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $item =  $CI->db->get(db_prefix() . 'wshop_devices')->row();
        if($item){
            $item_name = $item->name;
        }
    }
     return $item_name;
}

/**
 * get interval name
 * @param  boolean $id 
 * @return [type]      
 */
function get_interval_name($id = false)
{
    $CI           = & get_instance();
    $interval_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $item =  $CI->db->get(db_prefix() . 'wshop_intervals')->row();
        if($item){
            $interval_name = $item->name;
        }
    }
     return $interval_name;
}

/**
 * get repair job name
 * @param  boolean $id 
 * @return [type]      
 */
function get_repair_job_name($id = false)
{
    $CI           = & get_instance();
    $repair_job_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $item =  $CI->db->get(db_prefix() . 'wshop_repair_jobs')->row();
        if($item){
            $repair_job_name = $item->name;
        }
    }
     return $repair_job_name;
}


if (!function_exists('new_strlen')) {
    
    function new_strlen($str){
        return strlen($str ?? '');
    }
}

/**
 * wshop swarranty sexpiring salerts
 * @return [type] 
 */
function wshop_warranty_expiring_alerts()
{
    $array_data = [];
    for ($i=1; $i < 91 ; $i++) { 
        $array_data[] = [
            'name' => $i,
            'label' => $i,
        ];
    }

    return $array_data;
}

function handle_device_image($device_id){
    $path           = MAIN_IMAGE_DEVICES_IMAGES_FOLDER.$device_id .'/';
    $CI            = & get_instance();
    $totalUploaded = 0;
    if(isset($_FILES['primary_profile_image']) && ($_FILES['primary_profile_image'] != '' )){

        // Get the temp file path
        $tmpFilePath = $_FILES['primary_profile_image']['tmp_name'];

                // Get the temp file path
                // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            if (_perfex_upload_error($_FILES['primary_profile_image']['error']) || !_upload_extension_allowed($_FILES['primary_profile_image']['name'])) {
                return false;
            }
            if (is_dir(MAIN_IMAGE_DEVICES_IMAGES_FOLDER. $device_id)) {
            // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(MAIN_IMAGE_DEVICES_IMAGES_FOLDER. $device_id);
                // okey only index.html so we can delete the folder also
                delete_dir(MAIN_IMAGE_DEVICES_IMAGES_FOLDER. $device_id);
            }

            _maybe_create_upload_path($path);
            $filename    = unique_filename($path, $_FILES['primary_profile_image']['name']);
            $newFilePath = $path . $filename;
                    // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                if (is_image($newFilePath)) {
                    create_img_thumb($newFilePath, $filename);
                }

                $CI->db->where('id', $device_id);
                $CI->db->update(db_prefix().'wshop_devices', [
                    'primary_profile_image' => $filename,
                ]);

                $totalUploaded++;
            }

        }
    }

    if($totalUploaded > 0){
        return true;
    }

    return false;
}

/**
 * wshop handle device attachments
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_handle_device_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = DEVICES_IMAGES_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'wshop_device', $attachment);

            }
        }
    }

}

/**
 * wshop get fieldset id by_model
 * @param  [type] $model_id 
 * @return [type]           
 */
function wshop_get_fieldset_id_by_model($model_id)
{
    $CI   = & get_instance();
    $CI->load->model('workshop/workshop_model');

    $fieldset_id = 0;
    if($model_id != 0){
        $model = $CI->workshop_model->get_model($model_id);
        if($model && !is_null($model->fieldset_id) && $model->fieldset_id != 0){
            $fieldset_id = $model->fieldset_id;
        }
    }
    return $fieldset_id;
}
/**
 * get labour product name
 * @param  boolean $id 
 * @return [type]      
 */
function get_labour_product_name($id = false)
{
    $CI           = & get_instance();
    $name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $item =  $CI->db->get(db_prefix() . 'wshop_labour_products')->row();
        if($item){
            $name = $item->name;
        }
    }
     return $name;
}

/**
 * repair job status
 * @param  string $status 
 * @return [type]         
 */
function repair_job_status($status='')
{
    // Comentário: Modificado para buscar status do banco de dados
    $CI = &get_instance();
    $CI->load->model('workshop/workshop_model');
    
    // Verificar se existem status personalizados no banco de dados
    $db_statuses = $CI->workshop_model->get_repair_job_status(false, true);
    
    if (!empty($db_statuses)) {
        // Usar status do banco de dados
        $statuses = [];
        foreach ($db_statuses as $status) {
            $statuses[] = [
                'id'             => $status['status_id'],
                'color'          => $status['color'],
                'name'           => $status['name'],
                'order'          => $status['order'],
                'filter_default' => $status['filter_default'] == 1 ? true : false,
            ];
        }
    } else {
        // Usar status padrão se não houver registros no banco de dados
        $statuses = [
            [
                'id'             => 'Booked_In',
                'color'          => '#00BCD4',
                'name'           => _l('wshop_Booked_In'),
                'order'          => 1,
                'filter_default' => true,
            ],
            [
                'id'             => 'In_Progress',
                'color'          => '#03A9F4',
                'name'           => _l('wshop_In_Progress'),
                'order'          => 2,
                'filter_default' => true,
            ],
            [
                'id'             => 'Cancelled',
                'color'          => '#F44336',
                'name'           => _l('wshop_Cancelled'),
                'order'          => 3,
                'filter_default' => true,
            ],
            [
                'id'             => 'Waiting_For_Parts',
                'color'          => '#ffc107',
                'name'           => _l('wshop_Waiting_For_Parts'),
                'order'          => 4,
                'filter_default' => true,
            ],
            [
                'id'             => 'Job_Complete',
                'color'          => '#ffc107',
                'name'           => _l('wshop_Job_Complete'),
                'order'          => 5,
                'filter_default' => true,
            ],
            [
                'id'             => 'Customer_Notified',
                'color'          => '#3f51b5',
                'name'           => _l('wshop_Customer_Notified'),
                'order'          => 6,
                'filter_default' => true,
            ],
            [
                'id'             => 'Complete_Awaiting_Finalise',
                'color'          => '#8bc34a',
                'name'           => _l('wshop_Complete_Awaiting_Finalise'),
                'order'          => 7,
                'filter_default' => true,
            ],
            [
                'id'             => 'Finalised',
                'color'          => '#009688',
                'name'           => _l('wshop_Finalised'),
                'order'          => 8,
                'filter_default' => true,
            ],
            [
                'id'             => 'Waiting_For_User_Approval',
                'color'          => '#ff5722',
                'name'           => _l('wshop_Waiting_For_User_Approval'),
                'order'          => 9,
                'filter_default' => true,
            ],
        ];
        
        // Inserir os status padrão no banco de dados
        foreach ($statuses as $status) {
            $data = [
                'status_id'      => $status['id'],
                'name'           => $status['name'],
                'color'          => $status['color'],
                'order'          => $status['order'],
                'filter_default' => $status['filter_default'] ? 1 : 0,
            ];
            $CI->workshop_model->add_repair_job_status($data);
        }
    }

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get repair job status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_repair_job_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = repair_job_status();

    $status = [
        'id'             => 'Booked_In',
        'color'          => '#03A9F4',
        'name'           => _l('wshop_Booked_In'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render repair job status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_repair_job_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_repair_job_status_by_id($status_value, $type);

    $task_statuses = repair_job_status();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('workshop_repair_job', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="repair_job_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * Format repair_job number based on description
 * @param  mixed $id
 * @return string
 */
function format_repair_job_number($id)
{
    $CI = &get_instance();

    if (! is_object($id)) {
        $CI->db->select('appointment_date,number,prefix,number_format')->from(db_prefix() . 'wshop_repair_jobs')->where('id', $id);
        $repair_job = $CI->db->get()->row();
    } else {
        $repair_job = $id;
        $id       = $repair_job->id;
    }

    if (!$repair_job) {
        return '';
    }

    $number = sales_number_format($repair_job->number, $repair_job->number_format, $repair_job->prefix, $repair_job->appointment_date);

    return hooks()->apply_filters('format_repair_job_number', $number, [
        'id'       => $id,
        'repair_job' => $repair_job,
    ]);
}

/**
 * wshop convert item taxes
 * @param  [type] $tax      
 * @param  [type] $tax_rate 
 * @param  [type] $tax_name 
 * @return [type]           
 */
function wshop_convert_item_taxes($tax, $tax_rate, $tax_name)
{
    /*taxrate taxname
    5.00    TAX5
    id      rate        name
    2|1 ; 6.00|10.00 ; TAX5|TAX10%*/
    $CI           = & get_instance();
    $taxes = [];
    if($tax != null && new_strlen($tax) > 0){
        $arr_tax_id = new_explode('|', $tax);
        if($tax_name != null && new_strlen($tax_name) > 0){
            $arr_tax_name = new_explode('|', $tax_name);
            $arr_tax_rate = new_explode('|', $tax_rate);
            foreach ($arr_tax_name as $key => $value) {
                $taxes[]['taxname'] = $value . '|' .  $arr_tax_rate[$key];
            }
        }elseif($tax_rate != null && new_strlen($tax_rate) > 0){
            $CI->load->model('workshop/workshop_model');
            $arr_tax_id = new_explode('|', $tax);
            $arr_tax_rate = new_explode('|', $tax_rate);
            foreach ($arr_tax_id as $key => $value) {
                $_tax_name = $CI->workshop_model->get_tax_name($value);
                if(isset($arr_tax_rate[$key])){
                    $taxes[]['taxname'] = $_tax_name . '|' .  $arr_tax_rate[$key];
                }else{
                    $taxes[]['taxname'] = $_tax_name . '|' .  $CI->workshop_model->tax_rate_by_id($value);

                }
            }
        }else{
            $CI->load->model('workshop/workshop_model');
            $arr_tax_id = new_explode('|', $tax);
            $arr_tax_rate = new_explode('|', $tax_rate);
            foreach ($arr_tax_id as $key => $value) {
                $_tax_name = $CI->workshop_model->get_tax_name($value);
                $_tax_rate = $CI->workshop_model->tax_rate_by_id($value);
                $taxes[]['taxname'] = $_tax_name . '|' .  $_tax_rate;
            } 
        }

    }

    return $taxes;
}

/**
 * wshop render taxes html
 * @param  [type] $item_tax 
 * @param  [type] $width    
 * @return [type]           
 */
function wshop_render_taxes_html($item_tax, $width)
{
    $itemHTML = '';
    $itemHTML .= '<td align="right" width="' . $width . '%">';

    if(is_array($item_tax) && isset($item_tax)){
        if (count($item_tax) > 0) {
            foreach ($item_tax as $tax) {

                $item_tax = '';
                if ( get_option('remove_tax_name_from_item_table') == false || multiple_taxes_found_for_item($item_tax)) {
                    $tmp      = new_explode('|', $tax['taxname']);
                    $item_tax = $tmp[0] . ' ' . app_format_number($tmp[1]) . '%<br />';
                } else {
                    $item_tax .= app_format_number($tax['taxrate']) . '%';
                }
                $itemHTML .= $item_tax;
            }
        } else {
            $itemHTML .=  app_format_number(0) . '%';
        }
    }
    $itemHTML .= '</td>';

    return $itemHTML;
}

/**
 * get model name
 * @param  boolean $id 
 * @return [type]      
 */
function wshop_get_model_name($id = false)
{
    $CI           = & get_instance();
    $model_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $model =  $CI->db->get(db_prefix() . 'wshop_models')->row();
        if($model){
            $model_name = $model->name;
        }
    }
     return $model_name;
}

/**
 * wshop get category
 * @param  boolean $id 
 * @return [type]      
 */
function wshop_get_model($id = false)
{
    $CI           = & get_instance();
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $model =  $CI->db->get(db_prefix() . 'wshop_models')->row();
        if($model){
            return $model;
        }
    }
     return false;
}

/**
 * wshop get manufacturer name
 * @param  boolean $id 
 * @return [type]      
 */
function wshop_get_manufacturer_name($id = false)
{
    $CI           = & get_instance();
    $manufacturer_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $manufacturer =  $CI->db->get(db_prefix() . 'wshop_manufacturers')->row();
        if($manufacturer){
            $manufacturer_name = $manufacturer->name;
        }
    }
     return $manufacturer_name;
}

/**
 * wshop get appointment type name
 * @param  boolean $id 
 * @return [type]      
 */
function wshop_get_appointment_type_name($id = false)
{
    $CI           = & get_instance();
    $appointment_type_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $appointment =  $CI->db->get(db_prefix() . 'wshop_appointment_types')->row();
        if($appointment){
            $appointment_type_name = $appointment->name;
        }
    }
     return $appointment_type_name;
}

/**
 * wshop get branch name
 * @param  boolean $id 
 * @return [type]      
 */
function wshop_get_branch_name($id = false, $column_name = 'name')
{
    $CI           = & get_instance();
    $branch_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $branch =  $CI->db->get(db_prefix() . 'wshop_branches')->row();
        if($branch){
            $branch_name = $branch->{$column_name};
        }
    }
     return $branch_name;
}

/**
 * repair job transaction
 * @param  string $transaction 
 * @return [type]         
 */
function transaction_status($status='')
{

    $statuses = [

        [
            'id'             => 'Pending',
            'color'          => '#ffc107',
            'name'           => _l('wshop_Pending'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'Scheduled',
            'color'          => '#ffc107',
            'name'           => _l('wshop_Scheduled'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'In_Transit',
            'color'          => '#3f51b5',
            'name'           => _l('wshop_In_Transit'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'Delivered',
            'color'          => '#8bc34a',
            'name'           => _l('wshop_Delivered'),
            'order'          => 4,
            'filter_default' => true,
        ],
        [
            'id'             => 'Failed',
            'color'          => '#ff5722',
            'name'           => _l('wshop_Failed'),
            'order'          => 5,
            'filter_default' => true,
        ],
        [
            'id'             => 'Sent',
            'color'          => '#009688',
            'name'           => _l('wshop_Sent'),
            'order'          => 6,
            'filter_default' => true,
        ],
        [
            'id'             => 'Cancelled',
            'color'          => '#F44336',
            'name'           => _l('wshop_Cancelled'),
            'order'          => 7,
            'filter_default' => true,
        ],
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get transaction status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_transaction_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = transaction_status();

    $status = [
        'id'             => 'Pending',
        'color'          => '#03A9F4',
        'name'           => _l('wshop_Pending'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render repair job status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_transaction_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_transaction_status_by_id($status_value, $type);

    $task_statuses = transaction_status();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('workshop_repair_job', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#"  class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="transaction_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * wshop handle transaction attachments
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_handle_transaction_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = TRANSACTION_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'wshop_transaction', $attachment);

            }
        }
    }

}

/**
 * wshop handle note attachments
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_handle_note_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = NOTE_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'wshop_note', $attachment);

            }
        }
    }

}

/**
 * wshop handle workshop attachments
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_handle_workshop_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = WORKSHOP_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'wshop_workshop', $attachment);

            }
        }
    }

}

/**
 * inspection job status
 * @param  string $status 
 * @return [type]         
 */
function inspection_status($status='')
{

    $statuses = [

        [
            'id'             => 'Open',
            'color'          => '#00BCD4',
            'name'           => _l('wshop_Open'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'In_Progress',
            'color'          => '#03A9F4',
            'name'           => _l('wshop_In_Progress'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'Waiting_For_Approval',
            'color'          => '#F44336',
            'name'           => _l('wshop_Waiting_For_Approval'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'Waiting_For_Parts',
            'color'          => '#ffc107',
            'name'           => _l('wshop_Waiting_For_Parts'),
            'order'          => 4,
            'filter_default' => true,
        ],
        [
            'id'             => 'Complete_Awaiting_Finalise',
            'color'          => '#ffc107',
            'name'           => _l('wshop_Complete_Awaiting_Finalise'),
            'order'          => 5,
            'filter_default' => true,
        ],
        [
            'id'             => 'Completed',
            'color'          => '#3f51b5',
            'name'           => _l('wshop_Completed'),
            'order'          => 6,
            'filter_default' => true,
        ],
        [
            'id'             => 'Overdue',
            'color'          => '#8bc34a',
            'name'           => _l('wshop_Overdue'),
            'order'          => 7,
            'filter_default' => true,
        ],
        
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get repair job status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_inspection_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = inspection_status();

    $status = [
        'id'             => 'Open',
        'color'          => '#03A9F4',
        'name'           => _l('wshop_Open'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render repair job status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_inspection_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_inspection_status_by_id($status_value, $type);

    $task_statuses = inspection_status();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('workshop_inspection', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="inspection_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * wshop handle inspection attachments
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_handle_inspection_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = INSPECTION_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'wshop_inspection', $attachment);

            }
        }
    }

}

/**
 * Format inspection number based on description
 * @param  mixed $id
 * @return string
 */
function format_inspection_number($id)
{
    $CI = &get_instance();

    if (! is_object($id)) {
        $CI->db->select('datecreated,number,prefix,number_format')->from(db_prefix() . 'wshop_inspections')->where('id', $id);
        $inspection = $CI->db->get()->row();
    } else {
        $inspection = $id;
        $id       = $inspection->id;
    }

    if (!$inspection) {
        return '';
    }

    $number = sales_number_format($inspection->number, $inspection->number_format, $inspection->prefix, $inspection->datecreated);

    return hooks()->apply_filters('format_inspection_number', $number, [
        'id'       => $id,
        'inspection' => $inspection,
    ]);
}

if (!function_exists('new_html_entity_decode')) {

    function new_html_entity_decode($str){
        // Primeiro decodifica as entidades HTML, depois remove as tags HTML
        $decoded = html_entity_decode($str ?? '');
        return strip_tags($decoded);
    }
}

/**
 * wshop inspection template name
 * @param  [type] $id 
 * @return [type]     
 */
function wshop_inspection_template_name($id)
{
    $name = '';
    $CI = &get_instance();
    $CI->db->where('id', $id);
    $category = $CI->db->get(db_prefix() . 'wshop_inspection_templates')->row();
    if($category){
        $name = $category->name;
    }

    return $name;
}

/**
 * check change inspection status
 * @param  [type] $inspection_id 
 * @return [type]                
 */
function check_change_inspection_status($inspection_id)
{
    $update_inspection_status = false;
    $CI = &get_instance();
    $CI->db->where('relid', $inspection_id);
    $CI->db->where('inspection_result', 'repair');
    $CI->db->where('approve is NULL');
    $inspection_value = $CI->db->get(db_prefix() . 'wshop_inspection_values')->result_array();
    if(count($inspection_value) == 0){
        $update_inspection_status = true;
    }
    return $update_inspection_status;
}

/**
 * wshop get status modules
 * @param  [type] $module_name 
 * @return [type]              
 */
function wshop_get_status_modules($module_name)
{
    $CI             = &get_instance();

    $sql = 'select * from '.db_prefix().'modules where module_name = "'.$module_name.'" AND active =1 ';
    $module = $CI->db->query($sql)->row();
    if($module){
        return true;
    }else{
        return false;
    }
}

/**
 * workshop client init tail
 * @return [type] 
 */
function workshop_client_init_tail()
{
    $CI = &get_instance();
    $CI->load->view('clients/scripts');
}

/**
 * workshop customers portal footer
 * @return [type] 
 */
function workshop_customers_portal_footer()
{
    
    /**
     * @deprecated 2.3.0
     * Moved from themes/[THEME]/views/scripts.php
     * Use app_sale_agent_footer hook instead
     */
    do_action_deprecated('workshop_after_js_scripts_load', [], '2.3.0', 'workshop_customers_portal_footer');

    hooks()->do_action('workshop_customers_portal_footer');
}

/**
 * workshop get invoice hash
 * @param  [type] $id 
 * @return [type]     
 */
function workshop_get_invoice_hash($id)
{
    $hash = '';
    $CI           = & get_instance();
    $CI->db->where('id',$id);

    $invoices = $CI->db->get(db_prefix().'invoices')->row();
    if($invoices){
        $hash = $invoices->hash;
    }
    return $hash;
}

/**
 * workshop get qrcode
 * @param  [type]  $type          
 * @param  [type]  $movement_id   
 * @param  [type]  $hash          
 * @param  string  $qr_code_class 
 * @param  boolean $width         
 * @return [type]                 
 */
function workshop_get_qrcode($type, $movement_id, $hash, $qr_code_class="images_w_table", $width = false)
{
    $qr_code = '';

    switch ($type) {
        case 'repair_job':
            $upload_path = REPAIR_JOB_QR_UPLOAD_PATH . $movement_id.'/';
            $qrcode_path = md5(site_url('workshop/client/repair_job_detail/0/'.$hash.'?tab=detail') ?? '').'.svg';

            break;

        default:
            // code...
            break;
    }

    $qrcode_path  = site_url($upload_path . $qrcode_path);
    if($width){
        $qr_code = '<img width="' . $width . 'px" class="'.$qr_code_class.'" src="'.$qrcode_path.'" alt="' . $qrcode_path . '" >';
    }else{
        $qr_code = '<img class="'.$qr_code_class.'" src="'.$qrcode_path.'" alt="' . $qrcode_path . '" >';
    }

    return $qr_code;
}

/**
 * list workshop permisstion
 * @return [type] 
 */
function list_workshop_permisstion()
{
     $workshop_permissions[]='workshop_dashboard';
     $workshop_permissions[]='workshop_repair_job';
     $workshop_permissions[]='workshop_device';
     $workshop_permissions[]='workshop_mechanic';
     $workshop_permissions[]='workshop_labour_product';
     $workshop_permissions[]='workshop_branch';
     $workshop_permissions[]='workshop_inspection';
     $workshop_permissions[]='workshop_workshop';
     $workshop_permissions[]='workshop_report';
     $workshop_permissions[]='workshop_setting';

    return $workshop_permissions;
}

/**
 * workshop get staff id permissions
 * @return [type] 
 */
function workshop_get_staff_id_permissions()
{
    $CI = & get_instance();
    $array_staff_id = [];
    $index=0;

    $str_permissions ='';
    foreach (list_workshop_permisstion() as $per_key =>  $per_value) {
        if(new_strlen($str_permissions) > 0){
            $str_permissions .= ",'".$per_value."'";
        }else{
            $str_permissions .= "'".$per_value."'";
        }
    }

    $sql_where = "SELECT distinct staff_id FROM ".db_prefix()."staff_permissions
    where feature IN (".$str_permissions.")
    ";

    $staffs = $CI->db->query($sql_where)->result_array();

    if(count($staffs)>0){
        foreach ($staffs as $key => $value) {
            $array_staff_id[$index] = $value['staff_id'];
            $index++;
        }
    }
    return $array_staff_id;
}

/**
 * workshop get staff id dont permissions
 * @return [type] 
 */
function workshop_get_staff_id_dont_permissions()
{
    $CI = & get_instance();

    $CI->db->where('admin != ', 1);

    if(count(workshop_get_staff_id_permissions()) > 0){
        $CI->db->where_not_in('staffid', workshop_get_staff_id_permissions());
    }
    return $CI->db->get(db_prefix().'staff')->result_array();
}


if (!function_exists('new_str_replace')) {
    
    function new_str_replace($search, $replace, $subject){
        return str_replace($search, $replace, $subject ?? '');
    }
}

if (!function_exists('new_explode')) {
    
    function new_explode($delimiter, $string){
        return explode($delimiter, $string ?? '');
    }
}

/**
 * device by manufacturer
 * @param  boolean $manufacturer_id 
 * @return [type]                   
 */
function device_by_manufacturer($manufacturer_id = false)
{
    $CI           = & get_instance();
    $total_device = 0;
    if (is_numeric($manufacturer_id)) {
        $CI->db->select(db_prefix() . 'wshop_devices.id');
        $CI->db->from(db_prefix() . 'wshop_devices');
        $CI->db->join(db_prefix() . 'wshop_models', db_prefix() . 'wshop_models.id = ' . db_prefix() . 'wshop_devices.model_id', 'left');
        $CI->db->where(db_prefix() . 'wshop_models.manufacturer_id', $manufacturer_id);
        $devices =  $CI->db->get()->result_array();
        $total_device = count($devices);
    }
     return $total_device;
}

/**
 * cal model by fieldset
 * @param  boolean $fieldset 
 * @return [type]            
 */
function cal_model_by_fieldset($fieldset_id = false)
{
    $CI           = & get_instance();
    $total_model = 0;
    if (is_numeric($fieldset_id)) {
        $CI->db->select(db_prefix() . 'wshop_models.id');
        $CI->db->from(db_prefix() . 'wshop_models');
        $CI->db->where(db_prefix() . 'wshop_models.fieldset_id', $fieldset_id);
        $models =  $CI->db->get()->result_array();
        $total_model = count($models);
    }
     return $total_model;
}