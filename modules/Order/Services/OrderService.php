<?php

namespace Modules\Order\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use DateTime;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\Support\Conversion;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\WorkflowInterface;
use Gobiz\Workflow\WorkflowService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\Auth\Services\Permission;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\MerchantSetting;
use Modules\Order\Commands\AutoInsepection;
use Modules\Order\Commands\CreateBashOrder;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Commands\CreateOrderStock;
use Modules\Order\Commands\ExportOrder;
use Modules\Order\Commands\ExportOrderServices;
use Modules\Order\Commands\ImportFinanceStatus;
use Modules\Order\Commands\ImportForConfirm;
use Modules\Order\Commands\ImportForUpdate;
use Modules\Order\Commands\ImportFreightBillManual;
use Modules\Order\Commands\ImportFreightBillStatus;
use Modules\Order\Commands\ImportFreightBillStatusNew;
use Modules\Order\Commands\ImportMerchantFreightBill;
use Modules\Order\Commands\ImportOrderStatus;
use Modules\Order\Commands\MerchantImportDropshipOrders;
use Modules\Order\Commands\MerchantImportOrders;
use Modules\Order\Commands\MerchantListFinance;
use Modules\Order\Commands\OrderStats;
use Modules\Order\Events\OrderStockDeleted;
use Modules\Order\Jobs\CreatingBashMerchantOrder;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Models\OrderPackingItem;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Tenant\Models\TenantSetting;
use Modules\User\Models\User;
use Illuminate\Support\Str;
use Modules\Order\Commands\ImportOrders;
use Illuminate\Http\UploadedFile;
use Modules\Order\Commands\ImportFreightBill;
use Modules\Order\Commands\ListOrder;
use Modules\Warehouse\Models\Warehouse;
use Gobiz\Activity\ActivityService;
use Gobiz\Transformer\TransformerService;
use Modules\Order\Commands\CreateOrderFrom3rdPartner;
use Modules\Order\Resource\Data3rdResource;
use Modules\Store\Models\Store;

class OrderService implements OrderServiceInterface
{
    /**
     * Get order workflow instance
     *
     * @return WorkflowInterface
     */
    public function workflow()
    {
        return WorkflowService::workflow('order');
    }

    /**
     * @param array $inputs
     * @return mixed
     */
    public function create(array $inputs)
    {
        return (new CreateOrder($inputs))->handle();
    }

    /**
     * Create order stock
     *
     * @param Order $order
     * @param Stock $stock
     * @param int $quantity
     * @param User $creator
     * @return OrderStock|object
     */
    public function createOrderStock(Order $order, Stock $stock, $quantity, User $creator)
    {
        return (new CreateOrderStock($order, $stock, $quantity, $creator))->handle();
    }

