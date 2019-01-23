<?php
class ControllerApiCategory extends Controller {
	public function index()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['api_token'];
		$req_data 		= $this->dataFilter($allowKey);

		if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['code'=>'207','msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }
        
        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }
		
		// Categories
        $this->load->model('catalog/category');
        $this->load->model('mobile/mobile');
        $this->load->model('catalog/product');


        $data['categories'] = [];

        $categories 		= $this->model_catalog_category->getCategories(0);
        
        //Level 1
        foreach ($categories as $category) {
            // Level 2
            $children 		= $this->model_catalog_category->getCategories($category['category_id']);
            $children_data 	= [];
            if ($children) {
                foreach ($children as $child) {
                    $grand_children 		= $this->model_catalog_category->getCategories($child['category_id']);
                    $grand_children_data 	= [];

                    if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($child['category_id'])) {
                        $image = image_resize($mobile_image, 150, 150);
                    } else {
                        $image = image_resize($child['image'], 150, 150);
                    }

                    $children_data[] = array(
                		'cid'     		 => $child['category_id'],
                        'thumb'          => $image,
                        'name'           => str_replace('&amp;', ' ', $child['name']),
                        'grand_children' => $grand_children_data
                    );
                }
            }

            if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($category['category_id'])) {
                $image = image_resize($mobile_image, 150, 150);
            } else {
                $image = image_resize($category['image'], 150, 150);
            }

            // Level 1
            $data['categories'][] = array(
                'cid'     => $category['category_id'],
                'name'     => str_replace('&amp;', ' ', $category['name']),
                'thumb'    => $image,
                'children' => $children_data
            );
        }
	   
		$this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$data]));
	}
}
