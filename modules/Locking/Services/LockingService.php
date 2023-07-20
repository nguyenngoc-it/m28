<?php

namespace Modules\Locking\Services;

use Modules\Locking\Models\Locking;
use Closure;
use Illuminate\Support\Facades\DB;

class LockingService implements LockingServiceInterface
{
    /**
     * @param $tenant_id
     * @param $key
     * @return mixed
     */
    public function get($tenant_id, $key)
    {
        return Locking::query()->lockForUpdate()->where(
            [
                'tenant_id' => $tenant_id,
                'key' => $key
            ]
        )->first();
    }

    /**
     * @param $tenant_id
     * @param $key
     * @return mixed
     */
    public function set($tenant_id, $key)
    {
        return Locking::create(
            [
                'tenant_id' => $tenant_id,
                'key' => $key
            ]
        );
    }

    /**
     * @param $tenant_id
     * @param $key
     * @return mixed
     */
    public function selectOrCreate($tenant_id, $key)
    {
        $locking = $this->get(
            $tenant_id,
            $key
        );
        if(!$locking) {
            $locking = $this->set(
                $tenant_id,
                $key
            );
        }

        return $locking;
    }


    /**
     * @param Closure $handler
     * @param $tenant_id
     * @param $key
     * @return mixed
     */
    public function execute(Closure $handler, $tenant_id, $key)
    {
        return DB::transaction(function () use ($handler, $tenant_id, $key) {
            $this->selectOrCreate($tenant_id, $key);

            return $handler();
        });
    }
}