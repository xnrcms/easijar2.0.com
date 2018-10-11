<?php
/**
 * Redis Adapter for Session
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-04-09 09:35
 * @modified   2018-04-09 09:35
 */

namespace Session;

use Predis\Client as RedisClient;

final class redis extends \SessionHandler
{
    private $client;
    private $lifeTime;

    /**
     * redis constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        register_shutdown_function("session_write_close");
        $this->lifeTime = ini_get('session.gc_maxlifetime');
        $redisHost = defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1';
        $redisPort = defined('REDIS_PORT') ? REDIS_PORT : 6379;

        if (class_exists(\Redis::class)) {
            $this->client = new \Redis();
            $this->client->pconnect($redisHost, $redisPort);
        } elseif (class_exists(RedisClient::class)) {
            $this->client = new RedisClient(array(
                'scheme' => 'tcp',
                'host' => $redisHost,
                'port' => $redisPort
            ));
        } else {
            throw new \Exception('Please install Redis extension or Predis');
        }
    }

    public function create_sid()
    {
        return parent::create_sid();
    }

    public function open($savePath, $sessionName)
    {
        if ($this->client) {
            return true;
        } else {
            return false;
        }
    }

    public function close()
    {
        unset($this->client);
        return true;
    }

    public function read($sessionId)
    {
        $sessionId = 'sess_' . $sessionId;
        return $this->client->get($sessionId);
    }

    public function write($sessionId, $data)
    {
        $sessionId = 'sess_' . $sessionId;
        $this->client->get($sessionId);
        $this->client->setex($sessionId, $this->lifeTime, $data);
        return true;
    }

    public function destroy($sessionId)
    {
        $sessionId = 'sess_' . $sessionId;
        $this->client->del($sessionId);
        return true;
    }

    public function gc($sessionMaxLifeTime)
    {
        return true;
    }
}
