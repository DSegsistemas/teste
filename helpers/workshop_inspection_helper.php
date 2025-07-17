<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Render custom fields inputs
 * @param  string  $belongs_to             where this custom field belongs eq invoice, customers
 * @param  mixed   $rel_id                 relation id to set values
 * @param  array   $where                  where in sql - additional
 * @param  array $items_cf_params          used only for custom fields for items operations
 * @return mixed
 */
function wshop_render_inspection_form_fields($belongs_to, $rel_id = false, $where = [], $items_cf_params = [])
{
    // Is custom fields for items and in add/edit
    $items_add_edit_preview = isset($items_cf_params['add_edit_preview']) && $items_cf_params['add_edit_preview'] ? true : false;

    // Is custom fields for items and in add/edit area for this already added
    $items_applied = isset($items_cf_params['items_applied']) && $items_cf_params['items_applied'] ? true : false;

    // Used for items custom fields to add additional name on input
    $part_item_name = isset($items_cf_params['part_item_name']) ? $items_cf_params['part_item_name'] : '';

    // Is this custom fields for predefined items Sales->Items
    $items_pr = isset($items_cf_params['items_pr']) && $items_cf_params['items_pr'] ? true : false;

    // $one_item = isset($items_cf_params['one_item']) && $items_cf_params['one_item'] ? true : false;
    $one_item = false;


    $is_admin = is_admin();

    $CI = & get_instance();
    $CI->db->where('active', 1);
    $CI->db->where('fieldto', $belongs_to);

    if (is_array($where) && count($where) > 0 || is_string($where) && $where != '') {
        $CI->db->where($where);
    }

    $CI->db->order_by('field_order', 'asc');
    $fields = $CI->db->get(db_prefix() . 'wshop_inspection_form_details')->result_array();

    $fields_html = '';
    $inspection_form_id = 0;

    if (count($fields)) {
        if (!$items_add_edit_preview && !$items_applied && $one_item) {
            $fields_html .= '<div class="row custom-fields-form-row">';
        }
        // $fields_html .= '<input type="hidden" name="inspection_form_id" value="'.$inspection_form_id.'">';

        foreach ($fields as $field) {
            $inspection_form_id = $field['inspection_form_id'];

            $fields_html .= '<div class="form-question" id="form_question_'.$field['inspection_form_id'].'_'.$field['id'].'">';
            $fields_html .= '<input type="hidden" name="field_order" data-question_id="'.$field['id'].'" value="'.$field['field_order'].'">';
            
            $field['name'] = _wshop_maybe_translate_inspection_form_field_name($field['name'], $field['slug']);

            $value = '';
            $inspection_result = '';
            $inspection_comment = '';
            if ($field['bs_column'] == '' || $field['bs_column'] == 0) {
                $field['bs_column'] = 12;
            }
            $field['bs_column'] = 10;

            if (!$items_add_edit_preview && !$items_applied) {
                $fields_html .= '<div class="border-right col-md-' . $field['bs_column'] . '">';
            } elseif ($items_add_edit_preview) {
                $fields_html .= '<td class="custom_field" data-id="' . $field['id'] . '">';
            } elseif ($items_applied) {
                $fields_html .= '<td class="custom_field">';
            }


            if ($rel_id !== false) {
                if (!is_array($rel_id)) {
                    $value = wshop_get_inspection_form_field_value($rel_id, $field['id'], ($items_pr ? 'items_pr' : $belongs_to), false);
                    $wshop_get_inspection_form_field_result = wshop_get_inspection_form_field_result($rel_id, $field['id'], ($items_pr ? 'items_pr' : $belongs_to), false);
                    $inspection_result = $wshop_get_inspection_form_field_result['result'];
                    $inspection_comment = $wshop_get_inspection_form_field_result['comment'];

                } else {
                    if (wshop_is_custom_fields_smart_transfer_enabled()) {
                        // Used only in:
                        // 1. Convert proposal to estimate, invoice
                        // 2. Convert estimate to invoice
                        // This feature is executed only on CREATE, NOT EDIT
                        $transfer_belongs_to = $rel_id['belongs_to'];
                        $transfer_rel_id     = $rel_id['rel_id'];
                        $tmpSlug             = explode('_', $field['slug'], 2);
                        if (isset($tmpSlug[1])) {
                            $CI->db->where('fieldto', $transfer_belongs_to);
                            $CI->db->group_start();
                            $CI->db->like('slug', $rel_id['belongs_to'] . '_' . $tmpSlug[1], 'after');
                            $CI->db->where('type', $field['type']);
                            $CI->db->where('options', $field['options']);
                            $CI->db->where('active', 1);
                            $CI->db->group_end();
                            $cfTransfer = $CI->db->get(db_prefix() . 'wshop_inspection_form_details')->result_array();

                            // Don't make mistakes
                            // Only valid if 1 result returned
                            // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                            //
                            if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                                $value = wshop_get_inspection_form_field_value($transfer_rel_id, $cfTransfer[0]['id'], $transfer_belongs_to, false);
                                $wshop_get_inspection_form_field_result = wshop_get_inspection_form_field_result($transfer_rel_id, $cfTransfer[0]['id'], $transfer_belongs_to, false);
                                $inspection_result = $wshop_get_inspection_form_field_result['result'];
                                $inspection_comment = $wshop_get_inspection_form_field_result['comment'];

                            }
                        }
                    }
                }
            } elseif ($field['default_value'] && $field['type'] != 'link') {
                if (in_array($field['type'], ['date_picker_time', 'date_picker'])) {
                    if ($timestamp = strtotime($field['default_value'])) {
                        $value = $field['type'] == 'date_picker' ? date('Y-m-d', $timestamp) : date('Y-m-d H:i', $timestamp);
                    }
                } else {
                    $value = $field['default_value'];
                }
            }

            $_input_attrs = [];

            if ($field['required'] == 1) {
                $_input_attrs['data-custom-field-required'] = true;
            }


            $_input_attrs['data-fieldto'] = $field['fieldto'];
            $_input_attrs['data-fieldid'] = $field['id'];
            $_input_attrs['data-inspection_form_id'] = $field['inspection_form_id'];

            $cf_name = 'custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']';

            if ($part_item_name != '') {
                $cf_name = $part_item_name . '[custom_fields][items][' . $field['id'] . ']';
            }

            if ($items_add_edit_preview) {
                $cf_name = '';
            }

            if($field['required'] == 1 && ($field['type'] != 'select' && $field['type'] != 'multiselect')){
                $field_name = '<html><small class="req text-danger">* </small>'.$field['name'].'</html>';
            }else{
                $field_name = '<html>'.$field['name'].'</html>';
            }

            if ($field['type'] == 'input' || $field['type'] == 'number') {
                $t = $field['type'] == 'input' ? 'text' : 'number';
                $fields_html .= render_input($cf_name, $field_name, $value, $t, $_input_attrs);
            } elseif ($field['type'] == 'date_picker') {
                $fields_html .= render_date_input($cf_name, $field_name, _d($value), $_input_attrs);
            } elseif ($field['type'] == 'date_picker_time') {
                $fields_html .= render_datetime_input($cf_name, $field_name, _dt($value), $_input_attrs);
            } elseif ($field['type'] == 'textarea') {
                $fields_html .= render_textarea($cf_name, $field_name, $value, $_input_attrs);
            } elseif ($field['type'] == 'colorpicker') {
                $fields_html .= render_color_picker($cf_name, $field_name, $value, $_input_attrs);
            } elseif ($field['type'] == 'attachment') {
              $attachment_html = wshop_get_inspection_form_attachment_value($rel_id, $field['id'], ($items_pr ? 'items_pr' : $belongs_to), false);

                $t = 'file';
                $field['type'] = 'file';
                $_input_attrs['multiple'] = 'true';
                $cf_name .= '[]';

                $fields_html .= render_input($cf_name, $field_name, $value, $t, $_input_attrs);
                $fields_html .= $attachment_html;

            } elseif ($field['type'] == 'select' || $field['type'] == 'multiselect') {
                $_select_attrs = [];
                $select_attrs  = '';
                $select_name   = $cf_name;

                if ($field['required'] == 1) {
                    $_select_attrs['data-custom-field-required'] = true;
                }

                $_select_attrs['data-fieldto'] = $field['fieldto'];
                $_select_attrs['data-fieldid'] = $field['id'];
                $_select_attrs['data-inspection_form_id'] = $field['inspection_form_id'];

                if ($field['type'] == 'multiselect') {
                    $_select_attrs['multiple'] = true;
                    $select_name .= '[]';
                }

                foreach ($_select_attrs as $key => $val) {
                    $select_attrs .= $key . '=' . '"' . $val . '" ';
                }

                if ($field['required'] == 1) {
                    $field_name = '<small class="req text-danger">* </small>' . $field_name;
                }

                $fields_html .= '<div class="form-group">';
                $fields_html .= '<label for="' . $cf_name . '" class="control-label">' . $field_name . '</label>';
                $fields_html .= '<select ' . $select_attrs . ' name="' . $select_name . '" class="' . ($items_add_edit_preview == false ? 'select-placeholder ': '') . 'selectpicker form-control' . ($field['type'] == 'multiselect' ? ' custom-field-multi-select' : '') . '" data-width="100%" data-none-selected-text="' . _l('dropdown_non_selected_tex') . '"  data-live-search="true">';

                $fields_html .= '<option value=""' . ($field['type'] == 'multiselect' ? ' class="hidden"' : '') . '></option>';

                $options = new_strlen($field['options']) ? json_decode($field['options']) : null;

                if ($field['type'] == 'multiselect') {
                    $value = explode(',', $value);
                }

                foreach ($options as $option) {
                    $option = trim($option);
                    if ($option != '') {
                        $selected = '';
                        if ($field['type'] == 'select') {
                            if ($option == $value) {
                                $selected = ' selected';
                            }
                        } else {
                            foreach ($value as $v) {
                                $v = trim($v);
                                if ($v == $option) {
                                    $selected = ' selected';
                                }
                            }
                        }

                        $fields_html .= '<option value="' . $option . '"' . $selected . '' . set_select($cf_name, $option) . '>' . $option . '</option>';
                    }
                }
                $fields_html .= '</select>';
                $fields_html .= '</div>';
            } elseif ($field['type'] == 'checkbox') {
                $fields_html .= '<div class="form-group chk">';

                $fields_html .= '<br /><label class="control-label' . ($field['display_inline'] == 0 ? ' no-mbot': '') . '" for="' . $cf_name . '[]">' . $field_name . '</label>' . ($field['display_inline'] == 1 ? ' <br />': '');

                $options = new_strlen($field['options']) ? json_decode($field['options']) : null;

                $value = explode(',', $value);
                foreach ($options as $option) {
                    $checked = '';

                    // Replace double quotes with single.
                    $option = str_replace('"', '\'', $option);

                    $option = trim($option);
                    foreach ($value as $v) {
                        $v = trim($v);
                        if ($v == $option) {
                            $checked = 'checked';
                        }
                    }

                    $_chk_attrs                 = [];
                    $chk_attrs                  = '';
                    $_chk_attrs['data-fieldto'] = $field['fieldto'];
                    $_chk_attrs['data-fieldid'] = $field['id'];
                    $_chk_attrs['data-inspection_form_id'] = $field['inspection_form_id'];


                    if ($field['required'] == 1) {
                        $_chk_attrs['data-custom-field-required'] = true;
                    }

                    foreach ($_chk_attrs as $key => $val) {
                        $chk_attrs .= $key . '=' . '"' . $val . '" ';
                    }

                    $input_id = 'cfc_' . $field['id'] . '_' . slug_it($option) . '_' . app_generate_hash();

                    $fields_html .= '<div class="checkbox' . ($field['display_inline'] == 1 ? ' checkbox-inline': '') . '">';
                    $fields_html .= '<input class="custom_field_checkbox" ' . $chk_attrs . ' ' . set_checkbox($cf_name . '[]', $option) . ' ' . $checked . ' value="' . $option . '" id="' . $input_id . '" type="checkbox" name="' . $cf_name . '[]">';

                    $fields_html .= '<label for="' . $input_id . '" class="cf-chk-label">' . $option . '</label>';
                    $fields_html .= '<input type="hidden" name="' . $cf_name . '[]" value="cfk_hidden">';
                    $fields_html .= '</div>';
                }
                $fields_html .= '</div>';
            } elseif ($field['type'] == 'link') {
                if (startsWith($value, 'http')) {
                    $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                }

                $fields_html .= '<div class="form-group cf-hyperlink" data-fieldto="' . $field['fieldto'] . '" data-field-id="' . $field['id'] . '" data-value="' . e($value) . '" data-field-name="' . e($field_name) . '">';
                $fields_html .= '<label class="control-label" for="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']">' . $field_name . '</label></br>';

                $fields_html .= '<a id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_popover" type="button" href="javascript:">' . _l('cf_translate_input_link_tip') . '</a>';

                $fields_html .= '<input type="hidden" ' . ($field['required'] == 1 ? 'data-custom-field-required="1"' : '') . ' value="" id="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']" name="custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']">';

                $field_template = '';
                $field_template .= '<div id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_popover-content" class="hide cfh-field-popover-template"><div class="form-group">';
                $field_template .= '<div class="row"><div class="col-md-12"><label class="control-label" for="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_title">' . _l('cf_translate_input_link_title') . '</label>';
                $field_template .= '<input type="text" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_title" value="" class="form-control">';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '<div class="form-group">';
                $field_template .= '<div class="row">';
                $field_template .= '<div class="col-md-12">';
                $field_template .= '<label class="control-label" for="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_link">' . _l('cf_translate_input_link_url') . '</label>';
                $field_template .= '<div class="input-group"><input type="text" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_link" value="" class="form-control"><span class="input-group-addon"><a href="#" id="cf_hyperlink_open_' . $field['id'] . '" target="_blank"><i class="fa fa-globe"></i></a></span></div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $field_template .= '<div class="row">';
                $field_template .= '<div class="col-md-6">';
                $field_template .= '<button type="button" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_btn-cancel" class="btn btn-default btn-md pull-left" value="">' . _l('cancel') . '</button>';
                $field_template .= '</div>';
                $field_template .= '<div class="col-md-6">';
                $field_template .= '<button type="button" id="custom_fields_' . $field['fieldto'] . '_' . $field['id'] . '_btn-save" class="btn btn-primary btn-md pull-right" value="">' . _l('apply') . '</button>';
                $field_template .= '</div>';
                $field_template .= '</div>';
                $fields_html .= '<script>';
                $fields_html .= 'cfh_popover_templates[\'' . $field['id'] . '\'] = \'' . $field_template . '\';';
                $fields_html .= '</script>';
                $fields_html .= '</div>';
            }

            $name = $cf_name;

            if ($field['type'] == 'checkbox' || $field['type'] == 'multiselect') {
                $name .= '[]';
            }

            $fields_html .= form_error($name);

            if ((has_permission('workshop_inspection', '', 'create') || has_permission('workshop_inspection', '', 'edit'))
                && ($items_add_edit_preview == false && $items_applied == false && 1==2)
                && (!defined('CLIENTS_AREA') || hooks()->apply_filters('show_custom_fields_edit_link_on_clients_area', false))) {
                    $required_checked = '';
                if($field['required'] == 1){
                    $required_checked = 'checked';
                }
                $fields_html .= '<div class="col-md-6 pull-right">';
                $fields_html .= '<div class="pull-right mleft10">
                <div class="form-group">
                <div class="checkbox checkbox-primary mtop5">
                <input type="checkbox" id="required_'.$field['inspection_form_id'].'_'.$field['id'].'" name="required_'.$field['inspection_form_id'].'_'.$field['id'].'" value="1" '.$required_checked.'  data-id="'.$field['id'].'">
                <label for="required_'.$field['inspection_form_id'].'_'.$field['id'].'">'._l('inspection_template_form_detail_required').'</label>
                </div>
                </div>
                </div>';
                $fields_html .= '<div class="border-right pull-right"><span class="label label-tag ">'._l('inspection_template_form_detail_add_edit_type').'</span>: <span class="label btn btn-warning mright10">'.str_replace('_', ' ', $field['type']).'</span>';
                $fields_html .= '</div>';

                $fields_html .= '</div>';
            }

            $inspection_result_good_checked = '';
            $inspection_result_good_repair = '';
            $inspection_hide_comment = ' hide';

            if($inspection_result == 'good'){
                $inspection_hide_comment = ' hide';
                $inspection_result_good_checked = 'checked';
            }
            if($inspection_result == 'repair'){
                $inspection_hide_comment = '';
                $inspection_result_good_repair = 'checked';
            }

            $fields_html .= '<textarea id="inspection_comment'.$field['inspection_form_id'].'_'.$field['id'].'" name="inspection_comment[form_fieldset_'.$field['inspection_form_id'].']['.$field['id'].']" class="form-control '.$inspection_hide_comment.'" data-id="'.$field['id'].'" rows="4" aria-invalid="false" placeholder="'._l('wshop_comment').'">'.$inspection_comment.'</textarea>';

            if (!$items_add_edit_preview && !$items_applied) {
                $fields_html .= '</div>';
            } elseif ($items_add_edit_preview) {
                $fields_html .= '</td>';
            } elseif ($items_applied) {
                $fields_html .= '</td>';
            }

            if ((has_permission('workshop_inspection', '', 'create') || has_permission('workshop_inspection', '', 'edit'))
                && ($items_add_edit_preview == false && $items_applied == false)
                && (!defined('CLIENTS_AREA') || hooks()->apply_filters('show_custom_fields_edit_link_on_clients_area', false))) {
                
                $fields_html .= '<div class="col-md-2">
            <div class="form-group tw-flex tw-justify-between">
                <div class="radio radio-primary radio-inline mtop5">
                <input type="radio" id="inspection_result_good'.$field['inspection_form_id'].'_'.$field['id'].'" name="inspection_result[form_fieldset_'.$field['inspection_form_id'].']['.$field['id'].']" data-comment="inspection_comment[form_fieldset_'.$field['inspection_form_id'].']['.$field['id'].']" value="good"  data-id="'.$field['id'].'" '.$inspection_result_good_checked.' >
                <label for="inspection_result_good'.$field['inspection_form_id'].'_'.$field['id'].'">'._l('wshop_good').'</label>
                </div>
                
                <div class="radio radio-primary radio-inline mtop5">
                <input type="radio" id="inspection_result_repair'.$field['inspection_form_id'].'_'.$field['id'].'" name="inspection_result[form_fieldset_'.$field['inspection_form_id'].']['.$field['id'].']" 
                data-comment="inspection_comment[form_fieldset_'.$field['inspection_form_id'].']['.$field['id'].']"
                value="repair"  data-id="'.$field['id'].'" '.$inspection_result_good_repair.' >
                <label for="inspection_result_repair'.$field['inspection_form_id'].'_'.$field['id'].'">'._l('wshop_repair').'</label>
                </div>
                </div>
                

            </div>';
            $fields_html .= '<div class="col-md-2 form-group tw-flex tw-justify-between"><a href="javascript:void(0)" onclick="add_labour_product('.$field['id'].'); return false;" class="btn btn-sm btn-primary pull-right" data-toggle="tooltip" data-original-title="'._l('wshop_add_labour_product').'" data-placement="bottom"><i class="fa-solid fa-user-check"></i></a><a href="javascript:void(0)" onclick="add_part('.$field['id'].'); return false;" class="btn btn-sm btn-primary pull-right" data-toggle="tooltip" data-original-title="'._l('wshop_add_part').'" data-placement="bottom"><i class="fa-solid fa-recycle"></i></a></div>';

            }
            $fields_html .= '<div class="clearfix"></div><div class="col-md-12"><hr /></div></div>';

        }
       
        $labour_product_row_template = '';
        $part_row_template = '';

        $CI->db->where('inspection_id', $rel_id);
        $CI->db->where('inspection_form_id', $inspection_form_id);
        $inspection_labour_products = $CI->db->get(db_prefix().'wshop_repair_job_labour_products')->result_array();

        $CI->db->where('inspection_id', $rel_id);
        $CI->db->where('inspection_form_id', $inspection_form_id);
        $inspection_parts = $CI->db->get(db_prefix().'wshop_repair_job_labour_materials')->result_array();

        if(count($inspection_labour_products) > 0){
            $labour_index = 0;
            foreach ($inspection_labour_products as $key => $labour_product) {
                $labour_index++;

                $labour_product_row_template .= $CI->workshop_model->inspection_create_labour_product_row_template('labouritems[' . $labour_index . ']', $labour_product['labour_product_id'], $labour_product['name'], $labour_product['description'], $labour_product['inspection_id'], $labour_product['inspection_form_id'], $labour_product['inspection_form_detail_id'], $labour_product['labour_type'], $labour_product['estimated_hours'], $labour_product['unit_price'], $labour_product['qty'], $labour_product['tax_id'], $labour_product['tax_rate'], $labour_product['tax_name'], $labour_product['discount'], $labour_product['subtotal'], $labour_product['id'], true);
            }
        }

        if(count($inspection_parts) > 0){
            $part_index = 0;
            foreach ($inspection_parts as $key => $material) {
                $part_index++;

                $part_row_template .= $CI->workshop_model->inspection_create_part_row_template('partitems[' . $part_index . ']', $material['item_id'], $material['name'], $material['description'], $material['inspection_id'], $material['inspection_form_id'], $material['inspection_form_detail_id'], $material['rate'], $material['qty'], $material['estimated_qty'], $material['tax_id'], $material['tax_rate'], $material['tax_name'],  $material['discount'], $material['subtotal'], $material['id'], true);
            }
        }


        // add labour product
        $fields_html .= $CI->load->view('inspections/inspection_template_forms/labour_product_template', ['labour_product_row_template' => $labour_product_row_template], true);
        // add part
        $fields_html .= $CI->load->view('inspections/inspection_template_forms/part_template', ['part_row_template' => $part_row_template], true);
        

        // close row
        if (!$items_add_edit_preview && !$items_applied && $one_item) {
            $fields_html .= '</div>';
        }
    }

    return $fields_html;
}

