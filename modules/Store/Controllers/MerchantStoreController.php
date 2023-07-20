<?php

namespace Modules\Store\Controllers;

use App\Base\Controller;
use App\Base\Job;
use Modules\Lazada\Jobs\SyncStockSkusJob;
use Modules\Service;
use Illuminate\Http\JsonResponse;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;
use Modules\Store\Services\StoreEvent;
use Modules\Store\Transformers\StoreListItemTransformer;
use Modules\Warehouse\Models\Warehouse;
use Modules\Store\Factories\MerchantStoreFactory;

class MerchantStoreController extends Controller
{

    /**
     * @var KiotVietApiInterface
     */
    protected $api;


    /**
     * @var User
     */
    protected $user;


    /**
     * @var apiUrl
     */
    protected $apiUrl = 'https://id.kiotviet.vn';

    /**
     * MerchantStoreController constructor.
     * @param string $apiUrl
     * @param array $settings
     */
    public function __construct()
    {

        $this->user = $this->getAuthUser();
        $this->api = Service::kiotviet()->api();
    }


    /**
     * @return JsonResponse
     */
    public function index()
    {
        $stores = $this->user->merchant->stores()
            ->where('status', '!=', Store::STATUS_INACTIVE)
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->response()->success([
            'stores' => $stores->map(function (Store $store) {
                return (new StoreListItemTransformer())->transform($store);
            })
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
        $store->logActivity(StoreEvent::DELETE, $this->user);

        return $this->response()->success(['store' => $store]);
    }


    /**
     * Create a Store Merchant connect
     * @param Store $store
     * @return JsonResponse
     * @throws \ReflectionException
     */
    public function create(Store $store)
    {
        /*
        Get data request
         */
        $request = $this->request()->all();
        $warehouseId = intval(data_get($request, 'warehouse_id', null));
        $clientId = data_get($request, 'client_id', null);
        $clientSecret = data_get($request, 'secret_key', null);
        $sharedSecret = data_get($request, 'shared_secret', null);
        $channel = data_get($request, 'channel', 'NULL');
        $shopName = data_get($request, 'shop_name', 'NULL');
        $accessToken = null;
        $settings = [];
        $data = [];
        $validateData = true;
        $responseData = [
            'code' => 200,
            'message' => 'success',
            'errors' => [],
            'data' => []
        ];

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
                $responseData['code'] = 500;
                $responseData['message'] = 'Shop ' . $shopName . ' đã kết nối, vui lòng kiểm tra lại thông tin shop';
                $responseData['errors'] = ['Client Id connected: ' . $clientId];
                $responseData['data'] = $storeData;

                return $this->response()->success($responseData);
            }

            if ($channel == Marketplace::CODE_KIOTVIET) {
                $settings = $this->api->getSettingKiotViet($clientId, $clientSecret, $shopName);
                if (!$settings) {
                    $validateData = false;
                    $responseData['code'] = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors'] = ['Can not get access token from ' . $channel];
                }
            }

            if ($channel == Marketplace::CODE_SHOPBASE) {
                $params = [
                    'shop_name'     => $shopName,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'shared_secret' => $sharedSecret,
                ];

                $settings = Service::shopBaseUs()->connect($params);

                if (data_get($settings, 'shop_name', '')) {
                    $shopName = data_get($settings, 'shop_info.name', '');
                }

                if (!$settings) {
                    $validateData = false;
                    $responseData['code'] = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors'] = ['Can not get access token from ' . $channel];
                }
            }
            
            if ($channel == Marketplace::CODE_SAPO) {
                $params = [
                    'shop_name'     => $shopName,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ];

                $settings = Service::sapo()->connect($params);

                if (data_get($settings, 'shop_name', '')) {
                    $shopName = data_get($settings, 'shop_info.name', '');
                }

                if (!$settings) {
                    $validateData = false;
                    $responseData['code'] = 500;
                    $responseData['message'] = 'Please check client id or client secret infomation';
                    $responseData['errors'] = ['Can not get access token from ' . $channel];
                }
            }

            $query = [
                'marketplace_code'    => $channel,
                'settings->client_id' => $clientId
            ];

            $data = [
                'tenant_id'            => $this->user->tenant_id,
                'merchant_id'          => $this->user->merchant->id,
                'marketplace_code'     => $channel,
                'marketplace_store_id' => data_get($settings, 'marketplace_store_id', null),
                'name'                 => $shopName,
                'settings'             => $settings,
                'warehouse_id'         => $warehouseId,
                'status'               => Store::STATUS_ACTIVE
            ];

            // dd($data);

            if ($validateData && $storeData = $store->updateOrCreate($query, $data)) {
                $responseData['data'] = $storeData;
                
                $merchantStoreFactory = new MerchantStoreFactory($store);
                $jober = $merchantStoreFactory->makeSyncProductJober();
                if ($jober instanceof Job) {
                    dispatch($jober);
                }
            }

        } else {
            $responseData['code'] = 500;
            $responseData['message'] = 'The given data channel was invalid';
            $responseData['errors'] = ['Channel store ' . $channel . ' not define'];
        }

        return $this->response()->success($responseData);
    }


    /**
     * @param Store $store
     * @return JsonResponse
     */
    public function updateWarehouse(Store $store)
    {
        $warehouseId = intval($this->request()->get('warehouse_id'));
        $user = $this->user;
        $warehouse = $user->tenant->warehouses()->firstWhere('warehouses.id', $warehouseId);
        if (!$warehouse instanceof Warehouse) {
            return $this->response()->error('INPUT_INVALID', ['warehouse_id' => \App\Base\Validator::ERROR_NOT_EXIST]);
        }

        if ($store->warehouse_id != $warehouse->id) {
            $oldWarehouse = ($store->warehouse) ? clone $store->warehouse : null;
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
        $jober = $merchantStoreFactory->makeSyncProductJober();
        
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

    /** đồng bộ tồn kho lên các kênh bán hàng
     * @param Store $store
     * @return JsonResponse
     */
    public function syncStockSkus(Store $store)
    {
        $type = $this->request()->get('type');
        $merchantId = $store->merchant_id;

        if ($store->marketplace_code == Marketplace::CODE_LAZADA) {
            dispatch(new SyncStockSkusJob($store, $merchantId, null, $type));
        }

        $responseData = [
            'code'    => 200,
            'message' => 'success',
            'errors' => [],
            'data' => $store
        ];

        return $this->response()->success($responseData);
    }

    /**
     * @param Store $store
     * @return JsonResponse
     */
    public function settings(Store $store)
    {
        $settings        = $this->request()->only(['sync_stock', 'quantity_type']);
        $store->settings = array_merge($store->settings ?: [], $settings ?: []);
        $store->save();

        return $this->response()->success($settings);
    }

}
