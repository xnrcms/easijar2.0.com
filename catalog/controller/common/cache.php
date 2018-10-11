<?php

/**
 * cache.php
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2017-03-16 14:58
 * @modified   2017-03-16 14:58
 */
class ControllerCommonCache extends Controller
{
    private $logger = null;

    public function index()
    {
        $this->logger = new Log('api_clear_cache_' . date('Yd') . '.log');
        $cacheKey = gdValue($this->request->get, 'key');
        if (!$cacheKey) {
            $this->logger->write('cache keys is empty!');
            $json = array(
                'status' => 'error',
                'message' => 'cache key is empty',
            );
        } else {
            $this->logger->write('cache keys is ' . $cacheKey);
            $cacheKeyArray = explode(',', $cacheKey);
            foreach ($cacheKeyArray as $key) {
                $this->logger->write('Clear cache ' . trim($key));
                $this->cache->delete(trim($key));
            }
            $json = array(
                'status' => 'success',
                'message' => 'clear successfully',
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