/**
 * Get custom fields
 * @param  string  $field_to
 * @param  array   $where
 * @param  boolean $exclude_only_admin
 * @return array
 */
function wshop_get_inspection_form_fields($field_to, $where = [], $exclude_only_admin = false)
{
    $is_admin = is_admin();
    $CI       = & get_instance();
    $CI->db->where('fieldto', $field_to);
    if ((is_array($where) && count($where) > 0) || (!is_array($where) && $where != '')) {
        $CI->db->where($where);
    }
    
    $CI->db->where('active', 1);
    $CI->db->order_by('field_order', 'asc');

    $results = $CI->db->get(db_prefix() . 'wshop_inspection_form_details')->result_array();

    foreach ($results as $key => $result) {
        $results[$key]['name'] = _wshop_maybe_translate_inspection_form_field_name(e($result['name']), $result['slug']);
    }

    return $results;
}

function _wshop_maybe_translate_inspection_form_field_name($name, $slug)
{
    return _l('cf_translate_' . $slug, '', false) != 'cf_translate_' . $slug ? _l('cf_translate_' . $slug, '', false) : $name;
}

/**
 * Return custom fields checked to be visible to tables
 * @param  string $field_to field relation
 * @return array
 */
function wshop_get_table_inspection_form_fields($field_to)
{
    return wshop_get_inspection_form_fields($field_to, ['show_on_table' => 1]);
}
/**
 * Get custom field value
 * @param  mixed $rel_id              the main ID from the table, e.q. the customer id, invoice id
 * @param  mixed $field_id_or_slug    field id, the custom field ID or custom field slug
 * @param  string $field_to           belongs to e.q leads, customers, staff
 * @param  string $format             format date values
 * @return string
 */
