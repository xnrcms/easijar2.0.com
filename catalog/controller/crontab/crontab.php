<?php
class ControllerCrontabCrontab extends Controller {

	//订单支付
	public function index() 
	{	
		//订单超时处理
        $this->orderTimeoutProcessing();        
    }

    private function orderTimeoutProcessing()
    {
        if($this->executionFrequency(10,'orderTimeoutProcessing')){
            wr("\n====" . date('Y-m-d H:i:s',time()) . "===\n");
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
