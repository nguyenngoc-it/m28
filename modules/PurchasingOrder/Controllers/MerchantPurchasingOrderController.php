<?php

namespace Modules\PurchasingOrder\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingOrder\Transformers\PurchasingOrderDetailTransformer;
use Modules\PurchasingOrder\Validators\MerchantPurchasingOrderDetailValidator;
use Modules\PurchasingOrder\Validators\MerchantPurchasingOrderMappingVariantValidator;
use Modules\PurchasingOrder\Validators\UpdatingMerchantPurchasingOrderValidator;
use Modules\Service;

class MerchantPurchasingOrderController extends Controller
{
    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'code',
            'purchasing_account_id',
            'sort',
            'sortBy',
            'page',
            'per_page',
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter                = $this->getQueryFilter();
        $filter['merchant_id'] = $this->user->merchant->id;
        $filter['has_package'] = true;
        $filter['is_putaway']  = false;
        $listing               = Service::purchasingOrder()->listing($filter, $this->user);
        unset($listing['pagination']);
        return $this->response()->success($listing);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new MerchantPurchasingOrderDetailValidator(['id' => (int)$id]);
        $message   = $validator->errors()->messages();
        if (!empty($message['id']) && $message['id'] = 'exists') {
            return $this->response()->error(404, null, 404);
        }
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingOrder                   = $validator->getPurchasingOrder();
        $purchasingOrder->permission_views = Service::purchasingOrder()->pemissionViews($purchasingOrder, $this->user);
        return $this->response()->success(
            [
                'purchasing_order' => (new PurchasingOrderDetailTransformer())->transform($purchasingOrder),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $input     = $this->requests->only([
            'services',
            'warehouse_id',
            'is_putaway'
        ]);
        $validator = new UpdatingMerchantPurchasingOrderValidator(array_merge($input, ['id' => $id]));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingOrder = Service::purchasingOrder()->updateMerchantPurchasingOrder($validator->getPurchasingOrder(), $input, $this->user);

        return $this->response()->success(['purchasing_order' => (new PurchasingOrderDetailTransformer())->transform($purchasingOrder)]);
    }

    /**
     * @param $id
     * @param $itemId
     * @return JsonResponse
     */
    public function purchasingVariantMapping($id, $itemId)
    {
        $inputs            = $this->requests->only(['sku_id']);
        $inputs['id']      = (int)$id;
        $inputs['item_id'] = (int)$itemId;
        $validator         = new MerchantPurchasingOrderMappingVariantValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingVariant = $validator->getPurchasingVariant();

        Service::purchasingOrder()->mappingVariant($validator->getPurchasingOrder(), $purchasingVariant, $validator->getSku(), $this->user);

        return $this->response()->success([
            'purchasing_variant' => $purchasingVariant->refresh()
        ]);
    }
}
