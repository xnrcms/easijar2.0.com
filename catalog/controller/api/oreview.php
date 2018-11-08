<?php
class ControllerApiOreview extends Controller {

    //已评价列表
    public function have_oreview()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/oreview');

        $allowKey       = ['api_token','page'];
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

        $this->load->model('account/oreview');


        $page 			= (int)array_get($req_data, 'page', 1);
        $limit 			= 10;

        $filter_data 	= [
            'filter_customer_id' 	=> $this->customer->getId(),
            'filter_reviewed' 		=> 1,
            'start' 				=> ($page - 1) * $limit,
            'limit' 				=> $limit,
        ];

        $results                	= $this->model_account_oreview->getOreviewsForApi($filter_data);
        $oreview_list 				= [];

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

                $option_data                    = \Models\Product::find($result['product_id'])->getVariantLabels();
                $opt                            = [];
                foreach ($option_data as $okey => $ovalue) {
                    $opt[]      = $ovalue['name'] . ':' . $ovalue['value'];
                }

                $images 						= [];
                $oreview_img  					= $this->model_account_oreview->getOreviewImages($result['reviewed']);
                foreach ($oreview_img as $value) {
                	if (image_exists($value['filename'])) {
	                    $images[] = $this->url->imageLink($value['filename']);
	                }
                }

                $oreview_list[]     			= [
                    'avatar'                	=> $this->model_tool_image->resize($this->customer->getAvatar(), 100, 100),
                    'author'                	=> $result['author'],
                    'oreview_date_added' 		=> $result['date_added'],
                    'oreview_rating'            => $result['rating'],
                    'oreview_text'              => $result['text'],
                    'oreview_img' 				=> $images,
                    'product_name'              => $result['name'],
                    'product_image' 			=> $image,
                    'product_price'             => $price,
                    'product_option' 			=> implode(',', $opt)
                ];
            }
        }

        $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$oreview_list]));
    }
}