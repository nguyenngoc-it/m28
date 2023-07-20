<?php

namespace Modules\InvalidOrder\Services;

use Gobiz\Validation\ValidationException;
use Illuminate\Support\Arr;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\Commands\ProcessCreatingOrder;
use Modules\Service;
use Modules\Store\Models\Store;

class InvalidOrderService implements InvalidOrderServiceInterface
{
    /**
     * Đồng bộ lại đơn lỗi
     *
     * @param InvalidOrder $invalidOrder
     * @return Order|null
     */
    public function resync(InvalidOrder $invalidOrder)
    {
        try {
            switch ($invalidOrder->source) {
                case InvalidOrder::SOURCE_INTERNAL_API: {
                    return (new ProcessCreatingOrder($invalidOrder->payload, $invalidOrder->creator))->dispatch();
                }

                case InvalidOrder::SOURCE_SHOPEE: {
                    $store = Store::find($invalidOrder->payload['store_id']);
                    return Service::shopee()->syncOrder($store, $invalidOrder->payload['input'], $invalidOrder->creator);
                }
            }
        } catch (ValidationException $exception) {
            return null;
        }

        return null;
    }

    /**
     * Xóa đơn lỗi tương ứng với đơn
     *
     * @param string $source
     * @param Order $order
     * @return InvalidOrder|object|null
     */
    public function remove($source, Order $order)
    {
        $invalidOrder = InvalidOrder::query()->firstWhere([
            'tenant_id' => $order->tenant_id,
            'source' => $source,
            'code' => $order->code,
        ]);

        if ($invalidOrder) {
            $invalidOrder->delete();
        }

        return $invalidOrder;
    }

    public function listing($filter)
    {
        $page      = Arr::get($filter, 'page', config('paginate.page'));
        $perPage   = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $sortBy    = Arr::get($filter, 'sort_by', 'id');
        $sort      = Arr::get($filter, 'sort', 'desc');

        foreach (['sort', 'sort_by', 'page', 'per_page'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query     = $this->query($filter)->getQuery();
        $query->orderBy('invalid_orders.' . $sortBy, $sort);

        return $query->paginate($perPage, ['invalid_orders.*'], 'page', $page);
    }

    public function query($filter)
    {
        return (new InvalidOrderQuery())->query($filter);
    }
}