function wshop_get_inspection_form_field_value($rel_id, $field_id_or_slug, $field_to, $format = true)
{
    $CI = & get_instance();

    $CI->db->select(db_prefix() . 'wshop_inspection_values.value,'.db_prefix() . 'wshop_inspection_values.inspection_result,' . db_prefix() . 'wshop_inspection_form_details.type');
    $CI->db->join(db_prefix() . 'wshop_inspection_form_details', db_prefix() . 'wshop_inspection_form_details.id=' . db_prefix() . 'wshop_inspection_values.inspection_form_detail_id');
    $CI->db->where(db_prefix() . 'wshop_inspection_values.relid', $rel_id);
    if (is_numeric($field_id_or_slug)) {
        $CI->db->where(db_prefix() . 'wshop_inspection_values.inspection_form_detail_id', $field_id_or_slug);
    } else {
        $CI->db->where(db_prefix() . 'wshop_inspection_form_details.slug', $field_id_or_slug);
    }
    $CI->db->where(db_prefix() . 'wshop_inspection_values.fieldto', $field_to);

    $row = $CI->db->get(db_prefix() . 'wshop_inspection_values')->row();

    $result = '';
    if ($row) {
        $result = $row->value;
        if ($format == true) {
            if ($row->type == 'date_picker') {
                $result = _d($result);
            } elseif ($row->type == 'date_picker_time') {
                $result = _dt($result);
            }
        }
    }

    return $result;
}

