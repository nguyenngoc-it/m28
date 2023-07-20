<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service\Models\ServicePrice;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentImportingByPurchasingOrderValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $skuData;

    /**
     * @var array
     */
    protected $objects;

    /**
     * @var array
     */
    protected $services = [];

    /**
     * CreateDocumentImportingByPurchasingOrderValidator constructor.
     * @param User $user
     * @param array $input
     */
    public function __construct(User $user, array $input = [])
    {
        $this->tenant = $user->tenant;
        $this->user   = $user;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'warehouse_id' => "required",
            'skus' => 'required|array',
            'object_ids' => 'required|array',
            'barcode_type' => 'required|in:' . implode(',', ImportingBarcode::$listTypes),
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->user->warehouses->firstWhere('id', $this->input['warehouse_id'])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        if (!$this->validateSkus()) {
            return;
        }

        if (!$this->validateObjects()) {
            return;
        }

        if (!$this->validateServices()) {
            return;
        }
    }

    /**
     * @return bool
     */
    protected function validateSkus()
    {
        $skus = $this->input['skus'];
        foreach ($skus as $skuData) {
            $skuId            = $skuData['id'];
            $receivedQuantity = $skuData['received_quantity'];
            $quantity         = $skuData['old_received_quantity'];

            $sku = Sku::query()->firstWhere(['tenant_id' => $this->tenant->id, 'id' => $skuId]);
            if (!$sku instanceof Sku) {
                $this->errors()->add('sku_invalid', $skuId);
                return false;
            }
            $receivedQuantity = intval($receivedQuantity);
            if ($receivedQuantity < 0) {
                $this->errors()->add('quantity_invalid', $sku->code);
                return false;
            }

            $this->skuData[$sku->id] = [
                'sku' => $sku,
                'quantity' => $quantity,
                'received_quantity' => $receivedQuantity
            ];
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function validateServices(): bool
    {
        if (!empty($this->input['services'])) {
            $services = $this->input['services'];
            foreach ($services as $service) {
                $skuPriceId = $service['service_price_id'];
                $skuIds     = $service['sku_ids'];
                if (empty($skuIds)) {
                    $this->errors()->add('sku_ids', static::ERROR_REQUIRED);
                    return false;
                }
                $skus = $this->getSkus($skuIds);
                if (empty($skus)) {
                    $this->errors()->add('sku_ids', static::ERROR_INVALID);
                    return false;
                }

                $servicePrice = ServicePrice::query()->firstWhere(['tenant_id' => $this->tenant->id, 'id' => $skuPriceId]);
                if (!$servicePrice instanceof ServicePrice) {
                    $this->errors()->add('service_price_id', $skuPriceId);
                    return false;
                }

                $this->services[$servicePrice->id] = [
                    'servicePrice' => $servicePrice,
                    'service' => $servicePrice->service,
                    'skus' => $skus,
                ];
            }
        }

        return true;
    }

    /**
     * @param $objectId
     * @return Builder|Model|null|object
     */
    protected function getImportingBarcode($objectId)
    {
        $barcode_type = trim($this->input['barcode_type']);
        return ImportingBarcode::query()
            ->join('documents', 'importing_barcodes.document_id', '=', 'documents.id')
            ->where('documents.status', '!=', Document::STATUS_CANCELLED)
            ->where([
                'importing_barcodes.tenant_id' => $this->tenant->id,
                'importing_barcodes.type' => $barcode_type,
                'importing_barcodes.object_id' => $objectId,
            ])->first();

    }

    /**
     * @return bool
     */
    protected function validateObjects()
    {
        $barcode_type = trim($this->input['barcode_type']);
        $objectIds    = $this->input['object_ids'];
        foreach ($objectIds as $objectId) {
            $object = ($barcode_type == ImportingBarcode::TYPE_ORDER_CODE)
                ? $this->tenant->purchasingOrders()->firstWhere('id', trim($objectId))
                : $this->tenant->purchasingPackages()->firstWhere('id', trim($objectId));
            if (empty($object->id)) {
                $this->errors()->add('object_id', $objectId);
                return false;
            }

            /**
             * Nếu đơn nhập hoặc kiện nhập chưa được đánh dấu là sẽ nhập về kho thì báo lỗi
             */
            if ($object instanceof PurchasingOrder && !$object->is_putaway) {
                $this->errors()->add('purchasing_order', 'is_not_putaway');
                return false;
            }
            if ($object instanceof PurchasingPackage && !$object->is_putaway) {
                $this->errors()->add('purchasing_package', 'is_not_putaway');
                return false;
            }

            // Nếu mã quét này đang ở trong 1 phiếu nhập nào đó chưa hoặc đã xử lý
            $importingBarcode = $this->getImportingBarcode($objectId);
            if ($importingBarcode instanceof ImportingBarcode) {
                $document = $importingBarcode->document;
                $this->errors()->add(($document->status == Document::STATUS_DRAFT) ? 'has_processing_importing' : 'has_finished_importing', [
                    'document' => $document,
                    'object_code' => $object->code,
                    'object_id' => $object->id
                ]);
                return false;
            }

            $this->objects[] = $object;
        }

        $objectTotal = count($this->objects);
        if ($objectTotal > 1) {
            //#2910 quét nhập chỉ quét cho 1 kiện hoặc 1 đơn
            $this->errors()->add('object_has_many', $objectTotal);
            return false;
        }

        return true;
    }


    /**
     * @return array
     */
    public function getSkuData()
    {
        return $this->skuData;
    }

    /**
     * @return array
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }

    protected function getSkus(array $skuIds)
    {
        $skus = [];
        foreach ($skuIds as $skuId) {
            if (isset($this->skuData[$skuId])) {
                $skus[] = [
                    'sku_id' => $skuId,
                    'sku_code' => $this->skuData[$skuId]['sku']->code,
                    'sku_name' => $this->skuData[$skuId]['sku']->name,
                    'quantity' => $this->skuData[$skuId]['received_quantity']
                ];
            }
        }
        return $skus;
    }
}
