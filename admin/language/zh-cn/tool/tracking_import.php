<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-12-14 11:22:04
 * @modified         2016-12-14 17:37:17
 */

// Heading
$_['heading_title']                = '快递信息导入';

// Text
$_['text_success']                 = '成功：您已成功导入您的数据';
$_['text_csv_template']            = '订单ID,快递公司编号,快递单号,发货附言';  // 此处的“,”号必须是英文半角
$_['text_help']                    = '使用说明：导入请使用csv格式（可使用excel编辑，然后另存为csv格式文件即可），要求导入的数据表表头为：订单ID,快递公司编号,快递单号,发货附言';

// Entry
$_['entry_file']                   = '导入的文件';

// Button labels
$_['button_import']                = '导入';

//Error
$_['error_permission']             = '警告: 您没有权限修改导入!';
$_['error_upload']                 = '导入的电子表格无效或其中的数据格式错误!';
$_['error_post_max_size']          = '文件大小超过 %s (查看php设置 \'post_max_size\')';
$_['error_upload_max_filesize']    = '文件大小超过 %s (查看php设置 \'upload_max_filesize\')';
$_['error_filetype']               = '文件类型错误，请使用csv格式文件导入';
$_['error_csv_template']           = '警告: 你的csv数据错误，数据应该是：%s!';
$_['error_tracking_code']          = '警告: 你的csv中快递公司代码“%s”错误!';
