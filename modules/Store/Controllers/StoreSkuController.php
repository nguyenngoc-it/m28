<?php

namespace Modules\Store\Controllers;

use App\Base\Controller;
use Modules\Auth\Services\Permission;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Services\SkuEvent;
use Modules\Service;
use Modules\Store\Models\StoreSku;
use Illuminate\Http\JsonResponse;
use Modules\User\Models\User;
use App\Base\Validator;
class StoreSkuController extends Controller
{
    /**
     * Tạo filter để query store Sku
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'store_id',
            'marketplace_code',
            'marketplace_store_id',
            'code',
            'sku_id',
            'created_at',
        ];


        $filter = $this->requests->only($inputs);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $user = $this->getAuthUser();
        $filter['tenant_id'] = $user->tenant_id;
        $filter['marketplace_code'] = Marketplace::CODE_FOBIZ;

        $filter = $this->makeFilterProduct($filter, $user);

        return $filter;
    }

    /**
     * @param $filter
     * @param $user
     * @return mixed
     */
    protected function makeFilterProduct($filter, User $user)
    {
        $tenant_id   = $user->tenant_id;
        $productName = $this->request()->get('product_name');
        if(!empty($productName) && (strlen($productName) >= 3)) {
            $productIds = Product::query()->where('name', 'LIKE', '%'.trim($productName).'%')
                ->where('tenant_id', $tenant_id)->pluck('id')->toArray();
            $skuIds = 0;
            if(!empty($productIds)) {
                $skuIds = Sku::query()->whereIn('product_id', $productIds)->pluck('id')->toArray();
            }
            $filter['sku_id'] = (!empty($skuIds)) ? $skuIds : 0;
        }

        $productCode = $this->request()->get('product_code');
        if(!empty($productCode)) {
            $product = Product::query()->firstWhere(['code' => $productCode, 'tenant_id' => $user->tenant_id]);
            $filter['sku_id'] = ($product instanceof Product) ?
                $product->skus()->select(['id'])->pluck('id')->toArray() : 0;
        }

        $skuName = $this->request()->get('sku_name');
        if(!empty($skuName) && (strlen($skuName) >= 3)) {
            $skuIds = Sku::query()->where('name', 'LIKE', '%'.trim($skuName).'%')
                ->where('tenant_id', $tenant_id)->pluck('id')->toArray();
            $filter['sku_id'] = (!empty($skuIds)) ? $skuIds : 0;
        }

        $skuCode = $this->request()->get('sku_code');
        if(!empty($skuCode)) {
            $sku = Sku::query()->firstWhere(['code' => $skuCode, 'tenant_id' => $tenant_id]);
            $filter['sku_id'] = ($sku instanceof Sku) ? $sku->id : 0;
        }

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        $perPage = $this->request()->get('per_page') ?: 50;

        $paginator = Service::store()
            ->storeSkuQuery($filter)
            ->with(['sku'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        $user = $this->getAuthUser();
        return $this->response()->success([
            'store_skus' => array_map(function (StoreSku $storeSku){
                return [
                    'sku' => $storeSku->sku,
                    'store_sku' => $storeSku,
                ];
            }, $paginator->items()),
            'pagination' => $paginator,
            'can_update' => $user->can(Permission::PRODUCT_UPDATE)
        ]);
    }

    /**
     * @param StoreSku $storeSku
     * @return JsonResponse
     * @throws \Exception
     */
    public function delete(StoreSku $storeSku)
    {
        $storeSkuOld = clone $storeSku;

        $storeSku->delete();

        $storeSku->sku->logActivity(SkuEvent::STORE_SKU_DELETE, $this->getAuthUser(), [
            'code' => $storeSkuOld->code,
        ]);

        return $this->response()->success(['store_sku' => $storeSku]);
    }


    /**
     * @param StoreSku $storeSku
     * @return JsonResponse
     */
    public function update(StoreSku $storeSku)
    {
        $code = $this->request()->get('code');
        if(empty($code)) {
            return $this->response()->error('INPUT_INVALID', ['code' => Validator::ERROR_REQUIRED]);
        }

        $code = trim($code);
        if($storeSku->code != $code) {
            if($storeSku->store->storeSkus()->where('code', $code)->count() > 0) {
                return $this->response()->error('INPUT_INVALID', ['code' => Validator::ERROR_ALREADY_EXIST]);
            }

            $storeSkuOld = clone $storeSku;
            $storeSku->code = $code;
            $storeSku->save();

            $storeSku->sku->logActivity(SkuEvent::STORE_SKU_UPDATE, $this->getAuthUser(), [
                'from' => $storeSkuOld->code,
                'to' => $storeSku->code,
            ]);
        }

        return $this->response()->success(compact('storeSku'));
    }
}