/**
 * wshop_get_inspection_form_field_result
 * @param  [type]  $rel_id           
 * @param  [type]  $field_id_or_slug 
 * @param  [type]  $field_to         
 * @param  boolean $format           
 * @return [type]                    
 */
function wshop_get_inspection_form_field_result($rel_id, $field_id_or_slug, $field_to, $format = true)
{
    $CI = & get_instance();

    $CI->db->select(db_prefix() . 'wshop_inspection_values.value,'.db_prefix() . 'wshop_inspection_values.inspection_result,'.db_prefix() . 'wshop_inspection_values.comment,'.db_prefix() . 'wshop_inspection_values.approve,'.db_prefix() . 'wshop_inspection_values.approved_date,' . db_prefix() . 'wshop_inspection_form_details.type');
    $CI->db->join(db_prefix() . 'wshop_inspection_form_details', db_prefix() . 'wshop_inspection_form_details.id=' . db_prefix() . 'wshop_inspection_values.inspection_form_detail_id');
    $CI->db->where(db_prefix() . 'wshop_inspection_values.relid', $rel_id);
    if (is_numeric($field_id_or_slug)) {
        $CI->db->where(db_prefix() . 'wshop_inspection_values.inspection_form_detail_id', $field_id_or_slug);
    } else {
        $CI->db->where(db_prefix() . 'wshop_inspection_form_details.slug', $field_id_or_slug);
    }
    $CI->db->where(db_prefix() . 'wshop_inspection_values.fieldto', $field_to);

    $row = $CI->db->get(db_prefix() . 'wshop_inspection_values')->row();

    $result = '';
    $comment = '';
    $approve = NULL;
    $approved_date = NULL;
    if ($row) {
        $result = $row->inspection_result;
        $comment = $row->comment;
        $approve = $row->approve;
        $approved_date = $row->approved_date;
    }

    return ['result' => $result, 'comment' => $comment, 'approve' => $approve, 'approved_date' => $approved_date];
}

