<?php

namespace Modules\Product\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\User\Models\User;

class ProductOfSeller
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Product $product */
        $product = $request->route('product');
        if (!$user->merchant->productMerchants->where('product_id', $product->id)->first()) {
            return Service::app()->response()->error(404, null, 404);
        }
        return $next($request);
    }
}