    /**
     * Make query to order
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new OrderQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder
     *
     */
    public function listOrder(array $filter)
    {
        return (new ListOrder($filter))->handle();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator|object
     */
    public function merchantListFinance(array $filter)
    {
        return (new MerchantListFinance($filter))->handle();
    }

    /**
     * @param array $filter
     * @param User $creator
     * @return array
     */
    public function stats(array $filter, User $creator)
    {
        return (new OrderStats($filter, $creator))->handle();
    }

    /**
     * Import Orders from file
     *
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importOrders($filePath, User $creator)
    {
        return (new ImportOrders($filePath, $creator))->handle();
    }

    /**
     * Merchant Import Orders from file
     *
     * @param string $filePath
     * @param User $creator
     * @param Warehouse|null $warehouse
     * @return array
     * @throws Exception
     */
    public function merchantImportOrders($filePath, User $creator, $warehouse = null)
    {
        return (new MerchantImportOrders($filePath, $creator, $warehouse))->handle();
    }

    /**
     * Merchant Import DropShip Orders  from file
     *S
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function merchantImportDropshipOrders($filePath, User $creator)
    {
        return (new MerchantImportDropshipOrders($filePath, $creator))->handle();
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws Exception
     */
    public function getRealPathFile(UploadedFile $file)
    {
        $ext      = $file->getClientOriginalExtension();
        $fileName = Str::uuid();

        return $file->move('/tmp', $fileName . '.' . $ext)->getRealPath();
    }

    /**
     * @param array $filter
     * @param User $user
     * @param boolean $checkViewCustomer
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export(array $filter, User $user, $checkViewCustomer = true)
    {
        return (new ExportOrder($filter, $user, $checkViewCustomer))->handle();
    }

    /**
     * @param Order $order
     * @param User $user
     * @return bool|mixed
     */
    public function canInspection(Order $order, User $user)
    {
        if (!$user->can(Permission::ORDER_CREATE)) {
            return false;
        }
        return in_array($order->status, [Order::STATUS_WAITING_INSPECTION, Order::STATUS_WAITING_CONFIRM, Order::STATUS_WAITING_PROCESSING, Order::STATUS_WAITING_PICKING]);
    }

    /**
     * @param Order $order
     * @param User $user
     * @return bool|mixed
     */
    public function canDelivery(Order $order, User $user)
    {
        if (!$user->can(Permission::ORDER_CREATE)) {
            return false;
        }

        if ($order->status != Order::STATUS_DELIVERING) {
            return false;
        }

        return true;
    }

    /**
     * Quản trị có thể hủy đơn
     * @param Order $order
     * @param User $user
     * @return bool|mixed
     */
    public function canCancel(Order $order, User $user)
    {
        if (!$user->can(Permission::ORDER_CREATE)) {
            return false;
        }

        return $this->canCancelOrder($order);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function canCancelOrder(Order $order)
    {
        if (
            in_array($order->status, [
                Order::STATUS_WAITING_INSPECTION,
                Order::STATUS_WAITING_CONFIRM,
                Order::STATUS_WAITING_PROCESSING,
                Order::STATUS_WAITING_PICKING,
                Order::STATUS_WAITING_PACKING
            ])
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param User $user
     * @return bool|mixed
     */
    public function sellerCanCancel(Order $order, User $user)
    {
        if ($user->merchant->id != $order->merchant_id) {
            return false;
        }

        return $this->canCancelOrder($order);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function canCreatePackage(Order $order)
    {
        if ($order->status == Order::STATUS_WAITING_INSPECTION || $order->status == Order::STATUS_CANCELED) {
            return false;
        }
        $waitingPickSkus = $order->orderStocks()
            ->whereRaw('quantity != packaged_quantity')
            ->get();

        if (count($waitingPickSkus) == 0) {
            return false;
        }

        return true;
    }

    /**
     * @param $time
     * @return Carbon|null
     * @throws Exception
     */
    public function formatDateTime($time)
    {
        if (empty($time) || $time == null) {
            return null;
        }
        $time = (is_string($time)) ? str_replace('/', '-', $time) : $time;
        if ($time instanceof DateTime) {
            $time = $time->format('Y-m-d');
        }
        return new Carbon((string)$time);
    }

    /**
     * Import freight bill from file
     *
     * @param UploadedFile $file
     * @param Warehouse $warehouse
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function importFreightBill(UploadedFile $file, Warehouse $warehouse, User $user)
    {
        return (new ImportFreightBill($file, $warehouse, $user))->handle();
    }

    /**
     * Cập nhật mã vận đơn của đơn thông qua tools
     *
     * @param UploadedFile $file
     * @param User $user
     * @return array
     */
    public function importFreightBillManual(UploadedFile $file, User $user): array
    {
        return (new ImportFreightBillManual($file, $user))->handle();
    }

    /**
     * Import vận đơn của 1 merchant theo file
     * Nếu replace = true sẽ xoá vận đơn cũ của đơn nếu đã tồn tại
     *
     * @param UploadedFile $file
     * @param Merchant $merchant
     * @param User $creator
     * @param bool $replace
     * @return array
     *
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     */
    public function importMerchantFreightBill(UploadedFile $file, Merchant $merchant, User $creator, $replace = false)
    {
        return (new ImportMerchantFreightBill($file, $merchant, $creator, $replace))->handle();
    }


    /**
     * Import Order status from file
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importOrderStatus($filePath, User $creator)
    {
        return (new ImportOrderStatus($creator, $filePath))->handle();
    }

    /**
     * Import FreightBill status from file
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFreightBillStatus($filePath, User $creator)
    {
        return (new ImportFreightBillStatus($creator, $filePath))->handle();
    }

    /**
     * Import FreightBill status from file
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFreightBillStatusNew($filePath, User $creator)
    {
        return (new ImportFreightBillStatusNew($creator, $filePath))->handle();
    }

    /**
     * @param $file
     * @param User $user
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function importForUpdate($file, User $user)
    {
        return (new ImportForUpdate($file, $user))->handle();
    }

    /**
     * Xác nhận thông tin đơn thông qua file excel
     *
     * @param $file
     * @param User $user
     * @return array
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws UnsupportedTypeException
     * @throws WorkflowException
     */
    public function importForConfirm($file, User $user)
    {
        return (new ImportForConfirm($file, $user))->handle();
    }

    /**
     * Huỷ chọn kho xuất trên đơn
     *
     * @param Order $order
     * @param User $user
     * @return void
     * @throws Exception
     */
    public function removeStockOrder(Order $order, User $user)
    {
        $orderStocks = $order->orderStocks;
        if ($orderStocks->count()) {
            DB::transaction(function () use ($order, $orderStocks, $user) {
                $orderStockIds = [];
                $stockIds      = [];
                foreach ($orderStocks as $orderStock) {
                    $orderStockIds[] = $orderStock->id;
                    $stockIds[]      = $orderStock->stock_id;
                    $orderStock->delete();
                }

                //bỏ thông tin snapshot của stock trong OrderPackingItem
                OrderPackingItem::query()->whereIn('order_stock_id', $orderStockIds)->update(
                    [
                        'stock_id' => 0,
                        'order_stock_id' => 0,
                        'warehouse_area_id' => 0,
                    ]
                );

                $order->inspected = 0;
                $order->save();
                (new OrderStockDeleted($order, $user, Carbon::now(), $stockIds))->queue();
            });
        }
    }

    /**
     * Tự động chọn kho xuất cho đơn
     * Trả về thành công nếu toàn bộ skus trên đơn đã được chọn kho xuất
     *
     * @param Order $order
     * @param User $creator
     * @return boolean
     */
    public function autoInspection(Order $order, User $creator)
    {
        $order->refresh();
        (new AutoInsepection($order, $creator))->handle();
        return $order->inspected;
    }

    /**
     * Đồng bộ trạng thái đơn từ trạng thái mã vận đơn
     *
     * @param Order $order
     * @param FreightBill $freightBill
     * @param User $creator
     * @return bool
     * @throws WorkflowException
     */
    public function updateStatusFromFreightBill(Order $order, FreightBill $freightBill, User $creator)
    {
        $orderStatus = $freightBill->mapOrderStatus();
        if (!$orderStatus || !$order->canChangeStatus($orderStatus)) {
            return false;
        }

        $order->changeStatus($orderStatus, $creator, ['freight_bill_code' => $freightBill->freight_bill_code]);

        return true;
    }

    /**
     * Cập nhật lại tiền hàng trên đơn khi thay đổi thông tin sản phẩm
     *
     *
     * @param Order $order
     * @return void
     */
    public function updateMoneyWhenChangeSkus(Order $order)
    {
        $amountSkus = $taxAmountSkus = $discountAmountSkus = 0;
        /** @var OrderSku $orderSku */
        foreach ($order->orderSkus as $orderSku) {
            $amountSku          = $orderSku->price * $orderSku->quantity;
            $amountSkus         += $amountSku;
            $taxAmountSkus      += $amountSku * floatval($orderSku->tax) * 0.01;
            $discountAmountSkus += $orderSku->discount_amount;
        }

        $orderAmount         = $amountSkus + $taxAmountSkus - $discountAmountSkus;
        $totalAmount         = $orderAmount + $order->shipping_amount + $order->delivery_fee - $order->discount_amount;
        $debitAmount         = $totalAmount - $order->paid_amount;
        $order->order_amount = Conversion::convertMoney($orderAmount);
        $order->total_amount = Conversion::convertMoney($totalAmount);
        $order->debit_amount = Conversion::convertMoney($debitAmount);
        $order->save();
    }


    /**
     * @param Order $order
     * @param $status
     * @param User $creator
     * @return Order
     */
    public function updateFinanceStatus(Order $order, $status, User $creator)
    {
        if ($order->finance_status == $status) {
            return $order;
        }

        $order->finance_status = $status;
        $order->save();

        $order->logActivity(OrderEvent::UPDATE_FINANCE_STATUS, $creator, $order->getChanges());

        return $order;
    }

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportServices(array $filter, User $user, $type)
    {
        return (new ExportOrderServices($filter, $user, $type))->handle();
    }


    /**
     * @param $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFinanceStatus($filePath, User $creator)
    {
        return (new ImportFinanceStatus($filePath, $creator))->handle();
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getLogs(Order $order)
    {
        $logs     = ActivityService::logger()->get('order', (int)$order->id);
        $creators = User::query()->whereIn('id', array_map(function ($log) {
            return $log['creator']['id'];
        }, $logs))->get()->all();

        $logs = array_map(function ($log) use ($creators) {
            $creatorIndex = array_search($log['creator']['id'], array_column($creators, 'id'));
            $creator      = $creators[$creatorIndex];
            $created_at   = $log['created_at']->format('Y-m-d H:i:s');
            return array_merge(TransformerService::transform($log), ['creator' => $creator, 'created_at' => $created_at]);
        }, $logs);

        return $logs;
    }

    /**
     * Skus còn thiếu khi chọn vị trí kho
     * [
     *  [sku => 'code', 'quantity' => 2]
     * ]
     *
     * @param Order $order
     * @return array
     */
    public function getSkusMissingWhenInpected(Order $order)
    {
        if ($order->orderStocks->count() == 0) {
            return $order->orderSkus->map(function (OrderSku $orderSku) {
                return [
                    'sku' => $orderSku->sku->code,
                    'quantity' => $orderSku->quantity
                ];
            });
        }

        return $order->orderSkus->map(function (OrderSku $orderSku) use ($order) {
            $orderStockSkuQuantity = (int)$order->orderStocks->where('sku_id', $orderSku->sku_id)->sum('quantity');
            if ($orderStockSkuQuantity < $orderSku->quantity) {
                return [
                    'sku' => $orderSku->sku->code,
                    'quantity' => $orderSku->quantity - $orderStockSkuQuantity
                ];
            }
            return null;
        })->filter()->values();
    }

    /**
     * @param OrderStock $orderStock
     * @param bool $createIfEmpty
     * @return void
     */
    public function updateOrderPackingItems(OrderStock $orderStock, bool $createIfEmpty = true)
    {
        /** @var OrderSku $orderSku */
        $orderSku     = $orderStock->order->orderSkus()->where('sku_id', $orderStock->sku_id)->first();
        $orderPacking = $orderStock->order->orderPacking;

        if ($createIfEmpty) {
            OrderPackingItem::updateOrCreate(
                [
                    'order_id' => $orderStock->order_id,
                    'sku_id' => $orderStock->sku_id,
                ],
                [
                    'order_stock_id' => $orderStock->id,
                    'order_packing_id' => $orderPacking ? $orderPacking->id : 0,
                    'price' => $orderSku->price,
                    'warehouse_id' => $orderStock->warehouse_id,
                    'stock_id' => $orderStock->stock_id,
                    'warehouse_area_id' => $orderStock->warehouse_area_id,
                    'quantity' => $orderStock->quantity,
                    'values' => round($orderStock->quantity * $orderSku->price, 6)
                ]
            );
        } else {
            OrderPackingItem::query()->where('sku_id', $orderStock->sku_id)
                ->where('order_id', $orderStock->order_id)
                ->update(
                    [
                        'price' => $orderSku->price ?: 0,
                        'warehouse_id' => $orderStock->warehouse_id,
                        'stock_id' => $orderStock->stock_id,
                        'order_stock_id' => $orderStock->id,
                        'warehouse_area_id' => $orderStock->warehouse_area_id,
                        'quantity' => $orderStock->quantity,
                        'values' => round($orderStock->quantity * $orderSku->price, 6)
                    ]
                );
        }
    }

    /**
     * Cập nhật lại orderPackingItems của đơn theo orderStock
     *
     * @param Order $order
     * @return void
     */
    public function updateOrderPackingItemsByOrder(Order $order)
    {
        foreach ($order->orderSkus as $orderSku) {
            if ($orderStock = $order->orderStocks->where('sku_id', $orderSku->sku_id)->first()) {
                $this->updateOrderPackingItems($orderStock);
            } else {
                OrderPackingItem::updateOrCreate(
                    [
                        'order_id' => $orderSku->order_id,
                        'sku_id' => $orderSku->sku_id,
                    ],
                    [
                        'order_stock_id' => 0,
                        'order_packing_id' => $order->orderPacking ? $order->orderPacking->id : 0,
                        'price' => $orderSku->price ?: 0,
                        'warehouse_id' => $order->warehouse_id,
                        'stock_id' => 0,
                        'warehouse_area_id' => 0,
                        'quantity' => $orderSku->quantity ?: 0,
                        'values' => round($orderSku->price * $orderSku->quantity, 6)
                    ]
                );
            }
        }
        if ($order->orderSkus->count() == $order->orderStocks->count()) {
            /**
             * Xoá bản ghi orderStockId không còn dùng trong orderPackingItems
             */
            $stockIds = $order->orderStocks->pluck('stock_id')->all();
            OrderPackingItem::query()->whereNotIn('stock_id', $stockIds)
                ->where('order_id', $order->id)->delete();
        }
    }

    /**
     * @param array $cachedOrders
     * @param User $user
     * @return array
     */
    public function importBashOrder(array $cachedOrders, User $user)
    {
        $errorOrders = [];
        if (count($cachedOrders) > 100) {
            dispatch(new CreatingBashMerchantOrder($cachedOrders));
            return [
                'queue' => true
            ];
        } else {
            foreach ($cachedOrders as $cachedOrder) {
                try {
                    (new CreateBashOrder($cachedOrder))->handle();
                } catch (Exception $exception) {
                    $errorOrders[] = [
                        'order_code' => $cachedOrder['code'],
                        'message' => 'tech_error',
                        'exception' => $exception->getMessage()
                    ];
                }
            }
        }

        return ['errors' => $errorOrders];
    }

    /**
     * Thay đổi trạng thái đơn thủ công,
     * không bắt buộc theo đúng workflow, sử dụng với tools internal
     *
     * @param Order $order
     * @param $status
     * @param User $user
     * @return void
     */
    public function changeStatusWithoutWorkflow(Order $order, $status, User $user)
    {
        if (!in_array($status, Order::$listStatus) || $order->status == $status) {
            return;
        }
        $order->logActivity(OrderEvent::CHANGE_STATUS, $user, [
            'order' => $order,
            'old_status' => $order->status,
            'new_status' => $status,
        ]);
        $order->status = $status;
        $order->save();
    }

    /**
     *
     * @param Store $store
     * @param Data3rdResource $dataResource
     * @return Order
     * @throws WorkflowException
     */
    public function createOrderFrom3rdPartner(Store $store, Data3rdResource $dataResource)
    {
        return (new CreateOrderFrom3rdPartner($store, $dataResource))->handle();
    }

    /**
     * Kiểm tra đơn tự động xác nhận + tạo vận đơn được không
     * @param Order $order
     * @return bool
     */
    public function canAutoOrderConfirmAndCreateFreightBill(Order $order)
    {
        if (!$order->tenant->getSetting(TenantSetting::AUTO_CREATE_FREIGHT_BILL)) {
            return false;
        }

        if (
            $order->merchant instanceof Merchant &&
            $order->merchant->getSetting(MerchantSetting::SETTING_NOT_AUTO_CREATE_FREIGHT_BILL)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Chuyển sku lô trên đơn về lại sku cha
     *
     * @param Order $order
     * @return void
     */
    public function convertChildrenToParentSku(Order $order)
    {
        $batchOfGoods = BatchOfGood::query()->whereIn('sku_child_id', $order->orderSkus->pluck('sku_id'))
            ->get();
        $childParents = [];
        if ($batchOfGoods->count()) {
            /** @var BatchOfGood $batchOfGood */
            foreach ($batchOfGoods as $batchOfGood) {
                $childParents[$batchOfGood->sku_child_id] = $batchOfGood->sku_id;
            }
            $buildOrderSkus = [];
            /** @var OrderSku $orderSku */
            foreach ($order->orderSkus as $orderSku) {
                if (isset($childParents[$orderSku->sku_id])) {
                    $buildOrderSkus[$childParents[$orderSku->sku_id]]['tenant_id'] = $order->tenant_id;
                    $buildOrderSkus[$childParents[$orderSku->sku_id]]['sku_id']    = $childParents[$orderSku->sku_id];
                    $buildOrderSkus[$childParents[$orderSku->sku_id]]['quantity']  = isset($buildOrderSkus[$childParents[$orderSku->sku_id]]['quantity'])
                        ? $buildOrderSkus[$childParents[$orderSku->sku_id]]['quantity'] + $orderSku->quantity : $orderSku->quantity;
                }
            }
            DB::transaction(function () use ($order, $buildOrderSkus) {
                $order->orderSkus()->delete();
                $order->orderSkus()->createMany(array_values($buildOrderSkus));
            });
        }
    }
}
