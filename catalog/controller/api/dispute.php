<?php
class ControllerApiDispute extends Controller {

	//申请售后
	public function apply() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');
        $this->load->language('account/return');

        $allowKey       = ['api_token','order_sn','order_product_id','refund_money','is_receive','is_service','reason_id','evidences','quantity','comment'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        
        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/order');

        $order_info                     = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        //状态判断
        $order_status_id                = isset($order_info['order_status_id']) ? (int)$order_info['order_status_id'] : 0;
        if ( !in_array($order_status_id, [2,15]) ) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_status is error']));
        }

        //获取商品信息
        $product_info       = $this->model_account_order->getOrderProductForMsByOrderProductId($req_data['order_product_id']);
        if (empty($product_info) || !((int)$product_info['seller_id'] > 0 && (int)$product_info['seller_id'] === (int)$order_info['seller_id'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
        }

        $quantity           = ((int)$req_data['quantity'] <= 0 || (int)$req_data['quantity'] >= (int)$product_info['quantity']) ? (int)$product_info['quantity'] : (int)$req_data['quantity'];
        $return_money       = $this->get_return_money($order_info,$product_info);
        $refund_money       = ((float)$req_data['refund_money'] <= 0 || (float)$req_data['refund_money'] >= $return_money) ? $return_money : (float)$req_data['refund_money'];

        $this->load->model('account/return');

        //判断是否已经有了售后申请
        if ($this->model_account_return->getReturnRecord($order_info['order_id'],$req_data['order_product_id']) > 0) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_already')]));
        }

        $returnData                     = [];
        $returnData['order_id']         = $order_info['order_id'];
        $returnData['product_id']       = $req_data['order_product_id'];
        $returnData['fullname']         = $order_info['fullname'];
        $returnData['email']            = $order_info['email'];
        $returnData['telephone']        = $order_info['telephone'];
        $returnData['product']          = $product_info['name'];
        $returnData['model']            = $product_info['sku'];
        $returnData['quantity']         = $quantity;
        $returnData['opened']           = 0;
        $returnData['is_receive']       = (int)$req_data['is_receive'];
        $returnData['is_service']       = (int)$req_data['is_service'];
        $returnData['return_reason_id'] = (int)$req_data['reason_id'];
        $returnData['return_action_id'] = 0;
        $returnData['return_status_id'] = 1;
        $returnData['comment']          = $req_data['comment'];
        $returnData['date_ordered']     = $order_info['date_added'];
        $returnData['seller_id']        = $order_info['seller_id'];
        $returnData['image']            = $product_info['image'];
        $returnData['return_money']     = $refund_money;
        $returnData['order_status_id']  = $order_status_id;

        $return_id                      = $this->model_account_return->addReturn($returnData);
        $evidences                      = (isset($req_data['evidences']) && !empty($req_data['evidences'])) ? $req_data['evidences'] : '';
        
        /*//图片处理
        $image                          = (isset($req_data['evidences']) && !empty($req_data['evidences'])) ? explode(',', $req_data['evidences']) : [];
        $this->model_account_return->deleteReturnImagesByReturnId($return_id,$image);
        $this->model_account_return->addReturnImagesByReturnId($return_id,$image);*/

        //添加历史记录
        $comment                        = $returnData['comment'];
        $return_reason_id               = (int)$returnData['return_reason_id'];
        $proposal                       = $returnData['is_service'] == 1 ? 6 : 7;
        $overtime                       = 0;

        $this->load->model('multiseller/return');

        $history_data                           = [];
        $history_data['return_id']              = $return_id;
        $history_data['return_status_id']       = 1;
        $history_data['proposal']               = $proposal;
        $history_data['return_reason_id']       = $return_reason_id;
        $history_data['comment']                = $comment;
        $history_data['evidences']              = $evidences;
        $history_data['customer_id']            = $this->customer->getId();
        $history_data['utype']                  = 1;

        $this->model_multiseller_return->addReturnHistoryForMs($history_data);

        if ($order_status_id === 15) {
            //待发货状态 需要判断发货等待时间，如果距下单时间超过3天 自动处理退款
            $date_now                   = time();
            $date_added                 = isset($order_info['date_added']) ? strtotime($order_info['date_added']) : $date_now;
            $days                       = $date_now - $date_added;

            if ($days >= 86400*3) {
                //自动添加一条商家处理记录 
                $this->load->model('multiseller/return');

                $history_data                           = [];
                $history_data['return_id']              = $return_id;
                $history_data['return_status_id']       = 10;
                $history_data['comment']                = t('text_return_comment');
                $history_data['customer_id']            = $order_info['seller_id'];
                $history_data['utype']                  = 2;

                $this->model_multiseller_return->addReturnHistoryForMs($history_data);
                
                //商家承担手续费 2
                $this->model_multiseller_return->editReturnResponsibility($return_id,2);
            }else{
                
                $overtime = (time() + 86400*1);

                //买家承担手续费
                $this->model_multiseller_return->editReturnResponsibility($return_id,1);
            }
        }

        if ($order_status_id === 2) {
            $overtime = (time() + 86400*3);
        }

        //用户提交申请需要超时自动处理 
        $this->model_multiseller_return->editReturnOvertime($return_id,$overtime);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>['return_id'=> $return_id]]));
    }

    //获取退货退款详细信息
    public function return_info() 
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','return_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }
        
        $return_id      = (int)$req_data['return_id'];
        if ($return_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_id is error']));
        }

        $this->load->model('account/return');
        $this->load->model('tool/image');

        $return_info            = $this->model_account_return->getReturnForMs($return_id);

        $return_info['image']   = $this->model_tool_image->resize((!empty($return_info['image']) ? $return_info['image'] : 'no_image.png'), 100, 100);

        $order_id               = isset($return_info['order_id']) ? (int)$return_info['order_id'] : 0;
        $seller_id              = isset($return_info['seller_id']) ? (int)$return_info['seller_id'] : 0;
        $order_info             = $this->model_account_return->getSuborderInfo($order_id,$seller_id);
        
        $this->load->model('multiseller/seller');
        $seller_shipping        = $this->model_multiseller_seller->getSellerReturnAddress($seller_id);

        $seller_shipping['return_shipping_address']    = $seller_shipping['return_shipping_address'.(int)$this->config->get('config_language_id')];
        unset($seller_shipping['return_shipping_address1']);
        unset($seller_shipping['return_shipping_address2']);

        //根据订单状态分发信息
        $overtime               = 0;
        $status2                = 0;
        $order_status_id        = isset($order_info['order_status_id']) ? (int)$order_info['order_status_id'] : 0;
        $responsibility         = isset($return_info['responsibility']) ? (int)$return_info['responsibility'] : 0;

        if ($return_info['return_status_id'] == 1) {//待审核
            $overtime           = (int)$return_info['overtime'];
            $status2            = 1;
        }

        //申请结束或者买家撤销
        if ($return_info['return_status_id'] == 3 || $return_info['return_status_id'] == 8) {
            $overtime       = 0;
            $status2        = 1;
        }

        //拒绝状态
        $refuse_nums            = 0;
        if ($return_info['return_status_id'] == 4) {
            //拒绝次数
            $refuse_nums        = $this->model_account_return->getReturnHistoryForRefuseNums($return_id);
            if ($refuse_nums >= 2) {
                $overtime       = 0;
                $status2        = 2;
            }else{
                $overtime       = (int)$return_info['overtime'];
                $status2        = 1;
            }
        }

        switch ($order_status_id) {
            case 15://待发货

                if ($return_info['return_status_id'] == 1 && $responsibility == 1) {
                    $status2            = 2;
                }

                if ($return_info['return_status_id'] == 10) {//商家自动退款，退款中
                    if ($responsibility == 1) {//发货时效3内 - 卖家同意
                        $overtime           = (int)$return_info['overtime'];
                        $status2            = 4;
                    }else{
                        $overtime           = (int)$return_info['overtime'];
                        $status2            = 3;
                    }
                }
                
                if ($return_info['return_status_id'] == 4) {//拒绝
                    if ($responsibility == 1) {//发货时效3内 - 卖家拒绝
                        $overtime       = 0;
                        $status2        = 4;
                    }

                    //平台总裁拒绝
                    if($refuse_nums >= 3){
                        $overtime       = 0;
                        $status2        = 3;
                    }
                }

                break;
            case 2://待收货
                if ((int)$return_info['is_service'] == 2) {//退货退款

                    if ($return_info['return_status_id'] == 2) {//等待寄回商品
                        $overtime       = (int)$return_info['overtime'];//10天内将货品寄回
                        $status2        = in_array($responsibility, [1,2]) ? $responsibility + 1 : 1;

                    }

                    if ($return_info['return_status_id'] == 5) {//等待寄回商品
                        $overtime       = (int)$return_info['overtime'];
                        $status2        = 1;//10天内将货品寄回
                    }

                    if ($return_info['return_status_id'] == 6) {//商家已收到退货，退款中
                        $overtime       = 0;
                        $status2        = 1;//两天内打款
                    }
                }else{//仅仅退款
                    if ($return_info['return_status_id'] == 10) {//退款中
                        //平台仲裁同意
                        if ($refuse_nums >= 2) {
                            $overtime       = 0;
                            $status2        = 2;//两天内打款
                        }else{
                            $overtime       = 0;
                            $status2        = 1;//两天内打款
                        }
                    }
                }

                if ($return_info['return_status_id'] == 4) {//拒绝
                    //平台仲裁拒绝
                    if($refuse_nums >= 3){
                        if ($responsibility == 1) {
                            $overtime       = (int)$return_info['overtime'];
                            $status2        = 2;
                        }else{
                            $overtime       = (int)$return_info['overtime'];
                            $status2        = 3;
                        }
                    }
                }

                break;
            default:  break;
        }

        $return_info['status2']     = (string)$status2;
        $return_info['overtime']    = (int)$return_info['overtime'];
        $return_info['comment']     = $this->model_account_return->getReturnHistoryComment(['return_id'=>$return_id,'customer_id'=>$seller_id]);

        //获取商品信息 计算最多能退多少钱
        $this->load->model('account/order');

        $product_info                   = $this->model_account_order->getOrderProductForMsByOrderProductId($return_info['product_id']);
        if (empty($product_info) || !((int)$product_info['seller_id'] > 0 && (int)$product_info['seller_id'] === $seller_id)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
        }

        $currency_code                  = $product_info['currency_code'];
        $currency_value                 = $product_info['currency_value'];
        $return_money                   = $this->get_return_money($return_info,$product_info);

        $return_info['return_money']    = $this->currency->format($return_money, $currency_code, $currency_value, $this->session->data['currency']);
        
        $return_info                    = array_merge($return_info,$order_info,$seller_shipping);

        unset($return_info['date_added']);
        unset($return_info['date_modified']);
        unset($return_info['action']);
        unset($return_info['telephone']);
        unset($return_info['email']);
        unset($return_info['fullname']);
        unset($return_info['order_id']);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$return_info]));
    }

    //修改申请
    public function update()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/return');

        $allowKey       = ['api_token','return_id','is_service','reason_id','comment','evidences'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $return_id      = (int)$req_data['return_id'];
        if ($return_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_id is error']));
        }

        $this->load->model('account/return');

        //获取申请信息
        $return_info                    = $this->model_account_return->getReturnForMs($return_id);
        if (empty($return_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_info')]));
        }

        if ($return_info['return_status_id'] != 1 && $return_info['return_status_id'] != 4) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_info_status')]));
        }

        $returnData                         = [];
        $returnData['return_status_id']     = 1;
        $returnData['return_id']            = $req_data['return_id'];
        $returnData['proposal']             = (int)$req_data['is_service'] == 1 ? 6 : 7;
        $returnData['return_reason_id']     = (int)$req_data['reason_id'];
        $returnData['comment']              = $req_data['comment'];
        $returnData['evidences']            = (isset($req_data['evidences']) && !empty($req_data['evidences'])) ? $req_data['evidences'] : '';;

        $this->model_account_return->addReturnHistoryForCs($returnData);
        
        $order_status_id                    = isset($return_info['order_status_id']) ? (int)$return_info['order_status_id'] : 0;
        $overtime                           = 0;

        if ($order_status_id === 15) {
            $overtime = (time() + 86400*1);
        }

        if ($order_status_id === 2) {
            $overtime = (time() + 86400*3);
        }

        $this->load->model('multiseller/return');
        
        //用户提交申请需要超时自动处理 
        $this->model_multiseller_return->editReturnOvertime($return_id,$overtime);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'update Success']));
    }

    public function return_history()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/return');

        $allowKey       = ['api_token','return_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $return_id      = (int)$req_data['return_id'];
        if ($return_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_id is error']));
        }

        $this->load->model('account/return');
        $this->load->model('tool/image');

        //获取申请信息
        $rinfo                          = $this->model_account_return->getReturnForMs($return_id);
        if (empty($rinfo)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_info')]));
        }

        $return_history                 = $this->model_account_return->getReturnHistorysForMs($return_id);

        $return_info                    = [];
        $return_info['return_id']       = $rinfo['return_id'];
        $return_info['seller_id']       = $rinfo['seller_id'];
        $return_info['is_receive']      = $rinfo['is_receive'];

        foreach ($return_history as $key => $value)
        {
            $avatar                             = (int)$value['customer_id'] > 0 ? $this->customer->getAvatar($value['customer_id']) : "avatar/0.jpg";
            $return_history[$key]['avatar']     = $this->model_tool_image->resize($avatar, 100, 100);

            $evidences_img              = !empty($value['evidences']) ? explode(',', $value['evidences']) : [];
            $evidences                  = [];
            foreach ($evidences_img as $evidences_val) {
                if (!empty($evidences_val)) {
                    $evidences[] = $this->model_tool_image->resize($evidences_val, 100, 100);
                }
            }

            $return_history[$key]['fullname']       = (int)$value['customer_id'] > 0 ? $value['fullname'] : 'EasiJAR';
            $return_history[$key]['evidences']      = $evidences;
            $return_history[$key]['receive_text']   =  in_array((int)$rinfo['is_receive'], [1,2]) ? t('text_return_receive'.(int)$rinfo['is_receive']) : '';
        }

        $json['return_info']            = $return_info;
        $json['return_history']         = $return_history;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    public function return_cancle()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','return_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $return_id      = (int)$req_data['return_id'];
        if ($return_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_id is error']));
        }

        $this->load->model('account/return');

        //获取申请信息
        $rinfo                          = $this->model_account_return->getReturnForMs($return_id);
        if (empty($rinfo)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_info')]));
        }

        if ($rinfo['return_status_id'] == 8) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:after sale application has been withdrawn']));
        }

        $this->load->model('multiseller/return');

        $history_data                           = [];
        $history_data['return_id']              = $return_id;
        $history_data['return_status_id']       = 8;
        $history_data['proposal']               = 8;
        $history_data['return_reason_id']       = $rinfo['return_reason_id'];
        $history_data['customer_id']            = $this->customer->getId();
        $history_data['utype']                  = 1;

        $this->model_multiseller_return->addReturnHistoryForMs($history_data);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'after sale application has been withdrawn']));
    }

    //保存退款物流信息
    public function save_logistics()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/return');

        $allowKey       = ['api_token','return_id','shipping_company','shipping_number','shipping_telephone','shipping_explain','shipping_image'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $return_id      = (int)$req_data['return_id'];
        if ($return_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_id is error']));
        }

        $this->load->model('account/return');

        //获取申请信息
        $return_info                    = $this->model_account_return->getReturnForMs($return_id);
        if (empty($return_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_return_info')]));
        }

        //快递数据
        $kd_tracking_data           = $this->config->get('module_aftership_data');
        $allow_tracking             = [];
        foreach ($kd_tracking_data as $key => $value) {
            if ($value['status'] == 1 && $value['sort_order'] >= 500) {
                $allow_tracking[$value['code']]     = $value['code'];
            }
        }

        //数据检验
        if (!in_array($req_data['shipping_company'], $allow_tracking)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_shipping_company')]));
        }

        if ((utf8_strlen(trim($req_data['shipping_number'])) < 3) || (utf8_strlen(trim($req_data['shipping_number'])) > 128))
            return $this->response->setOutput($this->returnData(['msg'=>t('error_shipping_number')]));

        $telephone      = trim(array_get($req_data, 'shipping_telephone',''),'+');
        $telephones     = explode('-', $telephone);
        if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_shipping_telephone')]));
        }

        $returnData                         = [];
        $returnData['return_id']            = $req_data['return_id'];
        $returnData['shipping_telephone']   = $req_data['shipping_telephone'];
        $returnData['shipping_company']     = $req_data['shipping_company'];
        $returnData['shipping_number']      = $req_data['shipping_number'];
        $returnData['shipping_explain']     = $req_data['shipping_explain'];
        $returnData['shipping_image']       = (isset($req_data['shipping_image']) && !empty($req_data['shipping_image'])) ? $req_data['shipping_image'] : '';

        $this->model_account_return->updateReturnLogistics($returnData);

        $this->load->model('multiseller/return');

        $comment    = sprintf(t('text_return_logistics'), $req_data['shipping_company'], $req_data['shipping_number'], $req_data['shipping_explain']);

        $history_data                           = [];
        $history_data['return_id']              = $req_data['return_id'];
        $history_data['return_status_id']       = 5;
        $history_data['comment']                = $comment;
        $history_data['customer_id']            = $this->customer->getId();
        $history_data['utype']                  = 1;

        $this->model_multiseller_return->addReturnHistoryForMs($history_data);

        //用户提交申请需要超时自动处理
        $overtime                   = (time() + 86400*60);
        $this->model_multiseller_return->editReturnOvertime($req_data['return_id'],$overtime);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'Save Success']));
    }

    //获取订单退款的商品信息
    public function product() 
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','order_product_id','order_sn'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }
        
        $order_product_id      = (int)$req_data['order_product_id'];
        if ($order_product_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:order_product_id is error']));
        }

        $this->load->model('account/order');

        $order_info                     = $this->model_account_order->getOrderForMs($req_data['order_sn']);
        if (empty($order_info)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_order_info')]));
        }

        //获取商品信息
        $product_info                   = $this->model_account_order->getOrderProductForMsByOrderProductId($req_data['order_product_id']);
        if (empty($product_info) || !((int)$product_info['seller_id'] > 0 && (int)$product_info['seller_id'] === (int)$order_info['seller_id'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
        }

        $currency_code                  = $product_info['currency_code'];
        $currency_value                 = $product_info['currency_value'];
        $return_money                   = $this->get_return_money($order_info,$product_info);

        $product_info['total']          = $this->currency->format($product_info['total'], $currency_code, $currency_value, $this->session->data['currency']);
        $product_info['return_money']   = $this->currency->format($return_money, $currency_code, $currency_value, $this->session->data['currency']);

        unset($product_info['currency_code']);
        unset($product_info['currency_value']);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$product_info]));
    }

    private function get_return_money($order_info = [],$product_info = [])
    {
        $order_id           = isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id          = isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;
        $quantity           = isset($product_info['quantity']) ? (int)$product_info['quantity'] : 0;
        $product_money      = isset($product_info['total']) ? (float)$product_info['total'] : 0;

        if ($order_id <= 0 || $seller_id <= 0 || $quantity <= 0)  return 0;

        $all_totals         = $this->model_account_order->getTotals($order_id);
        $totals             = $this->model_account_order->getTotalsForMs($order_id,$seller_id,$quantity);
        $order_products     = $this->model_account_order->getOrderProducts($order_id);
        $ms_order_products  = $this->model_account_order->getOrderProductsForMs($order_id,0);
        
        $money                      = 0;
        $seller_quantity            = 0;
        $sub_total                  = 0;
        $seller_shipping            = 0;
        $seller_product_shipping    = 0;
        $seller_coupon              = 0;
        $platform_coupon            = 0;
        $seller_product_total       = 0;
        $seller_use_platform        = 0;

        foreach ($all_totals as $atkey => $atvalue)
        {
            //店铺小计
            if ( $atvalue['code'] === 'total') {
                $sub_total   = $atvalue['value'];
            }

            //使用店铺运费金额
            $stitle      = '&#' . $seller_id . 'multiseller_shipping&#Multi-seller Shipping Fee';
            if ( $atvalue['code'] === 'multiseller_shipping' && strpos($atvalue['title'],$stitle) !== false && $atvalue['value'] > 0) {
                $seller_shipping    = $atvalue['value'];
            }

            //使用店铺优惠券金额
            $ctitle      = '&#' . $seller_id . 'multiseller_coupon&#';
            if ( $atvalue['code'] === 'multiseller_coupon' && strpos($atvalue['title'],$ctitle) !== false && $atvalue['value'] > 0) {
                $seller_coupon    = $atvalue['value'];
            }

            //平台优惠券
            $ptitle      = '&#0multiseller_coupon&#';
            if ( $atvalue['code'] === 'multiseller_coupon' && strpos($atvalue['title'],$ptitle) !== false && $atvalue['value'] > 0) {
                $platform_coupon    = $atvalue['value'];
            }
        }

        foreach ($ms_order_products as $mskey => $msvalue) {
            if ($seller_id > 0 && (int)$msvalue['seller_id'] === $seller_id) {
                $seller_product_total       += $msvalue['total'];
                $seller_quantity            += $msvalue['quantity'];
            }
        }

        $seller_total               = $seller_product_total + $seller_shipping - $seller_coupon;
        $seller_use_platform        = ($seller_total / $sub_total) * $platform_coupon;
        $seller_use_all             = $seller_coupon + $seller_use_platform;
        $seller_product_shipping    = ($seller_shipping / $seller_quantity) * $quantity;
        $money                      = $product_money + $seller_product_shipping - ($product_money / $seller_product_total) * $seller_use_all;

        return round($money,2);
    }
}
