<?php
// Heading
$_['heading_title']          = 'GD Export Import';

// Text
$_['text_success']           = 'Success: You have successfully modified your database!';
$_['text_base_data']         = 'Export/Import Base Data';
$_['text_product_data']      = 'Export/Import Product Data';

// Entry
$_['entry_exportway_sel']    = 'Export Method';
$_['entry_start_id']         = 'Product ID (From):';
$_['entry_end_id']           = 'Product ID (To):';
$_['entry_number']           = 'Export Count:';
$_['entry_index']            = 'Batch Index:';

$_['button_export']          = 'Export';
$_['button_import']          = 'Import';
$_['button_export_pid']      = 'By Product ID';
$_['button_export_page']     = 'By Batch';

// Error
$_['error_required']              = 'The field \'%s\' is required in \'%s\' sheet on line %s';
$_['error_languages_count']       = 'In "%s" sheet, %s = %s missing some languages, %s required.';

$_['error_exist_product']    = 'Prroduct ID %s already exists in DB, Please check your file of excel!';
$_['error_permission']       = 'Warning: You do not have permission to Export or Import!';
$_['error_upload']                 = 'The imported excel is invalid or the data format error is in it!';
$_['error_sheet_count']            = 'Export/Import: count of sheet is invalid!';
$_['error_header']                 = 'Export/Import: The header of the \'%s\' sheet is invalid!';
$_['error_post_max_size']          = 'Export/Import: The file size exceeds the %s limit(view the parameter \'post_max_size\' in file php.ini)';
$_['error_upload_max_filesize']    = 'Export/Import: The file size exceeds the %s limit(view the parameter \'upload_max_filesize\' in file php.ini)';
$_['error_page_no_data']           = 'Warning: No result!';
$_['error_min_pid']                = 'Warning: Product ID (From) required!';
$_['error_max_pid']                = 'Warning: Product ID (To) required!';
$_['error_min_page']               = 'Warning: Export Count required!';
$_['error_max_page']               = 'Warning: Batch Index required!';
$_['error_min_max']                = 'Warning: Product ID (From) shoud less than Product ID (To)!';
$_['error_exportway']              = 'Warning: Export Method error!';
