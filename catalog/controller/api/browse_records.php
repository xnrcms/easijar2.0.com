<?php
class ControllerApiBrowseRecords extends Controller {

    //商品浏览记录
    public function product_records()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('api/browse_records');

        $allowKey       = ['api_token'];
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
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error2']));
        }

        if (!$this->customer->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        $this->load->model('account/product_browse_records');

        $results                = $this->model_account_product_browse_records->getProductBrowseRecords();
        $product_browse_records = [];

        if (!empty($results))
        {
            $this->load->model('tool/image');

            foreach ($results as $result)
            {
                $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = '';
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = '';
                }

                $product_browse_records[$result['browse_date']][]        = [
                    'product_id'                => (int)$result['product_id'],
                    'image'                     => $image,
                    'name'                      => $result['name'],
                    'price'                     => $this->currency->format($this->tax->calculate($price, $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                    'special'                   => $this->currency->format($this->tax->calculate(($special ? $special : $price), $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
                ];
            }
        }

        $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$product_browse_records]));
    }

    public function addProductBrowseRecords($product_id = 0)
    {
        $product_id = (int)$product_id;
        if ($product_id <= 0)  return false;

        $this->load->model('catalog/product');

        $product_info   = $this->model_catalog_product->getProduct($product_id);
        if ($product_info)
        {
            if ($this->customer->isLogged())
            {
                $this->load->model('account/product_browse_records');
                $this->model_account_product_browse_records->addProductBrowseRecords([(int)$product_id]);
                
            } else {
                if (!isset($this->session->data['ProductBrowseRecordsForIds'])) $this->session->data['ProductBrowseRecordsForIds'] = [];

                //如果存在说明是取消收藏
                if (!in_array($product_id, $this->session->data['ProductBrowseRecordsForIds'])) {
                    $this->session->data['ProductBrowseRecordsForIds'][]  = $product_id;
                    $this->session->data['ProductBrowseRecordsForIds']    = array_unique($this->session->data['ProductBrowseRecordsForIds']);
                }
            }

            return true;
        }

        return false;
    }
}
