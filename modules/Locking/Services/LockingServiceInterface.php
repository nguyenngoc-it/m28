<?php

namespace Modules\Locking\Services;
use Closure;

interface LockingServiceInterface
{
    /**
     * @param $id_partner
     * @param $key
     * @return mixed
     */
    public function get($id_partner, $key);

    /**
     * @param $id_partner
     * @param $key
     * @return mixed
     */
    public function set($id_partner, $key);

    /**
     * @param $id_partner
     * @param $key
     * @return mixed
     */
    public function selectOrCreate($id_partner, $key);

    /**
     * @param Closure $handler
     * @param $id_partner
     * @param $key
     * @return mixed
     */
    public function execute(Closure $handler, $id_partner, $key);
}