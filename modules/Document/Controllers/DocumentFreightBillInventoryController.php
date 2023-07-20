<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Document\Models\Document;
use Modules\Document\Validators\CancelDocumentFreightBillInventoryValidator;
use Modules\Document\Validators\CreatingDocumentFreightBillInventoryValidator;
use Modules\Document\Validators\CreatingInfoDocumentFreightBillInventoryValidator;
use Modules\Document\Validators\DocumentFreightBillInventoryConfirmValidator;
use Modules\Document\Validators\DocumentFreightBillInventoryDetailValidator;
use Modules\Document\Validators\DocumentFreightBillInventoryUpdateValidator;
use Modules\Location\Models\LocationShippingPartner;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentFreightBillInventoryController extends Controller
{

    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = []): array
    {
        $inputs              = $inputs ?: [
            'code',
            'tracking_code',
            'status',
            'creator_id',
            'shipping_partner_id',
            'sort',
            'sortBy',
            'verified_at',
            'created_at',
            'page',
            'per_page',
            'received_date'
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        $filter = $this->makeFilterByShippingPartner($filter);

        return $filter;
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function makeFilterByShippingPartner($filter)
    {
        $userLocationIds  = $this->user->locations->pluck('id')->toArray();
        $shippingPartnerIds = LocationShippingPartner::query()->whereIn('location_id', $userLocationIds)->pluck('shipping_partner_id')->toArray();
        if(!empty($filter['shipping_partner_id']) && in_array($filter['shipping_partner_id'], $shippingPartnerIds))
        {
            return $filter;
        }

        $filter['shipping_partner_id'] = $shippingPartnerIds;
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $document = Service::documentFreightBillInventory()->listing($this->getQueryFilter(), $this->user);
        return $this->response()->success($document);
    }

    public function updateInfoFreightBill($id)
    {
        $inputs = $this->request()->only([
            'received_date',
            'payment_slip',
            'received_pay_date',
            'note',
        ]);
        $inputs = array_merge($inputs,['id'=>$id]);
        $validator = new CreatingInfoDocumentFreightBillInventoryValidator($inputs);
        if ($validator->fails()){
            return $this->response()->error($validator);
        }
        $document = $validator->getDocumentInventory();
        $result = Service::documentFreightBillInventory()->updateInfoFreightBill($document, $inputs, $this->user);
        return  $this->response()->success($result);

    }

    /**
     * @return JsonResponse
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function create(): JsonResponse
    {
        $inputs    = $this->requests->only([
            'shipping_partner_id',
            'file',
            'confirm',
        ]);
        $validator = new CreatingDocumentFreightBillInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $confirm  = (bool)Arr::get($inputs, 'confirm', false);
        $result   = Service::documentFreightBillInventory()->create($validator->getShippingPartner(), $inputs['file'], $this->user, $confirm);
        return $this->response()->success($result);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentFreightBillInventoryDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentInventory = $validator->getDocumentInventory();

        return $this->response()->success(
            [
                'can_update' => (
                    $documentInventory->status == Document::STATUS_DRAFT &&
                    $this->user->can(Permission::FINANCE_CONFIRM_STATEMENT)
                ) ? true : false,
                'document' => $documentInventory,
                'shipping_partner' => $documentInventory->shippingPartner,
                'document_freight_bill_inventories' => $documentInventory->documentFreightBillInventories
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse|BinaryFileResponse
     */
    public function exportFreightBill($id)
    {
        $validator = new DocumentFreightBillInventoryDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentInventory = $validator->getDocumentInventory();
        $filter            = $this->request()->only(['status']);
        $pathFile          = Service::documentFreightBillInventory()->exportFreightBill($documentInventory, $filter, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function confirm($id)
    {
        $input       = $this->request()->only(['confirm']);
        $input['id'] = intval($id);
        $validator   = new DocumentFreightBillInventoryConfirmValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $document = $validator->getDocumentInventory();
        $document = Service::documentFreightBillInventory()->confirm($document, $this->user);

        return $this->response()->success(
            [
                'document' => $document,
                'shipping_partner' => $document->shippingPartner,
                'document_freight_bill_inventories' => $document->documentFreightBillInventories
            ]
        );
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $validator = new CancelDocumentFreightBillInventoryValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document = $validator->getDocument();

        return $this->response()->success(
            [
                'document' => Service::documentFreightBillInventory()->cancel($document, $this->user),
            ]
        );
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $input       = $this->request()->only(['other_fee']);
        $input['id'] = intval($id);
        $validator   = new DocumentFreightBillInventoryUpdateValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $document = $validator->getDocumentInventory();
        $document = Service::documentFreightBillInventory()->update($document, $input, $this->user);

        return $this->response()->success(
            [
                'document' => $document,
                'shipping_partner' => $document->shippingPartner,
                'document_freight_bill_inventories' => $document->documentFreightBillInventories
            ]
        );
    }

}
