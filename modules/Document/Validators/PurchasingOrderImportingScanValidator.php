<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingOrderItem;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class PurchasingOrderImportingScanValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var WarehouseArea
     */
    protected $warehouseArea;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var PurchasingOrder
     */
    protected $purchasingOrder;


    /**
     * @var PurchasingPackage
     */
    protected $purchasingPackage;

    /**
     * @var array
     */
    protected $scanData;

    /**
     * PurchasingOrderImportingScanValidator constructor.
     * @param User $creator
     * @param array $input
     */
    public function __construct(User $creator, array $input = [])
    {
        $this->creator = $creator;
        $this->tenant  = $creator->tenant;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'barcode' => 'required',
            'barcode_type' => 'required|in:' . implode(',', [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL]),
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->user->warehouses->firstWhere('id', $this->input['warehouse_id'])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        if (!$this->warehouseArea = $this->warehouse->getDefaultArea()) {
            $this->errors()->add('warehouse_area', static::ERROR_EXISTS);
            return;
        }

        $barcodeType = trim($this->input['barcode_type']);
        $barcode     = trim($this->input['barcode']);
        if (!$this->validateByBarcode($barcodeType, $barcode)) {
            return;
        }

        $importingBarcode = $barcodeType == ImportingBarcode::TYPE_ORDER_CODE
            ? $this->getImportingBarcode($this->purchasingOrder)
            : $this->getImportingBarcode($this->purchasingPackage);

        // Nếu mã quét này đang ở trong 1 phiếu nhập nào đó chưa hoặc đã xử lý
        if ($importingBarcode instanceof ImportingBarcode) {
            $document = $importingBarcode->document;
            $this->errors()->add(($document->status == Document::STATUS_DRAFT) ? 'has_processing_importing' : 'has_finished_importing', [
                'document' => $document,
            ]);
            return;
        }

    }

    /**
     * @param $barcodeType
     * @param $barcode
     * @return bool
     */
    protected function validateByBarcode($barcodeType, $barcode)
    {
        switch ($barcodeType) {
            case ImportingBarcode::TYPE_ORDER_CODE:
            {
                return $this->validateOrderCode($barcode);
            }
            case ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL:
            case ImportingBarcode::TYPE_PACKAGE_CODE:
            {
                return $this->validatePackage($barcode, $barcodeType);
            }
        }

        return false;
    }

    /**
     * @param $object
     * @return Builder|Model|null|object
     */
    protected function getImportingBarcode($object)
    {
        $importingBarcodeQuery = ImportingBarcode::query()
            ->join('documents', 'importing_barcodes.document_id', '=', 'documents.id')
            ->where('documents.status', '!=', Document::STATUS_CANCELLED)
            ->where([
                'importing_barcodes.tenant_id' => $this->tenant->id,
                'importing_barcodes.object_id' => $object->id,
            ]);

        $barcodeType = trim($this->input['barcode_type']);
        if (in_array($barcodeType, [ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL, ImportingBarcode::TYPE_PACKAGE_CODE])) {
            $importingBarcodeQuery->where(function (Builder $q) {
                $q->where('importing_barcodes.type', ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL);
                $q->orWhere('importing_barcodes.type', ImportingBarcode::TYPE_PACKAGE_CODE);
            });
        } else {
            $importingBarcodeQuery->where('importing_barcodes.type', $barcodeType);
        }

        return $importingBarcodeQuery->first();
    }


    /**
     * @param $barcode
     * @return bool
     */
    protected function validateOrderCode($barcode)
    {
        if (!$this->purchasingOrder = $this->tenant->purchasingOrders()->firstWhere(['code' => $barcode])) {
            $this->errors()->add('order_code', static::ERROR_EXISTS);
            return false;
        }

        /**
         * Nếu đơn nhập chưa được đánh dấu là sẽ nhập về kho thì báo lỗi
         */
        if (!$this->purchasingOrder->is_putaway) {
            $this->errors()->add('order_code', 'is_not_putaway');
            return false;
        }

        $purchasingOrderItems = $this->purchasingOrder->purchasingOrderItems;
        if ($purchasingOrderItems->count() == 0) {
            $this->errors()->add('order_items', static::ERROR_EXISTS);
            return false;
        }

        if (!$this->validateSkuMap($purchasingOrderItems, $this->purchasingOrder->id)) {
            return false;
        }

        $this->makeSanData($this->purchasingOrder);


        return true;
    }


    /**
     * @param $barcode
     * @param $barcodeType
     * @return bool
     */
    protected function validatePackage($barcode, $barcodeType)
    {
        $query = $this->tenant->purchasingPackages();
        if ($barcodeType == ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL) {
            $query->where('purchasing_packages.freight_bill_code', $barcode);
        } else {
            $query->where('purchasing_packages.code', $barcode);
        }

        $merchantId = Arr::get($this->input, 'merchant_id', 0);
        if (!empty($merchantId)) {
            $merchant = $this->tenant->merchants()->firstWhere('id', $merchantId);
            if (!$merchant instanceof Merchant) {
                $this->errors()->add('merchant_id', static::ERROR_EXISTS);
                return false;
            }
            $query->where('purchasing_packages.merchant_id', $merchant->id);
        }
        $query->where('purchasing_packages.status', '!=', PurchasingPackage::STATUS_CANCELED);

        $purchasingPackages = $query->get();

        if ($purchasingPackages->count() == 0) {
            $this->errors()->add('package_code', static::ERROR_EXISTS);
            return false;
        }

        if (
            $purchasingPackages->count() > 1
        ) {
            if (empty($merchantId)) {
                //nếu không chọn merchant và có nhiều hơn kiện của 2 merchant khác nhau thì báo lỗi
                $creatorId = '';
                foreach ($purchasingPackages as $purchasingPackage) {
                    if ($creatorId !== '' && $purchasingPackage->creator_id != $creatorId) {
                        $this->errors()->add('has_many_in_merchant', $purchasingPackage->code);
                        return false;
                    }
                    $creatorId = $purchasingPackage->creator_id;
                }
            }

            $purchasingOrderId = Arr::get($this->input, 'purchasing_order_id', 0);
            $orders            = [];
            /** @var PurchasingPackage $purchasingPackage */
            foreach ($purchasingPackages as $purchasingPackage) {
                $importingBarcode = $this->getImportingBarcode($purchasingPackage);
                if (!$importingBarcode instanceof ImportingBarcode) {
                    $orders[] = $purchasingPackage->purchasingOrder->only(['id', 'code']);
                }
            }

            if (empty($purchasingOrderId)) {
                $this->errors()->add('has_many_in_purchasing_order', $orders);
                return false;
            } else {
                $this->purchasingPackage = $purchasingPackages->where('purchasing_order_id', $purchasingOrderId)->first();
            }
        } else {
            $this->purchasingPackage = $purchasingPackages->first();
        }

        if (!$this->purchasingPackage instanceof PurchasingPackage) {
            $this->errors()->add('package_code', static::ERROR_EXISTS);
            return false;
        }

        /**
         * Nếu kiện nhập chưa được đánh dấu là sẽ nhập về kho thì báo lỗi
         */
        if (!$this->purchasingPackage->is_putaway) {
            $this->errors()->add('package_code', 'is_not_putaway');
            return false;
        }

        $purchasingPackageItems = $this->purchasingPackage->purchasingPackageItems;
        if ($purchasingPackageItems && !$this->validateSkuMap($purchasingPackageItems, $this->purchasingPackage->purchasing_order_id)) {
            return false;
        }

        $this->makeSanData($this->purchasingPackage->purchasingOrder, $this->purchasingPackage);

        return true;
    }

    /**
     * @param PurchasingOrder|null $purchasingOrder
     * @param PurchasingPackage|null $purchasingPackage
     */
    protected function makeSanData($purchasingOrder = null, $purchasingPackage = null)
    {
        $purchasingOrderItem       = null;
        $purchasingOrderItems      = null;
        $purchasingAccount         = null;
        $purchasingPackageServices = null;

        if ($purchasingOrder instanceof PurchasingOrder) {
            $purchasingOrderItems = $purchasingOrder->purchasingOrderItems;
            $purchasingOrderItem  = $purchasingOrderItems->first();
            $purchasingAccount    = $purchasingOrder->purchasingAccount;
        }

        $this->scanData['purchasing_package']     = $purchasingPackage;
        $this->scanData['purchasing_order']       = $purchasingOrder;
        $this->scanData['purchasing_order_items'] = $purchasingOrderItem ? $purchasingOrderItems->map(function (PurchasingOrderItem $purchasingOrderItem) {
            return [
                'purchasing_order_item' => $purchasingOrderItem->attributesToArray(),
                'sku' => $purchasingOrderItem->sku
            ];
        }) : null;
        $this->scanData['purchasing_order_image'] = ($purchasingOrderItem instanceof PurchasingOrderItem) ? $purchasingOrderItem->product_image : null;
        $this->scanData['purchasing_account']     = $purchasingAccount;

        $items = [];
        if ($purchasingPackage instanceof PurchasingPackage) {
            $purchasingPackageServices = $purchasingPackage->purchasingPackageServices()
                ->with(['service', 'servicePrice'])->get()->map(function ($purchasingPackageService) {
                    return [
                        'purchasing_package_service' => $purchasingPackageService,
                        'service' => $purchasingPackageService->service,
                        'service_price' => $purchasingPackageService->servicePrice,
                    ];
                });

            foreach ($purchasingPackage->purchasingPackageItems as $purchasingPackageItem) {
                $items = $this->mergeSku($purchasingPackageItem->sku, $purchasingPackageItem->quantity, $items);
            }
        } else if ($purchasingOrderItems) {
            foreach ($purchasingOrderItems as $purchasingOrderItem) {
                $items = $this->mergeSku($purchasingOrderItem->sku, $purchasingOrderItem->received_quantity, $items);
            }
        }

        $this->scanData['purchasing_package_services'] = $purchasingPackageServices;

        $this->scanData['items'] = $items;
    }

    /**
     * @param Sku $sku
     * @param $receivedQuantity
     * @param $items
     * @return mixed
     */
    protected function mergeSku(Sku $sku, $receivedQuantity, $items)
    {
        $receivedQuantity = intval($receivedQuantity);
        if (isset($items[$sku->id])) {
            $items[$sku->id]['received_quantity'] += $receivedQuantity;
        } else {
            $items[$sku->id] = [
                'received_quantity' => $receivedQuantity,
                'sku' => $sku,
            ];
        }

        return $items;
    }

    /**
     * @param $items
     * @param $purchasingOrderId
     * @return bool
     */
    protected function validateSkuMap($items, $purchasingOrderId)
    {
        $itemErrors = [];
        foreach ($items as $item) {
            if (empty($item->sku)) {
                $itemErrors[] = ($item instanceof PurchasingOrderItem) ? $item->item_code : $item->id;
            }
        }

        if (!empty($itemErrors)) {
            $errorKey = (
                $this->creator->can(Permission::MERCHANT_SKU_MAP_ALL) ||
                $this->creator->can(Permission::MERCHANT_SKU_MAP_ASSIGNED)
            ) ? 'order_item_not_map_sku' : 'not_permission_map_sku';

            $this->errors()->add($errorKey, [
                'items' => $itemErrors,
                'purchasing_order_id' => $purchasingOrderId
            ]);
            return false;
        }

        return true;

    }

    /**
     * @return array
     */
    public function getSanData()
    {
        return $this->scanData;
    }
}
