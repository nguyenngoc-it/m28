<?php

namespace Modules\Order\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Exception;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Commands\ChangeShippingPartner;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Commands\Insepection;
use Modules\Order\Commands\PaymentConfirm;
use Modules\Order\Commands\UpdateOrder;
use Modules\Order\Models\Order;
use Modules\Order\Transformers\ListFinanceTransformer;
use Modules\Order\Transformers\OrderListItemTransformer;
use Modules\Order\Transformers\OrderDetailTransformer;
use Modules\Order\Validators\CancelOrderValidator;
use Modules\Order\Validators\ChangeShippingPartnerValidator;
use Modules\Order\Validators\CreateOrderValidator;
use Modules\Order\Validators\InspectionValidator;
use Modules\Order\Validators\PaymentConfirmValidator;
use Modules\Order\Validators\UpdateOrderValidator;
use Modules\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Base\Validator as BaseValidator;
use Modules\Warehouse\Models\Warehouse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    /**
     * Tạo filter để query order
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'code',
            'marketplace_code',
            'status',
            'list_status',
            'finance_status',
            'merchant_id',
            'merchant_ids',
            'receiver_name',
            'receiver_phone',
            'intended_delivery_at',
            'warehouse_id',
            'warehouse_area_id',
            'payment_type',
            'payment_method',
            'freight_bill',
            'sku_code',
            'created_at',
            'location_id',
            'warehouse_id',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'no_inspected',
            'shipping_partner_id',
            'packed_at',
            'no_for_control',
            'exporting_warehouse_at'
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        if (!empty($filter['sku_code'])) {
            $filter = $this->makeFilterSkuCode($filter);
        }

        return $filter;
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilterSkuCode($filter)
    {
        $SkuCode = Arr::pull($filter, 'sku_code');
        $query   = $this->user->tenant->skus()->select(['id']);
        if (is_array($SkuCode)) {
            $query->whereIn('skus.code', $SkuCode);
        } else {
            $query->where('skus.code', trim($SkuCode));
        }

        $filter['sku_id'] = $query->pluck('id')->toArray();

        return $filter;
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function import()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::order()->getRealPathFile($input['file']);
        $errors = Service::order()->importOrders($path, $user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function create()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new CreateOrderValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new CreateOrder(array_merge($input, [
            'creator' => $user,
            'merchant' => $validator->getMerchant(),
            'orderSkus' => $validator->getOrderSkus(),
            'receiverCountry' => $validator->getReceiverCountry(),
            'receiverProvince' => $validator->getReceiverProvince(),
            'receiverDistrict' => $validator->getReceiverDistrict(),
            'receiverWard' => $validator->getReceiverWard(),
            'orderAmount' => $validator->getOrderAmount(),
            'totalAmount' => $validator->getTotalAmount(),
            'extraServices' => $validator->getExtraServices(),
            'shippingPartner' => $validator->getShippingPartner(),
        ])))->handle();

        $orderSkus         = $order->orderSkus;
        $orderTransactions = $order->orderTransactions;

        return $this->response()->success(compact('order', 'orderSkus', 'orderTransactions'));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function detail(Order $order)
    {
        if (!in_array($order->merchant->id, $this->user->merchants->pluck('id')->all())) {
            return $this->response()->error(404, null, 404);
        }
        $data            = (new OrderDetailTransformer($this->getAuthUser(), true))->transform($order);
        $canViewCustomer = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        if (!$canViewCustomer && !empty($data['order'])) {
            $data['order']['receiver_phone']   = '***';
            $data['order']['receiver_address'] = '***';
        }

        return $this->response()->success($data);
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function inspection(Order $order)
    {
        $creator   = $this->getAuthUser();
        $input     = $this->request()->only([
            'order_stocks'
        ]);
        $validator = (new InspectionValidator($order, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new Insepection($order, $input, $creator))->handle();

        $data            = (new OrderDetailTransformer($this->getAuthUser()))->transform($order);
        $canViewCustomer = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        if (!$canViewCustomer && !empty($data['order'])) {
            $data['order']['receiver_phone']   = '***';
            $data['order']['receiver_address'] = '***';
        }
        return $this->response()->success($data);
    }


    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        if (empty($filter['merchant_ids'])) {
            $filter['merchant_ids'] = $this->user->merchants->pluck('id')->all();
        }
        $results         = Service::order()->listOrder($filter);
        $canViewCustomer = $this->user->can(Permission::ORDER_VIEW_CUSTOMER);
        return $this->response()->success([
            'orders' => array_map(function ($order) use ($canViewCustomer) {
                $orderData = (new OrderListItemTransformer())->transform($order);
                if (!$canViewCustomer && !empty($orderData['order'])) {
                    $orderData['order']['receiver_phone']   = '***';
                    $orderData['order']['receiver_address'] = '***';
                }
                return $orderData;
            }, $results->items()),
            'pagination' => $results,
            'can_view_customer' => $canViewCustomer,
        ]);
    }

    /**
     * Xuất danh sách orders
     *
     * @return BinaryFileResponse
     */
    public function export()
    {
        $filter = $this->getQueryFilter(
            [
                'ids',
                'code',
                'list_status',
                'merchant_ids',
                'receiver_name',
                'receiver_phone',
                'intended_delivery_at',
                'exporting_warehouse_at',
                'warehouse_id',
                'warehouse_area_id',
                'payment_type',
                'payment_method',
                'freight_bill',
                'created_at',
                'location_id',
                'sku_code',
                'shipping_partner_id',
                'marketplace_code'
            ]
        );
        if (empty($filter['merchant_ids'])) {
            $filter['merchant_ids'] = $this->user->merchants->pluck('id')->all();
        }
        if (!empty($filter['sku_code'])) {
            $filter = $this->makeFilterSkuCode($filter);
        }

        $pathFile = Service::order()->export(empty($filter['ids']) ? $filter : ['ids' => $filter['ids']], $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     */
    public function importForUpdate()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::order()->importForUpdate($input['file'], $this->user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     */
    public function importForConfirm()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::order()->importForConfirm($input['file'], $this->user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function getWarehousesInOrder(Order $order)
    {
        $warehouseIds = $order->orderStocks()->get()->pluck('warehouse_id');

        $warehouses = Warehouse::query()->whereIn('id', $warehouseIds)->get();

        return $this->response()->success(compact('warehouses'));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function getWaitingPickSkus(Order $order)
    {
        $orderStocks = $order->orderStocks()->get()->all();
        $orderStocks = array_map(function ($orderStock) {
            return [
                'sku' => $orderStock->sku,
                'warehouse_area' => $orderStock->warehouseArea,
                'stock' => $orderStock,
                'remain_quantity' => $orderStock->quantity - $orderStock->packaged_quantity
            ];
        }, $orderStocks);

        $orderStocks = array_filter($orderStocks, function ($orderStock) {
            return $orderStock['remain_quantity'] != 0;
        });
        $orderStocks = array_values($orderStocks);

        return $this->response()->success(compact('orderStocks'));
    }


    /**
     * Chuyển đơn sang Đã Giao hàng
     * @param Order $order
     * @return JsonResponse
     * @throws WorkflowException
     */
    public function delivery(Order $order)
    {
        if (!Service::order()->canDelivery($order, $this->getAuthUser())) {
            return $this->response()->error('INVALID_INPUT', ['status' => BaseValidator::ERROR_INVALID]);
        }
        $order->changeStatus(Order::STATUS_DELIVERED, $this->getAuthUser());

        return $this->response()->success(compact('order'));
    }


    /**
     * Xác nhận thanh toán
     * @param Order $order
     * @return JsonResponse
     */
    public function paymentConfirm(Order $order)
    {
        $creator   = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new PaymentConfirmValidator($order, $creator, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order = (new PaymentConfirm($order, $input, $creator))->handle();

        $data = (new OrderDetailTransformer($this->getAuthUser()))->transform($order);
        return $this->response()->success($data);
    }

    /**
     * @param Order $order
     * @return JsonResponse
     * @throws WorkflowException
     */
    public function cancel(Order $order)
    {
        $input     = $this->request()->only(['cancel_note', 'cancel_reason']);
        $validator = new CancelOrderValidator($order, $this->getAuthUser(), $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order->cancel_reason = trim($input['cancel_reason']);
        $order->cancel_note   = (isset($input['cancel_note'])) ? trim($input['cancel_note']) : '';
        $order->changeStatus(Order::STATUS_CANCELED, $this->getAuthUser());

        return $this->response()->success(compact('order'));
    }


    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function update(Order $order)
    {
        $input     = $this->request()->only(Order::$updateOrderParams);
        $validator = new UpdateOrderValidator($order, $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $input = $validator->getInputs();
        $order = (new UpdateOrder($order, $input, $this->user))->handle();

        return $this->response()->success(compact('order'));
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function getLogs(Order $order)
    {
        $logs = Service::order()->getLogs($order);

        return $this->response()->success(compact('logs'));
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importFreightBill()
    {
        $input     = $this->request()->only(['file', 'warehouse_id', 'id_tenant']);
        $validator = Validator::make($input, [
            'warehouse_id' => 'required',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::order()->importFreightBill($input['file'], Warehouse::find($input['warehouse_id']), $this->user);

        return $this->response()->success(compact('errors'));
    }


    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importFreightBillStatus()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::order()->getRealPathFile($input['file']);
        $errors = Service::order()->importFreightBillStatus($path, $user);

        return $this->response()->success(compact('errors'));
    }

    /** cập nhật trạng thái vận đơn từ file import
     * @return JsonResponse
     * @throws Exception
     */
    public function importFreightBillStatusNew()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $user   = $this->getAuthUser();
        $path   = Service::order()->getRealPathFile($input['file']);
        $errors = Service::order()->importFreightBillStatusNew($path, $user);

        return $this->response()->success(compact('errors'));

    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importStatus()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::order()->getRealPathFile($input['file']);
        $errors = Service::order()->importOrderStatus($path, $user);

        return $this->response()->success(compact('errors'));
    }


    /**
     * Cập nhật đơn vị VC
     * @param Order $order
     * @return JsonResponse
     */
    public function shippingPartner(Order $order)
    {
        if (!$this->user->can(Permission::ORDER_UPDATE_CARRIER)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $input     = $this->request()->only(['shipping_partner_id']);
        $validator = new ChangeShippingPartnerValidator($order, $this->getAuthUser(), $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner = $validator->getShippingPartner();
        $order           = (new ChangeShippingPartner($order, $shippingPartner, $this->getAuthUser()))->handle();
        return $this->response()->success(compact('order'));
    }

    /**
     * Đồng bộ thông tin đơn từ marketplace
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function sync(Order $order)
    {
        if (!$order->canSync()) {
            return $this->response()->error('CANT_SYNC');
        }

        if ($order->marketplace_code === Marketplace::CODE_SHOPEE) {
            Service::shopee()->syncOrders($order->marketplace_store_id, [['ordersn' => $order->code]]);
        }

        return $this->response()->success(compact('order'));
    }


    /**
     * @return JsonResponse|BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportServices()
    {
        $type      = $this->request()->only('type_export');
        $validator = Validator::make($type, [
            'type_export' => 'required|in:export_refund_cost,export_cost'
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $filter   = $this->getQueryFilter();
        $pathFile = Service::order()->exportServices($filter, $this->user, $type);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function importFinanceStatus()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = Service::order()->importFinanceStatus($path, $user);

        return $this->response()->success(compact('errors'));
    }


    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function finance()
    {
        $filter = $this->getQueryFilter();
        if (empty($filter['created_at'])) {
            $filter['created_at'] = [
                'from' => (new Carbon())->subDays(30)->toDateTimeString(),
                'to' => (new Carbon())->toDateTimeString(),
            ];
        }
        if (empty($filter['location_id'])) {
            return $this->response()->success();
        }

        $results = Service::order()->merchantListFinance($filter);

        return $this->response()->success([
            'orders' => array_map(function ($order) {
                /** @var Order $order */
                $orderData             = (new ListFinanceTransformer())->transform($order);
                $orderData['merchant'] = $order->merchant;
                return $orderData;
            }, $results->items()),
            'pagination' => $results,
        ]);
    }


    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function stats()
    {
        $filter = $this->getQueryFilter([
            'location_id',
            'code',
            'merchant_id',
            'status',
            'finance_status',
            'created_at',
        ]);
        if (empty($filter['location_id'])) {
            return $this->response()->success();
        }

        if (empty($filter['created_at'])) {
            $filter['created_at'] = [
                'from' => (new Carbon())->subDays(30)->toDateTimeString(),
                'to' => (new Carbon())->toDateTimeString(),
            ];
        }

        $stats = Service::order()->stats($filter, $this->getAuthUser());

        $country  = Location::find($filter['location_id']);
        $currency = ($country instanceof Location) ? $country->currency : null;

        return $this->response()->success(array_merge($stats, ['currency' => $currency]));
    }


    /**
     * API này do PO yêu cầu, để PO call postman tự xử lý những đơn lỗi
     * @return JsonResponse
     */
    public function removeStock()
    {
        $user     = Service::user()->getSystemUserDefault();
        $orderIds = $this->request()->get('order_ids', []);
        if (empty($orderIds)) {
            return $this->response()->error('order ids empty');
        }

        $orders = Order::query()->whereIn('id', $orderIds)->get();
        foreach ($orders as $order) {
            Service::order()->removeStockOrder($order, $user);
            print_r("remove Stock Order " . $order->id . ' - ' . $order->code . "\n");

            if ($order->status == Order::STATUS_WAITING_CONFIRM) {
                $order->status = Order::STATUS_WAITING_INSPECTION;
                $order->save();

                print_r("Change Order status " . $order->id . ' - ' . $order->code . " WAITING_CONFIRM -> WAITING_INSPECTION \n");
            }
        }

        return $this->response()->success(compact('orderIds'));
    }
}
