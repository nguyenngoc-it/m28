<?php

namespace Modules\Order\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\WorkflowInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Jenssegers\Mongodb\Eloquent\Builder;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderStock;
use Modules\Order\Resource\Data3rdResource;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\Warehouse\Models\Warehouse;

interface OrderServiceInterface
{
    /**
     * Get order workflow instance
     *
     * @return WorkflowInterface
     */
    public function workflow();

    /**
     * @param array $inputs
     * @return mixed
     */
    public function create(array $inputs);

    /**
     * Create order stock
     *
     * @param Order $order
     * @param Stock $stock
     * @param int $quantity
     * @param User $creator
     * @return OrderStock
     */
    public function createOrderStock(Order $order, Stock $stock, $quantity, User $creator);

    /**
     * Make query to order
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder
     */
    public function listOrder(array $filter);

    /**
     * @param array $filter
     * @return LengthAwarePaginator|object
     */
    public function merchantListFinance(array $filter);


    /**
     * @param array $filter
     * @param User $creator
     * @return array
     */
    public function stats(array $filter, User $creator);


    /**
     * Import Orders from file
     *
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importOrders($filePath, User $creator);

    /**
     * @param UploadedFile $file
     * @return string
     * @throws Exception
     */
    public function getRealPathFile(UploadedFile $file);

    /**
     * @param array $filter
     * @param User $user
     * @param bool $checkViewCustomer
     * @return mixed
     */
    public function export(array $filter, User $user, $checkViewCustomer = true);

    /**
     * @param Order $order
     * @param User $user
     * @return mixed
     */
    public function canDelivery(Order $order, User $user);

    /**
     * @param Order $order
     * @param User $user
     * @return mixed
     */
    public function canInspection(Order $order, User $user);

    /**
     * @param Order $order
     * @param User $user
     * @return mixed
     */
    public function canCancel(Order $order, User $user);

    /**
     * @param Order $order
     * @param User $user
     * @return bool|mixed
     */
    public function sellerCanCancel(Order $order, User $user);

    /**
     * @param Order $order
     * @return bool
     */
    public function canCreatePackage(Order $order);

    /**
     * @param $time
     * @return Carbon|null
     */
    public function formatDateTime($time);

    /**
     * Import Order status from file
     *
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importOrderStatus($filePath, User $creator);

    /**
     * Import FreightBill status from file
     *
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importFreightBillStatus($filePath, User $creator);

    /**
     * Import FreightBill status from file
     *
     * @param string $filePath
     * @param User $creator
     * @return array
     */
    public function importFreightBillStatusNew($filePath, User $creator);

    /**
     * Import freight bill from file
     *
     * @param UploadedFile $file
     * @param Warehouse $warehouse
     * @param User $user
     * @return array
     */
    public function importFreightBill(UploadedFile $file, Warehouse $warehouse, User $user);

    /**
     * Cập nhật mã vận đơn của đơn thông qua tools
     *
     * @param UploadedFile $file
     * @param User $user
     * @return array
     */
    public function importFreightBillManual(UploadedFile $file, User $user): array;

    /**
     * Import vận đơn của 1 merchant theo file
     *
     * @param $file
     * @param Merchant $merchant
     * @param User $creator
     * @param bool $replace
     * @return array
     */
    public function importMerchantFreightBill(UploadedFile $file, Merchant $merchant, User $creator, $replace = false);

    /**
     * Cập nhật thông tin đơn thông qua file excel
     *
     * @param $file
     * @param User $user
     * @return array
     */
    public function importForUpdate($file, User $user);

    /**
     * Xác nhận thông tin đơn thông qua file excel
     *
     * @param $file
     * @param User $user
     * @return array
     */
    public function importForConfirm($file, User $user);

    /**
     * Huỷ chọn kho xuất trên đơn
     *
     * @param Order $order
     * @param User $user
     * @return void
     */
    public function removeStockOrder(Order $order, User $user);

    /**
     * Tự động chọn kho xuất cho đơn
     *
     * @param Order $order
     * @param User $creator
     * @return boolean
     */
    public function autoInspection(Order $order, User $creator);

    /**
     * Cập nhật trạng thái đơn từ trạng thái mã vận đơn
     *
     * @param Order $order
     * @param FreightBill $freightBill
     * @param User $creator
     * @return bool
     * @throws WorkflowException
     */
    public function updateStatusFromFreightBill(Order $order, FreightBill $freightBill, User $creator);

    /**
     * Cập nhật lại tiền hàng trên đơn khi thay đổi thông tin sản phẩm
     *
     * @param Order $order
     * @return void
     */
    public function updateMoneyWhenChangeSkus(Order $order);

    /**
     * Merchant Import Orders from file
     *
     * @param string $filePath
     * @param User $creator
     * @param Warehouse|null $warehouse
     * @return array
     * @throws Exception
     */
    public function merchantImportOrders($filePath, User $creator, $warehouse = null);

    /**
     * Merchant Import DropShip Orders  from file
     *S
     * @param string $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function merchantImportDropshipOrders($filePath, User $creator);

    /**
     * @param Order $order
     * @param $status
     * @param User $creator
     * @return Order
     */
    public function updateFinanceStatus(Order $order, $status, User $creator);

    /**
     * @param array $filter
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportServices(array $filter, User $user, $type);


    /**
     * @param $filePath
     * @param User $creator
     * @return array
     * @throws Exception
     */
    public function importFinanceStatus($filePath, User $creator);

    /**
     * @param Order $order
     * @return array
     */
    public function getLogs(Order $order);

    /**
     * Skus còn thiếu khi chọn vị trí kho
     * [
     *  [sku => 'code', 'quantity' => 2]
     * ]
     *
     * @param Order $order
     * @return array
     */
    public function getSkusMissingWhenInpected(Order $order);

    /**
     * @param OrderStock $orderStock
     * @param bool $createIfEmpty
     * @return void
     */
    public function updateOrderPackingItems(OrderStock $orderStock, bool $createIfEmpty = true);

    /**
     * Cập nhật lại orderPackingItems của đơn theo orderStock
     *
     * @param Order $order
     * @return void
     */
    public function updateOrderPackingItemsByOrder(Order $order);

    /**
     * @param array $cachedOrders
     * @param User $user
     * @return array
     */
    public function importBashOrder(array $cachedOrders, User $user);

    /**
     * Thay đổi trạng thái đơn thủ công,
     * không bắt buộc theo đúng workflow, sử dụng với tools internal
     *
     * @param Order $order
     * @param $status
     * @param User $user
     * @return void
     */
    public function changeStatusWithoutWorkflow(Order $order, $status, User $user);

    /**
     *
     * @param Store $store
     * @param Data3rdResource $dataResource
     * @return Order
     */
    public function createOrderFrom3rdPartner(Store $store, Data3rdResource $dataResource);

    /**
     * Kiểm tra đơn tự động xác nhận + tạo vận đơn được không
     * @param Order $order
     * @return bool
     */
    public function canAutoOrderConfirmAndCreateFreightBill(Order $order);

    /**
     * Chuyển sku lô trên đơn về lại sku cha
     *
     * @param Order $order
     * @return void
     */
    public function convertChildrenToParentSku(Order $order);
}
