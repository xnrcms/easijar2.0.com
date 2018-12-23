<?php
class ControllerCrontabHandle extends Controller {

	//定时任务
	public function index() 
	{
        $this->load->model('catalog/handle');
        
        $req_data           = array_merge($this->request->get,$this->request->post);
        $step               = (isset($req_data['step']) && (int)$req_data['step'] > 0) ? (int)$req_data['step'] : 0;
        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 1;
        
        $filter_data        = [
            'start'    => ($page - 1) * $limit,
            'limit'    => $limit
        ];

        if ($step == 0) {
            $this->session->data['handle_data']     = [];

            //统计需要处理的商品数量
            $ptotals   = $this->model_catalog_handle->get_product_option_value_total();
            $ototals   = $this->model_catalog_handle->get_options_description_total();

            $this->session->data['handle_data']['ptotals']   = $ptotals;
            $this->session->data['handle_data']['ototals']   = $ototals;

            $this->jumpurl($this->url->link('crontab/handle','step=1'));
        }else if ($step == 2) {//处理商品
            
            //需要处理的商品列表
            $product_info                   = $this->model_catalog_handle->get_product_option_value_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['ptotals'] - $limit * $page);

            if ($remainder > 0) {
                echo "<pre>";
                print_r(array_merge($this->session->data['handle_data'],['remainder'=>$remainder],$product_info));

                $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . ($page + 1)));
            }
        }else if ($step == 1) {//处理属性
            $option_info                    = $this->model_catalog_handle->get_options_description_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['ototals'] - $limit * $page);
            
            if ($remainder > 0) {

                if (!empty($option_info)) {
                    //根据选项名称查找属性信息
                    
                }
                echo "<pre>";
                print_r(array_merge($this->session->data['handle_data'],['remainder'=>$remainder],$option_info));
                $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . ($page + 1)));
            }else{
                $this->jumpurl($this->url->link('crontab/handle','step=' . ($step+1) . '&page=1'));
            }
        }
        
        echo "<pre>";
        print_r($this->session->data['handle_data']);exit();
        echo "string";exit();
    }

    private function jumpurl($url)
    {
        echo '<script type="text/javascript">window.location.href="' . str_replace('&amp;', '&', $url) . '"</script>';exit();
    }

    private function orderTimeoutProcessing()
    {
        if($this->executionFrequency(600,'orderTimeoutProcessing'))
        {
            //处理超出48小时未付款的订单 标记为已取消
            $this->db->query("UPDATE `" . DB_PREFIX . "ms_suborder` SET `order_status_id`= '7' WHERE `order_status_id` <= 1 AND `order_id` IN (SELECT `order_id` FROM `" . DB_PREFIX . "order` WHERE TIMESTAMPADD(HOUR, 48, date_added) < NOW() AND `order_status_id` <= 1)");

            wr("\n==========订单超时处理完成：" . date('Y-m-d H:i:s',time()) . "==========\n",'crontab.txt');
        }
        return;
    }

    //执行频率
    private function executionFrequency($time = 0 , $key = '')
    {
        if (empty($key) || $time <= 0) return true;

        $t                = time();
        $cache_time       = $this->cache->get($key);
        $cache_time       = !empty($cache_time) ? $cache_time : $t;

        if ($cache_time && $cache_time < $t) {

            $this->cache->set($key,$t + $time);
            return true;
        }

        $this->cache->set($key,$cache_time);

        return false;
    }
}
