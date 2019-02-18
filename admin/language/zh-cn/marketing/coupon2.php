<?php
/**
 *
 * @copyright        2017 www.guangdawangluo.com - All Rights Reserved
 * @author           opencart.cn <support@opencart.cn>
 * @created          2016-10-22 09:12:56
 * @modified         2016-11-05 17:35:25
 */

// Heading
$_['heading_title']       = '营销券';

// Text
$_['text_success']        = '成功：营销券已修改！';
$_['text_list']           = '营销券列表';
$_['text_add']            = '添加营销券';
$_['text_edit']           = '编辑营销券';
$_['text_percent']        = '百分比';
$_['text_amount']         = '固定金额';
$_['text_coupon']         = '优惠券历史';
$_['text_coupon_type1']   = '满减券';
$_['text_coupon_type2']   = '折扣券';
$_['text_coupon_type3']   = '红包券';
$_['text_launch_scene1']  = '通用';
$_['text_launch_scene2']  = '新人大礼包';

// Column
$_['column_name']         = '营销券名称';
$_['column_explain']      = '营销券说明';
$_['column_type']     	  = '营销券类型';
$_['column_order_total']  = '总金额';
$_['column_discount']     = '优惠力度';
$_['column_date_start']   = '开始日期';
$_['column_date_end']     = '结束日期';
$_['column_status']       = '状态';
$_['column_order_id']     = '订单号';
$_['column_customer']     = '客户';
$_['column_amount']       = '金额';
$_['column_date_added']   = '添加日期';
$_['column_action']       = '管理';

// Entry
$_['entry_name']          = '营销券名称';
$_['entry_explain']       = '营销券说明';
$_['entry_type']          = '营销券类型';
$_['entry_discount']      = '优惠力度';
$_['entry_order_total']   = '总金额';
$_['entry_date_start']    = '开始日期';
$_['entry_date_end']      = '结束日期';
$_['entry_coupon_total']  = '发放总数量';
$_['entry_get_limit']  	  = '领取次数';
$_['entry_uses_limit'] 	  = '使用次数';
$_['entry_status']        = '状态';
$_['entry_launch_scene']  = '投放渠道';

// Help
$_['help_explain']        = '对营销券使用进行解释说明';
$_['help_type']           = '注意：不同类型对应的优惠力度表现形式不同';
$_['help_discount']       = '注意：券类型为折扣券时对应的是一个百分比，如填50，则是50%。其他形式为固定金额';
$_['help_order_total']    = '在订单达到此金额以前不能使用优惠券。';
$_['help_coupon_total']   = '此券允许被领取的总数量，留空不限制';
$_['help_get_limit']   	  = '单个用户此券允许被领取的数量，默认只能领取一次';
$_['help_uses_limit']     = '单个用户此券被领取后允许被使用的次数，默认只能使用一次';
$_['help_launch_scene']   = '此处需要跟开发核实投放模块，防止误选导致营销券发放错误';

// Error
$_['error_permission']    = '错误：您没有权限修改优惠券！';
$_['error_exists']        = '错误：优惠券代码已经在使用！';
$_['error_name']          = '营销券券名称必须在 3 至 128 字符之间！';
$_['error_explain']       = '营销券券说明必须在 3 至 128 字符之间！';
$_['error_code']          = '代码必须在 3 至 10 字符之间！';
$_['error_coupon_type']   = '营销券类型错误！';
$_['error_discount_1']    = '满减券优惠力度必须是大于0并且小于总金额的数字！';
$_['error_discount_2']    = '折扣券优惠力度必须是0-100之间大于0数字！';
$_['error_discount_3']    = '红包券优惠力度必须是大于0并且小于总金额的数字！';
$_['error_order_total']   = '总金额必须是大于0的数字！';
$_['error_date_start']   	= '开始日期不能为空！';
$_['error_date_end']   		= '结束日期不能为空！';
$_['error_date_start_end']  = '结束日期不能小于开始日期！';
$_['error_coupon_total']   	= '发放总数量必须是大于0的数字！';
$_['error_get_limit']   	= '领取次数必须是大于0的数字！';
$_['error_uses_limit']   	= '使用次数必须是大于0的数字！';
$_['error_selected_coupon_id']   	= '请选择要操作的数据！';
$_['error_coupon_status_delete']   	= '你选择的营销券部分有正在进行中，请将其禁用再删除';
