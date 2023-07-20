<?php

namespace Gobiz\Sets;

interface SetsInterface
{
    /**
     * Push key vào collection
     *
     * @param string $key
     * @param null|int $expire Expire của key tồn tại trong collection (seconds)
     */
    public function push($key, $expire = null);

    /**
     * Kiểm tra key có tồn tại trong collection hay không
     *
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Xóa key khỏi collection
     *
     * @param string $key
     */
    public function remove($key);
}