<?php
class ControllerApiCategory extends Controller {
	public function index()
	{
		$this->response->addHeader('Content-Type: application/json');

		$allowKey		= ['page'];
		$req_data 		= $this->dataFilter($allowKey);

		if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
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
                    /*foreach ($grand_children as $grand_child) {
                        if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($grand_child['category_id'])) {
                            $image = image_resize($mobile_image, 150, 150);
                        } else {
                            $image = image_resize($child['image'], 150, 150);
                        }

                        $grand_children_data[] = array(
                            'thumb' => $image,
                            'name'  => $grand_child['name'],
                            'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grand_child['category_id'])
                        );
                    }*/

                    if ($mobile_image = $this->model_mobile_mobile->getCategoryMobileImage($child['category_id'])) {
                        $image = image_resize($mobile_image, 150, 150);
                    } else {
                        $image = image_resize($child['image'], 150, 150);
                    }

                    $children_data[] = array(
                		'cid'     		 => $child['category_id'],
                        'thumb'          => $image,
                        'name'           => $child['name'],
                        'href'           => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id']),
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
                'name'     => $category['name'],
                'thumb'    => $image,
                'children' => $children_data,
                'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
            );
        }
	
		$data 		= $this->returnData(['code'=>'200','msg'=>'success','data'=>$data]);

		$this->response->setOutput(json_encode($data));
	}
}
