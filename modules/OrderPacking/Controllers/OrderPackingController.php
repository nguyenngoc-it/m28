<?php /** @noinspection ALL */

namespace Modules\OrderPacking\Controllers;

use App\Base\Controller;
use App\Base\Validator;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Support\Conversion;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Services\Permission;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Commands\ChangeShippingPartner;
use Modules\OrderPacking\Commands\ImportBarcode;
use Modules\OrderPacking\Events\OrderPackingServiceUpdated;
use Modules\OrderPacking\Jobs\GetListOrderPackingJob;
use Modules\OrderPacking\Jobs\GrantPickerJob;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Transformers\OrderPackingTransformer;
use Modules\OrderPacking\Validators\ChangeShippingPartnerValidator;
use Modules\OrderPacking\Validators\OrderPackingDownloadTempTrackingValidator;
use Modules\OrderPacking\Validators\OrderPackingImportBarcodeValidator;
use Modules\OrderPacking\Validators\OrderPackingScanListValidator;
use Modules\OrderPacking\Validators\OrderPackingScanValidator;
use Modules\OrderPacking\Validators\UpdateServicesValidator;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderPackingController extends Controller
{
    /**
     * Tạo filter để query order
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'warehouse_id',
            'order_code',
            'status',
            'shipping_partner_id',
            'merchant_id',
            'receiver_name',
            'receiver_phone',
            'payment_type',
            'payment_method',
            'created_at',
            'intended_delivery_at',
            'late_delivery_risk',
            'page',
            'per_page',
            'sort',
            'sort_by',
            'ids',
            'updated_shipping_partner_id',
            'remark',
            'sku_id',
            'freight_bill',
            'ignore_ids',
            'order_status',
            'error_tracking',
            'inspected',
            'no_inspected',
            'grant_for_picker_id',
            'picker_id',
            'pickup_truck_id',
            'picking_session_id',
            'priority',
            'store_id'
        ];
        $filter = $this->requests->only($inputs);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $user                    = $this->getAuthUser();
        $filter['tenant_id']     = $user->tenant_id;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

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
        $skuCode = $this->request()->get('sku_code');
        if (!empty($skuCode)) {
            $sku              = Sku::query()->firstWhere(['code' => $skuCode, 'tenant_id' => $user->tenant_id]);
            $filter['sku_id'] = ($sku instanceof Sku) ? $sku->id : 0;
        }

        $productCode = $this->request()->get('product_code');
        if (!empty($productCode)) {
            $product          = Product::query()->firstWhere(['code' => $productCode, 'tenant_id' => $user->tenant_id]);
            $filter['sku_id'] = ($product instanceof Product) ?
                $product->skus()->select(['id'])->pluck('id')->toArray() : 0;
        }

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        if (empty($filter['warehouse_id'])) {
            return $this->response()->success([]);
        }
        $results         = Service::orderPacking()->listing($filter);
        $canViewCustomer = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        return $this->response()->success([
            'order_packings' => array_map(function ($order_packing) use ($canViewCustomer) {
                $order_packing = (new OrderPackingTransformer())->transform($order_packing);
                if (!$canViewCustomer && !empty($order_packing['order'])) {
                    $order_packing['order']['receiver_phone']   = '***';
                    $order_packing['order']['receiver_address'] = '***';
                }
                return $order_packing;
            }, $results->items()),
            'pagination' => $results,
            'can_view_customer' => $canViewCustomer,
            'can_update_carrier' => $this->user->can(Permission::ORDER_UPDATE_CARRIER),
            'can_import_freight_bill' => $this->user->can(Permission::ORDER_IMPORT_FREIGHT_BILL),
            'can_print_bill' => $this->user->can(Permission::ORDER_PRINT_BILL),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function beforeTrackingNo()
    {
        if (!$this->user->can(Permission::ORDER_IMPORT_FREIGHT_BILL)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $filter             = $this->getQueryFilter();
        $filter['status']   = [OrderPacking::STATUS_WAITING_PROCESSING];
        $filter['paginate'] = false;
        $results            = Service::orderPacking()->listing($filter);

        $hasShopeeProvider = false;

        $orderPackings = [];
        /** @var OrderPacking $orderPacking */
        foreach ($results as $orderPacking) {
            $shippingPartner = $orderPacking->shippingPartner;
            if ($shippingPartner && $shippingPartner->provider == ShippingPartner::PROVIDER_SHOPEE) {
                $hasShopeeProvider = true;
            }

            if (!$orderPacking->order->inspected) {
                $missedSkus      = Service::order()->getSkusMissingWhenInpected($orderPacking->order);
                $orderPackings[] = array_merge(
                    $orderPacking->order->only(['code', 'created_at', 'intended_delivery_at']),
                    ['missed_skus' => $missedSkus],
                    ['id' => $orderPacking->id],
                    ['shipping_partner' => $shippingPartner]
                );
            }
        }

        return $this->response()->success(
            [
                'order_packings' => $orderPackings,
                'has_shopee_provider' => $hasShopeeProvider
            ]
        );
    }

    /**
     * Gán danh sách YCĐH cho 1 nhân viên kho
     *
     * @return JsonResponse
     */
    public function grantPicker()
    {
        $filter             = $this->getQueryFilter();
        $filter['paginate'] = false;
        /** @var User|null $picker */
        $picker = null;
        if (isset($filter['grant_for_picker_id'])) {
            $picker = User::find($filter['grant_for_picker_id']);
            unset($filter['grant_for_picker_id']);
        }
        $filterNotAllow                           = $filter;
        $filterNotAllow['not_allow_grant_picker'] = 1;
        $filter['inspected']                      = 1;
        $filter['status']                         = [OrderPacking::STATUS_WAITING_PICKING];

        if (empty($picker)) {
            return $this->response()->error('INPUT_INVALID', ['grant_for_picker_id' => Validator::ERROR_EXISTS]);
        }
        /**
         * Xử lý gán nhân viên nhặt hàng qua queue jobs
         */
        dispatch(new GrantPickerJob($filter, $picker->id, $this->user->id));
        $resultNotAllows = Service::orderPacking()->listing($filterNotAllow);
        return $this->response()->success(
            [
                'order_packing_not_allows' => $resultNotAllows->map(function (OrderPacking $orderPacking) {
                    return $orderPacking->order->only(['code', 'created_at', 'intended_delivery_at']);
                }),
            ]
        );
    }

    /**
     * Tạo vận đơn
     * @return JsonResponse
     */
    public function trackingNo()
    {
        if (!$this->user->can(Permission::ORDER_IMPORT_FREIGHT_BILL)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $creator          = $this->getAuthUser();
        $filter           = $this->getQueryFilter();
        $filter['status'] = [OrderPacking::STATUS_WAITING_PROCESSING];
        $pickupType       = $this->request()->get('pickup_type');

        dispatch(new GetListOrderPackingJob($filter, $creator->id, GetListOrderPackingJob::ACTION_CREATE_TRACKING_NO, $pickupType));

        return $this->response()->success([]);
    }


    /**
     * Tạo vận đơn
     * @return JsonResponse
     */
    public function cancelTrackingNo()
    {
        if (!$this->user->can(Permission::ORDER_IMPORT_FREIGHT_BILL)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $creator          = $this->getAuthUser();
        $filter           = $this->getQueryFilter();
        $filter['status'] = [OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING];
        dispatch(new GetListOrderPackingJob($filter, $creator->id, GetListOrderPackingJob::ACTION_CANCEL_TRACKING_NO));

        return $this->response()->success([]);
    }

    /**
     * Tạo vận đơn
     * @return JsonResponse
     */
    public function addWarehouseArea()
    {
        $creator     = $this->getAuthUser();
        $filter      = $this->getQueryFilter();
        $orderStatus = [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING];
        if (!empty($filter['order_status'])) {
            $orderStatus = $filter['order_status'];
        }
        $filter['order_status'] = $orderStatus;

        dispatch(new GetListOrderPackingJob($filter, $creator->id, GetListOrderPackingJob::ACTION_ADD_WAREHOUSE_AREA));

        return $this->response()->success([]);
    }


    /**
     * @return JsonResponse
     */
    public function beforeRemoveWarehouseArea()
    {
        $filter      = $this->getQueryFilter();
        $orderStatus = [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING];
        if (!empty($filter['order_status'])) {
            $orderStatus = $filter['order_status'];
        }
        $filter['order_status'] = $orderStatus;
        $filter['paginate']     = false;
        $filter['priority']     = true;
        $results                = Service::orderPacking()->listing($filter);

        return $this->response()->success(
            [
                'order_packings' => $results->map(function (OrderPacking $orderPacking) {
                    return array_merge(
                        $orderPacking->only(['id', 'order_id']),
                        $orderPacking->order->only(['code', 'created_at', 'intended_delivery_at']),
                    );
                }),
            ]
        );
    }

    /**
     * Tạo vận đơn
     * @return JsonResponse
     */
    public function removeWarehouseArea()
    {
        $creator     = $this->getAuthUser();
        $filter      = $this->getQueryFilter();
        $orderStatus = [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING];
        if (!empty($filter['order_status'])) {
            $orderStatus = $filter['order_status'];
        }
        $filter['order_status'] = $orderStatus;

        dispatch(new GetListOrderPackingJob($filter, $creator->id, GetListOrderPackingJob::ACTION_REMOVE_WAREHOUSE_AREA));

        return $this->response()->success([]);
    }


    /**
     * Tạo vận đơn
     * @return JsonResponse
     */
    public function addPriority()
    {
        $creator     = $this->getAuthUser();
        $filter      = $this->getQueryFilter();
        $orderStatus = [Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING];
        if (!empty($filter['order_status'])) {
            $orderStatus = $filter['order_status'];
        }
        $filter['order_status'] = $orderStatus;

        dispatch(new GetListOrderPackingJob($filter, $creator->id, GetListOrderPackingJob::ACTION_ADD_PRIORITY));

        return $this->response()->success([]);
    }

    /**
     * Download danh sách sản phẩm của yêu cầu đóng hàng
     *
     * @return JsonResponse|BinaryFileResponse
     */
    public function downloadListItems()
    {
        $filter = $this->getQueryFilter([
            'warehouse_id',
            'order_code',
            'status',
            'order_status',
            'shipping_partner_id',
            'merchant_id',
            'receiver_name',
            'receiver_phone',
            'payment_type',
            'payment_method',
            'created_at',
            'freight_bill',
            'intended_delivery_at',
            'inspected',
            'no_inspected',
            'priority',
            'remark',
            'sort',
            'sort_by',
        ]);
        if (empty($filter['warehouse_id'])) {
            return $this->response()->success([]);
        }
        $orderPackingIds = $this->requests->get('ids');
        if ($orderPackingIds) {
            $pathFile = Service::orderPacking()->donwloadListItemsByIds($orderPackingIds, Warehouse::find($filter['warehouse_id']));
        } else {
            $pathFile = Service::orderPacking()->downloadListItemsByFilter($filter, Warehouse::find($filter['warehouse_id']));
        }

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * Download danh sách mẫu tạo vận đơn trên đơn vị vận chuyển
     *
     * @return JsonResponse|BinaryFileResponse
     */
    public function downloadTempTrackings()
    {
        $filter = $this->getQueryFilter([
            'warehouse_id',
            'order_code',
            'status',
            'shipping_partner_id',
            'merchant_id',
            'receiver_name',
            'receiver_phone',
            'payment_type',
            'payment_method',
            'created_at',
            'freight_bill',
            'intended_delivery_at',
            'remark',
            'inspected',
            'no_inspected',
            'priority',
            'sort',
            'sort_by',
        ]);
        if (empty($filter['warehouse_id'])) {
            return $this->response()->success([]);
        }
        $orderPackingIds = $this->requests->get('ids');
        $validator       = new OrderPackingDownloadTempTrackingValidator($filter, $orderPackingIds);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        if ($orderPackingIds) {
            $pathFile = Service::orderPacking()->donwloadTempTrackingsByIds($validator->getShippingPartner(), $orderPackingIds);
        } else {
            $pathFile = Service::orderPacking()->downloadTempTrackingsByFilter($validator->getShippingPartner(), $filter);
        }

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     */
    public function scan(): JsonResponse
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'barcode_type',
            'barcode',
        ]);
        $user   = $this->getAuthUser();

        $validator = new OrderPackingScanValidator($user->tenant, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $orderPacking         = $validator->getOrderPacking();
        $orderPackingServices = $orderPacking->orderPackingServices;
        foreach ($orderPackingServices as $orderPackingService) {
            $service = $orderPackingService->service;
            $orderPackingService->setServiceNameAttribute($service->name);
            $servicePrice = $orderPackingService->servicePrice;
            $orderPackingService->setServicePriceLableAttribute($servicePrice->label);
        }
        return $this->response()->success([
            'freight_bill' => $freightBill = $validator->getFreightBill(),
            'order' => $validator->getOrder() ?: $freightBill->order,
            'order_packing' => $orderPacking,
            'order_stocks' => $orderPacking ? ($orderPacking->order->orderStocks ? $orderPacking->order->orderStocks->map(function (OrderStock $orderStock) {
                return array_merge($orderStock->attributesToArray(), ['sku' => $orderStock->sku], ['warehouse' => $orderStock->warehouse], ['warehouse_area' => $orderStock->warehouseArea]);
            }) : []) : [],
            'order_packing_services' => $orderPacking->orderPackingServices
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function scanList()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'ids',
        ]);
        $user   = $this->getAuthUser();

        $validator = new OrderPackingScanListValidator($user->tenant, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $orderPackings = $validator->getOrderPackings();
        foreach ($orderPackings as $orderPacking) {
            $orderPackingServices = $orderPacking->orderPackingServices;
            foreach ($orderPackingServices as $orderPackingService) {
                $service = $orderPackingService->service;
                $orderPackingService->setServiceNameAttribute($service->name);
                $servicePrice = $orderPackingService->servicePrice;
                $orderPackingService->setServicePriceLableAttribute($servicePrice->label);
            }
        }
        $orderPackings = [];
        /** @var OrderPacking $orderPacking */
        foreach ($validator->getOrderPackings() as $orderPacking) {
            $orderPackingItems = $orderPacking->orderPackingItems->load(['sku']);
            $orderPackings[]   = [
                'freight_bill' => $orderPacking->freightBill,
                'order' => $orderPacking->order,
                'order_packing' => $orderPacking,
                'order_packing_items' => $orderPackingItems,
                'order_packing_services' => $orderPacking->orderPackingServices
            ];
        }
        return $this->response()->success(
            [
                'order_packings' => $orderPackings,
            ]
        );
    }


    /**
     * @return JsonResponse
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function importBarcode()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'barcode_type',
            'file'
        ]);
        $user   = $this->getAuthUser();

        $validator = new OrderPackingImportBarcodeValidator($user->tenant, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $importBarcode = (
        new ImportBarcode($inputs['file'], $validator->getWarehouse(), $inputs['barcode_type'], $user)
        )->handle();

        return $this->response()->success($importBarcode);
    }

    /**
     * Cập nhật đơn vị VC
     * @return JsonResponse
     */
    public function shippingPartner()
    {
        if (!$this->user->can(Permission::ORDER_UPDATE_CARRIER)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $filter    = $this->getQueryFilter();
        $validator = new ChangeShippingPartnerValidator($this->getAuthUser(), $filter);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner    = $validator->getShippingPartner();
        $filter['paginate'] = false;

        if (!empty($filter['ids'])) {
            $orderPackings = OrderPacking::query()->whereIn('id', $filter['ids'])->get();
        } else {
            if (isset($filter['updated_shipping_partner_id'])) {
                unset($filter['updated_shipping_partner_id']);
            }
            $orderPackings = Service::orderPacking()->listing($filter);
        }

        (new ChangeShippingPartner($orderPackings, $shippingPartner, $this->getAuthUser()))->handle();
        return $this->response()->success($orderPackings);
    }

    /**
     * @return JsonResponse
     */
    public function packingTypes()
    {
        $packingTypes = $this->getAuthUser()->tenant->packingTypes;
        return $this->response()->success($packingTypes);
    }

    /**
     * Update Service cho YCDH
     *
     * @return JsonResponse
     */
    public function services()
    {
        $filter    = $this->request()->only(['order_packing_ids', 'service_price_ids']);
        $validator = new UpdateServicesValidator($this->getAuthUser(), $filter);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $orderPackings = $validator->getOrderPackings();
        $servicePrices = $validator->getServicePrices();

        /** @var OrderPacking $orderPacking */
        foreach ($orderPackings as $orderPacking) {

            $syncServicePrices = [];
            /** @var Service\Models\ServicePrice $servicePrice */
            foreach ($servicePrices as $servicePrice) {
                $syncServicePrices[$servicePrice->id] = [
                    'service_id' => $servicePrice->service->id,
                    'order_id' => $orderPacking->order->id,
                    'price' => $servicePrice->price,
                    'quantity' => $orderPacking->total_quantity,
                    'amount' => Conversion::convertMoney(($orderPacking->total_quantity - 1) * $servicePrice->yield_price + $servicePrice->price)
                ];
            }

            if (empty($syncServicePrices)) {
                $orderPacking->orderPackingServices()->delete();
                continue;
            }

            $orderPacking->servicePrices()->sync($syncServicePrices);
            $orderPacking->service_amount = round($orderPacking->orderPackingServices()->sum('amount'), 2);
            $orderPacking->save();
            (new OrderPackingServiceUpdated($orderPacking, $this->user))->queue();
        }


        return $this->response()->success($orderPackings);
    }
}
