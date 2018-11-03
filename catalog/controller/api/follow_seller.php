<?php
class ControllerApiFollowSeller extends Controller {
    public function index()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/wishlist');

        $allowKey       = ['api_token'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/wishlist');

        $results                = $this->model_account_wishlist->getWishlist();
        $wish_products          = [];

        if (!empty($results))
        {
            $this->load->model('catalog/product');
            $this->load->model('tool/image');

            foreach ($results as $result)
            {
                $product_info = $this->model_catalog_product->getProduct($result['product_id']);
                if ($product_info)
                {
                    $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));

                    if ($product_info['quantity'] <= 0) {
                        $stock = $product_info['stock_status'];
                    } elseif ($this->config->get('config_stock_display')) {
                        $stock = $product_info['quantity'];
                    } else {
                        $stock = $this->language->get('text_instock');
                    }

                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = '';
                    }

                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = '';
                    }

                    $wish_products[] = array(
                        'product_id' => $product_info['product_id'],
                        'thumb'      => $image,
                        'name'       => $product_info['name'],
                        'stock'      => $stock,
                        'price'      => $price,
                        'special'    => $special ? $special : $price
                    );
                } else {
                    $this->model_account_wishlist->deleteWishlist($result['product_id']);
                }
            }
        }

        $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$wish_products]));
    }

    public function remove()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/wishlist');

        $allowKey       = ['api_token','product_ids'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/wishlist');

        $product_ids         = [];
        if (!empty($req_data['product_ids'])) {
            $product_id      = explode(',', $req_data['product_ids']);
            foreach ($product_id as $value)
            {
                if ((int)$value > 0) $product_ids[(int)$value]   = (int)$value;
            }
        }

        if (empty($product_ids)) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_product_id')]));
        }

        $product_ids        = implode(',', $product_ids);

        $this->load->model('catalog/product');
        
        $this->model_account_wishlist->deleteWishlists($product_ids);

        return $this->response->setOutput($this->returnData(['msg'=>'success','data'=>'product delete success']));
    }

    public function setting()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/follow_seller');

        $allowKey       = ['api_token','seller_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           =  $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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

                return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$wish_total]));
                //如果存在说明是取消收藏
                if ($wish_total > 0) {
                    $this->model_account_wishlist->deleteWishlist((int)$product_id);
                    $wtype       = 0;
                }else{
                    $this->model_account_wishlist->addWishlist((int)$product_id);
                    $wtype       = 1;
                }
                
            } else {
                if (!isset($this->session->data['wishlist'])) $this->session->data['wishlist'] = [];

                //如果存在说明是取消收藏
                if (in_array($product_id, $this->session->data['wishlist'])) {

                    foreach ($this->session->data['wishlist'] as $key => $value) {
                        if ($value == $product_id) unset($this->session->data['wishlist'][$key]);
                    }
                    
                    $wtype       = 0;
                }else{
                    $this->session->data['wishlist'][]  = $product_id;
                    $this->session->data['wishlist']    = array_unique($this->session->data['wishlist']);
                    $wtype       = 1;
                }
            }

            $info       = $wtype == 1 ? $this->language->get('text_success') : $this->language->get('text_remove');
            $data       = ['wtype'=>$wtype,'info'=>$info];
            $data       = $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);
        }

        $this->response->setOutput($data);
    }
}
