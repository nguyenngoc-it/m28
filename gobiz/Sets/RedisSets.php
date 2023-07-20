<?php

namespace Gobiz\Sets;

use Predis\ClientInterface;

class RedisSets implements SetsInterface
{
    /**
     * @var ClientInterface
     */
    protected $redis;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * RedisSets constructor
     *
     * @param ClientInterface $redis
     * @param string $prefix
     */
    public function __construct($redis, $prefix = '')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Push key vào collection
     *
     * @param string $key
     * @param null|int $expire Expire của key tồn tại trong collection (seconds)
     */
    public function push($key, $expire = null)
    {
        if ($expire) {
            $this->redis->set($this->makeKey($key), true, 'EX', $expire);
        } else {
            $this->redis->set($this->makeKey($key), true);
        }
    }

    /**
     * Kiểm tra key có tồn tại trong collection hay không
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->redis->exists($this->makeKey($key));
    }

    /**
     * Xóa key khỏi collection
     *
     * @param string $key
     */
    public function remove($key)
    {
        $this->redis->del($this->makeKey($key));
    }

    /**
     * @param string $key
     * @return string
     */
    protected function makeKey($key)
    {
        return $this->prefix . $key;
    }
}