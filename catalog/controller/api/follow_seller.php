<?php
class ControllerApiFollowSeller extends Controller {
    public function index()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/follow_seller');

        $allowKey       = ['api_token'];
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
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/customer_follow_seller');

        $results                = $this->model_account_customer_follow_seller->getSellerFollow();
        $follow_seller          = [];

        if (!empty($results))
        {
            $this->load->model('multiseller/seller');
            $this->load->model('tool/image');

            foreach ($results as $result)
            {
                $seller_info = $this->model_multiseller_seller->getSeller($result['seller_id']);
                if ($seller_info)
                {
                    $avatar                 = !empty($seller_info['avatar']) ? $seller_info['avatar'] : 'no_image.png';
                    $avatar                 = $this->model_tool_image->resize($avatar, 100, 100);

                    $follow_seller[]        = [
                        'seller_id'     => (int)$seller_info['seller_id'],
                        'thumb'         => $avatar,
                        'name'          => $seller_info['store_name'],
                        'new_num'       => 0
                    ];
                } else {
                    $this->model_multiseller_seller->deleteSellerFollow($seller_info['seller_id']);
                }
            }
        }

        $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$follow_seller]));
    }

    public function remove()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/follow_seller');

        $allowKey       = ['api_token','seller_ids'];
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
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $seller_ids         = [];
        if (!empty($req_data['seller_ids'])) {
            $product_id      = explode(',', $req_data['seller_ids']);
            foreach ($product_id as $value)
            {
                if ((int)$value > 0) $seller_ids[(int)$value]   = (int)$value;
            }
        }

        if (empty($seller_ids)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_seller_id')]));
        }

        $seller_ids        = implode(',', $seller_ids);
        
        $this->load->model('account/customer_follow_seller');
        $this->model_account_customer_follow_seller->deleteSellerFollows($seller_ids);

        return $this->response->setOutput($this->returnData(['msg'=>'success','data'=>'product delete success']));
    }

    public function setting()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/follow_seller');

        $allowKey       = ['api_token','seller_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $this->load->model('multiseller/seller');

        $seller_id          = isset($req_data['seller_id']) ? $req_data['seller_id'] : 0;
        $seller_info        = $this->model_multiseller_seller->getSeller($seller_id);

        if ($seller_info) {
            if ($this->customer->isLogged()) {
                // Edit customers cart
                $this->load->model('account/customer_follow_seller');

                $wish_total     = $this->model_account_customer_follow_seller->getSellerFollowBySellerId((int)$seller_id);

                //如果存在说明是取消收藏
                if ($wish_total > 0) {
                    $this->model_account_customer_follow_seller->deleteSellerFollow((int)$seller_id);
                    $ftype       = 0;
                }else{
                    $this->model_account_customer_follow_seller->addSellerFollow((int)$seller_id);
                    $ftype       = 1;
                }
            } else {
                if (!isset($this->session->data['customer_follow_seller'])) $this->session->data['customer_follow_seller'] = [];

                //如果存在说明是取消收藏
                if (in_array($seller_id, $this->session->data['customer_follow_seller'])) {

                    foreach ($this->session->data['customer_follow_seller'] as $key => $value) {
                        if ($value == $seller_id) unset($this->session->data['customer_follow_seller'][$key]);
                    }
                    
                    $ftype       = 0;
                }else{
                    $this->session->data['customer_follow_seller'][]  = $seller_id;
                    $this->session->data['customer_follow_seller']    = array_unique($this->session->data['customer_follow_seller']);
                    $ftype       = 1;
                }
            }

            $info       = $ftype == 1 ? $this->language->get('text_success') : $this->language->get('text_remove');
            $data       = ['wtype'=>$ftype,'info'=>$info];
            $data       = $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
        }else{
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_no_seller')]));
        }

        $this->response->setOutput($data);
    }
}
