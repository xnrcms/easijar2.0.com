<?php
class ControllerApiLogistics extends Controller
{
	//物流订单列表
	public function index() 
	{	
		$this->response->addHeader('Content-Type: application/json');
        $this->load->language('account/order');

        $allowKey       = ['api_token','page','limit'];
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

        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 10;

        $this->load->model('tool/image');
        $this->load->model('extension/module/aftership');

        $filter_data = array(
            'start'     => ($page - 1) * $limit,
            'limit'     => $limit
        );

        $totals                = $this->model_extension_module_aftership->getOrderLogisticsTotals();
        $read_totals           = $this->model_extension_module_aftership->getOrderLogisticsTotalsForRead();
        $order_logistics       = $this->model_extension_module_aftership->getOrderLogistics($filter_data);

        $logistics             = [];
        foreach ($order_logistics as $key => $value) {
            $logistics[]       =[
                'image'           => $this->model_tool_image->resize($value['image'], 100, 100),
                'name'            => $value['name'],
                'order_sn'        => $value['order_sn'],
                'tracking_number' => $value['tracking_number'],
                'date_added'      => $value['date_added'],
                'tracking_name'   => $value['tracking_name'],
                'tracking_code'   => $value['tracking_code'],
            ];
        }

        $remainder                  = intval($totals - $limit * $page);
        $data                       = [];
        $data['totals']             = (int)$totals;
        $data['read_totals']        = (int)$read_totals;
        $data['total_page']         = ceil($totals/$limit);
        $data['remainder']          = $remainder >= 0 ? $remainder : 0;
        $data['lists']              = $logistics;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=> $data ]));
    }
}
