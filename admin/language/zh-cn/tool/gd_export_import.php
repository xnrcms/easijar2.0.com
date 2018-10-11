<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-12-14 11:22:04
 * @modified         2016-12-14 17:37:17
 */

// Heading
$_['heading_title']                = '商品导入/导出';

// Text
$_['text_success']                 = '成功：您已成功导入您的数据';
$_['text_base_data']               = '基础数据导入导出';
$_['text_product_data']            = '商品数据导入导出';

// Entry
$_['entry_exportway_sel']          = '商品导出方式：';
$_['entry_start_id']               = '商品 ID （从）：';
$_['entry_end_id']                 = '商品 ID （到）：';
$_['entry_number']                 = '导出数量：';
$_['entry_index']                  = '导出批次：';

// Button labels
$_['button_import']                = '导入';
$_['button_export']                = '导出';
$_['button_export_pid']            = '按商品 ID 导出';
$_['button_export_page']           = '分批导出';

//Error
$_['error_required']              = '字段"%s"（工作表%s第%s行）不能为空！';
$_['error_languages_count']       = '工作表"%s"中%s为%s的记录语言数量不符，应该有%s种语言。';

$_['error_exist_product']          = '商品id %s 在数据库中已经存在, 请检查您的excel文件!';
$_['error_permission']             = '警告: 您没有权限修改导入/导出!';
$_['error_upload']                 = '导入的电子表格无效或其中的数据格式错误!';
$_['error_sheet_count']            = '导入/导出: 无效的工作表数量';
$_['error_header']                 = '导入/导出: 表“%s”的header头无效';
$_['error_post_max_size']          = '导入/导出: 文件大小超过 %s (查看php设置 \'post_max_size\')';
$_['error_upload_max_filesize']    = '导入/导出: 文件大小超过 %s (查看php设置 \'upload_max_filesize\')';
$_['error_page_no_data']           = '没有更多的产品数据';
$_['error_min_pid']                = '警告：商品ID（从）必填！';
$_['error_max_pid']                = '警告：商品ID（到）必填！';
$_['error_min_page']               = '警告：导出数量必填！';
$_['error_max_page']               = '警告：导出批次必填！';
$_['error_min_max']                = '警告：商品ID（从）不能大于商品ID（到）！';
$_['error_exportway']              = '警告：商品导出方式错误！';
