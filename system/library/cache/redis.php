<?php

namespace Cache;

class Redis
{
    private $expire;
    private $cache;
    private $host;
    private $port;
    private $prefix;

    public function __construct($expire)
    {
        $this->expire = $expire;
        $this->host = defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1';
        $this->port = defined('REDIS_PORT') ? REDIS_PORT : 6379;
        $this->prefix = defined('CACHE_PREFIX') ? CACHE_PREFIX : 'opencart_cn';

        $this->cache = new \Redis();
        $this->cache->pconnect($this->host, $this->port);
    }

    public function get($key)
    {
        $data = $this->cache->get($this->prefix . $key);
        return json_decode($data, true);
    }

    public function set($key, $value)
    {
        $status = $this->cache->set($this->prefix . $key, json_encode($value));
        if ($status) {
            $this->cache->setTimeout($this->prefix . $key, $this->expire);
        }
        return $status;
    }

    public function delete($key)
    {
        $this->cache->delete($this->prefix . $key);
    }
}
