<?php

namespace Modules\User\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\User\Models\User;

class UserQuery extends ModelQueryFactory
{
    protected $joins = [
        'user_merchants' => ['users.id', '=', 'user_merchants.user_id'],
        'merchants' => ['merchants.id', '=', 'user_merchants.merchant_id'],
        'user_warehouses' => ['users.id', '=', 'user_warehouses.user_id'],
        'warehouses' => ['warehouses.id', '=', 'user_warehouses.warehouse_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new User();
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applyMerchantIdFilter(ModelQuery $query, $id)
    {
        $query->join('user_merchants')
            ->where('user_merchants.merchant_id', intval($id));
    }

    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applyLocationIdFilter(ModelQuery $query, $id)
    {
        $query->join('user_merchants')
            ->where('user_merchants.merchant_id', '>', 0);

        $query->join('merchants')
            ->where('merchants.location_id', intval($id));
    }


    /**
     * @param ModelQuery $query
     * @param $id
     */
    protected function applyWarehouseIdFilter(ModelQuery $query, $id)
    {
        $query->join('user_warehouses')
            ->where('user_warehouses.warehouse_id', intval($id));
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'users.created_at', $input);
    }
}
