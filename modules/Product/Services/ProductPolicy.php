<?php

namespace Modules\Product\Services;

use Modules\Auth\Services\Permission;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class ProductPolicy
{
    /**
     * Quyền quản lý product phụ thuộc vào sellers mà user quản lý
     *
     * @param User $user
     * @param Product $product
     * @return bool
     */
    public function productManage(User $user, Product $product)
    {
        if ($user->can(Permission::PRODUCT_MANAGE_ALL)) {
            return true;
        }

        if ($product->merchants->pluck('id')->intersect($user->merchants->pluck('id'))->count()) {
            return true;
        }

        return false;
    }
}