/**
 * Check for custom fields, update on $_POST
 * @param  mixed $rel_id        the main ID from the table
 * @param  array $custom_fields all custom fields with id and values
 * @return boolean
 */
function wshop_handle_inspection_form_fields_post($rel_id, $custom_fields, $is_cf_items = false, $inspection_result = [], $inspection_comment = [])
{
    $affectedRows = 0;
    $CI           = & get_instance();

    foreach ($custom_fields as $key => $fields) {
        $inspection_form_id = str_replace('form_fieldset_', '', $key);

        foreach ($fields as $field_id => $field_value) {
            $_inspection_result = NULL;
            $_inspection_comment = NULL;
            

            $CI->db->where('relid', $rel_id);
            $CI->db->where('inspection_form_detail_id', $field_id);
            $CI->db->where('fieldto', ($is_cf_items ? 'items_pr' : $key));
            $row = $CI->db->get(db_prefix() . 'wshop_inspection_values')->row();
            if (!is_array($field_value)) {
                $field_value = trim($field_value);
            }
            // Make necessary checkings for fields
            if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                $CI->db->where('id', $field_id);
                $field_checker = $CI->db->get(db_prefix() . 'wshop_inspection_form_details')->row();
                if ($field_checker->type == 'date_picker') {
                    $field_value = to_sql_date($field_value);
                } elseif ($field_checker->type == 'date_picker_time') {
                    $field_value = to_sql_date($field_value, true);
                } elseif ($field_checker->type == 'textarea') {
                    $field_value = nl2br($field_value);
                } elseif ($field_checker->type == 'checkbox' || $field_checker->type == 'multiselect') {
                    
                    if (is_array($field_value)) {
                        $v = 0;
                        foreach ($field_value as $chk) {
                            if ($chk == 'cfk_hidden') {
                                unset($field_value[$v]);
                            }
                            $v++;
                        }
                        $field_value = implode(', ', $field_value);
                    }
                }
            }
            if ($row) {
                if(isset($inspection_result[$key][$field_id])){
                    $_inspection_result = $inspection_result[$key][$field_id];
                    unset($inspection_result[$key][$field_id]);
                }

                if(isset($inspection_comment[$key][$field_id])){
                    $_inspection_comment = $inspection_comment[$key][$field_id];
                    unset($inspection_comment[$key][$field_id]);
                }

                $CI->db->where('id', $row->id);
                $CI->db->update(db_prefix() . 'wshop_inspection_values', [
                    'value' => $field_value,
                    'inspection_result'   => $_inspection_result,
                    'comment'   => $_inspection_comment,
                ]);
                if ($CI->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            } else {

                if ($field_value != '') {
                    if(isset($inspection_result[$key][$field_id])){
                        $_inspection_result = $inspection_result[$key][$field_id];
                        unset($inspection_result[$key][$field_id]);
                    }

                    if(isset($inspection_comment[$key][$field_id])){
                        $_inspection_comment = $inspection_comment[$key][$field_id];
                        unset($inspection_comment[$key][$field_id]);
                    }
                    
                    $CI->db->insert(db_prefix() . 'wshop_inspection_values', [
                        'relid'   => $rel_id,
                        'inspection_form_id' => $inspection_form_id,
                        'inspection_form_detail_id' => $field_id,
                        'fieldto' => $is_cf_items ? 'items_pr' : $key,
                        'value'   => $field_value,
                        'inspection_result'   => $_inspection_result,
                        'comment'   => $_inspection_comment,
                    ]);
                    $insert_id = $CI->db->insert_id();
                    if ($insert_id) {
                        $affectedRows++;
                    }
                }
            }
        }
    }

    foreach ($inspection_result as $key => $fields) {
        $inspection_form_id = str_replace('form_fieldset_', '', $key);

        foreach ($fields as $field_id => $field_value) {
            $_inspection_result = NULL;
            $_inspection_comment = NULL;
            if(isset($inspection_result[$key][$field_id])){
                $_inspection_result = $inspection_result[$key][$field_id];
                unset($inspection_result[$key][$field_id]);
            }
            if(isset($inspection_comment[$key][$field_id])){
                $_inspection_comment = $inspection_comment[$key][$field_id];
                unset($inspection_comment[$key][$field_id]);
            }
        

            $CI->db->where('relid', $rel_id);
            $CI->db->where('inspection_form_detail_id', $field_id);
            $CI->db->where('fieldto', ($is_cf_items ? 'items_pr' : $key));
            $row = $CI->db->get(db_prefix() . 'wshop_inspection_values')->row();
            
            if ($row) {
                $CI->db->where('id', $row->id);
                $CI->db->update(db_prefix() . 'wshop_inspection_values', [
                    'inspection_result'   => $_inspection_result,
                    'comment'   => $_inspection_comment,
                ]);
                if ($CI->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            } else {
                $CI->db->insert(db_prefix() . 'wshop_inspection_values', [
                    'relid'   => $rel_id,
                    'inspection_form_id' => $inspection_form_id,
                    'inspection_form_detail_id' => $field_id,
                    'fieldto' => $is_cf_items ? 'items_pr' : $key,
                    'value'   => '',
                    'inspection_result'   => $_inspection_result,
                    'comment'   => $_inspection_comment,
                ]);
                $insert_id = $CI->db->insert_id();
                if ($insert_id) {
                    $affectedRows++;
                }
            }
        }
    }

    if ($affectedRows > 0) {
        return true;
    }

    return false;
}

/**
 * Return items custom fields array for table html eq invoice html invoice pdf based on usage
 * @param  mixed $rel_id   rel id eq invoice id
 * @param  string $rel_type relation type eq invoice
 * @return array
 */
function wshop_get_items_inspection_form_fields_for_table_html($rel_id, $rel_type)
{
    $CI       = &get_instance();
    $whereSQL = 'id IN (SELECT inspection_form_detail_id FROM ' . db_prefix() . 'wshop_inspection_values WHERE value != "" AND value IS NOT NULL AND fieldto="items" AND relid IN (SELECT id FROM ' . db_prefix() . 'itemable WHERE rel_type="' . $CI->db->escape_str($rel_type) . '" AND rel_id="' . $CI->db->escape_str($rel_id) . '") GROUP BY id HAVING COUNT(id) > 0)';

    $whereSQL = hooks()->apply_filters('items_custom_fields_for_table_sql', $whereSQL);

    return wshop_get_inspection_form_fields('items', $whereSQL);
}
/**
 * Render custom fields for table add/edit preview area
 * @return string
 */
function wshop_render_inspection_form_fields_items_table_add_edit_preview()
{
    $where = hooks()->apply_filters('custom_fields_where_items_table_add_edit_preview', []);

    return wshop_render_inspection_form_fields('items', false, $where, [
        'add_edit_preview' => true,
    ]);
}
/**
 * Render custom fields for items for table which are already applied to eq. Invoice
 * @param  array $item      the $item variable from the foreach loop
 * @param  mixed $part_item_name the input name for items eq. newitems or items for existing items
 * @return string
 */
function wshop_render_inspection_form_fields_items_table_in($item, $part_item_name)
{
    $item_id = false;

    // When converting eq proposal to estimate,invoice etc to get tha previous item values for auto populate
    if (isset($item['parent_item_id'])) {
        $item_id = $item['parent_item_id'];
    } elseif (isset($item['id']) && $item['id'] != 0) {
        $item_id = $item['id'];
    }

    return wshop_render_inspection_form_fields('items', $item_id, [], [
        'items_applied'  => true,
        'part_item_name' => $part_item_name,
    ]);
}

/**
 * Get manually added company custom fields
 * @since Version 1.0.4
 * @return array
 */
function wshop_get_company_inspection_form_fields()
{
    $fields = wshop_get_inspection_form_fields('company');
    $i      = 0;
    foreach ($fields as $field) {
        $fields[$i]['label'] = $field['name'];
        $fields[$i]['value'] = wshop_get_inspection_form_field_value(0, $field['id'], 'company');
        $i++;
    }

    return $fields;
}

/**
 * wshop handle inspection form attachment post
 * @param  [type]  $rel_id            
 * @param  [type]  $custom_fields     
 * @param  boolean $is_cf_items       
 * @param  array   $inspection_result 
 * @return [type]                     
 */
function wshop_handle_inspection_form_attachment_post($rel_id, $attachment_files, $is_cf_items = false)
{
    $affectedRows = 0;
    $CI           = & get_instance();

    foreach ($attachment_files['name'] as $key => $fields) {
        $inspection_form_id = str_replace('form_fieldset_', '', $key);

        foreach ($fields as $field_id => $field_value) {
            foreach ($field_value as $file_index => $file_name) {
                if(!($file_name != "")){
                    continue;
                }

                $path = INSPECTION_QUESTION_FOLDER . $field_id . '/';
                $_tmp_name = $attachment_files['tmp_name'][$key][$field_id][$file_index];
                $_file_type = $attachment_files['type'][$key][$field_id][$file_index];

                if ($file_name != "") {

                        // Get the temp file path
                    $tmpFilePath = $_tmp_name;
                        // Make sure we have a filepath
                    if (!empty($tmpFilePath) && $tmpFilePath != '') {

                        _maybe_create_upload_path($path);

                        $filename    = unique_filename($path, $file_name);

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
                                'filetype'  => $_file_type,
                            ];

                            $CI->misc_model->add_attachment_to_database($field_id, 'wshop_inspection_qs', $attachment);

                        }
                    }
                }
            }
        }
    }


    if ($affectedRows > 0) {
        return true;
    }

    return false;
}

/**
 * wshop get inspection form attachment value
 * @param  [type]  $rel_id           
 * @param  [type]  $field_id_or_slug 
 * @param  [type]  $field_to         
 * @param  boolean $format           
 * @return [type]                    
 */
function wshop_get_inspection_form_attachment_value($rel_id, $field_id_or_slug, $field_to, $format = true, $is_delete = true)
{
    $question_html = '';
    $data = [];

    $CI = & get_instance();
    $CI->load->model('workshop/workshop_model');
    $inspection_question_attachments = $CI->workshop_model->get_attachment_file($field_id_or_slug, 'wshop_inspection_qs');
    $data['inspection_attachments'] = $inspection_question_attachments;
    $data['is_delete'] = $is_delete;
    $question_html .= $CI->load->view('inspections/inspection_template_forms/_render_inspection_attachment', $data, true);

    return $question_html;
}