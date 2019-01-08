<?php
class ControllerCrontabCrontab extends Controller {

	//定时任务
	public function index() 
	{
        wr("\n==========任务执行开始" . date('Y-m-d H:i:s',time()) . "==========",'crontab.txt');


		//订单超时处理
        $this->orderTimeoutProcessing();


        wr("\n==========任务执行结束" . date('Y-m-d H:i:s',time()) . "==========",'crontab.txt');
    }

    private function orderTimeoutProcessing()
    {
        if($this->executionFrequency(10,'orderTimeoutProcessing'))
        {
            //处理超出48小时未付款的订单 标记为已取消
            $this->db->query("UPDATE ". get_tabname('ms_suborder') ." SET `order_status_id`= '7' WHERE `order_status_id` <= 1 AND TIMESTAMPADD(HOUR, 48, date_modified) < NOW()");

            //60天自动收货
            $this->db->query("UPDATE ". get_tabname('ms_suborder') ." SET `order_status_id`= '5' WHERE `order_status_id` = 2 AND TIMESTAMPADD(DAY, 60, date_modified) < NOW()");

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
