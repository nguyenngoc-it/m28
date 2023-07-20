<?php

namespace Modules\Store\Controllers\Api\V1;

use App\Base\Controller;
use App\Base\Job;
use App\Base\Validator;
use Gobiz\Support\Helper;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Transformers\ProductTransformer;
use Modules\Service;
use Modules\Store\Factories\MerchantStoreFactory;
use Modules\Store\Models\Store;
use Modules\Store\Services\StoreEvent;
use Modules\Store\Transformers\StoreListItemTransformer;
use Modules\Store\Transformers\StoreTransformerNew;
use Modules\Warehouse\Models\Warehouse;

class MerchantStoreController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $request          = $this->request()->all();
        $perPage          = data_get($request, 'per_page');
        $merchantCode     = data_get($request, 'merchant_code');
        $marketplaceCode  = data_get($request, 'marketplace_code');
        $wareHouse        = data_get($request, 'ware_house');
        $storeName        = data_get($request, 'store_name');
        $merchantCodes    = explode(',', $merchantCode);
        $merchants        = Merchant::whereIn('code', $merchantCodes)->where('tenant_id', $this->user->tenant->id)->get()->toArray();

        $dataReturn = [];
        if (count($merchants) >= 1) {
            $merchantIds = array_map(function ($merchant) {
                return data_get($merchant, 'id');
            },$merchants);

            $builder = Store::query()->select('stores.*')
                ->where('status', Store::STATUS_ACTIVE)
                ->whereIn('merchant_id', $merchantIds);

            if(!is_null($marketplaceCode)) {
                $marketplaceCodes = explode(',', $marketplaceCode);
                if(is_array($marketplaceCodes)) {
                    $builder = $builder->whereIn('marketplace_code', $marketplaceCodes);
                }
            }

            if(!is_null($wareHouse)) {
                $wareHouses = explode(',', $wareHouse); // list warehouse id
                if(is_array($wareHouses)) {
                    $builder = $builder->whereIn('warehouse_id', $wareHouses);
                }
            }

            if(!is_null($storeName)) {
                $builder = $builder->where('name', 'like', '%'.$storeName.'%');
            }

            $paginator = $builder->paginate($perPage);
            $stores    = $paginator->getCollection();
            $include   = data_get($request, 'include');
            $fractal   = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($stores, new StoreTransformerNew);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }


    /**
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Store $store)
    {
        if ($store->status === Store::STATUS_INACTIVE) {
            return $this->response()->error(404, null, 404);
        }

        $store->update(['status' => Store::STATUS_INACTIVE]);
        $store->logActivity(StoreEvent::DELETE, $this->user);

        return $this->response()->success(['store' => $store]);

    }

    /**
     * @param Store $store
     * @return JsonResponse
     */
    public function updateWarehouse(Store $store)
    {
        $warehouseId = intval($this->request()->get('warehouse_id'));
        $user        = $this->user;
        $warehouse   = $user->tenant->warehouses()->firstWhere('warehouses.id', $warehouseId);
        if (!$warehouse instanceof Warehouse) {
            return $this->response()->error('INPUT_INVALID', ['warehouse_id' => \App\Base\Validator::ERROR_NOT_EXIST]);
        }

        if ($store->warehouse_id != $warehouse->id) {
            $oldWarehouse        = ($store->warehouse) ? clone $store->warehouse : null;
            $store->warehouse_id = $warehouse->id;
            $store->save();

            $store->logActivity(StoreEvent::UPDATE, $this->user, [
                'form' => ($oldWarehouse) ? $oldWarehouse->only(['id', 'name', 'code']) : [],
                'to' => ($store->warehouse) ? $store->warehouse->only(['id', 'name', 'code']) : []
            ]);
        }

        return $this->response()->success(['store' => $store]);
    }

    /**
     * @param Store $store
     * @return JsonResponse
     */
    public function syncProducts(Store $store)
    {
        $merchantStoreFactory = new MerchantStoreFactory($store);
        $jober                = $merchantStoreFactory->makeSyncProductJober();

        if ($jober instanceof Job) {
            dispatch($jober);
        }

        $responseData = [
            'code' => 200,
            'message' => 'success',
            'errors' => [],
            'data' => $store
        ];

        return $this->response()->success($responseData);
    }

    /**
     * Create a Store Merchant connect
     * @param Store $store
     * @return JsonResponse
     * @throws \ReflectionException
     */
    public function create(Store $store)
    {
        $this->api = Service::kiotviet()->api();
        /*
        Get data request
         */
        $request      = $this->request()->all();
        $warehouseId  = intval(data_get($request, 'warehouse_id', null));
        $clientId     = data_get($request, 'client_id', null);
        $clientSecret = data_get($request, 'secret_key', null);
        $sharedSecret = data_get($request, 'shared_secret', null);
        $channel      = data_get($request, 'channel', 'NULL');
        $shopName     = data_get($request, 'shop_name', 'NULL');
        $url          = trim(data_get($request, 'url'));
        $merchantCode = data_get($request, 'merchant_code', 'NULL');
        $accessToken  = null;
        $settings     = [];
        $data         = [];
        $validateData = true;
        $responseData = [
            'code' => 200,
            'message' => 'success',
            'errors' => [],
            'data' => []
        ];
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response()->error('INPUT_INVALID', ['url' => Validator::ERROR_INVALID]);
        }
        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();
        if (!$merchant) {
            return $this->response()->error('INPUT_INVALID', ['merchant' => Validator::ERROR_NOT_EXIST]);
        }
        /*
        Validate Marketplace type
         */
        $refl = (new \ReflectionClass(Marketplace::class))->getConstants();
        if (in_array($channel, $refl)) {

            //Kiểm tra xem client_id này đã kết nối chưa
            $storeData = $store->where([
                ['marketplace_code', $channel],
                ['settings->client_id', $clientId],
                ['status', Store::STATUS_ACTIVE]
            ])->first();

            if ($storeData instanceof Store) {
                $responseData['code']    = 500;
                $responseData['message'] = 'Shop ' . $shopName . ' đã kết nối, vui lòng kiểm tra lại thông tin shop';
                $responseData['errors']  = ['Client Id connected: ' . $clientId];
                $responseData['data']    = $storeData;

                return $this->response()->success($responseData);
            }

            if ($channel == Marketplace::CODE_KIOTVIET) {
                $settings = $this->api->getSettingKiotViet($clientId, $clientSecret, $shopName);
                if (!$settings) {
                    $validateData            = false;
                    $responseData['code']    = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors']  = ['Can not get access token from ' . $channel];
                }
            }

            if ($channel == Marketplace::CODE_SHOPBASE) {
                $params = [
                    'shop_name' => $shopName,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'shared_secret' => $sharedSecret,
                ];

                $settings = Service::shopBaseUs()->connect($params);

                if (data_get($settings, 'shop_name', '')) {
                    $shopName = data_get($settings, 'shop_info.name', '');
                }

                if (!$settings) {
                    $validateData            = false;
                    $responseData['code']    = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors']  = ['Can not get access token from ' . $channel];
                }
            }

            if ($channel == Marketplace::CODE_SAPO) {
                $params = [
                    'shop_name' => $shopName,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ];

                $settings = Service::sapo()->connect($params);

                if (data_get($settings, 'shop_name', '')) {
                    $shopName = data_get($settings, 'shop_info.name', '');
                }

                if (!$settings) {
                    $validateData            = false;
                    $responseData['code']    = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors']  = ['Can not get access token from ' . $channel];
                }
            }

            $query = [
                'marketplace_code' => $channel,
                'settings->client_id' => $clientId
            ];

            $data = [
                'tenant_id' => $this->user->tenant_id,
                'merchant_id' => $merchant->id,
                'marketplace_code' => $channel,
                'marketplace_store_id' => data_get($settings, 'marketplace_store_id', null),
                'name' => $shopName,
                'settings' => $settings,
                'warehouse_id' => $warehouseId,
                'status' => Store::STATUS_ACTIVE
            ];

            // dd($data);

            if ($validateData && $storeData = $store->updateOrCreate($query, $data)) {
                $responseData['data'] = $storeData;

                $merchantStoreFactory = new MerchantStoreFactory($store);
                $jober                = $merchantStoreFactory->makeSyncProductJober();
                if ($jober instanceof Job) {
                    dispatch($jober);
                }
            }

        } else {
            $responseData['code']    = 500;
            $responseData['message'] = 'The given data channel was invalid';
            $responseData['errors']  = ['Channel store ' . $channel . ' not define'];
        }

        return response()->json([
            'responseData' => $responseData,
            'url' => $url
        ]);
    }

}
