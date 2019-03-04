<?php
class ControllerSellerCoupon extends Controller {
	private $error = array();
    private $ms_seller = null;

    public function index()
    {
		if (!$this->customer->isLogged())
		{
			$this->session->data['redirect'] = $this->url->link('seller/edit');
			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()){
        	
            $this->response->redirect($this->url->link('seller/add'));
		}

        $this->load->language('seller/coupon');
        $this->load->language('seller/account');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('marketing/coupon');

		$this->getList();
	}

	public function add()
	{
		if (!$this->customer->isLogged())
		{
			$this->session->data['redirect'] = $this->url->link('seller/edit');
			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()){

            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/coupon');
        $this->load->language('seller/account');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('marketing/coupon');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

			$this->request->post['launch_scene'] 		= 1;
			$this->request->post['seller_id'] 			= $this->customer->getId();

			$this->model_marketing_coupon->addCoupon2($this->request->post);

			$this->session->data['success'] 			= $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('seller/coupon', $url));
		}

		$this->getForm();
	}

	public function edit()
	{
		if (!$this->customer->isLogged())
		{
			$this->session->data['redirect'] = $this->url->link('seller/edit');
			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()){

            $this->response->redirect($this->url->link('seller/add'));
		}

		$this->load->language('seller/coupon');
        $this->load->language('seller/account');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('marketing/coupon');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

			$this->request->post['launch_scene'] 		= 1;
			$this->request->post['seller_id'] 			= $this->customer->getId();
			
			$this->model_marketing_coupon->editCoupon2($this->request->get['coupon_id'],$this->request->post);

			$this->session->data['success'] 			= $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('seller/coupon',$url));
		}

		$this->getForm();
	}

	public function delete() 
	{
		if (!$this->customer->isLogged())
		{
			$this->session->data['redirect'] = $this->url->link('seller/edit');
			$this->response->redirect($this->url->link('seller/login'));
        } else if (!$this->customer->isSeller()){

            $this->response->redirect($this->url->link('seller/add'));
		}
		
		$this->load->language('seller/coupon');
        $this->load->language('seller/account');
        $this->load->language('seller/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->language('marketing/coupon');
		$this->load->model('marketing/coupon');

		if ($this->validateDelete())
		{
			$this->model_marketing_coupon->deleteCoupon2($this->request->post['selected']);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('seller/coupon',$url));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('seller/account')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_coupon'),
			'href' => $this->url->link('seller/coupon')
		);

		$data['add'] = $this->url->link('seller/coupon/add',$url);
		$data['delete'] = $this->url->link('seller/coupon/delete',$url);

		$data['coupons'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$coupon_total = $this->model_marketing_coupon->getTotalCoupons2();

		$results = $this->model_marketing_coupon->getCoupons2($filter_data);

		$coupon_type 			= [
			1=>$this->language->get('text_coupon_type1'),
			2=>$this->language->get('text_coupon_type2'),
			3=>$this->language->get('text_coupon_type3'),
		];

		$coupon_status 			= [
			0=>$this->language->get('text_disabled'),
			1=>$this->language->get('text_enabled'),
		];

		foreach ($results as $result) {
			$data['coupons'][] = array(
				'coupon_id'  => $result['coupon_id'],
				'explain'    => $result['explain'],
				'order_total'=> $result['order_total'],
				'text_type'	 => isset($coupon_type[$result['type']]) ? $coupon_type[$result['type']] : '',
				'name'       => $result['name'],
				'discount'   => $result['discount'],
				'date_start' => date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'   => date($this->language->get('date_format_short'), strtotime($result['date_end'])),
				'status'     => isset($coupon_status[$result['status']]) ? $coupon_status[(int)$result['status']] : '',
				'edit'       => $this->url->link('seller/coupon/edit', 'coupon_id=' . $result['coupon_id'] . $url)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('seller/coupon','sort=name' . $url);
		$data['sort_code'] = $this->url->link('seller/coupon','sort=code' . $url);
		$data['sort_discount'] = $this->url->link('seller/coupon','sort=discount' . $url);
		$data['sort_date_start'] = $this->url->link('seller/coupon','sort=date_start' . $url);
		$data['sort_date_end'] = $this->url->link('seller/coupon','sort=date_end' . $url);
		$data['sort_status'] = $this->url->link('seller/coupon','sort=status' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $coupon_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('seller/coupon',$url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($coupon_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($coupon_total - $this->config->get('config_limit_admin'))) ? $coupon_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $coupon_total, ceil($coupon_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/coupon', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['coupon_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->request->get['coupon_id'])) {
			$data['coupon_id'] = (int)$this->request->get['coupon_id'];
		} else {
			$data['coupon_id'] = 0;
		}

		//表单字段
		$form_field 		= [
			['name'=>'name','value_type'=>'string','default_value'=>''],
			['name'=>'explain','value_type'=>'string','default_value'=>''],
			['name'=>'type','value_type'=>'number','default_value'=>1],
			['name'=>'order_total','value_type'=>'float','default_value'=>0],
			['name'=>'discount','value_type'=>'float','default_value'=>0],
			['name'=>'coupon_total','value_type'=>'number','default_value'=>0],
			['name'=>'date_start','value_type'=>'string','default_value'=>date('Y-m-d', time())],
			['name'=>'date_end','value_type'=>'string','default_value'=>date('Y-m-d', strtotime('+1 month'))],
			['name'=>'get_limit','value_type'=>'number','default_value'=>'1'],
			['name'=>'uses_limit','value_type'=>'number','default_value'=>'1'],
			['name'=>'status','value_type'=>'number','default_value'=>1],
			['name'=>'launch_scene','value_type'=>'number','default_value'=>1],
		];

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		
		foreach ($form_field as $key => $value) {
			$data['error_'.$value['name']] = isset($this->error[$value['name']]) ? $this->error[$value['name']] : '';
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home',$url)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account',$url),
			'href' => $this->url->link('seller/account',$url)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_coupon'),
			'href' => $this->url->link('seller/coupon',$url)
		);


		if (!isset($this->request->get['coupon_id'])) {
			$data['action'] = $this->url->link('seller/coupon/add',$url);
		} else {
			$data['action'] = $this->url->link('seller/coupon/edit','coupon_id=' . $this->request->get['coupon_id'] . $url);
		}

		$data['cancel'] = $this->url->link('seller/coupon',$url);

		if (isset($this->request->get['coupon_id']) && (!$this->request->server['REQUEST_METHOD'] != 'POST')) {
			$coupon_info = $this->model_marketing_coupon->getCoupon2($this->request->get['coupon_id']);
		}

		foreach ($form_field as $key => $value)
		{
			if (isset($this->request->post[$value['name']])) {
				$data[$value['name']] = $this->request->post[$value['name']];
			} elseif (!empty($coupon_info)) {
				$data[$value['name']] = $coupon_info[$value['name']];
			} else {
				$data[$value['name']] = isset($value['default_value']) ? $value['default_value'] : '';
			}

			if ($value['value_type'] == 'string') {
				$data[$value['name']] = trim((string)$data[$value['name']]);
			}

			if ($value['value_type'] == 'number') {
				$data[$value['name']] = (int)$data[$value['name']];
			}

			if ($value['value_type'] == 'float') {
				$data[$value['name']] = (float)$data[$value['name']];
			}
		}

		$data['date_start'] = $data['date_start'] != '0000-00-00' ? $data['date_start'] : '';
		$data['date_end'] 	= $data['date_end'] != '0000-00-00' ? $data['date_end'] : '';

		$coupon_type 			= [
			1=>$this->language->get('text_coupon_type1'),
			2=>$this->language->get('text_coupon_type2'),
			//3=>$this->language->get('text_coupon_type3'),
		];

		$data['coupon_type'] 	= $coupon_type;

		$coupon_status 			= [
			0=>$this->language->get('text_disabled'),
			1=>$this->language->get('text_enabled'),
		];

		$data['coupon_status'] 	= $coupon_status;

		$coupon_launch_scene 	= [
			1=>$this->language->get('text_launch_scene1'),
			2=>$this->language->get('text_launch_scene2'),
		];
		
		$data['coupon_launch_scene'] 	= $coupon_launch_scene;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('seller/coupon_form', $data));
	}

	protected function validateForm()
	{
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 128)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ((utf8_strlen($this->request->post['explain']) < 3) || (utf8_strlen($this->request->post['explain']) > 128)) {
			$this->error['explain'] = $this->language->get('error_explain');
		}

		$type 	= isset($this->request->post['type']) ? (int)$this->request->post['type'] : 0;
		if (!in_array($type, [1,2,3])) {
			$this->error['type'] = $this->language->get('error_coupon_type');
		}

		//总额
		$order_total 	= isset($this->request->post['order_total']) ? (int)$this->request->post['order_total'] : 0;
		if ($order_total <= 0) {
			$this->error['order_total'] = $this->language->get('error_order_total');
		}

		//优惠力度
		$discount 		= isset($this->request->post['discount']) ? (int)$this->request->post['discount'] : 0;
		if ($discount <= 0) {
			$this->error['discount'] = $this->language->get('error_discount_'.$type);
		}else{
			if ((($type == 1 || $type == 3) && $discount >= $order_total) || ($type == 2 && $discount >= 100)) {
				$this->error['discount'] = $this->language->get('error_discount_'.$type);
			}
		}

		//日期
		$date_start 	= isset($this->request->post['date_start']) ? (string)$this->request->post['date_start'] : '';
		$date_end 		= isset($this->request->post['date_end']) ? (string)$this->request->post['date_end'] : '';

		if (empty($date_start) || $date_start == '0000-00-00') {
			$this->error['date_start'] = $this->language->get('error_date_start');
		}

		if (empty($date_end) || $date_end == '0000-00-00') {
			$this->error['date_end'] 	= $this->language->get('error_date_end');
		}

		$this->request->post['date_start'] 	= date('Y-m-d 00:00:00',strtotime($this->request->post['date_start']));
		$this->request->post['date_end'] 	= date('Y-m-d 23:59:59',strtotime($this->request->post['date_end']));

		/*if ($date_end < $date_start) {
			$this->error['date_end'] 	= $this->language->get('error_date_start_end');
		}*/
		
		$coupon_total 		= isset($this->request->post['coupon_total']) ? (int)$this->request->post['coupon_total'] : 0;
		$get_limit 			= isset($this->request->post['get_limit']) ? (int)$this->request->post['get_limit'] : 0;
		$uses_limit 		= isset($this->request->post['uses_limit']) ? (int)$this->request->post['uses_limit'] : 0;
		if ($coupon_total <= 0) {
			$this->error['coupon_total'] = $this->language->get('error_coupon_total');
		}
		if ($get_limit <= 0) {
			$this->error['get_limit'] = $this->language->get('error_get_limit');
		}
		if ($uses_limit <= 0) {
			$this->error['uses_limit'] = $this->language->get('error_uses_limit');
		}

		return !$this->error;
	}

	protected function validateDelete()
	{
		if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
			$this->error['warning'] = $this->language->get('error_selected_coupon_id');
		}else{
			//判断是否有在进行的营销券
			$counts 	= $this->model_marketing_coupon->checkCoupon2($this->request->post['selected']);
			if ($counts > 0) {
				$this->error['warning'] = $this->language->get('error_coupon_status_delete');
			}
		}

		return !$this->error;
	}
}
