<?php /** @noinspection SpellCheckingInspection */

namespace Modules\Document\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Events\DocumentPackingCreated;
use Modules\Document\Jobs\CalculateBalanceMerchantWhenConfirmDocumentJob;
use Modules\Document\Models\Document;
use Modules\FreightBill\Models\FreightBill;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentPacking
{
    /**
     * @var Warehouse
     */
    protected $warehouse;
    /** @var array */
    protected $inputs;
    /** @var Collection $orderPackings */
    protected $orderPackings;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var string
     */
    protected $scanType = '';

    /**
     * CreateDocumentPacking constructor.
     * @param Warehouse $warehouse
     * @param Collection $orderPackings
     * @param array $inputs
     * @param User $creator
     */
    public function __construct(Warehouse $warehouse, Collection $orderPackings, array $inputs, User $creator)
    {
        $this->warehouse     = $warehouse;
        $this->creator       = $creator;
        $this->inputs        = $inputs;
        $this->scanType      = Arr::get($inputs, 'scan_type');
        $this->orderPackings = $orderPackings;
    }

    /**
     * @return Document
     */
    public function handle(): Document
    {
        $document = DB::transaction(function () {
            $document = $this->createDocument();
            $this->createOrderExporting();
            $this->createDocumentOrderPackings($document);
            $this->changeOrderPackingStatus();
            $this->updateOrderPackedAt($document);
            $document->orders()->sync($document->orderPackings->pluck('order_id')->unique()->values()->all());

            return $document;
        });
        (new DocumentPackingCreated($document))->queue();
        // trừ tiền dịch vụ đóng gói vào ví seller
        dispatch(new CalculateBalanceMerchantWhenConfirmDocumentJob($document->id));

        return $document;
    }

    /**
     * @return Document
     * @throws Exception
     */
    protected function createDocument(): Document
    {
        $input = [
            'type' => Document::TYPE_PACKING,
            'status' => Document::STATUS_COMPLETED,
            'verifier_id' => $this->creator->id,
            'verified_at' => (new Carbon()),
            'info' => [
                Document::INFO_DOCUMENT_EXPORTING_BARCODE_TYPE => $this->scanType
            ]
        ];

        return Service::document()->create($input, $this->creator, $this->warehouse);
    }

    /**
     * Cập nhật thời gian tạo chứng từ xác nhận đóng hàng trên đơn
     * @param Document $document
     */
    protected function updateOrderPackedAt(Document $document)
    {
        /** @var OrderPacking $orderPacking */
        foreach ($this->orderPackings as $orderPacking) {
            $order            = $orderPacking->order;
            $order->packer_id = $document->verifier_id;
            $order->packed_at = $document->created_at;
            $order->save();
        }
    }

    /**
     * Tạo các YCXH
     */
    protected function createOrderExporting()
    {
        foreach ($this->orderPackings as $orderPacking) {
            /**
             * Tạo mvd cho các YCĐH nếu YCĐH chưa có mvd
             */
            if (!$orderPacking->freight_bill_id) {
                $freightBill                   = FreightBill::updateOrCreate(
                    [
                        'freight_bill_code' => '',
                        'shipping_partner_id' => $orderPacking->order ? $orderPacking->order->shipping_partner_id : 0,
                        'tenant_id' => $orderPacking->tenant_id,
                        'order_packing_id' => $orderPacking->id,
                    ],
                    [
                        'order_id' => $orderPacking->order_id,
                        'snapshots' => Service::orderPacking()->makeSnapshots($orderPacking),
                        'status' => FreightBill::STATUS_WAIT_FOR_PICK_UP
                    ]
                );
                $orderPacking->freight_bill_id = $freightBill->id;
            }
            $orderPacking->save();

            $orderExporting = OrderExporting::create([
                'order_id' => $orderPacking->order_id,
                'freight_bill_id' => $orderPacking->freight_bill_id,
                'order_packing_id' => $orderPacking->id,
                'shipping_partner_id' => $orderPacking->shipping_partner_id,
                'status' => OrderExporting::STATUS_NEW,
                'tenant_id' => $this->warehouse->tenant_id,
                'warehouse_id' => $this->warehouse->id,
                'creator_id' => $this->creator->id,
                'total_quantity' => $orderPacking->total_quantity,
                'total_value' => $orderPacking->total_values,
            ]);

            Service::orderExporting()->updateOrderExportingItems($orderExporting, $orderPacking);
        }
    }


    /**
     * Chuyển YCDH sang đã xử lý và đơn sang chờ giao
     */
    protected function changeOrderPackingStatus()
    {
        foreach ($this->orderPackings as $orderPacking) {

            if ($orderPacking->canChangeStatus(OrderPacking::STATUS_PACKED)) {
                $orderPacking->changeStatus(OrderPacking::STATUS_PACKED, $this->creator);
            }
        }

    }

    protected function createDocumentOrderPackings(Document $document)
    {
        $scannedOrderPackings  = Arr::get($this->inputs, 'order_packings', []);
        $documentOrderPackings = [];
        foreach ($scannedOrderPackings as $scannedOrderPacking) {
            $documentOrderPackings[$scannedOrderPacking['order_packing_id']]['created_at'] = $scannedOrderPacking['scanned_at'];
        }
        $document->orderPackings()->sync($documentOrderPackings);
    }
}
