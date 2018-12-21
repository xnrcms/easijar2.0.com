<?php
class ControllerCrontabHandle extends Controller {

	//定时任务
	public function index() 
	{
        
        echo "string";exit();
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
