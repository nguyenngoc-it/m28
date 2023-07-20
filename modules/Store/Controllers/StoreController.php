<?php

namespace Modules\Store\Controllers;

use App\Base\Controller;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Store\Services\StoreEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Modules\Store\Commands\ImportStore;

class StoreController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->request()->only(['merchant_id', 'marketplace_code', 'marketplace_store_id']);
        $perPage = $this->request()->get('per_page') ?: 50;

        $paginator = Service::store()
            ->query(array_merge($filter, ['status' => Store::STATUS_ACTIVE]))
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return $this->response()->success([
            'stores' => array_map(function (Store $store) {
                return [
                    'store' => $store,
                    'merchant' => $store->merchant,
                    'warehouse' => $store->warehouse,
                ];
            }, $paginator->items()),
            'pagination' => $paginator,
        ]);
    }

    /**
     * @param Store $store
     * @return JsonResponse
     */
    public function delete(Store $store)
    {
        if ($store->status === Store::STATUS_INACTIVE) {
            return $this->response()->error(404, null, 404);
        }

        $store->update(['status' => Store::STATUS_INACTIVE]);
        $store->logActivity(StoreEvent::DELETE, $this->getAuthUser());

        return $this->response()->success(['store' => $store]);
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function import()
    {
        $input = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:'.config('upload.mimes').'|max:'.config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user = $this->getAuthUser();

        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = (new ImportStore($path, $user))->handle();

        return $this->response()->success(compact('errors'));
    }
}
