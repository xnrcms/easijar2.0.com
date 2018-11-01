<?php
class ControllerApiCrontab extends Controller {

	//订单支付
	public function index() 
	{	
		//订单超时处理
        $this->orderTimeoutProcessing();        
    }

    private function orderTimeoutProcessing()
    {
        if($this->executionFrequency(10)){
            wr("\n====" . date('Y-m-d H:i:s',time()) . "===\n");
        }
        return;
    }

    //执行频率
    private function executionFrequency($key='',$time=0)
    {
        if (empty($key) || $time <= 0) return true;

        $cache_time       = $this->cache->get($key);

        if ($cache_time && $cache_time <= time()) {

            $this->cache->set($key,time() + $time);
            return true;
        }

        $this->cache->set($key,$cache_time);

        return false;
    }
}
