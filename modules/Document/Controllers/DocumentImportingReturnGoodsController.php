<?php /** @noinspection ALL */

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\Document\Commands\CreateDocumentImportingReturnGoods;
use Modules\Document\Commands\UpdatingImportingBarcodeReturnGoods;
use Modules\Document\Validators\CancelingDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\ConfirmingDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\CreatingDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\DocumentImportingReturnGoodsDetailValidator;
use Modules\Document\Validators\ExportingReceivedSkusDocumentImportingValidator;
use Modules\Document\Validators\ScanListDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\ScanningDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\UpdatingDocumentImportingReturnGoodsValidator;
use Modules\Document\Validators\UpdatingImportingBarcodeReturnGoodsValidator;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderImportReturnGoodsService;
use Modules\Order\Services\OrderEvent;
use Modules\Document\Validators\UpdateOrderImportReturnGoodsServiceValidator;
use Modules\OrderPacking\Validators\OrderPackingScanListValidator;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentImportingReturnGoodsController extends Controller
{
    /**
     * Quét hàng hoàn thông qua mã vận đơn hoặc mã đơn
     *
     * @return JsonResponse
     */
    public function scan()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'barcode',
            'barcode_type',
        ]);

        $validator = new ScanningDocumentImportingReturnGoodsValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $order                          = $validator->getFreightBill()->order;
        $orderImportReturnGoodsServices = $order->orderImportReturnGoodsServices;
        foreach ($orderImportReturnGoodsServices as $orderImportReturnGoodsService) {
            $service      = $orderImportReturnGoodsService->service;
            $servicePrice = $orderImportReturnGoodsService->servicePrice;
            $orderImportReturnGoodsService->setServiceNameAttribute($service->name);
            $orderImportReturnGoodsService->setServicePriceLableAttribute($servicePrice->label);
        }
        return $this->response()->success([
            'document_importing_return_goods' => [
                'freight_bill' => $validator->getFreightBill()->only(['id', 'freight_bill_code', 'status']),
                'order' => $order->only(['id', 'code', 'receiver_country_id', 'merchant_id']),
                'skus' => $validator->getDocumentImportingReturnGoods(),
                'import_return_goods_service_prices' => $order->orderImportReturnGoodsServices
            ]
        ]);
    }

    public function scanList()
    {
        $inputs    = $this->requests->only([
            'ids',
        ]);
        $validator = new ScanListDocumentImportingReturnGoodsValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success([
            'document_importing_return_goods' => $validator->getOrders()->map(function (Order $order) {
                return [
                    'order' => $order->only(['id', 'code', 'receiver_country_id', 'merchant_id']),
                    'import_return_goods_service_prices' => $order->importReturnGoodsServicePrices->map(function (Service\Models\ServicePrice $servicePrice) {
                        return [
                            'service_id' => $servicePrice->service->id,
                            'service_name' => $servicePrice->service->name,
                            'service_price_id' => $servicePrice->id,
                            'service_price_label' => $servicePrice->label
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Tạo chứng từ nhập hàng hoàn
     *
     * order_items => [{id:1, skus: [{id:1, quantity:1}]}]
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $inputs    = $this->requests->only([
            'warehouse_id',
            'order_items',
            'note'
        ]);
        $validator = new CreatingDocumentImportingReturnGoodsValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document = (new CreateDocumentImportingReturnGoods($inputs, $this->user))->handle();

        return $this->response()->success(
            [
                'document' => $document
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function updateImportingBarcode($id)
    {
        $inputs       = $this->requests->only([
            'order_items'
        ]);
        $inputs['id'] = $id;

        $validator = new UpdatingImportingBarcodeReturnGoodsValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document = (new UpdatingImportingBarcodeReturnGoods($validator->getDocumentImporting(), $inputs, $this->user))->handle();

        return $this->response()->success(
            [
                'document' => $document
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentImportingReturnGoodsDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success([
            'document' => $documentImporting,
            'importing_barcodes' => $documentImporting->importingBarcodes
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $inputs    = $this->requests->only([
            'note',
        ]);
        $validator = new UpdatingDocumentImportingReturnGoodsValidator(array_merge(['id' => (int)$id], $inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        return $this->response()->success(
            [
                'document' => Service::document()->update($documentImporting, $inputs, $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $validator = new CancelingDocumentImportingReturnGoodsValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document' => Service::documentImporting()->cancel($documentImporting, $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function confirm($id)
    {
        $validator = new ConfirmingDocumentImportingReturnGoodsValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document' => Service::documentImporting()->confirm($documentImporting, $this->user, Stock::ACTION_IMPORT_BY_RETURN),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse|BinaryFileResponse
     */
    public function exportSkus($id)
    {
        $validator = new ExportingReceivedSkusDocumentImportingValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $pathFile = Service::documentImporting()->downloadReceivedSkus($validator->getDocumentImporting());

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }


    /**
     * Cập nhật dịch vụ nhập hoàn cho đơn hàng hoàn
     * @return JsonResponse
     */
    public function services()
    {
        $user      = $this->getAuthUser();
        $filter    = $this->request()->only(['order_ids', 'service_price_ids']);
        $validator = new UpdateOrderImportReturnGoodsServiceValidator($user, $filter);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $orders        = $validator->getOrders();
        $servicePrices = $validator->getServicePrices();

        $orderServicesNewIds = [];
        foreach ($servicePrices as $servicePrice) {
            $orderServicesNewIds[] = $servicePrice->id;
        }

        foreach ($orders as $order) {
            if (empty($orderServicesNewIds)) {
                $order->orderImportReturnGoodsServices()->delete();
                continue;
            }

            $orderImportReturnGoodsServices = $order->orderImportReturnGoodsServices;

            //xóa các dịch vụ cũ của đơn nếu giao dịch viên không tích chọn nữa
            foreach ($orderImportReturnGoodsServices as $orderImportReturnGoodsService) {
                if (!in_array($orderImportReturnGoodsService->service_price_id, $orderServicesNewIds)) {
                    $order->logActivity(OrderEvent::REMOVE_IMPORTING_RETURN_GOODS_SERVICE, $user, [
                        'service_price' => $orderImportReturnGoodsService->servicePrice->toArray(),
                        'service' => $orderImportReturnGoodsService->service->toArray(),
                    ]);

                    $orderImportReturnGoodsService->delete();
                }
            }

            /** @var Service\Models\ServicePrice $servicePrice */
            $serviceAmount = 0;
            foreach ($servicePrices as $servicePrice) {

                $serviceAmount += $servicePrice->price;

                $orderImportReturnGoodsService = $order->orderImportReturnGoodsServices()
                    ->where('service_price_id', $servicePrice->id)->first();
                if ($orderImportReturnGoodsService instanceof OrderImportReturnGoodsService) {
                    continue;
                } else {
                    $order->orderImportReturnGoodsServices()->create([
                        'service_price_id' => $servicePrice->id,
                        'service_id' => $servicePrice->service->id,
                        'order_id' => $order->id,
                        'price' => $servicePrice->price,
                    ]);

                    $order->logActivity(OrderEvent::ADD_IMPORTING_RETURN_GOODS_SERVICE, $user, [
                        'service_price' => $servicePrice->toArray(),
                        'service' => $servicePrice->service->toArray(),
                    ]);
                }
            }

            if ($order->service_import_return_goods_amount != $serviceAmount) {
                $order->service_import_return_goods_amount = $serviceAmount;
                $order->save();
            }
        }


        return $this->response()->success($orders);
    }

}
