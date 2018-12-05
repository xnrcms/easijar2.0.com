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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        //获取商品信息
        $product_info       = $this->model_account_order->getOrderProductForMsByOrderProductId($req_data['order_product_id']);
        if (empty($product_info) || !((int)$product_info['seller_id'] > 0 && (int)$product_info['seller_id'] === (int)$order_info['seller_id'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:product info is error']));
        }
        
        $quantity           = ((int)$req_data['quantity'] <= 0 || (int)$req_data['quantity'] >= (int)$product_info['quantity']) ? (int)$product_info['quantity'] : (int)$req_data['quantity'];
        $return_shipping    = $this->get_return_money($order_info['order_id'],$order_info['seller_id'],$product_info['quantity']);
        $return_money       = (float)$product_info['total'] + $return_shipping;
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

        $this->load->model('multiseller/return');
        $this->model_multiseller_return->addReturnHistoryForMs($return_id, 1,$proposal,$return_reason_id, $comment, $evidences,0);

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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        $return_info            = array_merge($return_info,$order_info);

        unset($return_info['comment']);
        unset($return_info['date_added']);
        unset($return_info['date_modified']);
        unset($return_info['reason']);
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

        $allowKey       = ['api_token','return_id','is_service','reason_id','comment','evidences'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        if ($return_info['return_status_id'] != 1) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_status is error']));
        }

        //获取最近一次记录ID
        $return_history_id              = $this->model_account_return->getReturnHistoryIdForMsByLast($return_id);
        if ($return_history_id <= 0) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return_history is error']));
        }

        $returnData                     = [];
        $returnData['proposal']         = (int)$req_data['is_service'] == 1 ? 6 : 7;
        $returnData['return_reason_id'] = (int)$req_data['reason_id'];
        $returnData['comment']          = $req_data['comment'];
        $returnData['evidences']        = $req_data['evidences'];

        $return_id                      = $this->model_account_return->editReturnHistoryForMsByLast($return_history_id,$returnData);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'update Success']));
    }

    public function return_history()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','return_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return info is error']));
        }

        $return_history                 = $this->model_account_return->getReturnHistorysForMs($return_id);

        $return_info                    = [];
        $return_info['return_id']       = $rinfo['return_id'];
        $return_info['seller_id']       = $rinfo['seller_id'];

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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:return info is error']));
        }

        if ($rinfo['return_status_id'] == 8) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:after sale application has been withdrawn']));
        }

        $this->load->model('multiseller/return');
        $this->model_multiseller_return->addReturnHistoryForMs($return_id, 8,8,$rinfo['return_reason_id']);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'after sale application has been withdrawn']));
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
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

        $return_shipping                = $this->get_return_money($order_info['order_id'],$order_info['seller_id'],$product_info['quantity']);

        $total                          = $product_info['total'];
        $currency_code                  = $product_info['currency_code'];
        $currency_value                 = $product_info['currency_value'];
        $return_money                   = $total + $return_shipping;

        $product_info['total']          = $this->currency->format($total, $currency_code, $currency_value, $this->session->data['currency']);
        $product_info['return_money']   = $this->currency->format($return_money, $currency_code, $currency_value, $this->session->data['currency']);

        unset($product_info['currency_code']);
        unset($product_info['currency_value']);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>[$product_info]]));
    }

    private function get_return_money($order_id = 0,$seller_id = 0,$quantity = 0)
    {
        $order_id           = (int)$order_id;
        $seller_id          = (int)$seller_id;
        $quantity           = (int)$quantity;

        if ($order_id <= 0 || $seller_id <= 0 || $quantity <= 0)  return 0;

        $totals             = $this->model_account_order->getTotalsForMs($order_id,$seller_id,$quantity);

        $return_shipping    = 0;
        foreach ($totals as $key => $value) {
            if ( $value['code'] === 'multiseller_shipping') {
                    $return_shipping    = round($value['value'] / $quantity,2);
            }
        }

        return $return_shipping;
    }
}
