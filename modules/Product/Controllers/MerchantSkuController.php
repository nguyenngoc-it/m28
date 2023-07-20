<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Sku;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Service;

class MerchantSkuController extends Controller
{
    /**
     * Lấy danh sách phí lưu kho theo ngày của 1 sku trong 1 warehouse cụ thể
     *
     * @param Sku $sku
     * @return JsonResponse
     */
    public function storageFeeDaily(Sku $sku)
    {
        $inputs  = $this->request()->only(['page', 'per_page', 'closing_time', 'warehouse_id']);
        $filter  = array_merge($inputs, ['sku_id' => $sku->id]);
        $results = Service::stock()->storageFeeDailyByWarehouse($filter);

        return $this->response()->success([
            'sku_storage_fee_dailies' => $results->items(),
            'pagination' => $results,
        ]);
    }

    /**
     * Cập nhật số lượng an toàn của sku
     * @param Sku $sku
     * @return JsonResponse
     */
    public function updateSafetyStock(Sku $sku)
    {
        $user      = $this->getAuthUser();
        if(!$this->request()->exists('safety_stock')) {
            return $this->response()->error('INPUT_INVALID', ['safety_stock' => \App\Base\Validator::ERROR_REQUIRED]);
        }
        $safetyStock = $this->request()->get('safety_stock');
        if($safetyStock === $sku->safety_stock) {
            return $this->response()->success(compact('sku'));
        }

        $payloadLogs = [
            'form' => $sku->safety_stock,
            'to' => $safetyStock
        ];

        $sku->safety_stock = intval($safetyStock);
        $sku->save();

        $sku->logActivity(SkuEvent::SKU_UPDATE_SAFETY_STOCK, $user, $payloadLogs);

        $sku->product->logActivity(ProductEvent::SKU_UPDATE_SAFETY_STOCK, $user, [
            'data' => $payloadLogs,
            'sku' => $sku->only(['id', 'code', 'name', 'ref'])
        ]);

        return $this->response()->success(compact('sku'));
    }
}
