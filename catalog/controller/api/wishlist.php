<?php
class ControllerApiWishList extends Controller {
    public function index() {
        
    }

    public function setting()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/wishlist');

        $allowKey       = ['api_token','product_id'];
        $req_data       = $this->dataFilter($allowKey);
        $data           =  $this->returnData();

        if ($this->checkSign($req_data)) {
            
            $product_id = isset($req_data['product_id']) ? $req_data['product_id'] : 0;
            
            $this->load->model('catalog/product');

            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                if ($this->customer->isLogged()) {
                    // Edit customers cart
                    $this->load->model('account/wishlist');

                    $wish_total     = $this->model_account_wishlist->getIsWishFByProductId((int)$product_id);

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
        }else{

            $data       = $this->returnData(['msg'=>'fail:sign error']);
        }

        $this->response->setOutput($data);
    }
}
